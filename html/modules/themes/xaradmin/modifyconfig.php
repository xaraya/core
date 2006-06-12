<?php
/**
 * Modify the configuration parameters
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * This is a standard function to modify the configuration parameters of the
 * module
 *
 * @author Marty Vance
 */
function themes_admin_modifyconfig()
{ 
    // Security Check
    if (!xarSecurityCheck('AdminTheme')) return; 
    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey(); 
    // everything else happens in Template for now
    // prepare labels and values for display by the template
    $data['title'] = xarVarPrepForDisplay(xarML('Configure Themes'));
    $data['configoverview'] = xarVarPrepForDisplay(xarML('Configure Overview'));
    $data['showhelplabel'] = xarVarPrepForDisplay(xarML('Show module "Help" in the menu:'));
    $data['showhelp'] = xarModGetVar('modules', 'showhelp') ? 'checked' : '' ;
    $data['submitbutton'] = xarVarPrepForDisplay(xarML('Submit')); 
    // Dashboard
    $data['dashboard']= xarModGetVar('themes', 'usedashboard');
    $data['dashtemplate']= trim(xarModGetVar('themes', 'dashtemplate'));
    if (!isset($data['dashtemplate']) || trim ($data['dashtemplate']=='')) {
        $data['dashtemplate']='dashboard';
    }
    // everything else happens in Template for now
    return $data;
} 
?>
