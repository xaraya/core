<?php
/**
 * File: $Id$
 *
 * Purpose of file:  Default setup for roles and privileges
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
*/

function initializeSetup() {

    /*********************************************************************
    * Enter some default groups and users
    *********************************************************************/

    makeGroup('Everybody');
    makeGroup('Administrators');
	makeUser('Admin','admin','admin@xaraya.com','xaraya');
    makeGroup('Oversight');
	makeUser('Overseer','overseer','overseer@xaraya.com');
    makeGroup('Users');
	makeUser('User','user','user@xaraya.com');
	makeUser('Anonymous','anonymous','anonymous@xaraya.com');
//	makeUser('Current','current','current@xaraya.com');

    /*********************************************************************
    * Arrange the roles in a hierarchy
    * Format is
    * makeMember(Child,Parent)
    *********************************************************************/

	makeRoleRoot('Everybody');
	makeRoleMember('Administrators','Everybody');
	makeRoleMember('Admin','Administrators');
	makeRoleMember('Oversight','Everybody');
	makeRoleMember('Overseer','Oversight');
	makeRoleMember('Users','Everybody');
	makeRoleMember('User','Users');
	makeRoleMember('Anonymous','Everybody');
//	makeRoleMember('Current','Everybody');

    /*********************************************************************
    * Enter some default privileges
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    registerPrivilege('NoPrivileges','All','All','All','All',ACCESS_NONE,'The base privilege granting no access');
    registerPrivilege('Administration','All','All','All','All',ACCESS_ADMIN,'The base privilege granting full access');
    registerPrivilege('ReadAll','All','All','All','All',ACCESS_READ,'The base privilege granting read access');
    registerPrivilege('EditAll','All','All','All','All',ACCESS_EDIT,'The base privilege granting edit access');
    registerPrivilege('AddAll','All','All','All','All',ACCESS_ADD,'The base privilege granting add access');
    registerPrivilege('DeleteAll','All','All','All','All',ACCESS_DELETE,'The base privilege granting delete access');
    registerPrivilege('ModPrivilege','All','Privileges','All','All',ACCESS_EDIT,'');
    registerPrivilege('AddPrivilege','All','Privileges','All','All',ACCESS_ADD,'');
    registerPrivilege('DelPrivilege','All','Privileges','All','All',ACCESS_DELETE,'');
    registerPrivilege('AdminPrivilege','All','Privileges','All','All',ACCESS_ADMIN,'A special privilege granting admin access to Privileges for Anonymous');
    registerPrivilege('AdminRole','All','Roles','All','All',ACCESS_ADMIN,'A special privilege granting admin access to Roles for Anonymous');

    /*********************************************************************
    * Arrange the  privileges in a hierarchy
    * Format is
    * makeEntry(Privilege)
    * makeMember(Child,Parent)
    *********************************************************************/

	makePrivilegeRoot('NoPrivileges');
	makePrivilegeRoot('Administration');
	//makePrivilegeMember('NoPrivileges','FullPrivileges');
	makePrivilegeRoot('ReadAll');
	//makePrivilegeMember('NoPrivileges','ReadAll');
	makePrivilegeRoot('EditAll');
	//makePrivilegeMember('NoPrivileges','EditAll');
	makePrivilegeRoot('AddAll');
	//makePrivilegeMember('NoPrivileges','AddAll');
	makePrivilegeRoot('DeleteAll');
	//makePrivilegeMember('NoPrivileges','DeleteAll');
	makePrivilegeRoot('AdminPrivilege');
	makePrivilegeRoot('AdminRole');

    /*********************************************************************
    * Assign the default privileges to groups/users
    * Format is
    * assign(Privilege,Role)
    *********************************************************************/

	assignPrivilege('NoPrivileges','Everybody');
	assignPrivilege('FullPrivileges','Administrators');
	assignPrivilege('AdminPrivilege','Anonymous');
	assignPrivilege('AdminRole','Anonymous');

    /*********************************************************************
    * Define instances for some modules
    * Format is
    * setInstance(Module,ModuleTable,IDField,NameField,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
    *********************************************************************/

    defineInstance('roles','xar_roles','xar_pid','xar_name',0,'xar_rolemembers','xar_pid','xar_parentid','Instances of the roles module, including multilevel nesting');
    defineInstance('privileges','xar_privileges','xar_pid','xar_name',0,'xar_privmembers','xar_pid','xar_parentid','Instances of the privileges module, including multilevel nesting');

    defineInstance('categories','xar_categories','xar_cid','xar_name',0,'xar_categories','xar_cid','xar_parent','Instances of the categories module, including multilevel nesting');
    defineInstance('articles','xar_articles','xar_aid','xar_title',0);
    defineInstance('xproject','xar_xproject','xar_projectid','xar_name',0);


    /*********************************************************************
    * Register the module components that are privileges objects
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    xarRegisterMask('PrivilegesGateway','All','Privileges','All','All',ACCESS_READ);
    xarRegisterMask('ViewPrivileges','All','Privileges','ViewPrivileges','All',ACCESS_READ);
    xarRegisterMask('EditPrivilege','All','Privileges','EditPrivilege','All',ACCESS_EDIT);
    xarRegisterMask('AddPrivilege','All','Privileges','AddPrivilege','All',ACCESS_ADD);
    xarRegisterMask('DeletePrivilege','All','Privileges','DeletePrivilege','All',ACCESS_DELETE);
    xarRegisterMask('ViewPrivilegeRoles','All','Privileges','ViewRoles','All',ACCESS_READ);
    xarRegisterMask('RemoveRole','All','Privileges','RemoveRole','All',ACCESS_DELETE);

    xarRegisterMask('AssignPrivAll','All','Privileges','AssignPrivilege','All',ACCESS_ADD);
    xarRegisterMask('RemovePrivAll','All','Privileges','RemovePrivilege','All',ACCESS_DELETE);

    xarRegisterMask('RolesGateway','All','Roles','All','All',ACCESS_READ);
   	xarRegisterMask('ViewRoles','All','Roles','ViewRoles','All',ACCESS_READ);
   	xarRegisterMask('ModMemberAll','All','Roles','EditMember','All',ACCESS_EDIT);
    xarRegisterMask('AddMemberAll','All','Roles','AddMember','All',ACCESS_ADD);
    xarRegisterMask('DelMemberAll','All','Roles','DeleteMember','All',ACCESS_DELETE);

//	'Mask to limit access to the installer to Oversight'
	xarRegisterMask('Admin','All','installer','All','All',ACCESS_ADMIN);

   	xarRegisterMask('Admin','All','modules','All','All',ACCESS_ADMIN);

    // Initialisation successful
    return true;
}
