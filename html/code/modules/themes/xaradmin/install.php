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
 * Installs a theme
 *
 * Loads module themes API and calls the initialise
 * function to actually perform the initialisation,
 * then redirects to the list function with a
 * status message and returns true.
 * <andyv implementation of JC's request> attempt to activate module immediately after it's inited
 *
 * @param int id the module id to initialise
 * @return boolean|string|void true on success, false on failure
 */
function themes_admin_install()
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

    $minfo=xarTheme::getInfo($id);
    if (!xarMod::apiFunc('themes','admin','install',array('regid'=>$id))) return;

    // set the target location (anchor) to go to within the page
    $target = $minfo['name'];
    if (empty($return_url))
        $return_url = xarController::URL('themes', 'admin', 'view', array('state' => xarTheme::STATE_ANY), NULL, $target);

    xarController::redirect($return_url);
    return true;
}
