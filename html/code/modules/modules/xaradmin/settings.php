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
 * @param array several params from the associated form in template
 *
 * @author Xaraya Development Team
 */
function modules_admin_settings()
{
    // Security
    if(!xarSecurity::check('AdminModules')) return;

    if (!xarVar::fetch('hidecore', 'str:1:', $hidecore, '0', xarVar::NOT_REQUIRED)) return; 
    if (!xarVar::fetch('selstyle', 'str:1:', $selstyle, 'plain', xarVar::NOT_REQUIRED)) return; 
    if (!xarVar::fetch('selfilter', 'str:1:', $selfilter, 'xarMod::STATE_ANY', xarVar::NOT_REQUIRED)) return; 
    if (!xarVar::fetch('selsort', 'str:1:', $selsort, 'namedesc', xarVar::NOT_REQUIRED)) return; 
    if (!xarVar::fetch('regen', 'str:1:', $regen, xarVar::NOT_REQUIRED)) return; 
    
    xarModUserVars::set('modules', 'hidecore', $hidecore);
    xarModUserVars::set('modules', 'selstyle', $selstyle);
    xarModUserVars::set('modules', 'selfilter', $selfilter);
    xarModUserVars::set('modules', 'selsort', $selsort);
    
    xarController::redirect(xarController::URL('modules', 'admin', 'list', array('regen' => $regen)));
    return true;
}
