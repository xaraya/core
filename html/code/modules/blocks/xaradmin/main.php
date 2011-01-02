<?php
/**
 * Main entry point for the admin interface of this module
 *
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Main entry point for the admin interface of this module
 *
 * This function is the default function for the admin interface, and is called whenever the module is
 * initiated with only an admin type but no func parameter passed.  
 * The function displays the module's overview page, or redirects to another page if overviews are disabled.
 *
 * @author Jim McDonald
 * @author Paul Rosania
 * @return mixed output display string or boolean true if redirected
 */
function blocks_admin_main()
{
    // Security
    if(!xarSecurityCheck('EditBlocks')) return;

    $refererinfo = xarController::$request->getInfo(xarServer::getVar('HTTP_REFERER'));
    $info = xarController::$request->getInfo();
    $samemodule = $info[0] == $refererinfo[0];

    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        $data = array();
        if (!xarVarFetch('tab', 'pre:trim:lower:str:1:', $data['tab'], '', XARVAR_NOT_REQUIRED)) return;
        return xarTplModule('blocks','admin','overview', $data);
    } else {
        xarController::redirect(xarModURL('blocks', 'admin', 'view_instances'));
        return true;
    }
}

?>
