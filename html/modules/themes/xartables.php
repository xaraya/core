<?php 
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marcel van der Boom
// Purpose of file:  Table information for themes module
// ----------------------------------------------------------------------

function themes_xartables()
{
    // Initialise table array
    $xartable = array();

    // Get the name for the autolinks item table
    $systemPrefix = xarDBGetSystemTablePrefix();
    $sitePrefix   = xarDBGetSiteTablePrefix();

    // Set the table name
    // FIXME: quick hack to make it work, this is NOT right <mrb>
    $xartable['themes'] = $systemPrefix . '_themes';
    $xartable['system/theme_states'] = $systemPrefix . '_theme_states';
    $xartable['site/theme_states'] = $sitePrefix . '_theme_states';
    $xartable['site/theme_vars'] = $sitePrefix . '_theme_vars';
    $xartable['system/theme_vars'] = $sytemPrefix . '_theme_vars';
    $xartable['theme_vars'] = $systemPrefix . '_theme_vars';

    // Return the table information
    return $xartable;
}

?>
