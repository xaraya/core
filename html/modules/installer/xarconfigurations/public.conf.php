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
                        'comment' => xarML('Registered users have read access to all modules of the site.')
                        ),
                  array(
                        'item' => '2',
                        'option' => 'false',
                        'comment' => xarML('Unregistered users have read access to the non-core modules of the site. If this option is not chosen unregistered users see only the first page.')
                        )
                  );

                  if (xarMod_getState(151) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
                      $options[] = array('item' => 'm151',
                                         'option' => 'true',
                                         'comment' => xarML('Install the Articles module. Categories will also automatically be installed.')
                                         );
                  }

if(xarMod_getState(11) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] =array(
                      'item' => 'm11',
                      'option' => 'true',
                      'comment' => xarML('Install the Autolinks module.')
                      );
}

if(xarMod_getState(745) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] =array(
                      'item' => 'm745',
                      'option' => 'true',
                      'comment' => xarML('Install the Bloggerapi module.')
                      );
        }

if(xarMod_getState(147) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] =array(
                      'item' => 'm147',
                      'option' => 'true',
                      'comment' => xarML('Install the Categories module.')
                      );
}
if(xarMod_getState(14) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] =array(
                      'item' => 'm14',
                      'option' => 'true',
                      'comment' => xarML('Install the Comments module.')
                      );
}

if(xarMod_getState(36) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] =array(
                      'item' => 'm36',
                      'option' => 'true',
                      'comment' => xarML('Install the Example module.')
                      );
}

if(xarMod_getState(177) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] =array(
                      'item' => 'm177',
                      'option' => 'true',
                      'comment' => xarML('Install the Hitcount module.')
                      );
}

if(xarMod_getState(747) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] = array(
                       'item' => 'm747',
                       'option' => 'true',
                       'comment' => xarML('Install the Metaweblogapi module.')
                       );
}

if(xarMod_getState(41) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] = array(
                       'item' => 'm41',
                       'option' => 'true',
                       'comment' => xarML('Install the Ratings module.')
                       );
}

if(xarMod_getState(32) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] = array(
                       'item' => 'm32',
                       'option' => 'true',
                       'comment' => xarML('Install the Search module.')
                       );
}

if(xarMod_getState(748) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] = array(
                       'item' => 'm748',
                       'option' => 'true',
                       'comment' => xarML('Install the Soapserver module.')
                       );
}

if(xarMod_getState(28) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] = array(
                       'item' => 'm28',
                       'option' => 'true',
                       'comment' => xarML('Install the Wiki module.')
                       );
}

if(xarMod_getState(743) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] = array(
                       'item' => 'm743',
                       'option' => 'true',
                       'comment' => xarML('Install the Xmlrpcserver module.')
                       );
}

if(xarMod_getState(744) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] = array(
                       'item' => 'm744',
                       'option' => 'true',
                       'comment' => xarML('Install the Xmlrpcsystemapi module.')
                       );
}

if(xarMod_getState(746) != XARMOD_STATE_MISSING_FROM_UNINSTALLED) {
    $options[] = array(
                       'item' => 'm746',
                       'option' => 'true',
                       'comment' => xarML('Install the Xmlrpcvalidatorapi module.')
                       );
}

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
// now do the necessary loading for each item

    if(in_array(1,$args)) {
        installer_public_moderatenoncore();
        xarAssignPrivilege('ModerateNonCore','Users');
    }
    else {
        installer_public_readnoncore();
        xarAssignPrivilege('ReadNonCore','Users');
    }

    if(in_array(2,$args)) {
        installer_public_commentnoncore();
        xarAssignPrivilege('CommentNonCore','Everybody');
   }
    else {
        if(in_array(1,$args)) installer_public_readnoncore2();
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
    xarRegisterPrivilege('DenyAdminPanels','All','adminpanels','All','All','ACCESS_NONE','Deny access to the AdminPanels module');
    xarRegisterPrivilege('DenyBlocks','All','blocks','All','All','ACCESS_NONE','Deny access to the Blocks module');
    xarRegisterPrivilege('DenyMail','All','mail','All','All','ACCESS_NONE','Deny access to the Mail module');
    xarRegisterPrivilege('DenyModules','All','modules','All','All','ACCESS_NONE','Deny access to the Modules module');
    xarRegisterPrivilege('DenyThemes','All','themes','All','All','ACCESS_NONE','Deny access to the Themes module');
    xarMakePrivilegeRoot('ModerateNonCore');
    xarMakePrivilegeRoot('ModerateAccess');
    xarMakePrivilegeRoot('DenyPrivileges');
    xarMakePrivilegeRoot('DenyAdminPanels');
    xarMakePrivilegeRoot('DenyBlocks');
    xarMakePrivilegeRoot('DenyMail');
    xarMakePrivilegeRoot('DenyModules');
    xarMakePrivilegeRoot('DenyThemes');
    xarMakePrivilegeMember('ModerateAccess','ModerateNonCore');
    xarMakePrivilegeMember('DenyPrivileges','ModerateNonCore');
    xarMakePrivilegeMember('DenyAdminPanels','ModerateNonCore');
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
    xarRegisterPrivilege('DenyAdminPanels','All','adminpanels','All','All','ACCESS_NONE','Deny access to the AdminPanels module');
    xarRegisterPrivilege('DenyBlocks','All','blocks','All','All','ACCESS_NONE','Deny access to the Blocks module');
    xarRegisterPrivilege('DenyMail','All','mail','All','All','ACCESS_NONE','Deny access to the Mail module');
    xarRegisterPrivilege('DenyModules','All','modules','All','All','ACCESS_NONE','Deny access to the Modules module');
    xarRegisterPrivilege('DenyThemes','All','themes','All','All','ACCESS_NONE','Deny access to the Themes module');
    xarMakePrivilegeRoot('ReadNonCore');
    xarMakePrivilegeRoot('ReadAccess');
    xarMakePrivilegeRoot('DenyPrivileges');
    xarMakePrivilegeRoot('DenyAdminPanels');
    xarMakePrivilegeRoot('DenyBlocks');
    xarMakePrivilegeRoot('DenyMail');
    xarMakePrivilegeRoot('DenyModules');
    xarMakePrivilegeRoot('DenyThemes');
    xarMakePrivilegeMember('ReadAccess','ReadNonCore');
    xarMakePrivilegeMember('DenyPrivileges','ReadNonCore');
    xarMakePrivilegeMember('DenyAdminPanels','ReadNonCore');
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
    xarMakePrivilegeMember('DenyAdminPanels','ReadNonCore');
    xarMakePrivilegeMember('DenyBlocks','ReadNonCore');
    xarMakePrivilegeMember('DenyMail','ReadNonCore');
    xarMakePrivilegeMember('DenyModules','ReadNonCore');
    xarMakePrivilegeMember('DenyThemes','ReadNonCore');
}
?>