<?php
/**
 * Public configuration
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Installer
 */
/*
 * Configuration for a public site
 * @author Marc Lutolf
 */

$configuration_name = xarML('Public Site - modules and privilege appropriate for open access');

function installer_public_moduleoptions() 
{
    return array(
        array('name' => "autolinks",            'regid' => 11),
        array('name' => "bloggerapi",           'regid' => 745),
        array('name' => "categories",           'regid' => 147),
        array('name' => "comments",             'regid' => 14),
        array('name' => "example",              'regid' => 36),
        array('name' => "hitcount",             'regid' => 177),
        array('name' => "ratings",              'regid' => 41),
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

function installer_public_privilegeoptions() 
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
                        )
                  );
}

/**
 * Load the configuration
 *
 * @access public
 * @return boolean
 */
function installer_public_configuration_load($args)
{
// now do the necessary loading for each item

    if(in_array('p1',$args)) {
        installer_public_moderatenoncore();
        xarAssignPrivilege('ModerateNonCore','Users');
    }
    else {
        installer_public_readnoncore();
        xarAssignPrivilege('ReadNonCore','Users');
    }

    if(in_array('p2',$args)) {
        installer_public_commentnoncore();
        xarAssignPrivilege('CommentNonCore','Everybody');
   }
    else {
        if(in_array('p1',$args)) installer_public_readnoncore2();
        xarAssignPrivilege('ReadNonCore','Everybody');
    }

    return true;
}

function installer_public_commentnoncore()
{
    xarRegisterPrivilege('CommentNonCore','All','empty','All','All','ACCESS_NONE','Read access only to none-core modules');
    xarRegisterPrivilege('CommentAccess','All','All','All','All','ACCESS_COMMENT','Comment access to all modules');
    xarMakePrivilegeRoot('CommentNonCore');
    xarMakePrivilegeRoot('CommentAccess');
    xarMakePrivilegeMember('CommentAccess','CommentNonCore');
    xarMakePrivilegeMember('DenyPrivileges','CommentNonCore');
    xarMakePrivilegeMember('DenyAdminPanels','CommentNonCore');
    xarMakePrivilegeMember('DenyBlocks','CommentNonCore');
    xarMakePrivilegeMember('DenyMail','CommentNonCore');
    xarMakePrivilegeMember('DenyModules','CommentNonCore');
    xarMakePrivilegeMember('DenyThemes','CommentNonCore');
}

function installer_public_moderatenoncore()
{
    xarRegisterPrivilege('ModerateNonCore','All','empty','All','All','ACCESS_NONE','Read access only to none-core modules');
    xarRegisterPrivilege('ModerateAccess','All','All','All','All','ACCESS_MODERATE','Moderate access to all modules');
    xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Deny access to the Privileges module');
    xarRegisterPrivilege('DenyBlocks','All','blocks','All','All','ACCESS_NONE','Deny access to the Blocks module');
    xarRegisterPrivilege('DenyMail','All','mail','All','All','ACCESS_NONE','Deny access to the Mail module');
    xarRegisterPrivilege('DenyModules','All','modules','All','All','ACCESS_NONE','Deny access to the Modules module');
    xarRegisterPrivilege('DenyThemes','All','themes','All','All','ACCESS_NONE','Deny access to the Themes module');
    xarMakePrivilegeRoot('ModerateNonCore');
    xarMakePrivilegeRoot('ModerateAccess');
    xarMakePrivilegeRoot('DenyPrivileges');

    xarMakePrivilegeRoot('DenyBlocks');
    xarMakePrivilegeRoot('DenyMail');
    xarMakePrivilegeRoot('DenyModules');
    xarMakePrivilegeRoot('DenyThemes');
    xarMakePrivilegeMember('ModerateAccess','ModerateNonCore');
    xarMakePrivilegeMember('DenyPrivileges','ModerateNonCore');
    xarMakePrivilegeMember('DenyBlocks','ModerateNonCore');
    xarMakePrivilegeMember('DenyMail','ModerateNonCore');
    xarMakePrivilegeMember('DenyModules','ModerateNonCore');
    xarMakePrivilegeMember('DenyThemes','ModerateNonCore');
}

function installer_public_readnoncore()
{
    xarRegisterPrivilege('ReadNonCore','All','empty','All','All','ACCESS_NONE','Read access only to none-core modules');
    xarRegisterPrivilege('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');
    xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Deny access to the Privileges module');
    xarRegisterPrivilege('DenyBlocks','All','blocks','All','All','ACCESS_NONE','Deny access to the Blocks module');
    xarRegisterPrivilege('DenyMail','All','mail','All','All','ACCESS_NONE','Deny access to the Mail module');
    xarRegisterPrivilege('DenyModules','All','modules','All','All','ACCESS_NONE','Deny access to the Modules module');
    xarRegisterPrivilege('DenyThemes','All','themes','All','All','ACCESS_NONE','Deny access to the Themes module');
    xarMakePrivilegeRoot('ReadNonCore');
    xarMakePrivilegeRoot('ReadAccess');
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
function installer_public_readnoncore2()
{
    xarRegisterPrivilege('ReadNonCore','All','empty','All','All','ACCESS_NONE','Read access only to none-core modules');
    xarRegisterPrivilege('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');
    xarMakePrivilegeMember('ReadAccess','ReadNonCore');
    xarMakePrivilegeMember('DenyPrivileges','ReadNonCore');
    xarMakePrivilegeMember('DenyBlocks','ReadNonCore');
    xarMakePrivilegeMember('DenyMail','ReadNonCore');
    xarMakePrivilegeMember('DenyModules','ReadNonCore');
    xarMakePrivilegeMember('DenyThemes','ReadNonCore');
}
?>
