<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserGui;
use Xaraya\Modules\Roles\UserApi;

/**
 * roles user main function
 * @extends MethodClass<UserGui>
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * The main user interface function of this module.
     * This function is the default function, and is called whenever the module is
     * initiated without defining arguments. The function checks if user is logged in and redirects the user to his/her account, or displays the showloginform page of the current authentication module.
     * @return bool true after redirection
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @author Jo Dalle Nogare <jojodee@xaraya.com>
     * @see UserGui::main()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get the default authentication data - this supplies default auth module and corrected login and logout module
        $defaultauthdata = $userapi->getdefaultauthdata();

        $loginmodule = $defaultauthdata['defaultloginmodname'];
        $authmodule = $defaultauthdata['defaultauthmodname'];

        if ($this->user()->isLoggedIn()) {
            $this->ctl()->redirect($this->ctl()->getModuleURL('roles', 'user', 'account'));
        } else {
            $this->ctl()->redirect($this->ctl()->getModuleURL($loginmodule, 'user', 'showloginform'));
        }
        return true;
    }
}
