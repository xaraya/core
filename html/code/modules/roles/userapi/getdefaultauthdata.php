<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi getdefaultauthdata function
 * @extends MethodClass<UserApi>
 */
class GetdefaultauthdataMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * getdefaultauthdata  - get the default authentication module date from roles
     * The login and logout may not be supplied by the authentication module and so could be different
     * @author Jo Dalle Nogare <jojodee@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return array defaultauthmodulename, defaultlogoutmodname, defaultloginmodname
     * @see UserApi::getdefaultauthdata()
     */
    public function __invoke(array $args = [])
    {
        $defaultauthdata = [];

        $defaultauthmodulename = $this->mod()->getVar('defaultauthmodule');
        //check the module is still available else we have no alternative to fall back
        if (!$this->mod()->isAvailable($defaultauthmodulename)) {
            $defaultauthmodulename = 'authsystem'; //core authentication
        }

        // <jojodee> do we reset the default authmodule modvar here? Review - may only be non-active due to upgrade

        if (isset($defaultauthmodulename)) {
            //check for default logout function provided
            if (file_exists(sys::code() . 'modules/' . $defaultauthmodulename . '/xaruser/logout.php')) {
                $defaultauthmodlogout = $defaultauthmodulename;
            } elseif ($this->mod()->getModuleClassMethod($defaultauthmodulename, 'usergui', 'logout')) {
                $defaultauthmodlogout = $defaultauthmodulename;
            } else {
                $defaultauthmodlogout = 'authsystem';
            }
            //check for default login function provided
            if (file_exists(sys::code() . 'modules/' . $defaultauthmodulename . '/xaruser/login.php')) {
                $defaultauthmodlogin = $defaultauthmodulename;
            } elseif ($this->mod()->getModuleClassMethod($defaultauthmodulename, 'usergui', 'login')) {
                $defaultauthmodlogin = $defaultauthmodulename;
            } else {
                $defaultauthmodlogin = 'authsystem';
            }
        } else {
            $defaultauthmodulename = 'authsystem';
            $defaultauthmodlogin = 'authsystem';
            $defaultauthmodlogout = 'authsystem';
        }

        $defaultauthdata =  ['defaultauthmodname' => $defaultauthmodulename,
            'defaultlogoutmodname'  => $defaultauthmodlogout,
            'defaultloginmodname'   => $defaultauthmodlogin,
        ];
        return $defaultauthdata;
    }
}
