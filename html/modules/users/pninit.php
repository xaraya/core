<?php 
// File: $Id: s.pninit.php 1.11 02/10/27 12:47:46-05:00 John.Cox@d38yrl11. $
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
    list($dbconn) = pnDBGetConn();
    $tables = pnDBGetTables();

    // Create the table
    // *_users
    $query = pnDBCreateTable($tables['users'],
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
    $index = array('name'      => 'i_pn_users_1',
                   'fields'    => array('pn_uid'),
                   'unique'    => TRUE);

    $query = pnDBCreateIndex($tables['users'],$index);

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    $index = array(
    'name'      => 'i_pn_users_2',
    'fields'    => array('pn_name'),
    'unique'    => true
    );

    $query = pnDBCreateIndex($tables['users'],$index);

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // *_user_data
    $query = pnDBCreateTable($tables['user_data'],
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
    $query = pnDBCreateTable($tables['user_property'],
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

    $query = pnDBCreateIndex($tables['user_property'],
                             array('name'   => 'i_pn_user_property_1',
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

    // Initialisation successful
    return true;
}

function users_activate()
{
    // Set up an initial value for module variables.
    pnModSetVar('users', 'usersperpage', 20);
    pnModSetVar('users', 'showtacs', 0);
    pnModSetVar('users', 'tacs', 0);

    // Register blocks
    pnBlockTypeRegister('users', 'login');
    pnBlockTypeRegister('users', 'online');
    pnBlockTypeRegister('users', 'user');

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
