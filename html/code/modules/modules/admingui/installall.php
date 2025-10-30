<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminGui;
use Xaraya\Modules\Modules\AdminApi;
use xarMod;
use sys;

sys::import('xaraya.modules.method');
sys::import('modules.modules.class.installer');
use Xaraya\Modules\InstallerTool;

/**
 * modules admin installall function
 * @extends MethodClass<AdminGui>
 */
class InstallallMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Installs a module
     * Loads module admin API and calls the initialise
     * function to actually perform the initialisation,
     * then redirects to the list function with a
     * status message and returns true.
     * <andyv implementation of JC's request> attempt to activate module immediately after it's inited
     * @author Xaraya Development Team
     * @param int id the module id to initialise
     * @return bool|void true on success, false on failure
     * @see AdminGui::installall()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        //Testing it directly for now... Insert this back when it is put into the template
        //    if (!$this->sec()->confirmAuthKey()) return;

        //This is a very lenghty process
        @set_time_limit(600);

        // Get all modules in DB
        $dbModules = $adminapi->getdbmodules();
        if (!isset($dbModules)) {
            return;
        }

        sys::import('modules.modules.class.installer');
        $installer = InstallerTool::getInstance();
        foreach ($dbModules as $name => $info) {
            //Jump if already installed
            if ($info['state'] == xarMod::STATE_INSTALLED) {
                continue;
            }
            $dependencies = $installer->getalldependencies($info['regid']);
            //If this cannot be installed, jump it
            if (count($dependencies['unsatisfiable']) > 0) {
                continue;
            } else {
                if (!$installer->installmodule($info['regid'])) {
                    foreach ($dependencies['satisfiable'] as $key => $modInfo) {
                        $dbModules[$modInfo['name']]['state'] = xarMod::STATE_INSTALLED;
                    }
                }
            }
        }

        $this->ctl()->redirect($this->ctl()->getModuleURL('modules', 'admin', 'list', ['state' => 0], null));
        return true;
    }
}
