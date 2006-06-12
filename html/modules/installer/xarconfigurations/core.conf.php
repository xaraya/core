<?php
/**
 * Core configuration
 *
 * @package Installer
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Installer
 * @link http://xaraya.com/index.php/release/200.html
 */
/*
 * @author Marcel van der Boom <marcel@xaraya.com>
 */

$configuration_name = xarML('Core Xaraya install - minimal modules needed to run Xaraya');

function installer_core_moduleoptions()
{
    return array();
}

function installer_core_privilegeoptions()
{
    return array(
        array(
            'item' => 'p1',
            'option' => 'true',
            'comment' => xarML('Registered users have read access to all modules of the site.')
        ),
        array(
            'item' => 'p2',
            'option' => 'false',
            'comment' => xarML('Unregistered users have read access to the non-core modules of the site. If this option is not chosen unregistered users see only the first page.')
        ),

    );
}

/**
 * Load the configuration
 *
 * @access public
 * @return boolean
 */
function installer_core_configuration_load($args)
{
// load the privileges chosen

    if(in_array('p1',$args)) {
        installer_core_readaccess();
        xarAssignPrivilege('ReadAccess','Users');
    }
    else {
        installer_core_casualaccess();
        xarAssignPrivilege('CasualAccess','Users');
    }

    if(in_array('p2',$args)) {
        installer_core_readaccess();
        installer_core_readnoncore();
        xarAssignPrivilege('ReadNonCore','Everybody');
   }
    else {
        if(in_array('p1',$args)) installer_core_casualaccess();
        xarAssignPrivilege('CasualAccess','Everybody');
    }

    return true;
}

function installer_core_casualaccess()
{
    xarRegisterPrivilege('CasualAccess','All','themes','Block','All','ACCESS_OVERVIEW','Minimal access to a site');
    //xarRegisterPrivilege('ViewLogin','All','roles','Block','login:Login:All','ACCESS_OVERVIEW','View the Login block');
    xarRegisterPrivilege('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW','View the Login block');
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

function installer_core_readnoncore()
{
    xarRegisterPrivilege('ReadNonCore','All','empty','All','All','ACCESS_NONE','Read access only to none-core modules');
    xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Deny access to the Privileges module');
    xarRegisterPrivilege('DenyBlocks','All','blocks','All','All','ACCESS_NONE','Deny access to the Blocks module');
    xarRegisterPrivilege('DenyMail','All','mail','All','All','ACCESS_NONE','Deny access to the Mail module');
    xarRegisterPrivilege('DenyModules','All','modules','All','All','ACCESS_NONE','Deny access to the Modules module');
    xarRegisterPrivilege('DenyThemes','All','themes','All','All','ACCESS_NONE','Deny access to the Themes module');
    xarMakePrivilegeRoot('ReadNonCore');
    xarMakePrivilegeRoot('DenyPrivileges');
    xarMakePrivilegeRoot('DenyBlocks');
    xarMakePrivilegeRoot('DenyMail');
    xarMakePrivilegeRoot('DenyModules');
    xarMakePrivilegeRoot('DenyThemes');
    xarMakePrivilegeMember('ReadAccess','ReadNonCore');
    xarMakePrivilegeMember('DenyPrivileges','ReadNonCore');
    xarMakePrivilegeMember('DenyBlocks','ReadNonCore');
    xarMakePrivilegeMember('DenyMail','ReadNonCore');
    xarMakePrivilegeMember('DenyModules','ReadNonCore');
    xarMakePrivilegeMember('DenyThemes','ReadNonCore');
}

function installer_core_readaccess()
{
        xarRegisterPrivilege('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');
        xarMakePrivilegeRoot('ReadAccess');
}
?>
