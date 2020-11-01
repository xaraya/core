<?php
/**
 * Default theme for site
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
    if (!xarSecurity::check('AdminThemes')) return;
    
    // Security and sanity checks
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }
    
    if (!xarVar::fetch('id', 'int:1:', $defaulttheme, 0, xarVar::NOT_REQUIRED)) return;
    if (empty($defaulttheme)) return xarResponse::notFound();


    $whatwasbefore = xarModVars::get('themes', 'default_theme');

    if (!isset($defaulttheme)) {
        $defaulttheme = $whatwasbefore;
    }

    $themeInfo = xarThemeGetInfo($defaulttheme);

    if ($themeInfo['class'] != 2) {
        xarController::redirect(xarController::URL('themes', 'admin', 'modifyconfig'));
    }

    if (xarVar::isCached('Mod.Variables.themes', 'default_theme')) {
        xarVar::delCached('Mod.Variables.themes', 'default_theme');
    }

    //update the database - activate the theme
    if (!xarMod::apiFunc('themes','admin','install',array('regid'=>$defaulttheme))) {
        xarController::redirect(xarController::URL('themes', 'admin', 'modifyconfig'));
    }

    // update the data
    xarTpl::setThemeDir($themeInfo['directory']);
    xarModVars::set('themes', 'default_theme', $themeInfo['directory']);

    // set the target location (anchor) to go to within the page
    $target = $themeInfo['name'];
    xarController::redirect(xarController::URL('themes', 'admin', 'view', array('state' => 0), NULL, $target));
    return true;
}
?>
