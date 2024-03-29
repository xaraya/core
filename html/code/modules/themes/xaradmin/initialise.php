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
 * Initialise a theme
 *
 * Loads theme admin API and calls the initialise
 * function to actually perform the initialisation,
 * then redirects to the list function with a
 * status message and returns true.
 * @author Marty Vance
 * @param int id $ the theme id to initialise
 * @return boolean|string|void true on success, false on failure
 */
function themes_admin_initialise()
{ 
    // Security
    if (!xarSecurity::check('AdminThemes')) return; 
    
    // Security and sanity checks
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if (!xarVar::fetch('id', 'int:1:', $id, 0, xarVar::NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();

    // Initialise theme
    $initialised = xarMod::apiFunc('themes',
        'admin',
        'initialise',
        array('regid' => $id));

    if (!isset($initialised)) return;

    xarController::redirect(xarController::URL('themes', 'admin', 'view'));
    return true;
}
