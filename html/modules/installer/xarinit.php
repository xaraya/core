<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Johnny Robeson
// Purpose of file:  Xaraya Install
// ----------------------------------------------------------------------

/**
 * Install Xaraya
 *
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function installer_init()
{
    // Load in installer API
    if (!xarInstallAPILoad('installer','admin')) {
        return NULL;
    }

    if (!xarInstallAPIFunc('installer',
                           'admin',
                           'initialise',
                           array('directory' => 'base',
                                 'initfunc'  => 'init'))) {
        return NULL;
    }

    // Initialisation successful
    return true;
}

/**
 * Upgrade Xaraya
 *
 * @param oldVersion
 * @returns bool
 */
function installer_upgrade($oldVersion)
{
    // Get database information
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();


    return false;
}

/**
 * Delete Xaraya
 *
 * @param none
 * @returns bool
 */
function installer_delete()
{
    return false;
}

?>
