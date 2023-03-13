<?php
/**
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */
/**
 * List themes and current settings
 * @author Marty Vance
 * @param array several params from the associated form in template
 */
function themes_admin_settings()
{
    // Security
    if(!xarSecurity::check('AdminThemes')) return;

    // form parameters
    if (!xarVar::fetch('hidecore',  'str:1:', $hidecore,  '0',                  xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('selstyle',  'str:1:', $selstyle,  'plain',              xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('selfilter', 'str:1:', $selfilter, 'xarTheme::STATE_ANY', xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('selclass',  'str:1:', $selclass,  'all',                xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('regen',     'str:1:', $regen,      false,               xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('useicons',  'checkbox', $useicons, false,               xarVar::NOT_REQUIRED)) return;

    if (!xarModUserVars::set('themes', 'hidecore', $hidecore)) return;
    if (!xarModUserVars::set('themes', 'selstyle', $selstyle)) return;
    if (!xarModUserVars::set('themes', 'selfilter', $selfilter)) return;
    if (!xarModUserVars::set('themes', 'selclass', $selclass)) return;
    if (!xarModUserVars::set('themes', 'useicons', $useicons)) return;

    xarController::redirect(xarController::URL('themes', 'admin', 'view', array('regen' => $regen = 1)));
    return true;
}
