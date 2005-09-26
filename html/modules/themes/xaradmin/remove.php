<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
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
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return; 
    // Remove theme
    $removed = xarModAPIFunc('themes',
        'admin',
        'remove',
        array('regid' => $id)); 
    // throw back
    if (!isset($removed)) return;

    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
} 

?>