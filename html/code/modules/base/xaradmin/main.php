<?php
/**
 * Main admin GUI function
 *
 * @package modules
 * @subpackage base module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/68.html
 * @author Marcel van der Boom
 */

/**
 * Main admin gui function, entry point
 * @author John Robeson
 * @author Greg Allan
 * @return bool true on success of return to sysinfo
 */
function base_admin_main()
{
    if(!xarSecurityCheck('EditBase')) return;

    $request = new xarRequest();
    $refererinfo = xarController::$request->getInfo(xarServer::getVar('HTTP_REFERER'));
    $module = xarController::$request->getModule();
    $samemodule = $module == $refererinfo[0];
    
    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return xarTplModule('base','admin','overview');
    } else {
        xarController::redirect(xarModURL('base', 'admin', 'modifyconfig'));
        return true;
    }
}

?>
