<?php
/**
 * File: community.conf.php
 *
 * Configuration file for a community site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage installer
 * @author Marc Lutolf
 */

$configuration_name = 'CommunitySite';

    $options  = array(
    array(
        'item' => '1',
        'option' => 'true',
        'comment' => 'Registered users have read access to all modules of the site.'),
    array(
        'item' => '2',
        'option' => 'false',
        'comment' => 'Unregistered users have read access to the non-core modules of the site.')
    );
$configuration_options = $options;

/**
 * Load the configuration
 *
 * @access public
 * @return boolean
 */
function installer_community_configuration_load($args)
{
// the following needs to be done in any case

// now do the necessary loading for each item

    if(in_array(2,$args)) {
        xarRegisterPrivilege('ReadAccess','All','All','All','All',ACCESS_READ,'The base privilege granting read access');
        xarMakePrivilegeRoot('ReadAccess');
        xarAssignPrivilege('ReadAccess','Users');
    }
    else {
    echo "no"; exit;
    }

    if(in_array(2,$args)) {
    echo "yes"; exit;
    }
    else {
    echo "no"; exit;
    }

    //xarRegisterPrivilege('Oversight','All','empty','All','All',ACCESS_NONE,'The privileges for the Obersight group');
    //xarRegisterPrivilege('DenyRoles','All','Roles','All','All',ACCESS_NONE,'Exclude access to the Roles module');
    //xarRegisterPrivilege('DenyPrivileges','All','Privileges','All','All',ACCESS_NONE,'Exclude access to the Privileges modules');
    //xarRegisterPrivilege('DenyRolesPrivileges','All','empty','All','All',ACCESS_NONE,'Exclude access to the Roles and Privileges modules');
    //xarRegisterPrivilege('Editing','All','All','All','All',ACCESS_EDIT,'The base privilege granting edit access');

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

    //xarMakePrivilegeRoot('Oversight');
    //xarMakePrivilegeRoot('DenyRolesPrivileges');
    //xarMakePrivilegeRoot('DenyRoles');
    //xarMakePrivilegeRoot('DenyPrivileges');
    //xarMakePrivilegeRoot('Editing');
    xarMakePrivilegeRoot('CasualAccess');
    xarMakePrivilegeRoot('ViewLogin');
    xarMakePrivilegeRoot('ViewBlocks');
    xarMakePrivilegeRoot('ViewLoginItems');
    //xarMakePrivilegeMember('DenyRoles','DenyRolesPrivileges');
    //xarMakePrivilegeMember('DenyPrivileges','DenyRolesPrivileges');
    //xarMakePrivilegeMember('DenyRolesPrivileges','Oversight');
    //xarMakePrivilegeMember('Administration','Oversight');
    //xarMakePrivilegeMember('DenyRolesPrivileges','Editing');
    //xarMakePrivilegeMember('DenyRolesPrivileges','Reading');
    xarMakePrivilegeMember('ViewLogin','CasualAccess');
    xarMakePrivilegeMember('ViewBlocks','CasualAccess');
    xarMakePrivilegeMember('ViewLoginItems','CasualAccess');

    //xarAssignPrivilege('Oversight','Oversight');
    xarAssignPrivilege('CasualAccess','Anonymous');

    return true;
}


?>
