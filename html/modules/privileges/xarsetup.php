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

    xarMakeGroup('Everybody');
	xarMakeUser('Anonymous','anonymous','anonymous@xaraya.com');
	xarMakeUser('Admin','admin','admin@xaraya.com','xaraya');
    xarMakeGroup('Administrators');
    xarMakeGroup('Oversight');
	xarMakeUser('Overseer','overseer','overseer@xaraya.com');
    xarMakeGroup('Users');
	xarMakeUser('User','user','user@xaraya.com');
//	xarMakeUser('Current','current','current@xaraya.com');

    /*********************************************************************
    * Arrange the roles in a hierarchy
    * Format is
    * makeMember(Child,Parent)
    *********************************************************************/

	xarMakeRoleRoot('Everybody');
	xarMakeRoleMember('Administrators','Everybody');
	xarMakeRoleMember('Admin','Administrators');
	xarMakeRoleMember('Oversight','Everybody');
	xarMakeRoleMember('Overseer','Oversight');
	xarMakeRoleMember('Users','Everybody');
	xarMakeRoleMember('User','Users');
	xarMakeRoleMember('Anonymous','Everybody');
//	xarMakeRoleMember('Current','Everybody');

    /*********************************************************************
    * Enter some default privileges
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    xarRegisterPrivilege('NoAccess','All','All','All','All',ACCESS_NONE,'The base privilege granting no access');
    xarRegisterPrivilege('Administration','All','All','All','All',ACCESS_ADMIN,'The base privilege granting full access');
    xarRegisterPrivilege('Oversight','All','empty','All','All',ACCESS_NONE,'The privileges for the Obersight group');
    xarRegisterPrivilege('DenyRoles','All','Roles','All','All',ACCESS_NONE,'Exclude access to the Roles module');
    xarRegisterPrivilege('DenyPrivileges','All','Privileges','All','All',ACCESS_NONE,'Exclude access to the Privileges modules');
    xarRegisterPrivilege('DenyRolesPrivileges','All','empty','All','All',ACCESS_NONE,'Exclude access to the Roles and Privileges modules');
    xarRegisterPrivilege('Editing','All','All','All','All',ACCESS_EDIT,'The base privilege granting edit access');
    xarRegisterPrivilege('Reading','All','All','All','All',ACCESS_READ,'The base privilege granting read access');

    xarRegisterPrivilege('ViewLogin','All','All','Loginblock','All',ACCESS_OVERVIEW,'A privilege for the Anonymous user');
    xarRegisterPrivilege('ViewBlocks','All','base','Block','All',ACCESS_OVERVIEW,'A privilege for the Anonymous user');
    xarRegisterPrivilege('AnonView','All','themes','metablock','All',ACCESS_OVERVIEW,'The base privilege for the Anonymous user');
//    xarRegisterPrivilege('AddAll','All','All','All','All',ACCESS_ADD,'The base privilege granting add access');
//    xarRegisterPrivilege('DeleteAll','All','All','All','All',ACCESS_DELETE,'The base privilege granting delete access');
    xarRegisterPrivilege('ModPrivilege','All','Privileges','All','All',ACCESS_EDIT,'');
    xarRegisterPrivilege('AddPrivilege','All','Privileges','All','All',ACCESS_ADD,'');
    xarRegisterPrivilege('DelPrivilege','All','Privileges','All','All',ACCESS_DELETE,'');
//    xarRegisterPrivilege('AdminPrivilege','All','Privileges','All','All',ACCESS_ADMIN,'A special privilege granting admin access to Privileges for Anonymous');
//    xarRegisterPrivilege('AdminRole','All','Roles','All','All',ACCESS_ADMIN,'A special privilege granting admin access to Roles for Anonymous');

    /*********************************************************************
    * Arrange the  privileges in a hierarchy
    * Format is
    * makeEntry(Privilege)
    * makeMember(Child,Parent)
    *********************************************************************/

	xarMakePrivilegeRoot('NoAccess');
	xarMakePrivilegeRoot('Administration');
	xarMakePrivilegeRoot('Oversight');
	xarMakePrivilegeRoot('DenyRolesPrivileges');
	xarMakePrivilegeRoot('DenyRoles');
	xarMakePrivilegeRoot('DenyPrivileges');
	xarMakePrivilegeRoot('Editing');
	xarMakePrivilegeRoot('Reading');
	xarMakePrivilegeRoot('AnonView');
	xarMakePrivilegeRoot('ViewLogin');
	xarMakePrivilegeRoot('ViewBlocks');
	xarMakePrivilegeMember('DenyRoles','DenyRolesPrivileges');
	xarMakePrivilegeMember('DenyPrivileges','DenyRolesPrivileges');
	xarMakePrivilegeMember('DenyRolesPrivileges','Oversight');
	xarMakePrivilegeMember('Administration','Oversight');
	xarMakePrivilegeMember('DenyRolesPrivileges','Editing');
	xarMakePrivilegeMember('DenyRolesPrivileges','Reading');
	xarMakePrivilegeMember('ViewLogin','AnonView');
	xarMakePrivilegeMember('ViewBlocks','AnonView');

    /*********************************************************************
    * Assign the default privileges to groups/users
    * Format is
    * assign(Privilege,Role)
    *********************************************************************/

	xarAssignPrivilege('NoAccess','Everybody');
	xarAssignPrivilege('Administration','Administrators');
	xarAssignPrivilege('Oversight','Oversight');
	xarAssignPrivilege('AnonView','Anonymous');
	xarAssignPrivilege('Reading','Users');

    /*********************************************************************
    * Define instances for the core modules
    * Format is
    * xarDefineInstance(Module,Component,Querystring,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
    *********************************************************************/

	$query = "SELECT xar_name,xar_id FROM xar_block_groups";
    xarDefineInstance('blocks','BlockGroups',$query);
	$query = "SELECT types.xar_type,instances.xar_title,instances.xar_id FROM xar_block_instances as instances LEFT JOIN xar_block_types as types ON types.xar_id = instances.xar_type_id";
    xarDefineInstance('blocks','Blocks',$query);

	$query = "SELECT xar_name FROM xar_admin_menu";
    xarDefineInstance('adminpanels','adminmenu',$query);
	$query = "SELECT xar_name FROM xar_admin_menu";
    xarDefineInstance('adminpanels','adminmenublock',$query);
	$query = "SELECT xar_name FROM xar_admin_menu";
    xarDefineInstance('adminpanels','admintopblock',$query);
	$query = "SELECT xar_name FROM xar_admin_menu";
    xarDefineInstance('adminpanels','Waitingcontentblock',$query);
	$query = "SELECT modules.xar_name,wc.xar_itemid FROM xar_admin_wc as wc LEFT JOIN xar_modules as modules ON wc.xar_moduleid = modules.xar_regid";
    xarDefineInstance('adminpanels','Item',$query);

	$query = "SELECT xar_name FROM xar_roles";
    xarDefineInstance('roles','Roles',$query,0,'xar_rolemembers','xar_pid','xar_parentid','Instances of the roles module, including multilevel nesting');
	$query = "SELECT xar_name FROM xar_privileges";
    xarDefineInstance('privileges','Privileges',$query,0,'xar_privmembers','xar_pid','xar_parentid','Instances of the privileges module, including multilevel nesting');

	$query = "SELECT xar_name,xar_id FROM xar_allowed_vars";
    xarDefineInstance('base','Base',$query);

// the follwing are apparently used in the base module
	$query = "SELECT types.xar_type,instances.xar_title FROM xar_block_instances as instances LEFT JOIN xar_block_types as types ON types.xar_id = instances.xar_type_id";
    xarDefineInstance('blocks','PHPBlock',$query);
    xarDefineInstance('blocks','Block',$query);
    xarDefineInstance('blocks','HTMLBlock',$query);

//    xarDefineInstance('xproject','Projects','xar_xproject','xar_projectid','xar_name');

	$query = "SELECT xar_name,xar_regid FROM xar_themes";
    xarDefineInstance('themes','Themes',$query);


    /*********************************************************************
    * Register the module components that are privileges objects
    * Format is
    * xarregisterMask(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    xarRegisterMask('ViewLogin','All','All','Loginblock','All',ACCESS_OVERVIEW);
    xarRegisterMask('AdminAll','All','All','All','All',ACCESS_ADMIN);

    xarRegisterMask('ViewBlocks','All','base','Block','All',ACCESS_OVERVIEW);
    xarRegisterMask('EditBlock','All','base','Block','All',ACCESS_EDIT);
    xarRegisterMask('AddBlock','All','base','Block','All',ACCESS_ADD);
    xarRegisterMask('DeleteBlock','All','base','Block','All',ACCESS_DELETE);
    xarRegisterMask('AdminBlock','All','base','Block','All',ACCESS_ADMIN);
    xarRegisterMask('ViewBase','All','base','All','All',ACCESS_OVERVIEW);
    xarRegisterMask('ReadBase','All','base','All','All',ACCESS_READ);
    xarRegisterMask('AdminBase','All','base','All','All',ACCESS_ADMIN);

	xarRegisterMask('AdminInstaller','All','installer','All','All',ACCESS_ADMIN);

    xarRegisterMask('ViewThemes','All','themes','metablock','All',ACCESS_OVERVIEW);
    xarRegisterMask('AdminTheme','All','themes','All','All',ACCESS_ADMIN);

    xarRegisterMask('EditPanel','All','adminpanels','All','All',ACCESS_EDIT);
    xarRegisterMask('AddPanel','All','adminpanels','Item','All',ACCESS_ADD);
    xarRegisterMask('DeletePanel','All','adminpanels','All','All',ACCESS_DELETE);
    xarRegisterMask('AdminPanel','All','adminpanels','All','All',ACCESS_ADMIN);

   	xarRegisterMask('ViewRoles','All','roles','All','All',ACCESS_OVERVIEW);
   	xarRegisterMask('ReadRole','All','roles','All','All',ACCESS_READ);
   	xarRegisterMask('EditRole','All','roles','All','All',ACCESS_EDIT);
    xarRegisterMask('AddRole','All','roles','All','All',ACCESS_ADD);
    xarRegisterMask('DeleteRole','All','roles','All','All',ACCESS_DELETE);
    xarRegisterMask('AdminRole','All','roles','All','All',ACCESS_ADMIN);

    xarRegisterMask('EditMail','All','mail','All','All',ACCESS_EDIT);
    xarRegisterMask('AdminMail','All','mail','All','All',ACCESS_ADMIN);
    xarRegisterMask('DeleteMailPanel','adminpanels','mail','All','All',ACCESS_DELETE);

    xarRegisterMask('EditBlock','All','blocks','All','All',ACCESS_EDIT);
    xarRegisterMask('AddBlock','All','blocks','All','All',ACCESS_ADD);
    xarRegisterMask('DeleteBlock','All','blocks','All','All',ACCESS_DELETE);
    xarRegisterMask('AdminBlock','All','blocks','All','All',ACCESS_ADMIN);

    xarRegisterMask('PrivilegesGateway','All','Privileges','All','All',ACCESS_READ);
    xarRegisterMask('ViewPrivileges','All','Privileges','ViewPrivileges','All',ACCESS_READ);
    xarRegisterMask('EditPrivilege','All','Privileges','EditPrivilege','All',ACCESS_EDIT);
    xarRegisterMask('AddPrivilege','All','Privileges','AddPrivilege','All',ACCESS_ADD);
    xarRegisterMask('DeletePrivilege','All','Privileges','DeletePrivilege','All',ACCESS_DELETE);
    xarRegisterMask('ViewPrivilegeRoles','All','Privileges','ViewRoles','All',ACCESS_READ);
    xarRegisterMask('RemoveRole','All','Privileges','RemoveRole','All',ACCESS_DELETE);

    xarRegisterMask('AssignPrivAll','All','Privileges','AssignPrivilege','All',ACCESS_ADD);
    xarRegisterMask('RemovePrivAll','All','Privileges','RemovePrivilege','All',ACCESS_DELETE);

//    xarRegisterMask('RolesGateway','All','Roles','All','All',ACCESS_READ);

   	xarRegisterMask('EditModules','All','modules','All','All',ACCESS_EDIT);
   	xarRegisterMask('AdminModules','All','modules','All','All',ACCESS_ADMIN);

    // Initialisation successful
    return true;
}

