<?php
/**
 * Activate a theme
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
 * Activate a theme
 * 
 * Loads theme admin API and calls the activate
 * function to actually perform the activation,
 * then redirects to the list function with a
 * status message and returns true.
 * 
 * @param id $ the theme id to activate
 * @returns 
 * @return 
 */
function themes_admin_activate()
{ 
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;
    if (!xarVarFetch('id', 'int:1:', $id)) return; 

    // Activate
    $activated = xarModAPIFunc('themes',
                               'admin',
                               'activate',
                               array('regid' => $id));

    //throw back
    if (!isset($activated)) return;
    $minfo=xarThemeGetInfo($id);
    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];
    xarResponseRedirect(xarModURL('themes', 'admin', 'list', array('state' => 0), NULL, $target));
    return true;
} 
?>