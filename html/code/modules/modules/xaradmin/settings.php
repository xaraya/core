<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
/**
 * List modules and current settings
 * @param several params from the associated form in template
 *
 * @author Xaraya Development Team
 */
function modules_admin_settings()
{
    // Security
    if(!xarSecurityCheck('AdminModules')) return;

    if (!xarVarFetch('hidecore', 'str:1:', $hidecore, '0', XARVAR_NOT_REQUIRED)) return; 
    if (!xarVarFetch('selstyle', 'str:1:', $selstyle, 'plain', XARVAR_NOT_REQUIRED)) return; 
    if (!xarVarFetch('selfilter', 'str:1:', $selfilter, 'XARMOD_STATE_ANY', XARVAR_NOT_REQUIRED)) return; 
    if (!xarVarFetch('selsort', 'str:1:', $selsort, 'namedesc', XARVAR_NOT_REQUIRED)) return; 
    if (!xarVarFetch('regen', 'str:1:', $regen, XARVAR_NOT_REQUIRED)) return; 
    
    xarModUserVars::set('modules', 'hidecore', $hidecore);
    xarModUserVars::set('modules', 'selstyle', $selstyle);
    xarModUserVars::set('modules', 'selfilter', $selfilter);
    xarModUserVars::set('modules', 'selsort', $selsort);
    
    xarController::redirect(xarModURL('modules', 'admin', 'list', array('regen' => $regen)));
    return true;
}

?>
