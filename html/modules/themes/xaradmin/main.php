<?php
/**
 * File: $Id$
 *
 * Main themes module function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage Themes
 * @author Marty Vance
*/
/**
 * main themes module function
 * @return themes_admin_main
 *
 */
function themes_admin_main()
{
    // Security Check
    if(!xarSecurityCheck('AdminTheme')) return;

    if (xarModGetVar('adminpanels', 'overview') == 0){
        // Return the output
        return array();
    } else {
        xarResponseRedirect(xarModURL('themes', 'admin', 'list'));
    }
    // success
    return true;
}

?>