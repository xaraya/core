<?php
// $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
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
    // Get database information
    list($dbconn) = pnDBGetConn();
    $tables = pnDBGetTables();
    
    // Load in installer API
    pnInstallAPILoad('installer','admin');

    // Install module tables
    $res = pnInstallAPIFunc('installer',
                            'admin',
                            'initialise',
                            array('directory' => 'modules',
                                  'initfunc'  => 'init'));

    unset($GLOBALS['PNSVuid']);
    unset($GLOBALS['PNSVnavigationLocale']);
    pnCoreInit(PNCORE_SYSTEM_MODULES);

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
    list($dbconn) = pnDBGetConn();
    $tables = pnDBGetTables();


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
