<?php

/**
 * Handle module installer functions
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Authsystem;

use Xaraya\Modules\InstallerClass;
use xarMasks;
use xarPrivileges;

/**
 * Handle module installer functions
 *
 * @internal replaced authsystem_*() function calls with $this->*() calls
 * @extends InstallerClass<Module>
 */
class Installer extends InstallerClass
{
    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * Initialise the module. This function is called once when the module is intalled.
     * @author Jan Schrage
     * @author John Cox
     * @author Gregor Rothfuss
     * @author Jo Dalle Nogare <jojodee@xaraya.com>
     * @return bool|void True on success, false on failure
     */
    public function init()
    {
        //Set the default authmodule if not already set
        $isdefaultauth = $this->mod('roles')->getVar('defaultauthmodule');
        if (empty($isdefaultauth)) {
            $this->mod('roles')->setVar('defaultauthmodule', 'authsystem');
        }

        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();
        $modulesTable = $this->db()->getPrefix() . '_modules';
        $modid = $this->mod()->getRegID('authsystem');
        // update the modversion class and admin capable
        $query = "UPDATE $modulesTable SET class=?, admin_capable=?
                 WHERE regid = ?";
        $bindvars = ['Authentication',true,$modid];
        $result = $dbconn->Execute($query, $bindvars);
        if (!$result) {
            return;
        }

        // Installation complete; don't upgrade twice on same version
        //return $this->upgrade('2.0.0');
        return true;
    }

    /**
     * Activate the module. This function is called when the module is changed from installed to active state.
     * @author Jan Schrage
     * @author John Cox
     * @author Gregor Rothfuss
     * @author Jo Dalle Nogare <jojodee@xaraya.com>
     * @return bool True on success, false on failure
     */
    public function activate()
    {
        xarPrivileges::register('AdminAuthsystem', 'All', 'authsystem', 'All', 'All', 'ACCESS_ADMIN');
        xarPrivileges::register('ViewAuthsystem', 'All', 'authsystem', 'All', 'All', 'ACCESS_OVERVIEW');

        xarMasks::register('ViewLogin', 'All', 'authsystem', 'Block', 'login:Login:All', 'ACCESS_OVERVIEW');
        xarMasks::register('ViewAuthsystemBlocks', 'All', 'authsystem', 'Block', 'All', 'ACCESS_OVERVIEW');
        xarMasks::register('ViewAuthsystem', 'All', 'authsystem', 'All', 'All', 'ACCESS_OVERVIEW');
        xarMasks::register('EditAuthsystem', 'All', 'authsystem', 'All', 'All', 'ACCESS_EDIT');
        xarMasks::register('ManageAuthsystem', 'All', 'authsystem', 'All', 'All', 'ACCESS_DELETE');
        xarMasks::register('AdminAuthsystem', 'All', 'authsystem', 'All', 'All', 'ACCESS_ADMIN');

        /* Define Module vars */
        $this->mod()->setVar('lockouttime', 15);
        $this->mod()->setVar('lockouttries', 3);
        $this->mod()->setVar('uselockout', false);

        // Installation complete; check for upgrades
        return $this->upgrade('2.0.0');
    }

    /**
     * Upgrade the module from an old version. This function is called when the module is being upgraded.
     * @author Jan Schrage
     * @author John Cox
     * @author Gregor Rothfuss
     * @author Jo Dalle Nogare <jojodee@xaraya.com>
     * @param string $oldversion The three digit version number of the currently installed (old) version
     * @return bool True on success, false on failure
     */
    public function upgrade($oldversion)
    {
        // Upgrade dependent on old version number
        switch ($oldversion) {
            case '2.0.0':
                // Register event subjects
                $this->events()->registerSubject('UserLogin', 'user', 'authsystem');
                $this->events()->registerSubject('UserLogout', 'user', 'authsystem');
                break;
        }
        return true;
    }

    /**
     * Delete the module.
     * This function is called when the module is being uninstalled.
     * @author Jan Schrage
     * @author John Cox
     * @author Gregor Rothfuss
     * @author Jo Dalle Nogare <jojodee@xaraya.com>
     * @return bool Function always returns false. It cannot be deleted.
     */
    public function delete()
    {
        //this module cannot be removed
        return false;
    }
}
