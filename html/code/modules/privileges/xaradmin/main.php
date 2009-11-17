<?php
/**
 * Main administration function
 *
 * @package core modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * the main administration function - pass-thru
 */
function privileges_admin_main()
{
    if(!xarSecurityCheck('EditPrivilege')) return;

    $refererinfo = xarRequest::getInfo(xarServer::getVar('HTTP_REFERER'));
    $info = xarRequest::getInfo();
    $samemodule = $info[0] == $refererinfo[0];
    
    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return xarTplModule('privileges','admin','overview');
    } else {
        xarResponse::redirect(xarModURL('privileges', 'admin', 'viewprivileges'));
        return true;
    }
}

?>
