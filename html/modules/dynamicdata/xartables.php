<?php 
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: mikespub
// Purpose of file:  Table information for dynamicdata module
// ----------------------------------------------------------------------

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
    $dynamic_data = xarDBGetSiteTablePrefix() . '_dynamic_data';
    $dynamic_properties = xarDBGetSiteTablePrefix() . '_dynamic_properties';

    // Set the table names
    $xartable['dynamic_data'] = $dynamic_data;
    $xartable['dynamic_properties'] = $dynamic_properties;

    // Return the table information
    return $xartable;
}

?>
