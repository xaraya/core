<?php
/**
 * Main entry point for the user interface of this module
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * The main user interface function of this module.
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments.  
 * The function displays a list of DD's available modules.
 *
 * @return array|bool empty array of data for the template display
 */
function dynamicdata_user_main(Array $args=array())
{
    $redirect = xarModVars::get('dynamicdata','frontend_page');
    if (!empty($redirect)) {
        $truecurrenturl = xarServer::getCurrentURL(array(), false);
        $urldata = xarMod::apiFunc('roles','user','parseuserhome',array('url'=> $redirect,'truecurrenturl'=>$truecurrenturl));
        xarController::redirect($urldata['redirecturl']);
        return true;
    } else {
        return array();
    }
}
