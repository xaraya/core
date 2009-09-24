<?php
/**
 * Community configuration
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
 * @author Marc Lutolf
 */

$configuration_name = xarML('Community Site -- modules and privilege for semi-open access');

function installer_community_moduleoptions()
{
    return array(
        array('name' => "autolinks",            'regid' => 11),
        array('name' => "bloggerapi",           'regid' => 745),
        array('name' => "categories",           'regid' => 147),
        array('name' => "comments",             'regid' => 14),
        array('name' => "example",              'regid' => 36),
        array('name' => "hitcount",             'regid' => 177),
        array('name' => "ratings",              'regid' => 41),
        array('name' => "registration",         'regid' => 30205),
        array('name' => "search",               'regid' => 32),
        array('name' => "sniffer",              'regid' => 755),
        array('name' => "stats",                'regid' => 34),
        array('name' => "xmlrpcserver",         'regid' => 743),
        array('name' => "xmlrpcsystemapi",      'regid' => 744),
        array('name' => "xmlrpcvalidatorapi",   'regid' => 746),
        array('name' => "articles",             'regid' => 151)
    );
}

function installer_community_privilegeoptions()
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
function installer_community_configuration_load($args)
{
// load the privileges chosen

    if(in_array('p1',$args)) {
        installer_community_readaccess();
        xarAssignPrivilege('ReadAccess','Users');
    }
    else {
        installer_community_casualaccess();
        xarAssignPrivilege('CasualAccess','Users');
    }

    if(in_array('p2',$args)) {
        // Only do readaccess if we havent already done so
        if(!in_array('p1',$args)) installer_community_readaccess();
        installer_community_readnoncore();
        xarAssignPrivilege('ReadNonCore','Everybody');
   }
    else {
        if(in_array('p1',$args)) installer_community_casualaccess();
        xarAssignPrivilege('CasualAccess','Everybody');
    }

    return true;
}

function installer_community_casualaccess()
{
    xarRegisterPrivilege('CasualAccess','All','themes','Block','All','ACCESS_OVERVIEW','Minimal access to a site');
    // FIXME there is no registration module at this point
//    xarRegisterPrivilege('ViewRegistrationLogin','All','registration','Block','rlogin:Login:All','ACCESS_OVERVIEW','View the User Access block');
    xarRegisterPrivilege('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW','View the Login block');
    xarRegisterPrivilege('ViewBlocks','All','base','Block','All','ACCESS_OVERVIEW','View blocks of the Base module');
    xarRegisterPrivilege('ViewLoginItems','All','dynamicdata','Item','All','ACCESS_OVERVIEW','View some Dynamic Data items');
    xarRegisterPrivilege('ViewBlockItems','All','blocks','BlockItem','All','ACCESS_OVERVIEW','View block items in general');
//    xarMakePrivilegeMember('ViewRegistrationLogin','CasualAccess');
    xarMakePrivilegeMember('ViewLogin','CasualAccess');
    xarMakePrivilegeMember('ViewBlocks','CasualAccess');
    xarMakePrivilegeMember('ViewAuthsystem','CasualAccess');
    xarMakePrivilegeMember('ViewLoginItems','CasualAccess');
    xarMakePrivilegeMember('ViewBlockItems','CasualAccess');
}

function installer_community_readnoncore()
{
    xarRegisterPrivilege('ReadNonCore','All',null,'All','All','ACCESS_NONE','Read access only to none-core modules');
//    xarRegisterPrivilege('ViewRegistrationLogin','All','registration','Block','rlogin:Login:All','ACCESS_OVERVIEW','View the User Access block');
    xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Deny access to the Privileges module');
    xarRegisterPrivilege('DenyBlocks','All','blocks','All','All','ACCESS_NONE','Deny access to the Blocks module');
    xarRegisterPrivilege('DenyMail','All','mail','All','All','ACCESS_NONE','Deny access to the Mail module');
    xarRegisterPrivilege('DenyModules','All','modules','All','All','ACCESS_NONE','Deny access to the Modules module');
    xarRegisterPrivilege('DenyThemes','All','themes','All','All','ACCESS_NONE','Deny access to the Themes module');
    xarMakePrivilegeMember('ReadAccess','ReadNonCore');
    xarMakePrivilegeMember('ViewAuthsystem','ReadNonCore');
    xarMakePrivilegeMember('DenyPrivileges','ReadNonCore');
    xarMakePrivilegeMember('DenyBlocks','ReadNonCore');
    xarMakePrivilegeMember('DenyMail','ReadNonCore');
    xarMakePrivilegeMember('DenyModules','ReadNonCore');
    xarMakePrivilegeMember('DenyThemes','ReadNonCore');
//    xarMakePrivilegeMember('ViewRegistrationLogin','ReadNonCore');
}

function installer_community_readaccess()
{
        xarRegisterPrivilege('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');
}
?>
