<?php
/**
 * Initialise a theme
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Initialise a theme
 *
 * Loads theme admin API and calls the initialise
 * function to actually perform the initialisation,
 * then redirects to the list function with a
 * status message and returns true.
 * @author Marty Vance
 * @param id $ the theme id to initialise
 * @returns 
 * @return 
 */
function themes_admin_initialise()
{ 
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return; 
    // Initialise theme
    $initialised = xarModAPIFunc('themes',
        'admin',
        'initialise',
        array('regid' => $id));

    if (!isset($initialised)) return;

    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
} 

?>