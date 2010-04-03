<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Installs a theme
 *
 * Loads module themes API and calls the initialise
 * function to actually perform the initialisation,
 * then redirects to the list function with a
 * status message and returns true.
 * <andyv implementation of JC's request> attempt to activate module immediately after it's inited
 *
 * @param id the module id to initialise
 * @returns
 * @return
 */
function themes_admin_install()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    if (!xarVarFetch('id', 'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();
    $minfo=xarThemeGetInfo($id);
    if (!xarMod::apiFunc('themes','admin','install',array('regid'=>$id))) return;

    // set the target location (anchor) to go to within the page
    $target = $minfo['name'];
    xarController::redirect(xarModURL('themes', 'admin', 'list', array('state' => 0), NULL, $target));
    return true;
}
?>
