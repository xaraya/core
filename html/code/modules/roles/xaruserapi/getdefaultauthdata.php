<?php
/**
 * Get the default authentication module and related data
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * getdefaultauthdata  - get the default authentication module date from roles
 * The login and logout may not be supplied by the authentication module and so could be different
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 * @return array defaultauthmodulename, defaultlogoutmodname, defaultloginmodname
 */
function roles_userapi_getdefaultauthdata()
{
    $defaultauthdata=array();

    $defaultauthmodulename = xarModVars::get('roles','defaultauthmodule');
    //check the module is still available else we have no alternative to fall back
    if (!xarModIsAvailable($defaultauthmodulename)) {
       $defaultauthmodulename='authsystem'; //core authentication
    }

    // <jojodee> do we reset the default authmodule modvar here? Review - may only be non-active due to upgrade

    if (isset($defaultauthmodulename)) {
        //check for default logout function provided
        if (file_exists(sys::code() . 'modules/'.$defaultauthmodulename.'/xaruser/logout.php')) {
            $defaultauthmodlogout=$defaultauthmodulename;
        } else{
           $defaultauthmodlogout='authsystem';
        }
        //check for default login function provided
        if (file_exists(sys::code() . 'modules/'.$defaultauthmodulename.'/xaruser/login.php')) {
            $defaultauthmodlogin=$defaultauthmodulename;
        } else{
            $defaultauthmodlogin='authsystem';
        }
    } else {
        $defaultauthmodulename='authsystem';
        $defaultauthmodlogin='authsystem';
        $defaultauthmodlogout='authsystem';
    }

    $defaultauthdata = array ('defaultauthmodname' => $defaultauthmodulename,
                              'defaultlogoutmodname'  => $defaultauthmodlogout,
                              'defaultloginmodname'   => $defaultauthmodlogin
                              );
    return $defaultauthdata;
}

?>