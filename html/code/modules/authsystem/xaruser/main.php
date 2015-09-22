<?php
/**
 * Main entry point for the user interface of this module
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/42.html
 */

/**
 * The main user interface function of this module.
 * 
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments.  
 * The function redirects to the showloginform funtion.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Jo Dalle Nogare<jojodee@xaraya.com>
 * 
 * @param void N/A
 * @return boolean True after redirection
 */
function authsystem_user_main()
{
    $redirect = xarModVars::get('authsystem','frontend_page');
    if (!empty($redirect)) {
        $truecurrenturl = xarServer::getCurrentURL(array(), false);
        $urldata = xarModAPIFunc('roles','user','parseuserhome',array('url'=> $redirect,'truecurrenturl'=>$truecurrenturl));
        xarController::redirect($urldata['redirecturl']);
    } else {
        xarController::redirect(xarModURL('authsystem', 'user', 'showloginform'));
    }
    return true;
}

?>
