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
 * @subpackage privileges
 * @author Marc Lutolf <marcinmilan@xaraya.com>
*/

function initializeSetup()
{
    /*********************************************************************
    * Enter some default groups and users
    *********************************************************************/

    xarMakeGroup('Everybody');
    xarMakeUser('Anonymous','anonymous','anonymous@xaraya.com');
    xarMakeUser('Admin','Admin','admin@xaraya.com','password');
    xarMakeGroup('Administrators');
    //xarMakeGroup('Oversight');
    //xarMakeUser('Overseer','overseer','overseer@xaraya.com','password');
    xarMakeGroup('Users');
    //xarMakeUser('User','user','user@xaraya.com','password');
    //xarMakeUser('Current','current','current@xaraya.com','password');

    /*********************************************************************
    * Arrange the roles in a hierarchy
    * Format is
    * makeMember(Child,Parent)
    *********************************************************************/

    xarMakeRoleRoot('Everybody');
    xarMakeRoleMemberByName('Administrators','Everybody');
    xarMakeRoleMemberByName('Admin','Administrators');
    //xarMakeRoleMemberByName('Oversight','Everybody');
    //xarMakeRoleMemberByName('Overseer','Oversight');
    xarMakeRoleMemberByName('Users','Everybody');
    //xarMakeRoleMemberByName('User','Users');
    xarMakeRoleMemberByName('Anonymous','Everybody');
    //xarMakeRoleMemberByName('Current','Everybody');

    /*********************************************************************
    * Enter some default privileges
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    xarRegisterPrivilege('NoAccess','All','All','All','All',ACCESS_NONE,'The base privilege granting no access');
    xarRegisterPrivilege('Administration','All','All','All','All',ACCESS_ADMIN,'The base privilege granting full access');
    //xarRegisterPrivilege('Oversight','All','empty','All','All',ACCESS_NONE,'The privileges for the Obersight group');
    xarRegisterPrivilege('ReadNonCore','All','empty','All','All',ACCESS_NONE,'Exclude access to the core modules');
    xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All',ACCESS_NONE,'Exclude access to the Privileges modules');
    xarRegisterPrivilege('DenyAdminPanels','All','adminpanels','All','All',ACCESS_NONE,'Exclude access to the AdminPanels module');
    xarRegisterPrivilege('DenyBase','All','base','All','All',ACCESS_NONE,'Exclude access to the Base module');
    xarRegisterPrivilege('DenyBlocks','All','blocks','All','All',ACCESS_NONE,'Exclude access to the Blocks module');
    xarRegisterPrivilege('DenyMail','All','mail','All','All',ACCESS_NONE,'Exclude access to the Mail module');
    xarRegisterPrivilege('DenyModules','All','modules','All','All',ACCESS_NONE,'Exclude access to the Modules module');
    xarRegisterPrivilege('DenyThemes','All','themes','All','All',ACCESS_NONE,'Exclude access to the Themes module');
    xarRegisterPrivilege('DenyDynamicData','All','dynamicdata','All','All',ACCESS_NONE,'Exclude access to the AdminPanels module');
    //xarRegisterPrivilege('Editing','All','All','All','All',ACCESS_EDIT,'The base privilege granting edit access');
    xarRegisterPrivilege('ReadAccess','All','All','All','All',ACCESS_READ,'The base privilege granting read access');

    xarRegisterPrivilege('ViewLogin','All','roles','Block','login:Login:All',ACCESS_OVERVIEW,'A privilege for the Anonymous user');
    xarRegisterPrivilege('ViewBlocks','All','base','Block','All',ACCESS_OVERVIEW,'A privilege for the Anonymous user');
    xarRegisterPrivilege('ViewLoginItems','All','dynamicdata','Item','All',ACCESS_OVERVIEW,'A privilege for the Anonymous user');
    xarRegisterPrivilege('CasualAccess','All','themes','Block','All',ACCESS_OVERVIEW,'The base privilege for the Anonymous user');
    //xarRegisterPrivilege('AddAll','All','All','All','All',ACCESS_ADD,'The base privilege granting add access');
    //xarRegisterPrivilege('DeleteAll','All','All','All','All',ACCESS_DELETE,'The base privilege granting delete access');
    //xarRegisterPrivilege('ModPrivilege','All','Privileges','All','All',ACCESS_EDIT,'');
    //xarRegisterPrivilege('AddPrivilege','All','Privileges','All','All',ACCESS_ADD,'');
    //xarRegisterPrivilege('DelPrivilege','All','Privileges','All','All',ACCESS_DELETE,'');
    //xarRegisterPrivilege('AdminPrivilege','All','Privileges','All','All',ACCESS_ADMIN,'A special privilege granting admin access to Privileges for Anonymous');
    //xarRegisterPrivilege('AdminRole','All','Roles','All','All',ACCESS_ADMIN,'A special privilege granting admin access to Roles for Anonymous');

    /*********************************************************************
    * Arrange the  privileges in a hierarchy
    * Format is
    * makeEntry(Privilege)
    * makeMember(Child,Parent)
    *********************************************************************/

    xarMakePrivilegeRoot('NoAccess');
    xarMakePrivilegeRoot('Administration');
    xarMakePrivilegeRoot('ReadAccess');

    //xarMakePrivilegeRoot('Oversight');

    xarMakePrivilegeRoot('ReadNonCore');
    xarMakePrivilegeRoot('DenyPrivileges');
    xarMakePrivilegeRoot('DenyAdminPanels');
    xarMakePrivilegeRoot('DenyBase');
    xarMakePrivilegeRoot('DenyBlocks');
    xarMakePrivilegeRoot('DenyMail');
    xarMakePrivilegeRoot('DenyModules');
    xarMakePrivilegeRoot('DenyThemes');
    xarMakePrivilegeRoot('DenyDynamicData');
    xarMakePrivilegeMember('ReadAccess','ReadNonCore');
    xarMakePrivilegeMember('DenyPrivileges','ReadNonCore');
    xarMakePrivilegeMember('DenyAdminPanels','ReadNonCore');
    xarMakePrivilegeMember('DenyBase','ReadNonCore');
    xarMakePrivilegeMember('DenyBlocks','ReadNonCore');
    xarMakePrivilegeMember('DenyMail','ReadNonCore');
    xarMakePrivilegeMember('DenyModules','ReadNonCore');
    xarMakePrivilegeMember('DenyThemes','ReadNonCore');
    xarMakePrivilegeMember('DenyDynamicData','ReadNonCore');

    xarMakePrivilegeRoot('CasualAccess');
    xarMakePrivilegeRoot('ViewLogin');
    xarMakePrivilegeRoot('ViewBlocks');
    xarMakePrivilegeRoot('ViewLoginItems');
    //xarMakePrivilegeMember('DenyPrivileges','DenyRolesPrivileges');
    //xarMakePrivilegeMember('DenyRolesPrivileges','Oversight');
    //xarMakePrivilegeMember('Administration','Oversight');
    //xarMakePrivilegeMember('DenyRolesPrivileges','Editing');
    //xarMakePrivilegeMember('DenyRolesPrivileges','Reading');
    xarMakePrivilegeMember('ViewLogin','CasualAccess');
    xarMakePrivilegeMember('ViewBlocks','CasualAccess');
    xarMakePrivilegeMember('ViewLoginItems','CasualAccess');

    /*********************************************************************
    * Assign the default privileges to groups/users
    * Format is
    * assign(Privilege,Role)
    *********************************************************************/

    xarAssignPrivilege('NoAccess','Everybody');
    xarAssignPrivilege('Administration','Administrators');
    //xarAssignPrivilege('Oversight','Oversight');
    xarAssignPrivilege('CasualAccess','Anonymous');
    xarAssignPrivilege('ReadAccess','Users');

    /*********************************************************************
    * Define instances for the core modules
    * Format is
    * xarDefineInstance(Module,Component,Querystring,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
    *********************************************************************/
    $systemPrefix = xarDBGetSystemTablePrefix();

    $blockGroupsTable    = $systemPrefix . '_block_groups';
    $blockTypesTable     = $systemPrefix . '_block_types';
    $blockInstancesTable = $systemPrefix . '_block_instances';
    $AdminWcTable        = $systemPrefix . '_admin_wc';
    $modulesTable        = $systemPrefix . '_modules';
    $rolesTable          = $systemPrefix . '_roles';
    $roleMembersTable    = $systemPrefix . '_rolemembers';
    $privilegesTable     = $systemPrefix . '_privileges';
    $privMembersTable    = $systemPrefix . '_privmembers';
    $themesTable         = $systemPrefix . '_themes';

    //-------------------------------- Blocks Module


    $query1 = "SELECT DISTINCT xar_name FROM $blockGroupsTable";
    $query2 = "SELECT DISTINCT xar_id FROM $blockGroupsTable";
    $instances = array(array('header'  => 'Group Name:',
                             'query'   => $query1,
                             'limit'   => 20),
                       array('header'  => 'Group ID:',
                             'query'   => $query2,
                             'limit'   => 20));

    xarDefineInstance('blocks','BlockGroups',$instances);

    $query1 = "SELECT DISTINCT xar_type FROM $blockTypesTable ";
    $query2 = "SELECT DISTINCT instances.xar_title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as types ON types.xar_id = instances.xar_type_id";
    $query3 = "SELECT DISTINCT instances.xar_id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as types ON types.xar_id = instances.xar_type_id";
    $instances = array(array('header' => 'Block Type:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Title:',
                             'query' => $query2,
                             'limit' => 20),
                       array('header' => 'Block ID:',
                             'query' => $query3,
                             'limit' => 20));
    xarDefineInstance('blocks','Blocks',$instances);

    //--------------------------------- Adminpanels Module

    $query1 = "SELECT DISTINCT xar_type FROM $blockTypesTable WHERE xar_module = 'adminpanels'";
    $query2 = "SELECT DISTINCT instances.xar_title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as types ON types.xar_id = instances.xar_type_id WHERE xar_module = 'adminpanels'";
    $query3 = "SELECT DISTINCT instances.xar_id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as types ON types.xar_id = instances.xar_type_id WHERE xar_module = 'adminpanels'";
    $instances = array(array('header' => 'Block Type:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Title:',
                             'query' => $query2,
                             'limit' => 20),
                       array('header' => 'Block ID:',
                             'query' => $query3,
                             'limit' => 20));
    xarDefineInstance('adminpanels','Block',$instances);

    $query1 = "SELECT DISTINCT modules.xar_name FROM $AdminWcTable as wc LEFT JOIN $modulesTable as modules ON wc.xar_moduleid = modules.xar_regid";
    $query2 = "SELECT DISTINCT modules.xar_name,wc.xar_itemid FROM $AdminWcTable as wc LEFT JOIN $modulesTable as modules ON wc.xar_moduleid = modules.xar_regid";
    $instances = array(array('header' => 'Module Name:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Item ID:',
                             'query' => $query2,
                             'limit' => 20));
    xarDefineInstance('adminpanels','Item',$instances);

   //--------------------------------- Roles Module
    $query1 = "SELECT DISTINCT xar_type FROM $blockTypesTable WHERE xar_module = 'roles'";
    $query2 = "SELECT DISTINCT instances.xar_title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as types ON types.xar_id = instances.xar_type_id WHERE xar_module = 'roles'";
    $query3 = "SELECT DISTINCT instances.xar_id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as types ON types.xar_id = instances.xar_type_id WHERE xar_module = 'roles'";
    $instances = array(array('header' => 'Block Type:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Title:',
                             'query' => $query2,
                             'limit' => 20),
                       array('header' => 'Block ID:',
                             'query' => $query3,
                             'limit' => 20));
    xarDefineInstance('roles','Block',$instances);

    $query = "SELECT DISTINCT xar_name FROM $rolesTable";
    $instances = array(array('header' => 'Users and Groups',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('roles','Roles',$instances,0,$roleMembersTable,'xar_uid','xar_parentid','Instances of the roles module, including multilevel nesting');

   // ----------------------------- Privileges Module
    $query = "SELECT DISTINCT xar_name FROM $privilegesTable";
    $instances = array(array('header' => 'Privileges',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('privileges','Privileges',$instances,0,$privMembersTable,'xar_pid','xar_parentid','Instances of the privileges module, including multilevel nesting');

    // ----------------------------- Base Module
    $query1 = "SELECT DISTINCT xar_type FROM $blockTypesTable WHERE xar_module = 'base'";
    $query2 = "SELECT DISTINCT instances.xar_title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as types ON types.xar_id = instances.xar_type_id WHERE xar_module = 'base'";
    $query3 = "SELECT DISTINCT instances.xar_id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as types ON types.xar_id = instances.xar_type_id WHERE xar_module = 'base'";
    $instances = array(array('header' => 'Block Type:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Title:',
                             'query' => $query2,
                             'limit' => 20),
                       array('header' => 'Block ID:',
                             'query' => $query3,
                             'limit' => 20));
    xarDefineInstance('base','Block',$instances);

   // ------------------------------- Themes Module
    $query1 = "SELECT DISTINCT xar_name FROM $themesTable";
    $query2 = "SELECT DISTINCT xar_regid FROM $themesTable";
    $instances = array(array('header' => 'Theme Name:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Theme ID:',
                             'query' => $query2,
                             'limit' => 20));
    xarDefineInstance('themes','Themes',$instances);

    $query1 = "SELECT DISTINCT xar_type FROM $blockTypesTable WHERE xar_module = 'themes'";
    $query2 = "SELECT DISTINCT instances.xar_title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as types ON types.xar_id = instances.xar_type_id WHERE xar_module = 'themes'";
    $query3 = "SELECT DISTINCT instances.xar_id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as types ON types.xar_id = instances.xar_type_id WHERE xar_module = 'themes'";
    $instances = array(array('header' => 'Block Type:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Title:',
                             'query' => $query2,
                             'limit' => 20),
                       array('header' => 'Block ID:',
                             'query' => $query3,
                             'limit' => 20));
    xarDefineInstance('themes','Block',$instances);

    /*********************************************************************
    * Register the module components that are privileges objects
    * Format is
    * xarregisterMask(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    xarRegisterMask('AdminAll','All','All','All','All',ACCESS_ADMIN);

    xarRegisterMask('ViewBlocks','All','base','Block','All:All:All',ACCESS_OVERVIEW);
    xarRegisterMask('EditBlock','All','base','Block','All:All:All',ACCESS_EDIT);
    xarRegisterMask('AddBlock','All','base','Block','All:All:All',ACCESS_ADD);
    xarRegisterMask('DeleteBlock','All','base','Block','All:All:ALL',ACCESS_DELETE);
    xarRegisterMask('AdminBlock','All','base','Block','All:All:ALL',ACCESS_ADMIN);
    xarRegisterMask('ViewBase','All','base','All','All',ACCESS_OVERVIEW);
    xarRegisterMask('ReadBase','All','base','All','All',ACCESS_READ);
    xarRegisterMask('AdminBase','All','base','All','All',ACCESS_ADMIN);

    xarRegisterMask('AdminInstaller','All','installer','All','All',ACCESS_ADMIN);

    xarRegisterMask('ViewThemes','All','themes','Block','All:All:ALL',ACCESS_OVERVIEW);
    xarRegisterMask('AdminTheme','All','themes','All','All',ACCESS_ADMIN);

    xarRegisterMask('EditPanel','All','adminpanels','All','All',ACCESS_EDIT);
    xarRegisterMask('AddPanel','All','adminpanels','Item','All',ACCESS_ADD);
    xarRegisterMask('DeletePanel','All','adminpanels','All','All',ACCESS_DELETE);
    xarRegisterMask('AdminPanel','All','adminpanels','All','All',ACCESS_ADMIN);

    xarRegisterMask('ViewLogin','All','roles','Block','login:Login:All',ACCESS_OVERVIEW);
    xarRegisterMask('ViewRoles','All','roles','Block','All',ACCESS_OVERVIEW);
    xarRegisterMask('ReadRole','All','roles','Block','All',ACCESS_READ);
    xarRegisterMask('ViewRoles','All','roles','All','All',ACCESS_OVERVIEW);
    xarRegisterMask('ReadRole','All','roles','All','All',ACCESS_READ);
    xarRegisterMask('EditRole','All','roles','All','All',ACCESS_EDIT);
    xarRegisterMask('AddRole','All','roles','All','All',ACCESS_ADD);
    xarRegisterMask('DeleteRole','All','roles','All','All',ACCESS_DELETE);
    xarRegisterMask('AdminRole','All','roles','All','All',ACCESS_ADMIN);

    xarRegisterMask('EditMail','All','mail','All','All',ACCESS_EDIT);
    xarRegisterMask('AdminMail','All','mail','All','All',ACCESS_ADMIN);
    xarRegisterMask('DeleteMail', 'All','mail','All','All',ACCESS_DELETE);

    xarRegisterMask('EditBlock','All','blocks','All','All',ACCESS_EDIT);
    xarRegisterMask('AddBlock','All','blocks','All','All',ACCESS_ADD);
    xarRegisterMask('DeleteBlock','All','blocks','All','All',ACCESS_DELETE);
    xarRegisterMask('AdminBlock','All','blocks','All','All',ACCESS_ADMIN);

    //xarRegisterMask('PrivilegesGateway','All','Privileges','All','All',ACCESS_READ);
    xarRegisterMask('ViewPrivileges','All','privileges','All','All',ACCESS_READ);
    xarRegisterMask('EditPrivilege','All','privileges','All','All',ACCESS_EDIT);
    xarRegisterMask('AddPrivilege','All','privileges','All','All',ACCESS_ADD);
    xarRegisterMask('DeletePrivilege','All','privileges','All','All',ACCESS_DELETE);
    //xarRegisterMask('ViewPrivilegeRoles','All','privileges','ViewRoles','All',ACCESS_READ);
    xarRegisterMask('EditRole','All','privileges','All','All',ACCESS_EDIT);
    xarRegisterMask('AssignRole','All','privileges','All','All',ACCESS_ADD);

    //xarRegisterMask('RolesGateway','All','Roles','All','All',ACCESS_READ);

    xarRegisterMask('EditModules','All','modules','All','All',ACCESS_EDIT);
    xarRegisterMask('AdminModules','All','modules','All','All',ACCESS_ADMIN);

    xarRegisterMask('ViewThemes','All','themes','All','All',ACCESS_OVERVIEW);
    xarRegisterMask('AdminTheme','All','themes','All','All',ACCESS_ADMIN);

    // Initialisation successful
    return true;
}

