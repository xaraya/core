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

    // prefix_roles
    /*********************************************************************
	* CREATE TABLE xar_roles (
	*   xar_uid int(11) NOT NULL auto_increment,
	*   xar_name varchar(100) NOT NULL default '',
	*   xar_type int(11) NOT NULL default '0',
	*   xar_users int(11) NOT NULL default '0',
	*   xar_uname varchar(100) NOT NULL default '',
	*   xar_email varchar(100) NOT NULL default '',
	*   xar_pass varchar(100) NOT NULL default '',
	*   xar_date_reg( varchar25) NOT NULL default '',
	*   xar_valcode varchar(35) NOT NULL default '',
	*   xar_state int(3) NOT NULL default '0',
	*   xar_auth_module varchar(100) NOT NULL default '',
	*   PRIMARY KEY  (xar_uid)
	* )
    *********************************************************************/

    $query = xarDBCreateTable($tables['roles'],
             array('xar_uid'  => array('type'       => 'integer',
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

// FIXME: why is the unique index on uname still commented out ?

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
    *   xar_uid int(11) NOT NULL default '0',
    *   xar_parentid int(11) NOT NULL default '0'
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['rolemembers'],
             array('xar_uid'       => array('type'       => 'integer',
                                           'null'        => false,
                                           'default'     => '0'),
                   'xar_parentid'      => array('type'   => 'integer',
                                           'null'        => false,
                                           'default'     => '0')));
    if (!$dbconn->Execute($query)) return;

    $index = array('name'      => 'xar_uid',
                   'fields'    => array('xar_uid'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($tables['rolemembers'],$index);
    if (!$dbconn->Execute($query)) return;

    $index = array('name'      => 'xar_parentid',
                   'fields'    => array('xar_parentid'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($tables['rolemembers'],$index);
    if (!$dbconn->Execute($query)) return;

    // Initialisation successful
    return true;
}

function roles_activate()
{
    // Set up an initial value for module variables.
    xarModSetVar('roles', 'welcomeemail', 'Your account is now active.  Thank you, and welcome to our community.');
    xarModSetVar('roles', 'itemsperpage', 20);
    xarModSetVar('roles', 'showtacs', 0);
    xarModSetVar('roles', 'defaultgroup', 'Users');
    xarModSetVar('roles', 'confirmationtitle', 'Confirmation Email for %%username%%');
    xarModSetVar('roles', 'welcometitle', 'Welcome to %%sitename%%');
    xarModSetVar('roles', 'frozenroles', 5);
    xarModSetVar('privileges', 'frozenprivileges', 7);

    // Unfortunately, crappy format here, and not to PEAR Standardards
    // But I need the line break to come into play without the tab.

$confirmationemail = 'Your account has been created for %%sitename%% and needs to be activated.  You can either do this now, or on the first time that you log in.  If you perfer to do it now, then you will need to follow this link:

%%link%%

Here are the details that were provided.

IP Address of the person creating that account: %%ipaddr%%
User Name:  %%username%%
Password:  %%password%%

Validation Code to activate your account:  %%valcode%%

If you did not create this account, then do nothing.  The account will be deemed inactive after a period of time and deleted from our records.  You will recieve no further emails from us.

Thank you,

%%siteadmin%%';

    xarModSetVar('roles', 'confirmationemail', $confirmationemail);

    $names = 'Admin
Root
Linux';
    $disallowednames = serialize($names);
    xarModSetVar('roles', 'disallowednames', $disallowednames);

    $emails = 'none@none.com
president@whitehouse.gov';
    $disallowedemails = serialize($emails);
    xarModSetVar('roles', 'disallowedemails', $disallowedemails);

    xarModSetVar('roles', 'minage', 13);

    // Register blocks
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'register_block_type',
                       array('modName'  => 'roles',
                             'blockType'=> 'login'))) return;

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'register_block_type',
                       array('modName'  => 'roles',
                             'blockType'=> 'online'))) return;

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'register_block_type',
                       array('modName'  => 'roles',
                             'blockType'=> 'user'))) return;

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'register_block_type',
                       array('modName'  => 'roles',
                             'blockType'=> 'language'))) return;

    // Register Hooks
    if (!xarModRegisterHook('item', 'search', 'GUI',
                           'roles', 'user', 'search')) {
        return false;
    }

    if (!xarModRegisterHook('item', 'usermenu', 'GUI',
                           'roles', 'user', 'usermenu')) {
        return false;
    }

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
 * Delete the roles module
 *
 * @access public
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function roles_delete()
{
    // this module cannot be removed
    return false;

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

    // Delete any module variables
    xarModDelVar('roles', 'tacs');
    xarModDelVar('roles', 'showtacs');
    xarModDelVar('roles', 'itemsperpage');
    xarModDelVar('roles', 'disallowednames');
    xarModDelVar('roles', 'disallowedemails');

    /**
     * Remove instances and masks
     */

    // Remove Masks and Instances
    xarRemoveMasks('roles');
    xarRemoveInstances('roles');

    // Deletion successful
    return true;
}

?>
