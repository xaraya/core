<?php
/**
 * File: $Id: s.xarinit.php 1.27 03/01/17 15:18:04-08:00 rcave@lxwdev-1.schwabfoundation.org $
 *
 * Short description of purpose of file
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Roles Module
 * @author Jan Schrage, John Cox, Gregor Rothfuss
 * @todo need the dynamic users menu
 * @todo needs dyanamic data interface
 * @todo ensure all mod vars are set
*/

/**
 * Initialise the users module
 *
 * @access public
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function roles_init()
{
    // Get database setup
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $sitePrefix = xarDBGetSiteTablePrefix();
    $tables['roles'] = $sitePrefix . '_roles';
    $tables['rolemembers'] = $sitePrefix . '_rolemembers';
    $tables['user_data']     = $sitePrefix . '_user_data';
    $tables['user_property'] = $sitePrefix . '_user_property';

    // prefix_roles
    /*********************************************************************
	* CREATE TABLE xar_roles (
	*   xar_pid int(11) NOT NULL auto_increment,
	*   xar_name varchar(100) NOT NULL default '',
	*   xar_type int(11) NOT NULL default '0',
	*   xar_users int(11) NOT NULL default '0',
	*   xar_uname varchar(100) NOT NULL default '',
	*   xar_email varchar(100) NOT NULL default '',
	*   xar_pass varchar(100) NOT NULL default '',
	*   xar_url varchar(100) NOT NULL default '',
	*   xar_auth_module varchar(100) NOT NULL default '',
	*   PRIMARY KEY  (xar_pid)
	* )
    *********************************************************************/

    $query = xarDBCreateTable($tables['roles'],
             array('xar_pid'  => array('type'       => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_name' => array('type'       => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_type' => array('type'       => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_users' => array('type'      => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_uname' => array('type'      => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_email' => array('type'      => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_pass' => array('type'        => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_url' => array('type'        => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_date_reg' => array('type'        => 'varchar',
                                      'size'        => 25,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_valcode' => array('type'        => 'varchar',
                                      'size'        => 35,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_state' => array('type'      => 'integer',
                                      'null'        => false,
                                      'default'     => '3'),
                   'xar_auth_module' => array('type'        => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => '')));

    if (!$dbconn->Execute($query)) return;

/*    $index = array(
    'name'      => 'i_xar_roles_1',
    'fields'    => array('xar_uname'),
    'unique'    => true
    );

    $query = xarDBCreateIndex($tables['roles'],$index);

    $result =& $dbconn->Execute($query);
    if (!$result) return;
*/

    // prefix_rolemembers
    /*********************************************************************
    * CREATE TABLE xar_rolemembers (
    *   xar_pid int(11) NOT NULL default '0',
    *   xar_parentid int(11) NOT NULL default '0'
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['rolemembers'],
             array('xar_pid'       => array('type'       => 'integer',
                                           'null'        => false,
                                           'default'     => '0'),
                   'xar_parentid'      => array('type'   => 'integer',
                                           'null'        => false,
                                           'default'     => '0')));
    if (!$dbconn->Execute($query)) return;

    /*********************************************************************
    * Enter some default groups and users
    *********************************************************************/

	$query = "INSERT INTO xar_roles (xar_pid, xar_name, xar_type)
			VALUES (1, 'Everybody', 1)";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_roles (xar_pid, xar_name, xar_type, xar_uname, xar_email)
			VALUES (2, 'Current', 0, 'current', 'current@xaraya.com')";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_roles (xar_pid, xar_name, xar_type)
			VALUES (3, 'Oversight', 1)";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_roles (xar_pid, xar_name, xar_type, xar_uname, xar_email, xar_pass)
			VALUES (4, 'Overseer', 0, 'overseer', 'overseer@xaraya.com', md5('xaraya'))";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_roles (xar_pid, xar_name, xar_type)
			VALUES (5, 'Admins', 1)";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_roles (xar_pid, xar_name, xar_type)
			VALUES (6, 'Users', 1)";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_roles (xar_pid, xar_name, xar_type, xar_uname, xar_email)
			VALUES (7, 'User', 0, 'user', 'user@xaraya.com')";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_roles (xar_pid, xar_name, xar_type, xar_uname, xar_email)
			VALUES (8, 'Anonymous', 0, 'anonymous', 'anonymous@xaraya.com')";
	if (!$dbconn->Execute($query)) return;

    /*********************************************************************
    * Arrange the roles in a hierarchy
    * Format is
    * makeMember(Child,Parent)
    *********************************************************************/

	$query = "INSERT INTO xar_rolemembers VALUES (1,0)";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_rolemembers VALUES (2,1)";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_rolemembers VALUES (3,2)";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_rolemembers VALUES (4,3)";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_rolemembers VALUES (5,1)";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_rolemembers VALUES (6,5)";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_rolemembers VALUES (7,1)";
	if (!$dbconn->Execute($query)) return;
	$query = "INSERT INTO xar_rolemembers VALUES (8,1)";
	if (!$dbconn->Execute($query)) return;

    /*********************************************************************
    * prefix_user_data
    *********************************************************************/
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

    /*********************************************************************
    * prefix_user_property
    *********************************************************************/
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

    // Initialisation successful
    return true;
}

/**
 * Activate the roles module
 *
 * @access public
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function roles_activate()
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

    if (!xarModRegisterHook('item', 'search', 'GUI',
                           'users', 'user', 'search')) {
        return false;
    }

    if (!xarModRegisterHook('item', 'usermenu', 'GUI',
                           'users', 'user', 'usermenu')) {
        return false;
    }

    return true;
}
/**
 * Upgrade the roles module from an old version
 *
 * @access public
 * @param oldVersion
 * @returns bool
 * @raise DATABASE_ERROR
 */
function roless_upgrade($oldVersion)
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
function roles_delete()
{
    /*********************************************************************
    * Drop the tables
    *********************************************************************/

    // Get database information
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $query = xarDBDropTable($tables['roles']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['rolemembers']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['user_data']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['user_property']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

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