<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminApi;
use DuplicateException;
use xarDB;
use xarMod;
use xarSecurity;
use xarVar;
use xarVersion;
use sys;
use InstallerTool;

sys::import('xaraya.modules.method');
sys::import('modules.modules.class.installer');

/**
 * modules adminapi regenerate function
 * @extends MethodClass<AdminApi>
 */
class RegenerateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Regenerate module list
     * @author Xaraya Development Team
     * @return bool|void true on success, false on failure
     * @see AdminApi::regenerate()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security Check
        // need to specify the module because this function is called by the installer module
        if (!xarSecurity::check('AdminModules', 1, 'All', 'All', 'modules')) {
            return;
        }

        //Finds and updates missing modules
        sys::import('modules.modules.class.installer');
        $installer = InstallerTool::getInstance();
        if (!$installer->checkformissing()) {
            return;
        }

        //Get all modules in the filesystem
        $fileModules = $adminapi->getfilemodules();
        if (!isset($fileModules)) {
            return;
        }

        // Get all modules in DB
        $dbModules = $adminapi->getdbmodules();
        if (!isset($dbModules)) {
            return;
        }

        //Setup database object for module insertion
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();
        $modules_table = & $xartable['modules'];

        // See if we have gained any modules since last generation,
        // or if any current modules have been upgraded
        foreach ($fileModules as $name => $modinfo) {

            // Check matching name and regid values
            foreach ($dbModules as $dbmodule) {
                // Bail if 2 modules have the same regid but not the same name
                if (($modinfo['regid'] == $dbmodule['regid']) && ($modinfo['name'] != $dbmodule['name'])) {
                    $msg = 'The same registered ID (#(1)) was found belonging to a #(2) module in the file system and a registered #(3) module in the database. Please correct this and regenerate the list.';
                    $vars = [$dbmodule['regid'], $modinfo['name'], $dbmodule['name']];
                    throw new DuplicateException($vars, $msg);
                }

                // Bail if 2 modules have the same name but not the same regid
                if (($modinfo['name'] == $dbmodule['name']) && ($modinfo['regid'] != $dbmodule['regid'])) {
                    $msg = 'The module #(1) is found with two different registered IDs, #(2)  in the file system and #(3) in the database. Please correct this and regenerate the list.';
                    $vars = [$modinfo['name'], $modinfo['regid'], $dbmodule['regid']];
                    throw new DuplicateException($vars, $msg);
                }
            }

            // If this is a new module, i.e. not in the db list, add it
            assert($modinfo["regid"] != 0);
            if (empty($dbModules[$name])) {
                // New module
                $sql = "INSERT INTO $modules_table
                          (name,
                           regid,
                           directory,
                           version,
                           class,
                           category,
                           admin_capable,
                           user_capable)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $modinfo['name'],
                    $modinfo['regid'],
                    $modinfo['directory'],
                    $modinfo['version'],
                    $modinfo['class'],
                    $modinfo['category'],
                    (bool) $modinfo['admin_capable'],
                    (bool) $modinfo['user_capable'],
                ];
                $result = $dbconn->Execute($sql, $params);

                $set = $adminapi->setstate(
                    [
                        'regid' => $modinfo['regid'],
                        'state' => xarMod::STATE_UNINITIALISED,
                    ]
                );
                if (!isset($set)) {
                    return;
                }

            } else {
                if ($dbModules[$name]['version'] != $modinfo['version']) {
                    // The version strings are different.
                    // Compare the versions, only going down to two levels. Only the first two
                    // levels are significant for upgrades. A module writer could use the third level
                    // from 1.0.3 to 1.0.4

                    sys::import('xaraya.version');
                    $vercompare = xarVersion::compare($modinfo['version'], $dbModules[$name]['version'], 2);

                    // Check if database version is less than (or equal to) the file version
                    // i.e. that the module is not being downgraded.
                    if ($vercompare >= 0) {
                        // The new version is either the same (to 3 levels) or higher.
                        $is_core = (substr($dbModules[$name]['class'], 0, 4) == 'Core') ? true : false;

                        if ($is_core && $vercompare > 0) {
                            // Bug 2879: Attempt to run the core module upgrade and activate functions.
                            $adminapi->upgrade(
                                [
                                    'regid' => $modinfo['regid'],
                                    'state' => xarMod::STATE_INACTIVE,
                                ]
                            );

                            $adminapi->activate(
                                [
                                    'regid' => $modinfo['regid'],
                                    'state' => xarMod::STATE_ACTIVE,
                                ]
                            );
                        }

                        // Automatically update the module version for uninstalled modules or
                        // where the version number is equivalent (but could be a different format)
                        // or if the module is a core module.
                        if ($dbModules[$name]['state'] == xarMod::STATE_UNINITIALISED ||
                            $dbModules[$name]['state'] == xarMod::STATE_MISSING_FROM_UNINITIALISED ||
                            $dbModules[$name]['state'] == xarMod::STATE_ERROR_UNINITIALISED ||
                            $vercompare == 0 || $is_core) {

                            // First we check if this module belongs to class Core or not
                            if (substr($modinfo['class'], 0, 4)  == 'Core') {
                                // Yup, this module either belongs to Core or maskarading as such..

                                // our main objective here, however, is to catch core modules that have been upgraded
                                // then we must try hard to upgrade and activate it transparently

                                // Get module ID
                                $regId = $modinfo['regid'];

                                $newstate = xarMod::STATE_INACTIVE;
                                $adminapi->upgrade([    'regid'    => $regId,
                                    'state'    => $newstate]);

                                $newstate = xarMod::STATE_ACTIVE;
                                $adminapi->activate([    'regid'    => $regId,
                                    'state'    => $newstate]);
                            }

                            // Update the module version number
                            $sql = "UPDATE $modules_table SET version = ? WHERE regid = ?";
                            $dbconn->Execute($sql, [$modinfo['version'], $modinfo['regid']]);
                        } else {
                            // Else set the module state to upgraded
                            $set = $adminapi->setstate(
                                [
                                    'regid' => $modinfo['regid'],
                                    'state' => xarMod::STATE_UPGRADED,
                                ]
                            );

                            if (!isset($set)) {
                                return;
                            }
                        }
                    } else {
                        // The database version is greater than the file version.
                        // We can't deactivate or remove the module as the user will
                        // lose all of their data, so the module should be placed into
                        // a holding state until the user has updated the files for
                        // the module and the module version is the same or greater
                        // than the db version.

                        // Check if error state is already set
                        if (($dbModules[$name]['state'] == xarMod::STATE_ERROR_UNINITIALISED) ||
                            ($dbModules[$name]['state'] == xarMod::STATE_ERROR_INACTIVE) ||
                            ($dbModules[$name]['state'] == xarMod::STATE_ERROR_ACTIVE) ||
                            ($dbModules[$name]['state'] == xarMod::STATE_ERROR_UPGRADED)) {
                            // Continue to next module
                            continue;
                        }

                        // Clear cache to make sure we set the correct states
                        //if ($this->var()->isCached('Mod.Infos', $modinfo['regid'])) {
                        //    $this->var()->delCached('Mod.Infos', $modinfo['regid']);
                        //}

                        // Set error state
                        $modstate = xarMod::STATE_ANY;
                        switch ($dbModules[$name]['state']) {
                            case xarMod::STATE_UNINITIALISED:
                                $modstate = xarMod::STATE_ERROR_UNINITIALISED;
                                break;
                            case xarMod::STATE_INACTIVE:
                                $modstate = xarMod::STATE_ERROR_INACTIVE;
                                break;
                            case xarMod::STATE_ACTIVE:
                                $modstate = xarMod::STATE_ERROR_ACTIVE;
                                break;
                            case xarMod::STATE_UPGRADED:
                                $modstate = xarMod::STATE_ERROR_UPGRADED;
                                break;
                        }
                        if ($modstate != xarMod::STATE_ANY) {
                            $set = $adminapi->setstate(
                                [
                                    'regid' => $dbModules[$name]['regid'],
                                    'state' => $modstate,
                                ]
                            );
                            if (!isset($set)) {
                                return;
                            }

                            // Continue to next module
                            continue;
                        }
                    }
                }

                // From here on we have something in the file system or the db
                $newstate = xarMod::STATE_ANY;
                switch ($dbModules[$name]['state']) {
                    case xarMod::STATE_MISSING_FROM_UNINITIALISED:
                    case xarMod::STATE_ERROR_UNINITIALISED:
                        $newstate = xarMod::STATE_UNINITIALISED;
                        break;
                    case xarMod::STATE_MISSING_FROM_INACTIVE:
                    case xarMod::STATE_ERROR_INACTIVE:
                        $newstate = xarMod::STATE_INACTIVE;
                        break;
                    case xarMod::STATE_MISSING_FROM_ACTIVE:
                    case xarMod::STATE_ERROR_ACTIVE:
                        $newstate = xarMod::STATE_ACTIVE;
                        break;
                    case xarMod::STATE_MISSING_FROM_UPGRADED:
                    case xarMod::STATE_ERROR_UPGRADED:
                        $newstate = xarMod::STATE_UPGRADED;
                        break;
                }
                if ($newstate != xarMod::STATE_ANY) {
                    $set = $adminapi->setstate(
                        [
                            'regid' => $dbModules[$name]['regid'],
                            'state' => $newstate,
                        ]
                    );
                }
            }
        }

        // Finds and updates event handlers - replaced with xarEvent code and event observers
        //if (!$adminapi->geteventhandlers()) {return;}

        return true;
    }
}
