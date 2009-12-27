<?php
/**
 * Default theme for site
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Default theme for site
 *
 * Sets the module var for the default site theme.
 *
 * @author Marty Vance
 * @param id the theme id to set
 * @returns
 * @return
 */
function themes_admin_setdefault()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    if (!xarSecurityCheck('AdminTheme')) return;
    if (!xarVarFetch('id', 'int:1:', $defaulttheme)) return;

    $whatwasbefore = xarModVars::get('themes', 'default');

    if (!isset($defaulttheme)) {
        $defaulttheme = $whatwasbefore;
    }

    $themeInfo = xarThemeGetInfo($defaulttheme);

    if ($themeInfo['class'] != 2) {
        xarResponse::redirect(xarModURL('themes', 'admin', 'modifyconfig'));
    }

    if (xarVarIsCached('Mod.Variables.themes', 'default')) {
        xarVarDelCached('Mod.Variables.themes', 'default');
    }

    //update the database - activate the theme
    if (!xarMod::apiFunc('themes','admin','install',array('regid'=>$defaulttheme))) {
        xarResponse::redirect(xarModURL('themes', 'admin', 'modifyconfig'));
    }

    // update the data
    xarTplSetThemeDir($themeInfo['directory']);
    xarModVars::set('themes', 'default', $themeInfo['directory']);

    // set the target location (anchor) to go to within the page
    $target = $themeInfo['name'];
    xarResponse::redirect(xarModURL('themes', 'admin', 'list', array('state' => 0), NULL, $target));
    return true;
}
?>
