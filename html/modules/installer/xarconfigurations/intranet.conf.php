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

 $configuration_name = 'Intranet';

    $options = array(
    array(
        'item' => '1',
        'option' => 'true',
        'comment' => xarML('Registered users have read access to the non-core modules of the site.')),
    array(
        'item' => '2',
        'option' => 'false',
        'comment' => xarML("Create an Oversight role that has full access but cannot change security. Password will be 'password'."))
    );
 $configuration_options = $options;


/**
 * Load the configuration
 *
 * @access public
 * @return boolean
 */
function installer_intranet_configuration_load($args)
{
// the following needs to be done in any case

    installer_intranet_casualaccess();
    xarAssignPrivilege('CasualAccess','Anonymous');

// now do the necessary loading for each item

    if(in_array(1,$args)) {
        installer_intranet_readnoncore();
        xarAssignPrivilege('ReadNonCore','Users');
    }
    else {
        xarAssignPrivilege('CasualAccess','Users');
    }

    if(in_array(2,$args)) {
        installer_intranet_oversightprivilege();
        installer_intranet_oversightrole();
        xarAssignPrivilege('Oversight','Oversight');
   }

   return true;

}

function installer_intranet_oversightprivilege()
{
    xarRegisterPrivilege('Oversight','All','empty','All','All',ACCESS_NONE,'The privileges for the Obersight group');
    xarMakePrivilegeRoot('Oversight');

    if(!in_array(1,$args)) {
        xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All',ACCESS_NONE,'Exclude access to the Privileges modules');
        xarMakePrivilegeRoot('DenyPrivileges');
    }
    xarMakePrivilegeMember('DenyPrivileges','Oversight');
    xarMakePrivilegeMember('Administration','Oversight');
}

function installer_intranet_oversightrole()
{
    xarMakeGroup('Oversight');
    xarMakeUser('Overseer','overseer','overseer@xaraya.com','password');
    xarMakeRoleMemberByName('Oversight','Everybody');
    xarMakeRoleMemberByName('Overseer','Oversight');
}

function installer_intranet_casualaccess()
{
    xarRegisterPrivilege('CasualAccess','All','themes','Block','All',ACCESS_OVERVIEW,'The base privilege for the Anonymous user');
    xarRegisterPrivilege('ViewLogin','All','roles','Block','login:Login:All',ACCESS_OVERVIEW,'A privilege for the Anonymous user');
    xarRegisterPrivilege('ViewBlocks','All','base','Block','All',ACCESS_OVERVIEW,'A privilege for the Anonymous user');
    xarRegisterPrivilege('ViewLoginItems','All','dynamicdata','Item','All',ACCESS_OVERVIEW,'A privilege for the Anonymous user');
    xarMakePrivilegeRoot('CasualAccess');
    xarMakePrivilegeRoot('ViewLogin');
    xarMakePrivilegeRoot('ViewBlocks');
    xarMakePrivilegeRoot('ViewLoginItems');
    xarMakePrivilegeMember('ViewLogin','CasualAccess');
    xarMakePrivilegeMember('ViewBlocks','CasualAccess');
    xarMakePrivilegeMember('ViewLoginItems','CasualAccess');
}

function installer_intranet_readnoncore()
{
    xarRegisterPrivilege('ReadNonCore','All','empty','All','All',ACCESS_NONE,'Exclude access to the core modules');
    xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All',ACCESS_NONE,'Exclude access to the Privileges modules');
    xarRegisterPrivilege('DenyAdminPanels','All','adminpanels','All','All',ACCESS_NONE,'Exclude access to the AdminPanels module');
    xarRegisterPrivilege('DenyBase','All','base','All','All',ACCESS_NONE,'Exclude access to the Base module');
    xarRegisterPrivilege('DenyBlocks','All','blocks','All','All',ACCESS_NONE,'Exclude access to the Blocks module');
    xarRegisterPrivilege('DenyMail','All','mail','All','All',ACCESS_NONE,'Exclude access to the Mail module');
    xarRegisterPrivilege('DenyModules','All','modules','All','All',ACCESS_NONE,'Exclude access to the Modules module');
    xarRegisterPrivilege('DenyThemes','All','themes','All','All',ACCESS_NONE,'Exclude access to the Themes module');
    xarRegisterPrivilege('DenyDynamicData','All','dynamicdata','All','All',ACCESS_NONE,'Exclude access to the AdminPanels module');
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
}
?>
