<?php
/**
 * Themes administration and initialization
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
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
