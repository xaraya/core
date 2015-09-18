<?php
/**
 * Regenerate list of available themes
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Regenerate list of available themes
 *
 * Loads theme admin API and calls the regenerate function
 * to actually perform the regeneration, then redirects
 * to the list function with a status meessage and returns true.
 *
 * @author Marty Vance
 * @access public
 * @return boolean true on success, false on failure
 */
function themes_admin_regenerate()
{
    // Security
    if (!xarSecurityCheck('AdminThemes')) return; 
    
    // Security check
    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    // Regenerate themes
    $regenerated = xarMod::apiFunc('themes', 'admin', 'regenerate');

    if (!isset($regenerated)) return;
    // Redirect
    xarController::redirect(xarModURL('themes', 'admin', 'list'));
    return true;
}

?>
