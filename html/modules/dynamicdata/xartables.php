<?php 
/**
 * File: $Id$
 *
 * Dynamic Data Table Definitions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * This function is called internally by the core whenever the module is
 * loaded.  It adds in the information
 */
function dynamicdata_xartables()
{
    // Initialise table array
    $xartable = array();

    // Get the name for the dynamicdata item table.  This is not necessary
    // but helps in the following statements and keeps them readable
    $dynamic_objects = xarDBGetSiteTablePrefix() . '_dynamic_objects';
    $dynamic_properties = xarDBGetSiteTablePrefix() . '_dynamic_properties';
    $dynamic_data = xarDBGetSiteTablePrefix() . '_dynamic_data';

    // Set the table names
    $xartable['dynamic_objects'] = $dynamic_objects;
    $xartable['dynamic_properties'] = $dynamic_properties;
    $xartable['dynamic_data'] = $dynamic_data;

    // Return the table information
    return $xartable;
}

?>
