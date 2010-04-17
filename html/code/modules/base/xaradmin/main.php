<?php
/**
 * Main admin GUI function
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
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

    $refererinfo = xarRequest::getInfo(xarServer::getVar('HTTP_REFERER'));
    $info = xarRequest::getInfo();
    $samemodule = $info[0] == $refererinfo[0];
    
    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return xarTplModule('base','admin','overview');
    } else {
        xarResponse::redirect(xarModURL('base', 'admin', 'modifyconfig'));
        return true;
    }
}

?>