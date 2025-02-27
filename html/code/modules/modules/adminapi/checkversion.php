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
use xarMod;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi checkversion function
 * @extends MethodClass<AdminApi>
 */
class CheckversionMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Checks for change in module versions, and updates the status of them if any is found
     * @author Xaraya Development Team
     * @return bool|void null on exceptions, true on sucess to update
     * @see AdminApi::checkversion()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        static $check = false;

        // Now with dependency checking, this function may be called multiple times
        // Let's check if it already return ok and stop the processing here
        if ($check) {
            return true;
        }

        // Security Check
        // need to specify the module because this function is called by the installer module
        if (!$this->sec()->check('AdminModules', 1, 'All', 'All', 'modules')) {
            return;
        }

        // Get all modules in the filesystem
        $fileModules = $adminapi->getfilemodules();
        if (!isset($fileModules)) {
            return;
        }

        // Get all modules in DB
        $dbModules = $adminapi->getdbmodules();
        if (!isset($dbModules)) {
            return;
        }

        // See if we have lost any modules since last generation
        foreach ($dbModules as $name => $modInfo) {

            // First we check if this module belongs to class Core or not
            if (substr($modInfo['class'], 0, 4)  == 'Core') {
                // Yup, this module either belongs to Core or maskarading as such..

                // although it's unlikeley that such a module is uninitialised
                // lets check anyway, and if so just skip it for now..
                // our main objective here, however, is to catch core modules that have been upgraded
                // then we must try hard to upgrade and activate it transparently
                if (!empty($fileModules[$name]) && $modInfo['version'] != $fileModules[$name]['version']) {

                    // Get module ID
                    $regId = $modInfo['regid'];
                    switch ($modInfo['state']) {
                        case xarMod::STATE_UNINITIALISED:
                            break;
                        case xarMod::STATE_INACTIVE || xarMod::STATE_ACTIVE || xarMod::STATE_UPGRADED:
                            $newstate = xarMod::STATE_INACTIVE;
                            $adminapi->upgrade([    'regid'    => $regId,
                                'state'    => $newstate]);

                            $newstate = xarMod::STATE_ACTIVE;
                            $adminapi->activate([    'regid'    => $regId,
                                'state'    => $newstate]);
                            break;
                    }
                }

                // We are going to upgrade and activate it transparently

            } else {
                // It is and ordinary mortal module, no special treatment for it

                //TODO: Add check for any module that might depend on this one
                // If found, change its state to something inoperative too
                // New state? XAR_MODULE_DEPENDENCY_MISSING?

                if (!empty($fileModules[$name]) && $modInfo['version'] != $fileModules[$name]['version']) {

                    // Get module ID
                    $regId = $modInfo['regid'];
                    switch ($modInfo['state']) {
                        case xarMod::STATE_UNINITIALISED:
                            break;
                        case xarMod::STATE_INACTIVE:
                            $newstate = xarMod::STATE_UPGRADED;
                            break;
                        case xarMod::STATE_ACTIVE:
                            $newstate = xarMod::STATE_UPGRADED;
                            break;
                        case xarMod::STATE_UPGRADED:
                            $newstate = xarMod::STATE_UPGRADED;
                            break;
                    }
                    if (isset($newstate)) {
                        $set = $adminapi->setstate([    'regid'    => $regId,
                            'state'    => $newstate]);
                    }
                }
            }
        }

        $check = true;

        return true;
    }
}
