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
    // Get database setup
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
    
    $sitePrefix = xarDBGetSiteTablePrefix();
    
    $tables['users']         = $sitePrefix . '_users';
    $tables['user_data']     = $sitePrefix . '_user_data';
    $tables['user_property'] = $sitePrefix . '_user_property';
    $tables['user_status']   = $sitePrefix . '_user_status';
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
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $index = array('name'      => 'i_xar_users_1',
                   'fields'    => array('xar_uid'),
                   'unique'    => TRUE);

    $query = xarDBCreateIndex($tables['users'],$index);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $index = array(
    'name'      => 'i_xar_users_2',
    'fields'    => array('xar_name'),
    'unique'    => true
    );

    $query = xarDBCreateIndex($tables['users'],$index);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

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
    $result =& $dbconn->Execute($query);
    if (!$result) return;

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
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $query = xarDBCreateIndex($tables['user_property'],
                             array('name'   => 'i_xar_user_property_1',
                                   'fields' => array('xar_prop_label'),
                                   'unique' => 'true'));
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $query = xarDBCreateTable($tables['user_status'],
                             array('xar_uid'         => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0',
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'xar_uname'       => array('type'        => 'varchar',
                                                             'size'        => 25,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_date_reg'    => array('type'        => 'varchar',
                                                             'size'        => 25,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_valcode'     => array('type'        => 'varchar',
                                                             'size'        => 35,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_state'       => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0',
                                                             'increment'   => false,
                                                             'primary_key' => false)));

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $index = array('name'      => 'i_xar_users_1',
                   'fields'    => array('xar_uid'),
                   'unique'    => TRUE);

    $query = xarDBCreateIndex($tables['user_status'],$index);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

function users_activate()
{
    // Set up an initial value for module variables.
    xarModSetVar('users', 'welcomeemail', 'Your account is now active.  Thank you, and welcome to our community.');
    xarModSetVar('users', 'usersperpage', 20);
    xarModSetVar('users', 'showtacs', 0);
    xarModSetVar('users', 'defaultgroup', 'Users');
    xarModSetVar('users', 'confirmationtitle', 'Confirmation Email for %%username%%');
    xarModSetVar('users', 'welcometitle', 'Welcome to %%sitename%%');

    // Unfortunately, crappy format here, and not to PEAR Standardards
    // But I need the line break to come into play without the tab.

$confirmationemail = 'Your account has been created for %%sitename%% and needs to be activated.  You can either do this now, or on the first time that you log in.  If you perfer to do it now, then you will need to follow this link:

%%link%%

Here are the details that were provided.

IP Address of the person creating that account: %%ipaddr%%
User Name:  %%username%%
Password:  %%password%%

If you did not create this account, then do nothing.  The account will be deemed inactive after a period of time and deleted from our records.  You will recieve no further emails from us.

Thank you, 

%%siteadmin%%';

    xarModSetVar('users', 'confirmationemail', $confirmationemail);

    $names = 'Admin
Root
Linux';
    $disallowednames = serialize($names);
    xarModSetVar('users', 'disallowednames', $disallowednames);

    $emails = 'none@none.com
president@whitehouse.gov';
    $disallowedemails = serialize($emails);
    xarModSetVar('users', 'disallowedemails', $disallowedemails);

    xarModSetVar('users', 'minage', 13);

    // Register blocks
    xarBlockTypeRegister('users', 'login');
    xarBlockTypeRegister('users', 'online');
    xarBlockTypeRegister('users', 'user');
    xarBlockTypeRegister('users', 'language');

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
    $query = "DROP TABLE $xartable[users]";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Delete any module variables
    xarModDelVar('users', 'tacs');
    xarModDelVar('users', 'showtacs');
    xarModDelVar('users', 'usersperpage');
    xarModDelVar('users', 'disallowednames');
    xarModDelVar('users', 'disallowedemails');

    // Deletion successful
    return true;
}

?>