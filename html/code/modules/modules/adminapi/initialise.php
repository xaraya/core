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
use EmptyParameterException;
use Exception;
use ModuleNotFoundException;
use xarEvents;
use xarMod;
use sys;

sys::import('xaraya.modules.method');
sys::import('modules.modules.class.installer');
use Xaraya\Modules\InstallerTool;

/**
 * modules adminapi initialise function
 * @extends MethodClass<AdminApi>
 */
class InitialiseMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Initialise a module
     * @author Xaraya Development Team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['regid'] registered module id
     * string   $args['name'] module's name
     * @return bool|void true on success, false on failure
     * @throws \EmptyParameterException
     * @see AdminApi::initialise()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Get arguments from argument array
        extract($args);

        // Argument check
        if (isset($name)) {
            $regid = $this->mod()->getRegID($name);
        }
        if (!isset($regid)) {
            throw new EmptyParameterException('regid');
        }

        // Get module information
        $modInfo = $this->mod()->getInfo($regid);
        if (!isset($modInfo)) {
            throw new ModuleNotFoundException($regid, 'Module (regid: $regid) does not exist.');
        }

        //Checks module dependency
        sys::import('modules.modules.class.installer');
        $installer = InstallerTool::getInstance();
        if (!$installer->verifydependency($regid)) {
            //TODO: Add description of the dependencies
            $msg = $this->ml('The dependencies to initialise the module "#(1)" were not met.', $modInfo['displayname']);
            $this->exit($msg);
            return;
        }

        // Module deletion function
        if (!$adminapi->executeinitfunction(['regid'    => $regid,
            'function' => 'init'])) {
            //Raise an Exception
            return;
        }

        // Update state of module
        $set = $adminapi->setstate(['regid' => $regid,
            'state' => xarMod::STATE_INACTIVE]);

        // xar_debug($set);
        if (!isset($set)) {
            $msg = $this->ml('Module state change failed');
            throw new Exception($msg);
        }
        // notify any observers that this module was initialised
        // NOTE: the ModInitialise event observer notifies ModuleInit hooks
        xarEvents::notify('ModInitialise', $modInfo['name'], $this->getContext());
        // Success
        return true;
    }
}
