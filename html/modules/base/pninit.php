<?php
// $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file: Paul Rosania
// Purpose of file:  Initialisation functions for base
// ----------------------------------------------------------------------

//Load Table Maintainance API
pnDBLoadTableMaintenanceAPI();
/**
 * Initialise the base module
 */
function base_init()
{
    // Get database information
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    $prefix = pnConfigGetVar('prefix');


    // Create tables

    // *_session_info
    $query = pnDBCreateTable($prefix . '_session_info',
                             array('pn_sessid'    => array('type'        => 'varchar',
                                                           'size'        => 32,
                                                           'null'        => false,
                                                           'default'     => '',
                                                           'primary_key' => true),
                                   'pn_ipaddr'    => array('type'     => 'varchar',
                                                           'size'     => 20,
                                                           'null'     => false,
                                                           'default'  => ''),
                                   'pn_firstused' => array('type'     => 'integer',
                                                           'null'     => false,
                                                           'default'  => '0'),
                                   'pn_lastused'  => array('type'     => 'integer',
                                                           'null'     => false,
                                                           'default'  => '0'),
                                   'pn_uid'       => array('type'     => 'integer',
                                                           'null'     => false,
                                                           'default'  => '0'),
                                   'pn_vars'      => array('type'     => 'blob')));
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // *_template_tags
    $query = pnDBCreateTable($prefix . '_template_tags',
                             array('pn_id'      => array('type'        => 'integer',
                                                         'null'        => false,
                                                         'default'     => '0',
                                                         'increment'   => true,
                                                         'primary_key' => true),
                                   'pn_name'    => array('type'    => 'varchar',
                                                         'size'    => 255,
                                                         'null'    => false,
                                                         'default' => ''),
                                   'pn_module'  => array('type'    => 'varchar',
                                                         'size'    => 255,
                                                         'default' => NULL),
                                   'pn_handler' => array('type'    => 'varchar',
                                                         'size'    => 255,
                                                         'null'    => false,
                                                         'default' => ''),
                                   'pn_data'    => array('type'    => 'text')));
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // *_languages (?)
    // Start Configuration system
    pnCoreInit(PNCORE_SYSTEM_CONFIGURATION);
    die('here');
    // Set config vars
    pnConfigSetVar('sitename', 'Your Site Name');
    pnConfigSetVar('slogan', 'Your slogan here');

    pnConfigSetVar('seclevel', 'Medium');
    pnConfigSetVar('secmeddays', 7);
    pnConfigSetVar('secinactivemins', 90);

    pnConfigSetVar('Version_Num', '0.80-pre');
    pnConfigSetVar('Version_ID', 'PostNuke');
    pnConfigSetVar('Version_Sub', 'adam_baum');

    pnConfigSetVar('Default_Theme', 'SeaBreeze');

    pnConfigSetVar('Site.DefaultModule', array('module'=>'base', 'type'=>'user', 'func'=>'main'));
    pnConfigSetVar('Site.TranslationsBackend', 'php');
    // FIXME: <marco> Temporary config vars, ask them at install time
    pnConfigSetVar('Site.MLSMode', 1);
    pnConfigSetVar('Site.Locale', 'en_US.iso-8859-1');
    pnConfigSetVar('Site.TimeZone', 'Europe/Rome');
    pnConfigSetVar('System.TimeZone', 'Europe/Rome');

    // Simple logger
    /*
    pnConfigSetVar('Site.Logger', 'simple');
    pnConfigSetVar('Site.Logger.Args', array('filename'=>'cache/logs/log.txt'));
    */
    // HTML logger
    /*
    pnConfigSetVar('Site.Logger', 'html');
    pnConfigSetVar('Site.Logger.Args', array('filename'=>'cache/logs/log.html'));
    */
    // Javascript Logger
    /*
    pnConfigSetVar('Site.Logger', 'javascript');
    */
    // Dummy logger
    pnConfigSetVar('Site.Logger', 'dummy');

    pnConfigSetVar('Site.Logger.Level', 1 /*PNLOG_LEVEL_DEBUG*/);

    // Install Modules module
    $seq_id = $dbconn->GenId($prefix.'_modules');
    $query = "INSERT INTO {$prefix}_modules (pn_id, pn_name, pn_type, pn_displayname, pn_description, pn_regid, pn_directory, pn_version, pn_admin_capable, pn_user_capable, pn_state) VALUES ('".$seq_id."', 'modules', 2, 'Modules', 'Module configuration', 1, 'modules', '2.02', 1, 0, 3);";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }


    include 'includes/pnBlocks.php';

    // coverups for missing funcs at this point (hack!)
    define ('ACCESS_ADMIN', 1);
    define ('ACCESS_EDIT', 3);
    define ('ACCESS_ADD', 2);
    function pnSecAuthAction() {return true;}
    function pnUserGetLang() {return 'eng';}

    // load modules admin API
    $res = pnModAPILoad('modules', 'admin');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    // load modules into *_modules table
    $res = pnModAPIFunc('modules', 'admin', 'regenerate');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    // initialize blocks module
    $res = pnModAPIFunc('modules', 'admin', 'initialise', array('regid' => pnModGetIDFromName('blocks')));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    $res = pnModAPIFunc('modules', 'admin', 'setstate', array('regid' => pnModGetIDFromName('blocks'),
                                                              'state' => _PNMODULE_STATE_ACTIVE));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    // initialize users module
    $res = pnModAPIFunc('modules', 'admin', 'initialise', array('regid' => pnModGetIDFromName('users')));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    $res = pnModAPIFunc('modules', 'admin', 'setstate', array('regid' => pnModGetIDFromName('blocks'),
                                                              'state' => _PNMODULE_STATE_ACTIVE));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    // initialize installer module
    $res = pnModAPIFunc('modules', 'admin', 'initialise', array('regid' => pnModGetIDFromName('installer')));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    $res = pnModAPIFunc('modules', 'admin', 'setstate', array('regid' => pnModGetIDFromName('blocks'),
                                                              'state' => _PNMODULE_STATE_ACTIVE));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    // Set up groups
    $query = "INSERT INTO {$prefix}_groups (pn_gid, pn_name) VALUES (1, 'Users');";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $query = "INSERT INTO {$prefix}_groups (pn_gid, pn_name) VALUES (2, 'Admins');";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $query = "INSERT INTO {$prefix}_group_membership (pn_gid, pn_uid) VALUES (1, 1);";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $query = "INSERT INTO {$prefix}_group_membership (pn_gid, pn_uid) VALUES (2, 2);";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Install basic permissions
    $query = "INSERT INTO {$prefix}_group_perms (pn_pid, pn_gid, pn_sequence, pn_realm, pn_component, pn_instance, pn_level, pn_bond) VALUES (1, 2, 1, 0, '.*', '.*', 800, 0);";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Set up default user properties, etc.

    // Fill language list(?)

    // Set up blocks

    // Create admin user (l/p: admin/password) and Anonymous
    $query = "INSERT INTO {$prefix}_users (pn_uid, pn_name, pn_uname, pn_email, pn_pass, pn_url, pn_auth_module) VALUES (1, '', 'Anonymous', '', '', '', '');";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $query = "INSERT INTO {$prefix}_users (pn_uid, pn_name, pn_uname, pn_email, pn_pass, pn_url, pn_auth_module) VALUES (2, 'Admin', 'Admin', 'none@none.com', '5f4dcc3b5aa765d61d8327deb882cf99', 'http://www.postnuke.com', 'authsystem');";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Register Block types
    pnBlockTypeRegister('base', 'finclude');
    pnBlockTypeRegister('base', 'html');
    pnBlockTypeRegister('base', 'menu');
    pnBlockTypeRegister('base', 'php');
    pnBlockTypeRegister('base', 'text');
    $res = pnBlockTypeRegister('base', 'thelang'); // FIXME <paul> should this be here???
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    // Register BL tags
    pnTplRegisterTag('base', 'var',
                     array(new pnTemplateAttribute('name', PN_TPL_STRING|PN_TPL_REQUIRED),
                           new pnTemplateAttribute('scope', PN_TPL_STRING|PN_TPL_OPTIONAL)),
                     'base_userapi_handleVarTag');

    pnTplRegisterTag('base', 'block',
                     array(new pnTemplateAttribute('name', PN_TPL_STRING|PN_TPL_REQUIRED),
                           new pnTemplateAttribute('module', PN_TPL_STRING|PN_TPL_REQUIRED),
                           new pnTemplateAttribute('title', PN_TPL_STRING|PN_TPL_OPTIONAL),
                           new pnTemplateAttribute('template', PN_TPL_STRING|PN_TPL_OPTIONAL),
                           new pnTemplateAttribute('type', PN_TPL_STRING|PN_TPL_OPTIONAL)),
                     'base_userapi_handleBlockTag');

    pnTplRegisterTag('base', 'blockgroup',
                     array(new pnTemplateAttribute('name', PN_TPL_STRING|PN_TPL_REQUIRED),
                           new pnTemplateAttribute('template', PN_TPL_STRING|PN_TPL_OPTIONAL)),
                     'base_userapi_handleBlockgroupTag');

    pnTplRegisterTag('base', 'module',
                     array(new pnTemplateAttribute('name', PN_TPL_STRING|PN_TPL_OPTIONAL),
                           new pnTemplateAttribute('main', PN_TPL_BOOLEAN|PN_TPL_OPTIONAL)),
                     'base_userapi_handleModuleTag');

    pnTplRegisterTag('base', 'if',
                     array(new pnTemplateAttribute('condition', PN_TPL_STRING|PN_TPL_REQUIRED)),
                     'base_userapi_handleIfTag');

    pnTplRegisterTag('base', 'elseif',
                     array(new pnTemplateAttribute('condition', PN_TPL_STRING|PN_TPL_REQUIRED)),
                     'base_userapi_handleElseifTag');

    pnTplRegisterTag('base', 'else', array(), 'base_userapi_handleElseTag');

    pnTplRegisterTag('base', 'loop',
                     array(new pnTemplateAttribute('name', PN_TPL_STRING|PN_TPL_REQUIRED),
                           new pnTemplateAttribute('key', PN_TPL_STRING|PN_TPL_OPTIONAL)),
                     'base_userapi_handleLoopTag');

    pnTplRegisterTag('base', 'set',
                     array(new pnTemplateAttribute('name', PN_TPL_STRING|PN_TPL_REQUIRED),
                           new pnTemplateAttribute('scope', PN_TPL_STRING|PN_TPL_OPTIONAL)),
                     'base_userapi_handleSetTag');

    pnTplRegisterTag('base', 'sec',
                     array(new pnTemplateAttribute('realm', PN_TPL_INTEGER|PN_TPL_OPTIONAL),
                           new pnTemplateAttribute('component', PN_TPL_STRING|PN_TPL_REQUIRED),
                           new pnTemplateAttribute('instance', PN_TPL_STRING|PN_TPL_REQUIRED),
                           new pnTemplateAttribute('level', PN_TPL_STRING|PN_TPL_REQUIRED)),
                     'base_userapi_handleSecTag');

    pnTplRegisterTag('base', 'baseurl',
                     array(),
                	 'base_userapi_handleBaseurlTag');

    pnTplRegisterTag('base', 'mlstring',
                     array(new pnTemplateAttribute('name', PN_TPL_STRING|PN_TPL_OPTIONAL)),
                     'base_userapi_handleMlstringTag');



    // Initialisation successful
    return true;
}

/**
 * upgrade the base module from an old version
 */
function base_upgrade($oldversion)
{
    return false;
}

/**
 * delete the base module
 */
function base_delete()
{
    return false;
}

?>
