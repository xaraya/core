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

function base_xartables()
{
    // Initialise table array
    $xartable = array();

    // Get the name for the autolinks item table
    $allowed_vars = xarConfigGetVar('prefix') . '_allowed_vars';
    $templat_tags = xarConfigGetVar('prefix') . '_template_tags';
    // Set the table name
    $xartable['allowed_vars'] = $allowed_vars;
    // Q: does this need to be here?
    $xartable['template_tags']= $templat_tags;
    // Return the table information
    return $xartable;
}

?>