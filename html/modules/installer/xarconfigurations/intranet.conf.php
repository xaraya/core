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
        'comment' => xarML("Create an Oversight role that has full access but cannot change security. Password will be 'password'.")),
    array(
        'item' => 'm151',
        'option' => 'true',
        'comment' => xarML('Install the Articles module. Categories will also automatically be installed.')
    ),
    array(
        'item' => 'm11',
        'option' => 'true',
        'comment' => xarML('Install the Autolinks module.')
    ),
    array(
        'item' => 'm745',
        'option' => 'true',
        'comment' => xarML('Install the Bloggerapi module.')
    ),
    array(
        'item' => 'm147',
        'option' => 'true',
        'comment' => xarML('Install the Categories module.')
    ),
    array(
        'item' => 'm14',
        'option' => 'true',
        'comment' => xarML('Install the Comments module.')
    ),
    array(
        'item' => 'm36',
        'option' => 'true',
        'comment' => xarML('Install the Example module.')
    ),
/*    array(
        'item' => 'm177',
        'option' => 'true',
        'comment' => xarML('Install the Hitcount module.')
    ),
    array(
        'item' => 'm41',
        'option' => 'true',
        'comment' => xarML('Install the Ratings module.')
    ),
*/
    array(
        'item' => 'm747',
        'option' => 'true',
        'comment' => xarML('Install the Metaweblogapi module.')
    ),
    array(
        'item' => 'm32',
        'option' => 'true',
        'comment' => xarML('Install the Search module.')
    ),
    array(
        'item' => 'm748',
        'option' => 'true',
        'comment' => xarML('Install the Soapserver module.')
    ),
    array(
        'item' => 'm28',
        'option' => 'true',
        'comment' => xarML('Install the Wiki module.')
    ),
    array(
        'item' => 'm743',
        'option' => 'true',
        'comment' => xarML('Install the Xmlrpcserver module.')
    ),
    array(
        'item' => 'm744',
        'option' => 'true',
        'comment' => xarML('Install the Xmlrpcsystemapi module.')
    ),
    array(
        'item' => 'm746',
        'option' => 'true',
        'comment' => xarML('Install the Xmlrpcvalidatorapi module.')
    )
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
// disable caching of module state in xarMod.php
    $GLOBALS['xarMod_noCacheState'] = true;

// load the modules chosen
    xarModAPIFunc('modules','admin','regenerate');
    if(in_array('m11',$args)) {
        xarModAPIFunc('modules','admin','initialise',array('regid'=>11));     // autolinks
        xarModAPIFunc('modules','admin','activate',array('regid'=>11));
    }
    if(in_array('m147',$args)) {
        xarModAPIFunc('modules','admin','initialise',array('regid'=>147));    // categories
        xarModAPIFunc('modules','admin','activate',array('regid'=>147));
    }
    if(in_array('m14',$args)) {
        xarModAPIFunc('modules','admin','initialise',array('regid'=>14));     // comments
        xarModAPIFunc('modules','admin','activate',array('regid'=>14));
    }
    if(in_array('m177',$args)) {
        xarModAPIFunc('modules','admin','initialise',array('regid'=>177));    // hitcount
        xarModAPIFunc('modules','admin','activate',array('regid'=>177));
    }
    if(in_array('m41',$args)) {
        xarModAPIFunc('modules','admin','initialise',array('regid'=>41));     // ratings
        xarModAPIFunc('modules','admin','activate',array('regid'=>41));
    }
    if(in_array('m32',$args)) {
        xarModAPIFunc('modules','admin','initialise',array('regid'=>32));     // search
        xarModAPIFunc('modules','admin','activate',array('regid'=>32));
    }
    if(in_array('m151',$args)) {
        if(!in_array('m147',$args)) {
            xarModAPIFunc('modules','admin','initialise',array('regid'=>147));
            xarModAPIFunc('modules','admin','activate',array('regid'=>147));
        }
        xarModAPIFunc('modules','admin','initialise',array('regid'=>151));    // articles
        xarModAPIFunc('modules','admin','activate',array('regid'=>151));
    }
    if(in_array('m36',$args)) {
        xarModAPIFunc('modules','admin','initialise',array('regid'=>36));     // example
        xarModAPIFunc('modules','admin','activate',array('regid'=>36));
    }
    if(in_array('m28',$args)) {
        xarModAPIFunc('modules','admin','initialise',array('regid'=>28));     // wiki
        xarModAPIFunc('modules','admin','activate',array('regid'=>28));
    }
    if(in_array('m743',$args)) {
        xarModAPIFunc('modules','admin','initialise',array('regid'=>743));    // webservices
        xarModAPIFunc('modules','admin','activate',array('regid'=>743));
    }

    $content['marker'] = '[x]';                                           // create the user menu
    $content['displaymodules'] = 1;
    $content['content'] = '';

    // Load up database
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $blockGroupsTable = $tables['block_groups'];

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = 'left'";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'left' not found.");
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list ($leftBlockGroup) = $result->fields;

    $adminBlockId= xarModAPIFunc('blocks',
                                 'admin',
                                 'block_type_exists',
                                 array('modName'  => 'base',
                                       'blockType'=> 'menu'));

    if (!isset($adminBlockId) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    xarModAPIFunc('blocks','admin','create_instance',array('title' => 'Main Menu',
                                                           'type' => $adminBlockId,
                                                           'group' => $leftBlockGroup,
                                                           'template' => '',
                                                           'content' => serialize($content),
                                                           'state' => 2));

// load the privileges chosen

    installer_intranet_casualaccess();
    xarAssignPrivilege('CasualAccess','Everybody');

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
        if(!in_array(1,$args)) {
            xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All',ACCESS_NONE,'Exclude access to the Privileges modules');
            xarMakePrivilegeRoot('DenyPrivileges');
        }
        xarMakePrivilegeMember('DenyPrivileges','Oversight');
//        xarMakePrivilegeMember('Administration','Oversight');
   }

   return true;

}

function installer_intranet_oversightprivilege()
{
    xarRegisterPrivilege('Oversight','All','empty','All','All',ACCESS_NONE,'The privilege container for the Oversight group');
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
    xarRegisterPrivilege('CasualAccess','All','themes','Block','All',ACCESS_OVERVIEW,'Minimal access to a site');
    xarRegisterPrivilege('ViewLogin','All','roles','Block','login:Login:All',ACCESS_OVERVIEW,'View the Login block');
    xarRegisterPrivilege('ViewBlocks','All','base','Block','All',ACCESS_OVERVIEW,'View blocks of the Base module');
    xarRegisterPrivilege('ViewLoginItems','All','dynamicdata','Item','All',ACCESS_OVERVIEW,'View some Dynamic Data items');
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
    xarRegisterPrivilege('ReadNonCore','All','empty','All','All',ACCESS_NONE,'Read access only to none-core modules');
    xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All',ACCESS_NONE,'Deny access to the Privileges module');
    xarRegisterPrivilege('DenyAdminPanels','All','adminpanels','All','All',ACCESS_NONE,'Deny access to the AdminPanels module');
    xarRegisterPrivilege('DenyBlocks','All','blocks','All','All',ACCESS_NONE,'Deny access to the Blocks module');
    xarRegisterPrivilege('DenyMail','All','mail','All','All',ACCESS_NONE,'Deny access to the Mail module');
    xarRegisterPrivilege('DenyModules','All','modules','All','All',ACCESS_NONE,'Deny access to the Modules module');
    xarRegisterPrivilege('DenyThemes','All','themes','All','All',ACCESS_NONE,'Deny access to the Themes module');
//    xarRegisterPrivilege('DenyDynamicData','All','dynamicdata','All','All',ACCESS_NONE,'Exclude access to the AdminPanels module');
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
?>
