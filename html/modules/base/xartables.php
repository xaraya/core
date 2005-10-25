<?php
/**
 * Base Table Definitions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */

/**
 * Passes table definitons back to Xaraya core
 * @author Paul Rosania
 * @return string
 */
function base_xartables()
{
    // Initialise table array
    $tables = array();

    $systemPrefix = xarDBGetSystemTablePrefix();

    // Get the name for the template Tags table table
    $templateTagsTable = $systemPrefix . '_template_tags';

    // Q: does this need to be here?
    $tables['template_tags']= $templateTagsTable;
    // Return the table information
    return $tables;
}

?>
