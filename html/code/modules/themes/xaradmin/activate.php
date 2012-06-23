<?php
/**
 * Activate a theme
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Activate a theme
 * 
 * Loads theme admin API and calls the activate
 * function to actually perform the activation,
 * then redirects to the list function with a
 * status message and returns true.
 * 
 * @param id $ the theme id to activate
 * @return boolean true on success, false on failure
 */
function themes_admin_activate()
{ 
    // Security
    if (!xarSecurityCheck('AdminThemes')) return; 
    
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }
    
    if (!xarVarFetch('id', 'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();
    if (!xarVarFetch('return_url', 'pre:trim:str:1:',
        $return_url, '', XARVAR_NOT_REQUIRED)) return;

    // Activate
    $activated = xarMod::apiFunc('themes',
                               'admin',
                               'activate',
                               array('regid' => $id));

    //throw back
    if (!isset($activated)) return;
    $minfo = xarThemeGetInfo($id);
    // set the target location (anchor) to go to within the page
    $target = $minfo['name'];
    if (empty($return_url))
        $return_url = xarModURL('themes', 'admin', 'list', array('state' => XARTHEME_STATE_ANY), NULL, $target);
    xarController::redirect($return_url);
    return true;
} 
?>
