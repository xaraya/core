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
use ixarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi deactivate function
 * @extends MethodClass<AdminApi>
 */
class DeactivateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Deactivate a module if it has a deactive function, otherwise just set the state to deactive
     * @author Xaraya Development Team
     * @access public
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['regid'] module's registered id
     * string   $args['name'] module's name
     * @return bool|void true on success, false on failure
     * @throws \EmptyParameterException
     * @see AdminApi::deactivate()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        // Argument check
        if (isset($name)) {
            $regid = $this->mod()->getRegID($name);
        }
        if (!isset($regid)) {
            throw new EmptyParameterException('regid');
        }

        $modInfo = $this->mod()->getInfo($regid);

        //Shouldnt we check first if the module is alredy ACTIVATED????
        //What should we do with UPGRADED STATE? What is it meant to?
        //  if ($modInfo['state'] != ixarMod::STATE_ACTIVE)

        // Module activate function
        // only run if the module is actually there. It may have been removed
        if ($modInfo['state'] != ixarMod::STATE_MISSING_FROM_ACTIVE) {
            if (!$adminapi->executeinitfunction(['regid'    => $regid,
                'function' => 'deactivate'])) {
                //Raise an Exception
                return;
            }
        }
        // Update state of module
        $res = $adminapi->setstate(['regid' => $regid,
            'state' => ixarMod::STATE_INACTIVE]);

        // notify any observers that this module was deactivated
        // NOTE: the ModDeactivate event observer notifies ModuleDeactivate hooks
        xarEvents::notify('ModDeactivate', $modInfo['name'], $this->getContext());
        return true;
    }
}
