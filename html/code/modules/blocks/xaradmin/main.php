<?php
/**
 * Block Functions
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
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
    if(!xarSecurityCheck('EditBlocks')) return;

    $refererinfo = xarRequest::getInfo(xarServer::getVar('HTTP_REFERER'));
    $info = xarRequest::getInfo();
    $samemodule = $info[0] == $refererinfo[0];

    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        $data = array();
        if (!xarVarFetch('tab', 'pre:trim:lower:str:1:', $data['tab'], '', XARVAR_NOT_REQUIRED)) return;
        return xarTplModule('blocks','admin','overview', $data);
    } else {
        xarResponse::redirect(xarModURL('blocks', 'admin', 'view'));
        return true;
    }
}

?>