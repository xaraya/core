<?php
// File: $Id: s.xarinit.php 1.11 02/10/27 12:47:46-05:00 John.Cox@d38yrl11. $
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file:  Initialisation functions for users
// ----------------------------------------------------------------------

/**
 * Initialise the users module
 *
 * @access public
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function users_init()
{
    // Get datbase setup
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
    
    $sitePrefix = xarDBGetSiteTablePrefix();
    
    $tables['users']         = $sitePrefix . '_users';
    $tables['user_data']     = $sitePrefix . '_user_data';
    $tables['user_property'] = $sitePrefix . '_user_property';
    // Create the table
    // *_users
    $query = xarDBCreateTable($tables['users'],
                             array('xar_uid'         => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0',
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'xar_name'        => array('type'        => 'varchar',
                                                             'size'        => 60,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_uname'       => array('type'        => 'varchar',
                                                             'size'        => 25,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_email'       => array('type'        => 'varchar',
                                                             'size'        => 100,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_pass'        => array('type'        => 'varchar',
                                                             'size'        => 40,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_url'         => array('type'        => 'varchar',
                                                             'size'        => 100,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_auth_module' => array('type'        => 'varchar',
                                                             'size'        => 64,
                                                             'null'        => false,
                                                             'default'     => '')));
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    $index = array('name'      => 'i_xar_users_1',
                   'fields'    => array('xar_uid'),
                   'unique'    => TRUE);

    $query = xarDBCreateIndex($tables['users'],$index);

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    $index = array(
    'name'      => 'i_xar_users_2',
    'fields'    => array('xar_name'),
    'unique'    => true
    );

    $query = xarDBCreateIndex($tables['users'],$index);

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // *_user_data
    $query = xarDBCreateTable($tables['user_data'],
                             array('xar_uda_id'     => array('type'        => 'integer',
                                                            'null'        => false,
                                                            'default'     => '0',
                                                            'increment'   => true,
                                                            'primary_key' => true),
                                   'xar_uda_propid' => array('type'        => 'integer',
                                                            'null'        => false,
                                                            'default'     => '0'),
                                   'xar_uda_uid'    => array('type'        => 'integer',
                                                            'null'        => false,
                                                            'default'     => '0'),
                                   'xar_uda_value'  => array('type'        => 'blob',
                                                            'size'        => 'medium',
                                                            'null'        => 'false')));
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // *_user_property
    $query = xarDBCreateTable($tables['user_property'],
                             array('xar_prop_id'         => array('type'        => 'integer',
                                                                 'null'        => false,
                                                                 'default'     => '0',
                                                                 'increment'   => true,
                                                                 'primary_key' => true),
                                   'xar_prop_label'      => array('type'        => 'varchar',
                                                                 'size'        => 255,
                                                                 'null'        => false,
                                                                 'default'     => ''),
                                   'xar_prop_dtype'      => array('type'        => 'integer',
                                                                 'null'        => false,
                                                                 'default'     => NULL),
                                   'xar_prop_default'    => array('type'        => 'varchar',
                                                                 'size'        => 255,
                                                                 'default'     => NULL),
                                   'xar_prop_validation' => array('type'        => 'varchar',
                                                                 'size'        => 255,
                                                                 'default'     => NULL)));
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $query = xarDBCreateIndex($tables['user_property'],
                             array('name'   => 'i_xar_user_property_1',
                                   'fields' => array('xar_prop_label'),
                                   'unique' => 'true'));
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Initialisation successful
    return true;
}

function users_activate()
{
    // Set up an initial value for module variables.
    xarModSetVar('users', 'usersperpage', 20);
    xarModSetVar('users', 'showtacs', 0);
    xarModSetVar('users', 'tacs', 0);

    // Register blocks
    xarBlockTypeRegister('users', 'login');
    xarBlockTypeRegister('users', 'online');
    xarBlockTypeRegister('users', 'user');

    return true;
}
/**
 * Upgrade the users module from an old version
 *
 * @access public
 * @param oldVersion
 * @returns bool
 * @raise DATABASE_ERROR
 */
function users_upgrade($oldVersion)
{
    // Upgrade dependent on old version number
    switch($oldVersion) {
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
 * Delete the users module
 *
 * @access public
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function users_delete()
{
    // Get datbase setup
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // Drop the table
    $sql = "DROP TABLE $xartable[users]";
    $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        xarSessionSetVar('errormsg', 'Deletion of user table failed');
        return false;
    }

    // Delete any module variables
    xarModDelVar('users', 'tacs');
    xarModDelVar('users', 'showtacs');
    xarModDelVar('users', 'usersperpage');

    // Deletion successful
    return true;
}

?>
