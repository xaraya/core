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

$configuration_name = 'Community Site';

    $options  = array(
    array(
        'item' => '1',
        'option' => 'true',
        'comment' => xarML('Registered users have read access to all modules of the site.')),
    array(
        'item' => '2',
        'option' => 'false',
        'comment' => xarML('Unregistered users have read access to the non-core modules of the site. If this option is not chosen unregistered users see only the first page.'))
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

    if(in_array(1,$args)) {
        installer_community_readaccess();
        xarAssignPrivilege('ReadAccess','Users');
    }
    else {
        installer_community_casualaccess();
        xarAssignPrivilege('CasualAccess','Users');
    }

    if(in_array(2,$args)) {
        installer_community_readnoncore();
        xarAssignPrivilege('ReadNonCore','Everybody');
   }
    else {
        if(in_array(1,$args)) installer_community_casualaccess();
        xarAssignPrivilege('CasualAccess','Everybody');
    }

    return true;
}

function installer_community_casualaccess()
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

function installer_community_readnoncore()
{
    xarRegisterPrivilege('ReadNonCore','All','empty','All','All',ACCESS_NONE,'Exclude access to the core modules');
    xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All',ACCESS_NONE,'Exclude access to the Privileges modules');
    xarRegisterPrivilege('DenyAdminPanels','All','adminpanels','All','All',ACCESS_NONE,'Exclude access to the AdminPanels module');
    xarRegisterPrivilege('DenyBlocks','All','blocks','All','All',ACCESS_NONE,'Exclude access to the Blocks module');
    xarRegisterPrivilege('DenyMail','All','mail','All','All',ACCESS_NONE,'Exclude access to the Mail module');
    xarRegisterPrivilege('DenyModules','All','modules','All','All',ACCESS_NONE,'Exclude access to the Modules module');
    xarRegisterPrivilege('DenyThemes','All','themes','All','All',ACCESS_NONE,'Exclude access to the Themes module');
    xarRegisterPrivilege('DenyDynamicData','All','dynamicdata','All','All',ACCESS_NONE,'Exclude access to the AdminPanels module');
    xarMakePrivilegeRoot('ReadNonCore');
    xarMakePrivilegeRoot('DenyPrivileges');
    xarMakePrivilegeRoot('DenyAdminPanels');
    xarMakePrivilegeRoot('DenyBlocks');
    xarMakePrivilegeRoot('DenyMail');
    xarMakePrivilegeRoot('DenyModules');
    xarMakePrivilegeRoot('DenyThemes');
    xarMakePrivilegeRoot('DenyDynamicData');
    xarMakePrivilegeMember('ReadAccess','ReadNonCore');
    xarMakePrivilegeMember('DenyPrivileges','ReadNonCore');
    xarMakePrivilegeMember('DenyAdminPanels','ReadNonCore');
    xarMakePrivilegeMember('DenyBlocks','ReadNonCore');
    xarMakePrivilegeMember('DenyMail','ReadNonCore');
    xarMakePrivilegeMember('DenyModules','ReadNonCore');
    xarMakePrivilegeMember('DenyThemes','ReadNonCore');
    xarMakePrivilegeMember('DenyDynamicData','ReadNonCore');
}

function installer_community_readaccess()
{
        xarRegisterPrivilege('ReadAccess','All','All','All','All',ACCESS_READ,'The base privilege granting read access');
        xarMakePrivilegeRoot('ReadAccess');
}
?>
