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
function dynamicdata_pntables()
{
    // Initialise table array
    $pntable = array();

    // Get the name for the dynamicdata item table.  This is not necessary
    // but helps in the following statements and keeps them readable
    $dynamic_data = pnDBGetSiteTablePrefix() . '_dynamic_data';
    $dynamic_properties = pnDBGetSiteTablePrefix() . '_dynamic_properties';

    // Set the table names
    $pntable['dynamic_data'] = $dynamic_data;
    $pntable['dynamic_properties'] = $dynamic_properties;

    // Return the table information
    return $pntable;
}

?>
