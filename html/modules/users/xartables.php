<?php 
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: John Cox
// Purpose of file:  Table information for base module
// ----------------------------------------------------------------------

function users_xartables()
{
    // Initialise table array
    $xartable = array();

    // Get the name for the autolinks item table
    $user_status   = xarConfigGetVar('prefix') . '_user_status';
    // Set the table name
    $xartable['user_status'] = $user_status;

    // Return the table information
    return $xartable;
}

?>