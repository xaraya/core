<?php 
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file:  Table information for blocks module
// ----------------------------------------------------------------------

function blocks_xartables()
{
    // Initialise table array
    $xartable = array();

    // Get the name for the example item table.  This is not necessary
    // but helps in the following statements and keeps them readable
    $userblocks = xarDBGetSiteTablePrefix() . '_userblocks';

    // Set the table name
    $xartable['userblocks'] = $userblocks;

    // Return the table information
    return $xartable;
}

?>