<?php
/**
 * File: public.conf.php
 *
 * Configuration file for a public site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage installer
 * @author Marc Lutolf
 */

$configuration_name = 'Public Site';

    $options  = array(
    array(
        'item' => '1',
        'option' => 'true',
        'comment' => xarML('Registered users have moderate access to all modules of the site, i.e. they can submit and edit submitted items. If this option is not chosen registered users only have read access in non-core modules.')),
    array(
        'item' => '2',
        'option' => 'true',
        'comment' => xarML('Unregistered users have comment access to the non-core modules of the site, i.e. they can only submit items. If this option is not chosen unregistered users only have read access in non-core modules.'))
    );
$configuration_options = $options;

/**
 * Load the configuration
 *
 * @access public
 * @return boolean
 */
function installer_public_configuration_load($args)
{
// disable caching of module state in xarMod.php
    $GLOBALS['xarMod_noCacheState'] = true;

// the following needs to be done in any case
    xarModAPIFunc('modules','admin','regenerate');
    xarModAPIFunc('modules','admin','initialise',array('regid'=>11));     // autolinks
    xarModAPIFunc('modules','admin','activate',array('regid'=>11));
    xarModAPIFunc('modules','admin','initialise',array('regid'=>147));    // categories
    xarModAPIFunc('modules','admin','activate',array('regid'=>147));
    xarModAPIFunc('modules','admin','initialise',array('regid'=>14));     // comments
    xarModAPIFunc('modules','admin','activate',array('regid'=>14));
    xarModAPIFunc('modules','admin','initialise',array('regid'=>177));    // hitcount
    xarModAPIFunc('modules','admin','activate',array('regid'=>177));
    xarModAPIFunc('modules','admin','initialise',array('regid'=>41));     // ratings
    xarModAPIFunc('modules','admin','activate',array('regid'=>41));
    xarModAPIFunc('modules','admin','initialise',array('regid'=>32));     // search
    xarModAPIFunc('modules','admin','activate',array('regid'=>32));

    xarModAPIFunc('modules','admin','initialise',array('regid'=>151));    // articles
    xarModAPIFunc('modules','admin','activate',array('regid'=>151));
    xarModAPIFunc('modules','admin','initialise',array('regid'=>36));     // example
    xarModAPIFunc('modules','admin','activate',array('regid'=>36));

    xarModAPIFunc('modules','admin','initialise',array('regid'=>28));     // wiki
    xarModAPIFunc('modules','admin','activate',array('regid'=>28));
    xarModAPIFunc('modules','admin','initialise',array('regid'=>743));    // webservices
    xarModAPIFunc('modules','admin','activate',array('regid'=>743));

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
// now do the necessary loading for each item

    if(in_array(1,$args)) {
        installer_public_moderateaccess();
        xarAssignPrivilege('ModerateAccess','Users');
    }
    else {
        installer_public_readnoncore();
        xarAssignPrivilege('ReadNonCore','Users');
    }

    if(in_array(2,$args)) {
        installer_public_commentaccess();
        xarAssignPrivilege('CommentAccess','Everybody');
   }
    else {
        if(in_array(1,$args)) installer_public_readnoncore();
        xarAssignPrivilege('ReadNonCore','Everybody');
    }

    return true;
}

function installer_public_commentaccess()
{
    xarRegisterPrivilege('CommentAccess','All','All','All','All',ACCESS_COMMENT);
    xarMakePrivilegeRoot('CommentAccess');
}

function installer_public_moderateaccess()
{
    xarRegisterPrivilege('ModerateAccess','All','All','All','All',ACCESS_MODERATE);
    xarMakePrivilegeRoot('ModerateAccess');
}

function installer_public_readnoncore()
{
    xarRegisterPrivilege('ReadNonCore','All','empty','All','All',ACCESS_NONE,'Exclude access to the core modules');
    xarRegisterPrivilege('ReadAccess','All','All','All','All',ACCESS_READ,'The base privilege granting read access');
    xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All',ACCESS_NONE,'Exclude access to the Privileges modules');
    xarRegisterPrivilege('DenyAdminPanels','All','adminpanels','All','All',ACCESS_NONE,'Exclude access to the AdminPanels module');
    xarRegisterPrivilege('DenyBlocks','All','blocks','All','All',ACCESS_NONE,'Exclude access to the Blocks module');
    xarRegisterPrivilege('DenyMail','All','mail','All','All',ACCESS_NONE,'Exclude access to the Mail module');
    xarRegisterPrivilege('DenyModules','All','modules','All','All',ACCESS_NONE,'Exclude access to the Modules module');
    xarRegisterPrivilege('DenyThemes','All','themes','All','All',ACCESS_NONE,'Exclude access to the Themes module');
    xarRegisterPrivilege('DenyDynamicData','All','dynamicdata','All','All',ACCESS_NONE,'Exclude access to the AdminPanels module');
    xarMakePrivilegeRoot('ReadNonCore');
    xarMakePrivilegeRoot('ReadAccess');
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
?>
