<?php
/**
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Deactivate a theme
 * 
 * Loads theme admin API and calls the setstate
 * function    to actually    perfrom    the    deactivation,
 * then    redirects to the list function with    a status
 * message and returns true.
 * @author Marty Vance
 * @access public 
 * @param id $ the theme id    to deactivate
 * @return boolean true on success, false on failure
 */
function themes_admin_deactivate()
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
        
    //Checking if the user has already passed thru the GUI:
    xarVarFetch('command', 'checkbox', $command, false, XARVAR_NOT_REQUIRED);

    // set the target location (anchor) to go to within the page
    $minfo=xarThemeGetInfo($id);
    $target=$minfo['name'];
    if (empty($return_url))
        $return_url = xarModURL('themes', 'admin', 'list', array('state' => XARTHEME_STATE_ANY), NULL, $target);

    // See if we have lost any modules since last generation
    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance('themes');  
    if (!$installer->checkformissing()) {return;}

    // deactivate
    $deactivated = xarMod::apiFunc('themes','admin','setstate',array('regid' => $id,'state' => XARTHEME_STATE_INACTIVE)); 

    // Hmmm, I wonder if the target adding is considered a hack
    // it certainly depends on the implementation of xarModUrl
    xarController::redirect($return_url);
    return true;
}
?>
