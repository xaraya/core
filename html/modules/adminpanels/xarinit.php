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
xarDBLoadTableMaintenanceAPI();

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
    list($dbconn) = xarDBGetConn();
    $table = xarDBGetTables();

    // Create tables
    $adminMenuTable = xarDBGetSiteTablePrefix() . '_admin_menu';
    /*********************************************************************
     * Here we create all the tables for the adminpanels module
     *
     * prefix_admin_menu       - admin modules
     ********************************************************************/

    // prefix_admin_menu
    /*********************************************************************
    * CREATE TABLE xar_admin_menu (
    *  xar_amid int(11) NOT NULL auto_increment,
    *  xar_name varchar(32) NOT NULL default '',
    *  xar_category varchar(32) NOT NULL default '',
    *  xar_weight int(11) NOT NULL default '0',
    *  xar_flag tinyint(4) NOT NULL default '1',
    *  PRIMARY KEY  (xar_amid)
    * )
    *********************************************************************/
    // *_admin_menu
    $query = xarDBCreateTable($adminMenuTable,
                             array('xar_amid'        => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0',
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'xar_name'        => array('type'        => 'varchar',
                                                             'size'        => 32,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_category'    => array('type'        => 'varchar',
                                                             'size'        => 32,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_weight'       => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'xar_flag'         => array('type'        => 'integer',
                                                             'size'        => 'tiny',
                                                             'null'        => false,
                                                             'default'     => '1')));
    $result =& $dbconn->Execute($query);
    if (!$result) return;
    
    // Set config vars

    // Fill admin menu
    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'adminpanels', 'Global', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'authsystem', 'Global', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'base', 'Global', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'blocks', 'Global', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'groups', 'Users & Groups', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'modules', 'Global', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'permissions', 'Users & Groups', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'users', 'Users & Groups', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Register Block types
    $res = xarBlockTypeRegister('adminpanels', 'adminmenu');
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    // Register Block types
    $res = xarBlockTypeRegister('adminpanels', 'waitingcontent');
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }
    
    // Set module variables
    xarModSetVar('adminpanels','showold', 1);
    xarModSetVar('adminpanels','menuposition', 'l');
    xarModSetVar('adminpanels','menustyle', 'bycat');
    xarModSetVar('adminpanels','showontop', 1);
    xarModSetVar('adminpanels','showhelp', 1);
    
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
