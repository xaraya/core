<?php
/**
 * Main themes module function
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * main themes module function
 * @return themes_admin_main
 *
 * @author Marty Vance
 */
function themes_admin_main()
{
    if(!xarSecurityCheck('EditThemes')) return;

    $refererinfo = xarRequest::getInfo(xarServer::getVar('HTTP_REFERER'));
    $info = xarRequest::getInfo();
    $samemodule = $info[0] == $refererinfo[0];
    
    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return xarTplModule('themes','admin','overview');
    } else {
        xarResponse::Redirect(xarModURL('themes', 'admin', 'list'));
        return true;
    }
}

?>