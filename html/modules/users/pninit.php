<?php // $Id$
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
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file:  Initialisation functions for users
// ----------------------------------------------------------------------

/**
 * initialise the users module
 * This function is only ever called once during the lifetime of a particular
 * module instance
 */
function users_init()
{
    // Get datbase setup
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $prefix = pnConfigGetVar('prefix');

    // Create the table
    // *_users
    $query = pnDBCreateTable($prefix . '_users',
                             array('pn_uid'         => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0',
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'pn_name'        => array('type'        => 'varchar',
                                                             'size'        => 60,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'pn_uname'       => array('type'        => 'varchar',
                                                             'size'        => 25,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'pn_email'       => array('type'        => 'varchar',
                                                             'size'        => 100,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'pn_pass'        => array('type'        => 'varchar',
                                                             'size'        => 40,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'pn_url'         => array('type'        => 'varchar',
                                                             'size'        => 100,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'pn_auth_module' => array('type'        => 'varchar',
                                                             'size'        => 64,
                                                             'null'        => false,
                                                             'default'     => '')));
    $dbconn->Execute($query);
        
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = pnDBCreateIndex($prefix . '_users',
                             array('name'   => 'pn_uname_index',
                                   'fields' => array('pn_uid'),
                                   'unique' => 'true'));
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    // *_user_data
    $query = pnDBCreateTable($prefix . '_user_data',
                             array('pn_uda_id'     => array('type'        => 'integer',
                                                            'null'        => false,
                                                            'default'     => '0',
                                                            'increment'   => true,
                                                            'primary_key' => true),
                                   'pn_uda_propid' => array('type'        => 'integer',
                                                            'null'        => false,
                                                            'default'     => '0'),
                                   'pn_uda_uid'    => array('type'        => 'integer',
                                                            'null'        => false,
                                                            'default'     => '0'),
                                   'pn_uda_value'  => array('type'        => 'blob',
                                                            'size'        => 'medium',
                                                            'null'        => 'false')));
    $dbconn->Execute($query);
        
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    // *_user_property
    $query = pnDBCreateTable($prefix . '_user_property',
                             array('pn_prop_id'         => array('type'        => 'integer',
                                                                 'null'        => false,
                                                                 'default'     => '0',
                                                                 'increment'   => true,
                                                                 'primary_key' => true),
                                   'pn_prop_label'      => array('type'        => 'varchar',
                                                                 'size'        => 255,
                                                                 'null'        => false,
                                                                 'default'     => ''),
                                   'pn_prop_dtype'      => array('type'        => 'integer',
                                                                 'null'        => false,
                                                                 'default'     => NULL),
                                   'pn_prop_default'    => array('type'        => 'varchar',
                                                                 'size'        => 255,
                                                                 'default'     => NULL),
                                   'pn_prop_validation' => array('type'        => 'varchar',
                                                                 'size'        => 255,
                                                                 'default'     => NULL)));
    $dbconn->Execute($query);
        
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = pnDBCreateIndex($prefix . '_user_property',
                             array('name'   => 'pn_prop_label_index',
                                   'fields' => array('pn_prop_label'),
                                   'unique' => 'true'));
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Set up an initial value for module variables.
    pnModSetVar('users', 'usersperpage', 20);
    pnModSetVar('users', 'showtacs', 0);
    pnModSetVar('users', 'tacs', 0);

    // Register blocks
    pnBlockTypeRegister('users', 'login');
    pnBlockTypeRegister('users', 'online');
    pnBlockTypeRegister('users', 'user');
    
    // Initialisation successful
    return true;
}

/**
 * upgrade the users module from an old version
 */
function users_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch($oldversion) {
        case 1.0:
            // Code to upgrade from version 1.0 goes here
            break;
        case 2.0:
            // Code to upgrade from version 2.0 goes here
            break;
    }

    // Update successful
    return true;
}

/**
 * delete the users module
 */
function users_delete()
{
    // Get datbase setup
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    // Drop the table
    $sql = "DROP TABLE $pntable[users]";
    $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        pnSessionSetVar('errormsg', 'Deletion of user table failed');
        return false;
    }

    // Delete any module variables
    pnModDelVar('users', 'tacs');
    pnModDelVar('users', 'showtacs');
    pnModDelVar('users', 'usersperpage');

    // Deletion successful
    return true;
}

?>
