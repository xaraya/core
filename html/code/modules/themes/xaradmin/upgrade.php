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
 * Upgrade a theme
 *
 * Loads theme admin API and calls the upgrade function
 * to actually perform the upgrade, then redrects to
 * the list function and with a status message and returns
 * true.
 *
 * @author Marty Vance
 * @param int id the theme id to upgrade
 * @return boolean|string|void true on success, false on failure
 */
function themes_admin_upgrade()
{
    // Security
    if (!xarSecurity::check('AdminThemes')) return; 
    
    // Security and sanity checks
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if (!xarVar::fetch('id', 'int:1:', $id, 0, xarVar::NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();
    if (!xarVar::fetch('return_url', 'pre:trim:str:1:',
        $return_url, '', xarVar::NOT_REQUIRED)) return;
    
    // Upgrade theme
    $upgraded = xarMod::apiFunc('themes', 'admin', 'upgrade', array('regid' => $id));
    
    //throw back
    if(!isset($upgraded)) return;
    
    if (empty($return_url))
        $return_url = xarController::URL('themes', 'admin', 'view');
    xarController::redirect($return_url);
    return true;
}
