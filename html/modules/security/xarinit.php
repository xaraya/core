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
 * Initialise the permissions module
 *
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function security_init()
{
 // Get database information
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
	xarDBLoadTableMaintenanceAPI();

    $sitePrefix = xarDBGetSiteTablePrefix();
    $tables['permissions'] = $sitePrefix . '_permissions';
    $tables['permmembers'] = $sitePrefix . '_permmembers';
    $tables['acl'] = $sitePrefix . '_acl';
    $tables['schemas'] = $sitePrefix . '_schemas';

    // Create tables
    /*********************************************************************
     * Here we create all the tables for the participants module
     *
     * prefix_permissions       - holds permissions info
     * prefix_permmembers 		- holds info on permissions group membership
     ********************************************************************/

    // prefix_permissions
    /*********************************************************************
 	* CREATE TABLE xar_permissions (
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

    $query = xarDBCreateTable($tables['permissions'],
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

    // prefix_permmembers
    /*********************************************************************
    * CREATE TABLE xar_permmembers (
    *   xar_pid int(11) NOT NULL default '0',
    *   xar_parentid int(11) NOT NULL default '0',
    *   KEY xar_pid (xar_pid,xar_parentid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['permmembers'],
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

    // prefix_schemas
    /*********************************************************************
    * CREATE TABLE xar_schemas (
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

    $query = xarDBCreateTable($tables['schemas'],
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
    * Enter some default permissions
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    include_once 'modules/security/xarsecurity.php';
    $permissions = new xarPermissions();

    $permissions->register('NoPermissions','All','All','All','All',ACCESS_NONE,'The base permission granting no access');
    $permissions->register('FullPermissions','All','All','All','All',ACCESS_ADMIN,'The base permission granting full access');
    $permissions->register('ReadAll','All','All','All','All',ACCESS_READ,'The base permission granting read access');
    $permissions->register('EditAll','All','All','All','All',ACCESS_EDIT,'The base permission granting edit access');
    $permissions->register('AddAll','All','All','All','All',ACCESS_ADD,'The base permission granting add access');
    $permissions->register('DeleteAll','All','All','All','All',ACCESS_DELETE,'The base permission granting delete access');

    /*********************************************************************
    * Arrange the  permissions in a hierarchy
    * Format is
    * makeEntry(Permission)
    * makeMember(Child,Parent)
    *********************************************************************/

	$permissions->makeEntry('NoPermissions');
	$permissions->makeEntry('FullPermissions');
	//$permissions->makeMember('NoPermissions','FullPermissions');
	$permissions->makeEntry('ReadAll');
	//$permissions->makeMember('NoPermissions','ReadAll');
	$permissions->makeEntry('EditAll');
	//$permissions->makeMember('NoPermissions','EditAll');
	$permissions->makeEntry('AddAll');
	//$permissions->makeMember('NoPermissions','AddAll');
	$permissions->makeEntry('DeleteAll');
	//$permissions->makeMember('NoPermissions','DeleteAll');

    /*********************************************************************
    * Assign the default permissions to groups/users
    * Format is
    * assign(Permission,Participant)
    *********************************************************************/

	$permissions->assign('NoPermissions','Everybody');
	$permissions->assign('FullPermissions','Oversight');
	$permissions->assign('FullPermissions','Overseer');

    /*********************************************************************
    * Register the module components that are permissions objects
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    include_once 'modules/security/xarsecurity.php';
    $schemas = new xarSchemas();

    $schemas->register('Gateway','All','Security','All','All',ACCESS_READ);
    $schemas->register('ModPermsAll','All','Security','ModifyPermission','All',ACCESS_EDIT);
    $schemas->register('AddPermsAll','All','Security','AddPermission','All',ACCESS_ADD);
    $schemas->register('DelPermsAll','All','Security','DelPermission','All',ACCESS_DELETE);

    $schemas->register('AssignPermsAll','All','Security','AssignPermission','All',ACCESS_ADD);
    $schemas->register('RemovePermsAll','All','Security','RemovePermission','All',ACCESS_DELETE);

    $schemas->register('Gateway','All','Participants','All','All',ACCESS_READ);
   	$schemas->register('ModMember','All','Participants','ModifyMember','All',ACCESS_EDIT);
    $schemas->register('AddMemberAll','All','Participants','AddMember','All',ACCESS_ADD);
    $schemas->register('DelMemberAll','All','Participants','DelMember','All',ACCESS_DELETE);

    // Initialisation successful
    return true;
}

function security_activate()
{
    return true;
}
/**
 * Upgrade the participants module from an old version
 *
 * @param oldVersion the old version to upgrade from
 * @returns bool
 */
function security_upgrade($oldVersion)
{
    return false;
}

/**
 * Delete the participants module
 *
 * @param none
 * @returns boolean
 */
function security_delete()
{
    /*********************************************************************
    * Drop the tables
    *********************************************************************/

 // Get database information
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
	xarDBLoadTableMaintenanceAPI();

    $query = xarDBDropTable($tables['permissions']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['permmembers']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['acl']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['schemas']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    return true;
}

?>