<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * List themes and current settings
 * @author Marty Vance
 * @param several params from the associated form in template
 */
function themes_admin_settings()
{
    // Security Check
    if(!xarSecurityCheck('AdminTheme')) return;

    // form parameters
    if (!xarVarFetch('hidecore',  'str:1:', $hidecore,  '0',                  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selstyle',  'str:1:', $selstyle,  'plain',              XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selfilter', 'str:1:', $selfilter, 'XARTHEME_STATE_ANY', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selclass',  'str:1:', $selclass,  'all',                XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('regen',     'str:1:', $regen,     false,                XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('useicons',  'checkbox', $useicons, 'XARTHEME_STATE_ANY', XARVAR_NOT_REQUIRED)) return;

    if (!xarModUserVars::set('themes', 'hidecore', $hidecore)) return;
    if (!xarModUserVars::set('themes', 'selstyle', $selstyle)) return;
    if (!xarModUserVars::set('themes', 'selfilter', $selfilter)) return;
    if (!xarModUserVars::set('themes', 'selclass', $selclass)) return;
    if (!xarModUserVars::set('themes', 'useicons', $useicons)) return;

    xarResponseRedirect(xarModURL('themes', 'admin', 'list', array('regen' => $regen = 1)));
}

?>
