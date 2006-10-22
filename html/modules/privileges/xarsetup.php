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
    xarMakeGroup('Everybody');
    xarMakeUser('Anonymous','anonymous','anonymous@xaraya.com');
    xarMakeUser('Admin','Admin','admin@xaraya.com','password');
    xarMakeGroup('Administrators');
    xarMakeGroup('Users');
    xarMakeUser('Myself','myself','myself@xaraya.com','password');

    /*********************************************************************
    * Arrange the roles in a hierarchy
    * Format is
    * makeMember(Child,Parent)
    *********************************************************************/

    xarMakeRoleRoot('Everybody');
    xarMakeRoleMemberByName('Administrators','Everybody');
    xarMakeRoleMemberByName('Admin','Administrators');
    xarMakeRoleMemberByName('Users','Everybody');
    xarMakeRoleMemberByName('Anonymous','Everybody');
    xarMakeRoleMemberByName('Myself','Everybody');

    /*********************************************************************
    * Define instances for the core modules
    * Format is
    * xarDefineInstance(Module,Component,Querystring,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
    *********************************************************************/
    $systemPrefix = xarDBGetSystemTablePrefix();

    $blockGroupsTable    = $systemPrefix . '_block_groups';
    $blockTypesTable     = $systemPrefix . '_block_types';
    $blockInstancesTable = $systemPrefix . '_block_instances';
    $modulesTable        = $systemPrefix . '_modules';
    $rolesTable          = $systemPrefix . '_roles';
    $roleMembersTable    = $systemPrefix . '_rolemembers';
    $privilegesTable     = $systemPrefix . '_privileges';
    $privMembersTable    = $systemPrefix . '_privmembers';
    $themesTable         = $systemPrefix . '_themes';

    /*********************************************************************
    * Register the module components that are privileges objects
    * Format is
    * xarregisterMask(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    xarRegisterMask('AdminAll','All','All','All','All','ACCESS_ADMIN');

    xarRegisterMask('ViewBaseBlocks','All','base','Block','All:All:All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadBaseBlock','All','base','Block','All:All:All','ACCESS_READ');
    xarRegisterMask('EditBaseBlock','All','base','Block','All:All:All','ACCESS_EDIT');
    xarRegisterMask('AddBaseBlock','All','base','Block','All:All:All','ACCESS_ADD');
    xarRegisterMask('DeleteBaseBlock','All','base','Block','All:All:All','ACCESS_DELETE');
    xarRegisterMask('AdminBaseBlock','All','base','Block','All:All:All','ACCESS_ADMIN');
    xarRegisterMask('ViewBase','All','base','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadBase','All','base','All','All','ACCESS_READ');
    xarRegisterMask('AdminBase','All','base','All','All','ACCESS_ADMIN');
    /* This AdminPanel mask is added to replace the adminpanel module equivalent
     *   - since adminpanel module is removed as of 1.1.0
     * At some stage we should remove this but practice has been to use this mask in xarSecurityCheck
     * frequently in module code and templates - left here for now for ease in backward compatibiilty
     * @todo remove this
     */
    xarRegisterMask('AdminPanel','All','base','All','All','ACCESS_ADMIN');

    xarRegisterMask('AdminInstaller','All','installer','All','All','ACCESS_ADMIN');

    xarRegisterMask('ViewRolesBlocks','All','roles','Block','All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewRoles','All','roles','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadRole','All','roles','All','All','ACCESS_READ');
    xarRegisterMask('EditRole','All','roles','All','All','ACCESS_EDIT');
    xarRegisterMask('AddRole','All','roles','All','All','ACCESS_ADD');
    xarRegisterMask('DeleteRole','All','roles','All','All','ACCESS_DELETE');
    xarRegisterMask('AdminRole','All','roles','All','All','ACCESS_ADMIN');
    xarRegisterMask('MailRoles','All','roles','Mail','All','ACCESS_ADMIN');

    xarRegisterMask('AttachRole','All','roles','Relation','All','ACCESS_ADD');
    xarRegisterMask('RemoveRole','All','roles','Relation','All','ACCESS_DELETE');

    xarRegisterMask('AssignPrivilege','All','privileges','All','All','ACCESS_ADD');
    xarRegisterMask('DeassignPrivilege','All','privileges','All','All','ACCESS_DELETE');
    xarRegisterMask('ViewPrivileges','All','privileges','All','All','ACCESS_READ');
    xarRegisterMask('EditPrivilege','All','privileges','All','All','ACCESS_EDIT');
    xarRegisterMask('AddPrivilege','All','privileges','All','All','ACCESS_ADD');
    xarRegisterMask('DeletePrivilege','All','privileges','All','All','ACCESS_DELETE');
    xarRegisterMask('AdminPrivilege','All','privileges','All','All','ACCESS_ADMIN');

    xarRegisterMask('ViewPrivileges','All','privileges','Realm','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadPrivilege','All','privileges','Realm','All','ACCESS_READ');
    xarRegisterMask('EditPrivilege','All','privileges','Realm','All','ACCESS_EDIT');
    xarRegisterMask('AddPrivilege','All','privileges','Realm','All','ACCESS_ADD');
    xarRegisterMask('DeletePrivilege','All','privileges','Realm','All','ACCESS_DELETE');

    xarRegisterMask('EditModules','All','modules','All','All','ACCESS_EDIT');
    xarRegisterMask('AdminModules','All','modules','All','All','ACCESS_ADMIN');

    return true;
}
?>
