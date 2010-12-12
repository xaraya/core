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
 * Remove a theme
 * 
 * Loads theme admin API and calls the remove function
 * to actually perform the removal, then redirects to
 * the list function with a status message and retursn true.
 *
 * @author Marty Vance 
 * @access public 
 * @param id $ the theme id
 * @returns mixed
 * @return true on success
 */
function themes_admin_remove()
{ 
    // Security
    if (!xarSecurityCheck('ManageThemes')) return; 
    
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if (!xarVarFetch('id', 'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();

    // Remove theme
    $removed = xarMod::apiFunc('themes',
        'admin',
        'remove',
        array('regid' => $id)); 
    // throw back
    if (!isset($removed)) return;

    xarResponse::redirect(xarModURL('themes', 'admin', 'list'));

    return true;
} 

?>