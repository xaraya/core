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
// Original Author of file: Paul Rosania
// Purpose of file:  Initialisation functions for adminpanels
// ----------------------------------------------------------------------

// Load Table Maintaince API
pnDBLoadTableMaintenanceAPI();

/**
 * Initialise the adminpanels module
 * 
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function adminpanels_init()
{
    // Get database information
    list($dbconn) = pnDBGetConn();
    $table = pnDBGetTables();

    // Create tables
    /*********************************************************************
     * Here we create all the tables for the adminpanels module
     *
     * prefix_admin_menu       - admin modules
     ********************************************************************/

    // prefix_admin_menu
    /*********************************************************************
    * CREATE TABLE pn_admin_menu (
    *  pn_amid int(11) NOT NULL auto_increment,
    *  pn_name varchar(32) NOT NULL default '',
    *  pn_category varchar(32) NOT NULL default '',
    *  pn_weight int(11) NOT NULL default '0',
    *  pn_flag tinyint(4) NOT NULL default '1',
    *  PRIMARY KEY  (pn_amid)
    * )
    *********************************************************************/
    // *_admin_menu
    $query = pnDBCreateTable($table['admin_menu'],
                             array('pn_amid'        => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0',
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'pn_name'        => array('type'        => 'varchar',
                                                             'size'        => 32,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'pn_category'    => array('type'        => 'varchar',
                                                             'size'        => 32,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'pn_weight'       => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'pn_flag'         => array('type'        => 'integer',
                                                             'size'        => 'tiny',
                                                             'null'        => false,
                                                             'default'     => '1')));
    $dbconn->Execute($query);
        
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    // Set config vars


    
    // Fill admin menu
    $query = "INSERT INTO $table['admin_menu'] (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES (1, 'adminpanels', 'Global', 0, 1);";
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = "INSERT INTO $table['admin_menu'] (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES (2, 'authsystem', 'Global', 0, 1);";
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = "INSERT INTO $table['admin_menu'] (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES (4, 'base', 'Global', 0, 1);";
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = "INSERT INTO $table['admin_menu'] (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES (5, 'blocks', 'Global', 0, 1);";
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = "INSERT INTO $table['admin_menu'] (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES (7, 'groups', 'Users & Groups', 0, 1);";
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = "INSERT INTO $table['admin_menu'] (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES (8, 'modules', 'Global', 0, 1);";
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = "INSERT INTO $table['admin_menu'] (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES (9, 'permissions', 'Users & Groups', 0, 1);";
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = "INSERT INTO $table['admin_menu'] (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES (10, 'users', 'Users & Groups', 0, 1);";
    
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    // Register Block types
    pnBlockTypeRegister('adminpanels', 'adminmenu');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    
    // Initialisation successful
    return true;
}

/**
 * Upgrade the adminpanels module from an old version
 * 
 * @param oldVersion old version of module to upgrade from
 * @returns bool
 */
function adminpanels_upgrade($oldVersion)
{
    return false;
}

/**
 * Delete the adminpanels module
 * 
 * @param none
 * @returns bool
 */
function adminpanels_delete()
{
    return false;
}

?>
