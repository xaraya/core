<?php
/**
 * Default theme for site
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Default theme for site
 *
 * Sets the module var for the default site theme.
 *
 * @author Marty Vance
 * @param id the theme id to set
 * @return boolean true on success, false on failure
 */
function themes_admin_setdefault()
{
    // Security
    if (!xarSecurityCheck('AdminThemes')) return;
    
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }
    
    if (!xarVarFetch('id', 'int:1:', $defaulttheme, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($defaulttheme)) return xarResponse::notFound();


    $whatwasbefore = xarModVars::get('themes', 'default_theme');

    if (!isset($defaulttheme)) {
        $defaulttheme = $whatwasbefore;
    }

    $themeInfo = xarThemeGetInfo($defaulttheme);

    if ($themeInfo['class'] != 2) {
        xarController::redirect(xarModURL('themes', 'admin', 'modifyconfig'));
    }

    if (xarVarIsCached('Mod.Variables.themes', 'default_theme')) {
        xarVarDelCached('Mod.Variables.themes', 'default_theme');
    }

    //update the database - activate the theme
    if (!xarMod::apiFunc('themes','admin','install',array('regid'=>$defaulttheme))) {
        xarController::redirect(xarModURL('themes', 'admin', 'modifyconfig'));
    }

    // update the data
    xarTpl::setThemeDir($themeInfo['directory']);
    xarModVars::set('themes', 'default_theme', $themeInfo['directory']);

    // set the target location (anchor) to go to within the page
    $target = $themeInfo['name'];
    xarController::redirect(xarModURL('themes', 'admin', 'list', array('state' => 0), NULL, $target));
    return true;
}
?>
