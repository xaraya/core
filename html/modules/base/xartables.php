<?php
/**
 * File: $Id$
 *
 * base table defintions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html} 
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @author Paul Rosania
 * @todo template tag table!!! what are we gonna do !
 */

/**
 * Passes table definitons back to Xaraya core
 *
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