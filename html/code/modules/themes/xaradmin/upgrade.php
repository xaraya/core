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
 * Upgrade a theme
 *
 * Loads theme admin API and calls the upgrade function
 * to actually perform the upgrade, then redrects to
 * the list function and with a status message and returns
 * true.
 *
 * @author Marty Vance
 * @param id the theme id to upgrade
 * @returns
 * @return
 */
function themes_admin_upgrade()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if (!xarVarFetch('id', 'int:1:', $id)) return; 
    if (!xarVarFetch('id', 'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return; 
    if (empty($id)) return xarResponse::notFound();

    
    // Upgrade theme
    $upgraded = xarMod::apiFunc('themes', 'admin', 'upgrade', array('regid' => $id));
    
    //throw back
    if(!isset($upgraded)) return;

    xarResponse::redirect(xarModURL('themes', 'admin', 'list'));

    return true;
}

?>
