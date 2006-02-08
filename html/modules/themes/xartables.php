<?php
/**
 * Themes administration and initialization
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/* Themes administration
 * @author Marty Vance
*/

function themes_xartables()
{
    // Initialise table array
    $xartable = array();

    // Get the name for the autolinks item table
    $systemPrefix = xarDBGetSystemTablePrefix();
    $sitePrefix   = xarDBGetSiteTablePrefix();

    // Set the table name
    $xartable['themes']                 = $systemPrefix . '_themes';

    // Return the table information
    return $xartable;
}

?>
