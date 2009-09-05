<?php
/**
 * Block Functions
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Blocks Functions
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_main()
{
    if(!xarSecurityCheck('EditBlock')) return;

    $refererinfo = xarRequest::getInfo(xarServer::getVar('HTTP_REFERER'));
    $info = xarRequest::getInfo();
    $samemodule = $info[0] == $refererinfo[0];
    
    if ((xarModVars::get('modules', 'disableoverview') == 0) || $samemodule){
        return xarTplModule('blocks','admin','overview');
    } else {
        xarResponse::Redirect(xarModURL('blocks', 'admin', 'view_instances'));
        return true;
    }
}

?>
