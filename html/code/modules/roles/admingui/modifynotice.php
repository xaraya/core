<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminGui;
use xarController;
use xarModHooks;
use xarModVars;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin modifynotice function
 * @extends MethodClass<AdminGui>
 */
class ModifynoticeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * modify configuration
     * @return array|string|bool|void data for the template display
     * @see AdminGui::modifynotice()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminRoles')) {
            return;
        }

        xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED);
        $hooks = [];
        switch (strtolower($phase)) {
            case 'modify':
            default:
                $ips = xarModVars::get('roles', 'disallowedips');
                $data['ips'] = empty($ips) ? '' : unserialize($ips);
                $data['authid'] = xarSec::genAuthKey();
                $data['updatelabel'] = xarML('Update Notification Configuration');

                $hooks = xarModHooks::call(
                    'module',
                    'modifyconfig',
                    'roles',
                    ['module' => 'roles']
                );
                $data['hooks'] = $hooks;

                break;

            case 'update':
                xarVar::fetch('askwelcomeemail', 'checkbox', $askwelcomeemail, false, xarVar::NOT_REQUIRED);
                xarVar::fetch('askdeactivationemail', 'checkbox', $askdeactivationemail, false, xarVar::NOT_REQUIRED);
                xarVar::fetch('askvalidationemail', 'checkbox', $askvalidationemail, false, xarVar::NOT_REQUIRED);
                xarVar::fetch('askpendingemail', 'checkbox', $askpendingemail, false, xarVar::NOT_REQUIRED);
                xarVar::fetch('askpasswordemail', 'checkbox', $askpasswordemail, false, xarVar::NOT_REQUIRED);
                // Confirm authorisation code
                if (!xarSec::confirmAuthKey()) {
                    return xarController::badRequest('bad_author', $this->getContext());
                }
                // Update module variables
                xarModVars::set('roles', 'askwelcomeemail', $askwelcomeemail);
                xarModVars::set('roles', 'askdeactivationemail', $askdeactivationemail);
                xarModVars::set('roles', 'askvalidationemail', $askvalidationemail);
                xarModVars::set('roles', 'askpendingemail', $askpendingemail);
                xarModVars::set('roles', 'askpasswordemail', $askpasswordemail);

                xarModHooks::call(
                    'module',
                    'updateconfig',
                    'roles',
                    ['module' => 'roles']
                );

                xarController::redirect(xarController::URL('roles', 'admin', 'modifynotice'), null, $this->getContext());
                // Return
                return true;
        }
        return $data;
    }
}
