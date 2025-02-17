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
use xarEvents;
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi activate function
 * @extends MethodClass<AdminApi>
 */
class ActivateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Activate a module if it has an active function, otherwise just set the state to active
     * @access public
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] module's registered id
     * string   $args['name'] module's name
     * @return bool
     * @throws \EmptyParameterException
     * @see AdminApi::activate()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        // Argument check
        if (isset($name)) {
            $regid = xarMod::getRegID($name, 'module');
        }
        if (!isset($regid)) {
            throw new EmptyParameterException('regid');
        }

        $modInfo = xarMod::getInfo($regid);

        if ($modInfo['state'] == xarMod::STATE_UNINITIALISED) {
            throw new Exception("Calling activate function while module is uninitialised");
        }
        // Module activate function
        if (!$adminapi->executeinitfunction(['regid'    => $regid,
            'function' => 'activate'])) {
            $msg = xarML('Unable to execute "activate" function in the xarinit.php file of module (#(1))', $modInfo['displayname']);
            throw new Exception($msg);
        }

        // Update state of module
        $res = $adminapi->setstate(['regid' => $regid,
            'state' => xarMod::STATE_ACTIVE]);

        // notify any observers that this module was activated
        // NOTE: the ModActivate event observer notifies ModuleActivate hooks
        xarEvents::notify('ModActivate', $modInfo['name']);
        return true;
    }
}
