<?php
/**
 * File: $Id$
 *
 * Installer initialization functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage installer
 * @author Johnny Robeson
 */

/**
 * Install Xaraya
 *
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function installer_init()
{
    /*********************************************************************
    * Register the module components that are privileges objects
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

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
 * Delete Installer module
 *
 * @returns bool
 */
function installer_delete()
{
    // this module cannot be removed
    return false;
}

?>