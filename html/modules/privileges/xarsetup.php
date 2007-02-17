<?php
/**
 * @package core modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 */
/**
 * Default setup for roles and privileges
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
*/
function initializeSetup()
{
    /*********************************************************************
    * Enter some default groups and users
    *********************************************************************/
    xarRoles::makeGroup('Everybody');
    xarRoles::makeUser('Anonymous','anonymous','anonymous@xaraya.com');
    xarRoles::makeUser('Admin','Admin','admin@xaraya.com','password');
    xarRoles::makeGroup('Administrators');
    xarRoles::makeGroup('Users');
    xarRoles::makeUser('Myself','myself','myself@xaraya.com','password');

    /*********************************************************************
    * Arrange the roles in a hierarchy
    * Format is
    * makeMember(Child,Parent)
    *********************************************************************/

    xarRoles::isRoot('Everybody');
    xarRoles::makeMemberByName('Administrators','Everybody');
    xarRoles::makeMemberByName('Admin','Administrators');
    xarRoles::makeMemberByName('Users','Everybody');
    xarRoles::makeMemberByName('Anonymous','Everybody');
    xarRoles::makeMemberByName('Myself','Everybody');

    /*********************************************************************
    * Define instances for the core modules
    * Format is
    * xarDefineInstance(Module,Component,Querystring,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
    *********************************************************************/
    $systemPrefix = xarDBGetSystemTablePrefix();

    $blockTypesTable     = $systemPrefix . '_block_types';
    $blockInstancesTable = $systemPrefix . '_block_instances';
    $modulesTable        = $systemPrefix . '_modules';
    $rolesTable          = $systemPrefix . '_roles';
    $roleMembersTable    = $systemPrefix . '_rolemembers';
    $privilegesTable     = $systemPrefix . '_privileges';
    $privMembersTable    = $systemPrefix . '_privmembers';
    $themesTable         = $systemPrefix . '_themes';

   //--------------------------------- Roles Module
    $info = xarMod::getBaseInfo('roles');
    $sysid = $info['systemid'];
    $query1 = "SELECT DISTINCT xar_type FROM $blockTypesTable WHERE xar_modid = $sysid";
    $query2 = "SELECT DISTINCT instances.xar_title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_modid = $sysid";
    $query3 = "SELECT DISTINCT instances.xar_id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_modid = $sysid";
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

    $instances = array(array('header' => 'Parent:',
                             'query' => $query,
                             'limit' => 20),
                       array('header' => 'Child:',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('roles','Relation',$instances,0,$roleMembersTable,'xar_uid','xar_parentid','Instances of the roles module, including multilevel nesting');

   // ----------------------------- Privileges Module
    $query = "SELECT DISTINCT xar_name FROM $privilegesTable";
    $instances = array(array('header' => 'Privileges',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('privileges','Privileges',$instances,0,$privMembersTable,'xar_pid','xar_parentid','Instances of the privileges module, including multilevel nesting');

    // ----------------------------- Base Module
    $info = xarMod::getBaseInfo('base');
    $sysid = $info['systemid'];
    $query1 = "SELECT DISTINCT xar_type FROM $blockTypesTable WHERE xar_modid = $sysid";
    $query2 = "SELECT DISTINCT instances.xar_title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_modid = $sysid";
    $query3 = "SELECT DISTINCT instances.xar_id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_modid = $sysid";
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

    $info = xarMod::getBaseInfo('themes');
    $sysid = $info['systemid'];
    $query1 = "SELECT DISTINCT xar_type FROM $blockTypesTable WHERE xar_modid = $sysid";
    $query2 = "SELECT DISTINCT instances.xar_title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_modid = $sysid";
    $query3 = "SELECT DISTINCT instances.xar_id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_modid = $sysid";
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
    * xarMasks::register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    xarMasks::register('AdminAll','All','All','All','All',xarSecurityLevel('ACCESS_ADMIN'));

    xarMasks::register('ViewBaseBlocks','All','base','Block','All:All:All',xarSecurityLevel('ACCESS_OVERVIEW'));
    xarMasks::register('ReadBaseBlock','All','base','Block','All:All:All',xarSecurityLevel('ACCESS_READ'));
    xarMasks::register('EditBaseBlock','All','base','Block','All:All:All',xarSecurityLevel('ACCESS_EDIT'));
    xarMasks::register('AddBaseBlock','All','base','Block','All:All:All',xarSecurityLevel('ACCESS_ADD'));
    xarMasks::register('DeleteBaseBlock','All','base','Block','All:All:All',xarSecurityLevel('ACCESS_DELETE'));
    xarMasks::register('AdminBaseBlock','All','base','Block','All:All:All',xarSecurityLevel('ACCESS_ADMIN'));
    xarMasks::register('ViewBase','All','base','All','All',xarSecurityLevel('ACCESS_OVERVIEW'));
    xarMasks::register('ReadBase','All','base','All','All',xarSecurityLevel('ACCESS_READ'));
    xarMasks::register('AdminBase','All','base','All','All',xarSecurityLevel('ACCESS_ADMIN'));
    /* This AdminPanel mask is added to replace the adminpanel module equivalent
     *   - since adminpanel module is removed as of 1.1.0
     * At some stage we should remove this but practice has been to use this mask in xarSecurityCheck
     * frequently in module code and templates - left here for now for ease in backward compatibiilty
     * @todo remove this
     */
    xarMasks::register('AdminPanel','All','base','All','All',xarSecurityLevel('ACCESS_ADMIN'));

    xarMasks::register('AdminInstaller','All','installer','All','All',xarSecurityLevel('ACCESS_ADMIN'));

    xarMasks::register('ViewRolesBlocks','All','roles','Block','All',xarSecurityLevel('ACCESS_OVERVIEW'));
    xarMasks::register('ViewRoles','All','roles','All','All',xarSecurityLevel('ACCESS_OVERVIEW'));
    xarMasks::register('ReadRole','All','roles','All','All',xarSecurityLevel('ACCESS_READ'));
    xarMasks::register('EditRole','All','roles','All','All',xarSecurityLevel('ACCESS_EDIT'));
    xarMasks::register('AddRole','All','roles','All','All',xarSecurityLevel('ACCESS_ADD'));
    xarMasks::register('DeleteRole','All','roles','All','All',xarSecurityLevel('ACCESS_DELETE'));
    xarMasks::register('AdminRole','All','roles','All','All',xarSecurityLevel('ACCESS_ADMIN'));
    xarMasks::register('MailRoles','All','roles','Mail','All',xarSecurityLevel('ACCESS_ADMIN'));

    xarMasks::register('AttachRole','All','roles','Relation','All',xarSecurityLevel('ACCESS_ADD'));
    xarMasks::register('RemoveRole','All','roles','Relation','All',xarSecurityLevel('ACCESS_DELETE'));

    xarMasks::register('AssignPrivilege','All','privileges','All','All',xarSecurityLevel('ACCESS_ADD'));
    xarMasks::register('DeassignPrivilege','All','privileges','All','All',xarSecurityLevel('ACCESS_DELETE'));
    xarMasks::register('ViewPrivileges','All','privileges','All','All',xarSecurityLevel('ACCESS_READ'));
    xarMasks::register('EditPrivilege','All','privileges','All','All',xarSecurityLevel('ACCESS_EDIT'));
    xarMasks::register('AddPrivilege','All','privileges','All','All',xarSecurityLevel('ACCESS_ADD'));
    xarMasks::register('DeletePrivilege','All','privileges','All','All',xarSecurityLevel('ACCESS_DELETE'));
    xarMasks::register('AdminPrivilege','All','privileges','All','All',xarSecurityLevel('ACCESS_ADMIN'));
/*
    xarMasks::register('ViewPrivileges','All','privileges','Realm','All',xarSecurityLevel('ACCESS_OVERVIEW'));
    xarMasks::register('ReadPrivilege','All','privileges','Realm','All',xarSecurityLevel('ACCESS_READ'));
    xarMasks::register('EditPrivilege','All','privileges','Realm','All',xarSecurityLevel('ACCESS_EDIT'));
    xarMasks::register('AddPrivilege','All','privileges','Realm','All',xarSecurityLevel('ACCESS_ADD'));
    xarMasks::register('DeletePrivilege','All','privileges','Realm','All',xarSecurityLevel('ACCESS_DELETE'));
*/
    xarMasks::register('EditModules','All','modules','All','All',xarSecurityLevel('ACCESS_EDIT'));
    xarMasks::register('AdminModules','All','modules','All','All',xarSecurityLevel('ACCESS_ADMIN'));

    return true;
}
?>
