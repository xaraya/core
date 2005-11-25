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
    // FIXME: quick hack to make it work, this is NOT right <mrb>
    $xartable['themes']                 = $systemPrefix . '_themes';
    $xartable['system/theme_states']    = $systemPrefix . '_theme_states';
    $xartable['site/theme_states']      = $sitePrefix . '_theme_states';
    $xartable['site/theme_vars']        = $sitePrefix . '_theme_vars';
    $xartable['system/theme_vars']      = $systemPrefix . '_theme_vars';
    $xartable['theme_vars']             = $systemPrefix . '_theme_vars';

    // Return the table information
    return $xartable;
}

?>
