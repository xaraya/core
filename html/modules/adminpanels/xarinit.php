<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
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
    $adminMenuTable = pnDBGetSiteTablePrefix() . '_admin_menu';
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
    $query = pnDBCreateTable($adminMenuTable,
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
    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES ($id, 'adminpanels', 'Global', 0, 1);";
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES ($id, 'authsystem', 'Global', 0, 1);";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES ($id, 'base', 'Global', 0, 1);";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES ($id, 'blocks', 'Global', 0, 1);";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES ($id, 'groups', 'Users & Groups', 0, 1);";
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES ($id, 'modules', 'Global', 0, 1);";
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES ($id, 'permissions', 'Users & Groups', 0, 1);";
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (pn_amid, pn_name, pn_category, pn_weight, pn_flag) VALUES ($id, 'users', 'Users & Groups', 0, 1);";
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Register Block types
    $res = pnBlockTypeRegister('adminpanels', 'adminmenu');
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
