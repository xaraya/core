<?php
/**
 * File: $Id$
 *
 * Purpose of file:  Initialisation functions for the security module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Security Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
*/

// Load Table Maintainance API

/**
 * Initialise the privileges module
 *
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function privileges_init()
{
 // Get database information
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
	xarDBLoadTableMaintenanceAPI();

    $sitePrefix = xarDBGetSiteTablePrefix();
    $tables['privileges'] = $sitePrefix . '_privileges';
    $tables['privmembers'] = $sitePrefix . '_privmembers';
    $tables['acl'] = $sitePrefix . '_acl';
    $tables['masks'] = $sitePrefix . '_masks';

    // Create tables
    /*********************************************************************
     * Here we create all the tables for the privileges module
     *
     * prefix_privileges       - holds privileges info
     * prefix_privmembers 	   - holds info on privileges group membership
     ********************************************************************/

    // prefix_privileges
    /*********************************************************************
 	* CREATE TABLE xar_privileges (
 	*   xar_pid int(11) NOT NULL auto_increment,
 	*   xar_name varchar(100) NOT NULL default '',
 	*   xar_realm varchar(100) NOT NULL default '',
 	*   xar_module varchar(100) NOT NULL default '',
 	*   xar_component varchar(100) NOT NULL default '',
 	*   xar_instance varchar(100) NOT NULL default '',
 	*   xar_level int(11) NOT NULL default '0',
 	*   xar_description varchar(255) NOT NULL default '',
 	*   PRIMARY KEY  (xar_pid)
 	* )
    *********************************************************************/

    $query = xarDBCreateTable($tables['privileges'],
             array('xar_pid'  => array('type'       => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_name' => array('type'       => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_realm' => array('type'      => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_module' => array('type'     => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_component' => array('type'  => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instance' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_level' => array('type'      => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_description' => array('type'=> 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => '')));

   if (!$dbconn->Execute($query)) return;

    // prefix_privmembers
    /*********************************************************************
    * CREATE TABLE xar_privmembers (
    *   xar_pid int(11) NOT NULL default '0',
    *   xar_parentid int(11) NOT NULL default '0',
    *   KEY xar_pid (xar_pid,xar_parentid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['privmembers'],
             array('xar_pid'       => array('type'       => 'integer',
                                           'null'        => false,
                                           'default'     => '0',
                                           'key'         => true),
                   'xar_parentid'      => array('type'   => 'integer',
                                           'null'        => false,
                                           'default'     => '0',
                                           'key'         => true)));
    if (!$dbconn->Execute($query)) return;

    // prefix_acl
    /*********************************************************************
    * CREATE TABLE xar_acl (
    *   xar_partmember int(11) NOT NULL default '0',
    *   xar_permmember int(11) NOT NULL default '0',
    *   KEY xar_pid (xar_pid,xar_parentid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['acl'],
             array('xar_partid'       => array('type'  => 'integer',
                                           'null'        => false,
                                           'default'     => '0',
                                           'key'         => true),
                   'xar_permid'      => array('type'   => 'integer',
                                           'null'        => false,
                                           'default'     => '0',
                                           'key'         => true)));
    if (!$dbconn->Execute($query)) return;

    // prefix_masks
    /*********************************************************************
    * CREATE TABLE xar_masks (
    *   xar_sid int(11) NOT NULL default '0',
    *   xar_name varchar(100) NOT NULL default '',
    *   xar_realm varchar(100) NOT NULL default '',
    *   xar_module varchar(100) NOT NULL default '',
    *   xar_component varchar(100) NOT NULL default '',
    *   xar_instance varchar(100) NOT NULL default '',
    *   xar_level int(11) NOT NULL default '0',
    *   xar_description varchar(255) NOT NULL default '',
    *   PRIMARY KEY  (xar_sid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['masks'],
             array('xar_sid'  => array('type'       => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_name' => array('type'       => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_realm' => array('type'      => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_module' => array('type'     => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_component' => array('type'  => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instance' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_level' => array('type'      => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_description' => array('type'=> 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => '')));

    if (!$dbconn->Execute($query)) return;

    /*********************************************************************
    * Enter some default privileges
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    include_once 'modules/privileges/xarprivileges.php';
    $privileges = new xarPrivileges();

    $privileges->register('NoPrivileges','All','All','All','All',ACCESS_NONE,'The base privilege granting no access');
    $privileges->register('FullPrivileges','All','All','All','All',ACCESS_ADMIN,'The base privilege granting full access');
    $privileges->register('ReadAll','All','All','All','All',ACCESS_READ,'The base privilege granting read access');
    $privileges->register('EditAll','All','All','All','All',ACCESS_EDIT,'The base privilege granting edit access');
    $privileges->register('AddAll','All','All','All','All',ACCESS_ADD,'The base privilege granting add access');
    $privileges->register('DeleteAll','All','All','All','All',ACCESS_DELETE,'The base privilege granting delete access');

    /*********************************************************************
    * Arrange the  privileges in a hierarchy
    * Format is
    * makeEntry(Privilege)
    * makeMember(Child,Parent)
    *********************************************************************/

	$privileges->makeEntry('NoPrivileges');
	$privileges->makeEntry('FullPrivileges');
	//$privileges->makeMember('NoPrivileges','FullPrivileges');
	$privileges->makeEntry('ReadAll');
	//$privileges->makeMember('NoPrivileges','ReadAll');
	$privileges->makeEntry('EditAll');
	//$privileges->makeMember('NoPrivileges','EditAll');
	$privileges->makeEntry('AddAll');
	//$privileges->makeMember('NoPrivileges','AddAll');
	$privileges->makeEntry('DeleteAll');
	//$privileges->makeMember('NoPrivileges','DeleteAll');

    /*********************************************************************
    * Assign the default privileges to groups/users
    * Format is
    * assign(Privilege,Role)
    *********************************************************************/

	$privileges->assign('NoPrivileges','Everybody');
	$privileges->assign('FullPrivileges','Oversight');
	$privileges->assign('FullPrivileges','Overseer');

    /*********************************************************************
    * Register the module components that are privileges objects
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    include_once 'modules/privileges/xarprivileges.php';
    $masks = new xarMasks();

    $masks->register('Gateway','All','Privileges','All','All',ACCESS_READ);
    $masks->register('ModPrivAll','All','Privileges','ModifyPrivilege','All',ACCESS_EDIT);
    $masks->register('AddPrivAll','All','Privileges','AddPrivilege','All',ACCESS_ADD);
    $masks->register('DelPrivAll','All','Privileges','DelPrivilege','All',ACCESS_DELETE);

    $masks->register('AssignPrivAll','All','Privileges','AssignPrivilege','All',ACCESS_ADD);
    $masks->register('RemovePrivAll','All','Privileges','RemovePrivilege','All',ACCESS_DELETE);

    $masks->register('Gateway','All','Roles','All','All',ACCESS_READ);
   	$masks->register('ModMember','All','Roles','ModifyMember','All',ACCESS_EDIT);
    $masks->register('AddMemberAll','All','Roles','AddMember','All',ACCESS_ADD);
    $masks->register('DelMemberAll','All','Roles','DelMember','All',ACCESS_DELETE);

    // Initialisation successful
    return true;
}

function privileges_activate()
{
    return true;
}
/**
 * Upgrade the roles module from an old version
 *
 * @param oldVersion the old version to upgrade from
 * @returns bool
 */
function privileges_upgrade($oldVersion)
{
    return false;
}

/**
 * Delete the roles module
 *
 * @param none
 * @returns boolean
 */
function privileges_delete()
{
    /*********************************************************************
    * Drop the tables
    *********************************************************************/

 // Get database information
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
	xarDBLoadTableMaintenanceAPI();

    $query = xarDBDropTable($tables['privileges']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['privmembers']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['acl']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['masks']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    return true;
}

?>