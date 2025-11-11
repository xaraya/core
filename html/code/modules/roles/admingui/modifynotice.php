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
        if (!$this->sec()->checkAccess('AdminRoles')) {
            return;
        }

        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $hooks = [];
        switch (strtolower($phase)) {
            case 'modify':
            default:
                $ips = $this->mod()->getVar('disallowedips');
                $data['ips'] = empty($ips) ? '' : unserialize($ips);
                $data['authid'] = $this->sec()->genAuthKey();
                $data['updatelabel'] = $this->ml('Update Notification Configuration');

                $hooks = $this->mod()->callHooks(
                    'module',
                    'modifyconfig',
                    'roles',
                    ['module' => 'roles']
                );
                $data['hooks'] = $hooks;

                break;

            case 'update':
                $this->var()->find('askwelcomeemail', $askwelcomeemail, 'checkbox', false);
                $this->var()->find('askdeactivationemail', $askdeactivationemail, 'checkbox', false);
                $this->var()->find('askvalidationemail', $askvalidationemail, 'checkbox', false);
                $this->var()->find('askpendingemail', $askpendingemail, 'checkbox', false);
                $this->var()->find('askpasswordemail', $askpasswordemail, 'checkbox', false);
                // Confirm authorisation code
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }
                // Update module variables
                $this->mod()->setVar('askwelcomeemail', $askwelcomeemail);
                $this->mod()->setVar('askdeactivationemail', $askdeactivationemail);
                $this->mod()->setVar('askvalidationemail', $askvalidationemail);
                $this->mod()->setVar('askpendingemail', $askpendingemail);
                $this->mod()->setVar('askpasswordemail', $askpasswordemail);

                $this->mod()->callHooks(
                    'module',
                    'updateconfig',
                    'roles',
                    ['module' => 'roles']
                );

                $this->ctl()->redirect($this->ctl()->getModuleURL('roles', 'admin', 'modifynotice'));
                // Return
                return true;
        }
        return $data;
    }
}
