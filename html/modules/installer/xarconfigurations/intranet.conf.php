<?php
/**
 * Intranet configuration
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Installer
 */
/*
 * @author Marc Lutolf
 */
$configuration_name = xarML('Intranet - modules and privilege appropriate for restricted access');

function installer_intranet_moduleoptions()
{
    return array(
        array('name' => "autolinks",            'regid' => 11),
        array('name' => "bloggerapi",           'regid' => 745),
        array('name' => "categories",           'regid' => 147),
        array('name' => "comments",             'regid' => 14),
        array('name' => "example",              'regid' => 36),
        array('name' => "hitcount",             'regid' => 177),
        array('name' => "search",               'regid' => 32),
        array('name' => "sniffer",              'regid' => 755),
        array('name' => "stats",                'regid' => 34),
        array('name' => "wiki",                 'regid' => 28),
        array('name' => "xmlrpcserver",         'regid' => 743),
        array('name' => "xmlrpcsystemapi",      'regid' => 744),
        array('name' => "xmlrpcvalidatorapi",   'regid' => 746),
        array('name' => "articles",             'regid' => 151)
    );
}

function installer_intranet_privilegeoptions()
{
    return array(
                     array(
                           'item' => 'p1',
                           'option' => 'true',
                           'comment' => xarML('Registered users have read access to the non-core modules of the site.')),
                     array(
                           'item' => 'p2',
                           'option' => 'false',
                           'comment' => xarML("Create an Oversight role that has full access but cannot change security. Password will be 'password'."))
                     );
}

/**
 * Load the configuration
 *
 * @access public
 * @return boolean
 */
function installer_intranet_configuration_load($args)
{
// load the privileges chosen

    installer_intranet_casualaccess();
    xarAssignPrivilege('CasualAccess','Everybody');

// now do the necessary loading for each item

    if(in_array('p1',$args)) {
        installer_intranet_readaccess();
        installer_intranet_readnoncore();
        xarAssignPrivilege('ReadNonCore','Users');
    }
    else {
        xarAssignPrivilege('CasualAccess','Users');
    }

    if(in_array('p2',$args)) {
        installer_intranet_oversightprivilege();
        installer_intranet_oversightrole();
        xarAssignPrivilege('Oversight','Oversight');
        if(!in_array('p1',$args)) {
            xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Exclude access to the Privileges modules');
            xarMakePrivilegeRoot('DenyPrivileges');
        }
        xarMakePrivilegeMember('DenyPrivileges','Oversight');
//        xarMakePrivilegeMember('Administration','Oversight');
   }

   return true;

}

function installer_intranet_oversightprivilege()
{
    xarRegisterPrivilege('Oversight','All','empty','All','All','ACCESS_NONE','The privilege container for the Oversight group');
    xarMakePrivilegeRoot('Oversight');
}

function installer_intranet_oversightrole()
{
    xarMakeGroup('Oversight');
    xarMakeUser('Overseer','overseer','overseer@xaraya.com','password');
    xarMakeRoleMemberByName('Oversight','Administrators');
    xarMakeRoleMemberByName('Overseer','Oversight');
}

function installer_intranet_casualaccess()
{
    xarRegisterPrivilege('CasualAccess','All','themes','Block','All','ACCESS_OVERVIEW','Minimal access to a site');
    xarRegisterPrivilege('ViewLogin','All','roles','Block','login:Login:All','ACCESS_OVERVIEW','View the Login block');
    xarRegisterPrivilege('ViewBlocks','All','base','Block','All','ACCESS_OVERVIEW','View blocks of the Base module');
    xarRegisterPrivilege('ViewLoginItems','All','dynamicdata','Item','All','ACCESS_OVERVIEW','View some Dynamic Data items');
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
    xarRegisterPrivilege('ReadNonCore','All','empty','All','All','ACCESS_NONE','Read access only to none-core modules');
    xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Deny access to the Privileges module');
    xarRegisterPrivilege('DenyAdminPanels','All','adminpanels','All','All','ACCESS_NONE','Deny access to the AdminPanels module');
    xarRegisterPrivilege('DenyBlocks','All','blocks','All','All','ACCESS_NONE','Deny access to the Blocks module');
    xarRegisterPrivilege('DenyMail','All','mail','All','All','ACCESS_NONE','Deny access to the Mail module');
    xarRegisterPrivilege('DenyModules','All','modules','All','All','ACCESS_NONE','Deny access to the Modules module');
    xarRegisterPrivilege('DenyThemes','All','themes','All','All','ACCESS_NONE','Deny access to the Themes module');
//    xarRegisterPrivilege('DenyDynamicData','All','dynamicdata','All','All','ACCESS_NONE','Exclude access to the AdminPanels module');
    xarMakePrivilegeRoot('ReadNonCore');
    xarMakePrivilegeRoot('DenyPrivileges');
    xarMakePrivilegeRoot('DenyAdminPanels');
    xarMakePrivilegeRoot('DenyBlocks');
    xarMakePrivilegeRoot('DenyMail');
    xarMakePrivilegeRoot('DenyModules');
    xarMakePrivilegeRoot('DenyThemes');
//    xarMakePrivilegeRoot('DenyDynamicData');
    xarMakePrivilegeMember('ReadAccess','ReadNonCore');
    xarMakePrivilegeMember('DenyPrivileges','ReadNonCore');
    xarMakePrivilegeMember('DenyAdminPanels','ReadNonCore');
    xarMakePrivilegeMember('DenyBlocks','ReadNonCore');
    xarMakePrivilegeMember('DenyMail','ReadNonCore');
    xarMakePrivilegeMember('DenyModules','ReadNonCore');
    xarMakePrivilegeMember('DenyThemes','ReadNonCore');
//    xarMakePrivilegeMember('DenyDynamicData','ReadNonCore');
}

function installer_intranet_readaccess()
{
        xarRegisterPrivilege('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');
        xarMakePrivilegeRoot('ReadAccess');
}
?>
