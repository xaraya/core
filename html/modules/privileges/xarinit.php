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
    if(!xarModIsAvailable('roles')) {
        $msg=xarML('The roles module should be activated first');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,'MODULE_DEPENDENCY',
                        new SystemException($msg));
        return;
    }
 // Get database information
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
	xarDBLoadTableMaintenanceAPI();

    $sitePrefix = xarDBGetSiteTablePrefix();
    $tables['privileges'] = $sitePrefix . '_privileges';
    $tables['privmembers'] = $sitePrefix . '_privmembers';
    $tables['acl'] = $sitePrefix . '_acl';
    $tables['masks'] = $sitePrefix . '_masks';
    $tables['instances'] = $sitePrefix . '_instances';

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
    *   xar_instancetable1 varchar(100) NOT NULL default '',
    *   xar_instancevaluefield1 varchar(100) NOT NULL default '',
    *   xar_instancedisplayfield1 varchar(100) NOT NULL default '',
    *   xar_instanceapplication int(11) NOT NULL default '0',
    *   xar_instancetable2 varchar(100) NOT NULL default '',
    *   xar_instancevaluefield2 varchar(100) NOT NULL default '',
    *   xar_instancedisplayfield2 varchar(100) NOT NULL default '',
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

    // prefix_instances
    /*********************************************************************
    * CREATE TABLE xar_instances (
    *   xar_iid int(11) NOT NULL default '0',
    *   xar_name varchar(100) NOT NULL default '',
    *   xar_module varchar(100) NOT NULL default '',
    *   xar_instancetable1 varchar(100) NOT NULL default '',
    *   xar_instancevaluefield1 varchar(100) NOT NULL default '',
    *   xar_instancedisplayfield1 varchar(100) NOT NULL default '',
    *   xar_instanceapplication int(11) NOT NULL default '0',
    *   xar_instancetable2 varchar(100) NOT NULL default '',
    *   xar_instancevaluefield2 varchar(100) NOT NULL default '',
    *   xar_instancedisplayfield2 varchar(100) NOT NULL default '',
    *   xar_description varchar(255) NOT NULL default '',
    *   PRIMARY KEY  (xar_sid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['instances'],
             array('xar_iid'  => array('type'       => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_module' => array('type'     => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instancetable1' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instancevaluefield' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instancedisplayfield' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instanceapplication' => array('type'      => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_instancetable2' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instancechildid' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instanceparentid' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
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
    $privileges->register('ModPrivilege','All','Privileges','All','All',ACCESS_EDIT,'');
    $privileges->register('AddPrivilege','All','Privileges','All','All',ACCESS_ADD,'');
    $privileges->register('DelPrivilege','All','Privileges','All','All',ACCESS_DELETE,'');
    $privileges->register('AdminPrivilege','All','Privileges','All','All',ACCESS_ADMIN,'A special privilege granting admin access to Privileges for Anonymous');
    $privileges->register('AdminRole','All','Roles','All','All',ACCESS_ADMIN,'A special privilege granting admin access to Roles for Anonymous');

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
	$privileges->makeEntry('AdminPrivilege');
	$privileges->makeEntry('AdminRole');

    /*********************************************************************
    * Assign the default privileges to groups/users
    * Format is
    * assign(Privilege,Role)
    *********************************************************************/

	$privileges->assign('NoPrivileges','Everybody');
	$privileges->assign('FullPrivileges','Oversight');
	$privileges->assign('AdminPrivilege','Anonymous');
	$privileges->assign('AdminRole','Anonymous');

    /*********************************************************************
    * Define instances for some modules
    * Format is
    * setInstance(Module,ModuleTable,IDField,NameField,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
    *********************************************************************/

    $privileges->setInstance('roles','xar_roles','xar_pid','xar_name',0,'xar_rolemembers','xar_pid','xar_parentid','Instances of the roles module, including multilevel nesting');
    $privileges->setInstance('privileges','xar_privileges','xar_pid','xar_name',0,'xar_privmembers','xar_pid','xar_parentid','Instances of the privileges module, including multilevel nesting');

    $privileges->setInstance('categories','xar_categories','xar_cid','xar_name',0,'xar_categories','xar_cid','xar_parent','Instances of the categories module, including multilevel nesting');
    $privileges->setInstance('articles','xar_articles','xar_aid','xar_title',0);


    /*********************************************************************
    * Register the module components that are privileges objects
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    include_once 'modules/privileges/xarprivileges.php';
    $masks = new xarMasks();

    $masks->register('PrivilegesGateway','All','Privileges','All','All',ACCESS_READ);
    $masks->register('EditPrivilege','All','Privileges','EditPrivilege','All',ACCESS_EDIT);
    $masks->register('AddPrivilege','All','Privileges','AddPrivilege','All',ACCESS_ADD);
    $masks->register('DeletePrivilege','All','Privileges','DeletePrivilege','All',ACCESS_DELETE);
    $masks->register('ViewPrivilegeRoles','All','Privileges','ViewRoles','All',ACCESS_READ);
    $masks->register('RemoveRole','All','Privileges','RemoveRole','All',ACCESS_DELETE);

    $masks->register('AssignPrivAll','All','Privileges','AssignPrivilege','All',ACCESS_ADD);
    $masks->register('RemovePrivAll','All','Privileges','RemovePrivilege','All',ACCESS_DELETE);

    $masks->register('RolesGateway','All','Roles','All','All',ACCESS_READ);
   	$masks->register('ViewRoles','All','Roles','ViewRoles','All',ACCESS_READ);
   	$masks->register('ModMemberAll','All','Roles','EditMember','All',ACCESS_EDIT);
    $masks->register('AddMemberAll','All','Roles','AddMember','All',ACCESS_ADD);
    $masks->register('DelMemberAll','All','Roles','DeleteMember','All',ACCESS_DELETE);

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

    $query = xarDBDropTable($tables['instances']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    return true;
}

?>