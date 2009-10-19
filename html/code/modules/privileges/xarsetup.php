<?php
/**
 * @package core modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage privileges
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
    * Define instances for the core modules
    * Format is
    * xarDefineInstance(Module,Component,Querystring,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
    *********************************************************************/
    $prefix = xarDB::getPrefix();

    $blockTypesTable     = $prefix . '_block_types';
    $blockInstancesTable = $prefix . '_block_instances';
    $modulesTable        = $prefix . '_modules';
    $rolesTable          = $prefix . '_roles';
    $roleMembersTable    = $prefix . '_rolemembers';
    $privilegesTable     = $prefix . '_privileges';
    $privMembersTable    = $prefix . '_privmembers';
    $themesTable         = $prefix . '_themes';

   //--------------------------------- Roles Module
    $info = xarMod::getBaseInfo('roles');
    $sysid = $info['systemid'];
    $query1 = "SELECT DISTINCT name FROM $blockTypesTable WHERE module_id = $sysid";
    $query2 = "SELECT DISTINCT instances.title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
    $query3 = "SELECT DISTINCT instances.id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
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

    $query = "SELECT DISTINCT name FROM $rolesTable";
    $instances = array(array('header' => 'Users and Groups',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('roles','Roles',$instances,0,$roleMembersTable,'id','parentid','Instances of the roles module, including multilevel nesting');

    $instances = array(array('header' => 'Parent:',
                             'query' => $query,
                             'limit' => 20),
                       array('header' => 'Child:',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('roles','Relation',$instances,0,$roleMembersTable,'id','parentid','Instances of the roles module, including multilevel nesting');

   // ----------------------------- Privileges Module
    $query = "SELECT DISTINCT name FROM $privilegesTable";
    $instances = array(array('header' => 'Privileges',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('privileges','Privileges',$instances,0,$privMembersTable,'privilege_id','parent_id','Instances of the privileges module, including multilevel nesting');

    // ----------------------------- Base Module
    $info = xarMod::getBaseInfo('base');
    $sysid = $info['systemid'];
    $query1 = "SELECT DISTINCT name FROM $blockTypesTable WHERE module_id = $sysid";
    $query2 = "SELECT DISTINCT instances.title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
    $query3 = "SELECT DISTINCT instances.id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
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
    $query1 = "SELECT DISTINCT name FROM $themesTable";
    $query2 = "SELECT DISTINCT regid FROM $themesTable";
    $instances = array(array('header' => 'Theme Name:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Theme ID:',
                             'query' => $query2,
                             'limit' => 20));
    xarDefineInstance('themes','Themes',$instances);

    $info = xarMod::getBaseInfo('themes');
    $sysid = $info['systemid'];
    $query1 = "SELECT DISTINCT name FROM $blockTypesTable WHERE module_id = $sysid";
    $query2 = "SELECT DISTINCT instances.title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
    $query3 = "SELECT DISTINCT instances.id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
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
    * Privileges_Master::register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

//    Privileges_Master::register('AdminAll','All','All','All','All',xarSecurityLevel('ACCESS_ADMIN'));

    Privileges_Master::register('ViewBaseBlocks','All','base','Block','All:All:All',xarSecurityLevel('ACCESS_OVERVIEW'));
    Privileges_Master::register('ReadBaseBlock','All','base','Block','All:All:All',xarSecurityLevel('ACCESS_READ'));
    Privileges_Master::register('EditBaseBlock','All','base','Block','All:All:All',xarSecurityLevel('ACCESS_EDIT'));
    Privileges_Master::register('AddBaseBlock','All','base','Block','All:All:All',xarSecurityLevel('ACCESS_ADD'));
    Privileges_Master::register('DeleteBaseBlock','All','base','Block','All:All:All',xarSecurityLevel('ACCESS_DELETE'));
    Privileges_Master::register('AdminBaseBlock','All','base','Block','All:All:All',xarSecurityLevel('ACCESS_ADMIN'));
    Privileges_Master::register('ViewBase','All','base','All','All',xarSecurityLevel('ACCESS_OVERVIEW'));
    Privileges_Master::register('ReadBase','All','base','All','All',xarSecurityLevel('ACCESS_READ'));
    Privileges_Master::register('EditBase','All','base','All','All',xarSecurityLevel('ACCESS_EDIT'));
    Privileges_Master::register('AdminBase','All','base','All','All',xarSecurityLevel('ACCESS_ADMIN'));
    /* This AdminPanel mask is added to replace the adminpanel module equivalent
     *   - since adminpanel module is removed as of 1.1.0
     * At some stage we should remove this but practice has been to use this mask in xarSecurityCheck
     * frequently in module code and templates - left here for now for ease in backward compatibiilty
     * @todo remove this
     */
    Privileges_Master::register('AdminPanel','All','base','All','All',xarSecurityLevel('ACCESS_ADMIN'));

    Privileges_Master::register('AdminInstaller','All','installer','All','All',xarSecurityLevel('ACCESS_ADMIN'));

    Privileges_Master::register('ViewRolesBlocks','All','roles','Block','All',xarSecurityLevel('ACCESS_OVERVIEW'));
    Privileges_Master::register('ViewRoles','All','roles','All','All',xarSecurityLevel('ACCESS_OVERVIEW'));
    Privileges_Master::register('ReadRole','All','roles','All','All',xarSecurityLevel('ACCESS_READ'));
    Privileges_Master::register('EditRole','All','roles','All','All',xarSecurityLevel('ACCESS_EDIT'));
    Privileges_Master::register('AddRole','All','roles','All','All',xarSecurityLevel('ACCESS_ADD'));
    Privileges_Master::register('DeleteRole','All','roles','All','All',xarSecurityLevel('ACCESS_DELETE'));
    Privileges_Master::register('AdminRole','All','roles','All','All',xarSecurityLevel('ACCESS_ADMIN'));
    Privileges_Master::register('MailRoles','All','roles','Mail','All',xarSecurityLevel('ACCESS_ADMIN'));

    Privileges_Master::register('AttachRole','All','roles','Relation','All',xarSecurityLevel('ACCESS_ADD'));
    Privileges_Master::register('RemoveRole','All','roles','Relation','All',xarSecurityLevel('ACCESS_DELETE'));

    Privileges_Master::register('AssignPrivilege','All','privileges','All','All',xarSecurityLevel('ACCESS_ADD'));
    Privileges_Master::register('DeassignPrivilege','All','privileges','All','All',xarSecurityLevel('ACCESS_DELETE'));
    Privileges_Master::register('ViewPrivileges','All','privileges','All','All',xarSecurityLevel('ACCESS_READ'));
    Privileges_Master::register('EditPrivilege','All','privileges','All','All',xarSecurityLevel('ACCESS_EDIT'));
    Privileges_Master::register('AddPrivilege','All','privileges','All','All',xarSecurityLevel('ACCESS_ADD'));
    Privileges_Master::register('DeletePrivilege','All','privileges','All','All',xarSecurityLevel('ACCESS_DELETE'));
    Privileges_Master::register('AdminPrivilege','All','privileges','All','All',xarSecurityLevel('ACCESS_ADMIN'));
/*
    Privileges_Master::register('ViewPrivileges','All','privileges','Realm','All',xarSecurityLevel('ACCESS_OVERVIEW'));
    Privileges_Master::register('ReadPrivilege','All','privileges','Realm','All',xarSecurityLevel('ACCESS_READ'));
    Privileges_Master::register('EditPrivilege','All','privileges','Realm','All',xarSecurityLevel('ACCESS_EDIT'));
    Privileges_Master::register('AddPrivilege','All','privileges','Realm','All',xarSecurityLevel('ACCESS_ADD'));
    Privileges_Master::register('DeletePrivilege','All','privileges','Realm','All',xarSecurityLevel('ACCESS_DELETE'));
*/
    Privileges_Master::register('EditModules','All','modules','All','All',xarSecurityLevel('ACCESS_EDIT'));
    Privileges_Master::register('AdminModules','All','modules','All','All',xarSecurityLevel('ACCESS_ADMIN'));

    return true;
}
?>
