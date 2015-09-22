<?php
/**
 * Display the user login form
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/42.html
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * Shows the user login form when login block is not active
 * 
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Jo Dalle Nogare <jojodeexaraya.com>
 * 
 * @param array $args Optional 'redirecturl' parameter
 * @return array Returns data for display template.
 */
function authsystem_user_showloginform(Array $args = array())
{
    extract($args);
    xarVarFetch('redirecturl', 'str:1:254', $redirecturl, '', XARVAR_NOT_REQUIRED);
    if (empty($redirecturl)) {
        $redirecturl = xarModVars::get('authsystem', 'forwarding_page');
        if(empty($redirecturl)) $redirecturl = xarServer::getBaseURL();
    }
    $redirecturl = xarVarPrepHTMLDisplay($redirecturl);
    $truecurrenturl = xarServer::getCurrentURL(array(), false);
    $urldata = xarModAPIFunc('roles','user','parseuserhome',array('url'=> $redirecturl,'truecurrenturl'=>$truecurrenturl));
    $data['redirecturl'] = $urldata['redirecturl'];
    
    // If we don't ask to forward, then forward immediately
    if (!(int)xarModVars::get('authsystem', 'ask_forward') && xarUser::isLoggedIn()) {
        xarController::redirect($data['redirecturl']);
        return true;
    }

    return $data;
}
?>
