<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Paul Rosania
// Purpose of file:  Initialisation functions for base
// ----------------------------------------------------------------------

//Load Table Maintainance API
pnDBLoadTableMaintenanceAPI();

/**
 * Initialise the base module
 *
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function base_init()
{
    // Get database information
    list($dbconn) = pnDBGetConn();
    $tables = pnDBGetTables();

    $systemPrefix = pnDBGetSystemTablePrefix();


    /*********************************************************************
    * Here we create non module associated tables
    *
    * prefix_config_vars   - system configuration variables
    * prefix_allowed_vars  - Allowed system variable (IE HTML, dirty words)
    * prefix_session_info  - Session table
    * prefix_template_tags - module template tag registry
    *********************************************************************/
    $sessionInfoTable = $systemPrefix . '_session_info';
    /*********************************************************************
    * CREATE TABLE pn_session_info (
    *  pn_sessid varchar(32) NOT NULL default '',
    *  pn_ipaddr varchar(20) NOT NULL default '',
    *  pn_firstused int(11) NOT NULL default '0',
    *  pn_lastused int(11) NOT NULL default '0',
    *  pn_uid int(11) NOT NULL default '0',
    *  pn_vars blob,
    *  pn_remembersess int(1) default '0',
    *  PRIMARY KEY  (pn_sessid)
    * )
    *********************************************************************/
    $fields = array(
    'pn_sessid'       => array('type'=>'varchar','size'=>32,'null'=>false,'primary_key'=>true),
    'pn_ipaddr'       => array('type'=>'varchar','size'=>20,'null'=>false),
    'pn_firstused'    => array('type'=>'integer','null'=>false,'default'=>'0'),
    'pn_lastused'     => array('type'=>'integer','null'=>false,'default'=>'0'),
    'pn_uid'          => array('type'=>'integer','null'=>false,'default'=>'0'),
    'pn_vars'         => array('type'=>'blob'),
    'pn_remembersess' => array('type'=>'integer','size'=>'tiny','default'=>'0')
    );

    $query = pnDBCreateTable($sessionInfoTable,$fields);

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        // we can't do this here !!!!!
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    /*********************************************************************
    * Here we install the configuration table and set some default
    * configuration variables
    *********************************************************************/
    $configVarsTable  = $systemPrefix . '_config_vars';
    /*********************************************************************
    * CREATE TABLE pn_config_vars (
    *  pn_id int(11) unsigned NOT NULL auto_increment,
    *  pn_name varchar(64) NOT NULL default '',
    *  pn_value longtext,
    *  PRIMARY KEY  (pn_id),
    *  KEY pn_name (pn_name)
    * )
    *********************************************************************/

    $fields = array(
    'pn_id'    => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'pn_name'  => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_value' => array('type'=>'text','size'=>'long')
    );

    $query = pnDBCreateTable($configVarsTable,$fields);
    $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    // FIXME: should be unique or not?
    $index = array('name'   => 'pn_name',
                   'fields' => array('pn_name'));

    $query = pnDBCreateIndex($configVarsTable,$index);

    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    $config_id = $dbconn->GenId($configVarsTable);
    $query = "INSERT INTO $configVarsTable VALUES ($config_id,'Site.Core.AllowableHTML','a:25:{s:3:\"!--\";s:1:\"2\";s:1:\"a\";s:1:\"2\";s:1:\"b\";s:1:\"2\";s:10:\"blockquote\";s:1:\"2\";s:2:\"br\";s:1:\"2\";s:6:\"center\";s:1:\"2\";s:3:\"div\";s:1:\"2\";s:2:\"em\";s:1:\"2\";s:4:\"font\";i:0;s:2:\"hr\";s:1:\"2\";s:1:\"i\";s:1:\"2\";s:3:\"img\";i:0;s:2:\"li\";s:1:\"2\";s:7:\"marquee\";i:0;s:2:\"ol\";s:1:\"2\";s:1:\"p\";s:1:\"2\";s:3:\"pre\";s:1:\"2\";s:4:\"span\";i:0;s:6:\"strong\";s:1:\"2\";s:2:\"tt\";s:1:\"2\";s:2:\"ul\";s:1:\"2\";s:5:\"table\";s:1:\"2\";s:2:\"td\";s:1:\"2\";s:2:\"th\";s:1:\"2\";s:2:\"tr\";s:1:\"2\";}')";
    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    pnCoreInit(PNCORE_SYSTEM_CONFIGURATION);
    /****************************************************************
    * Set System Configuration Variables
    *****************************************************************/
    pnConfigSetVar('System.Core.TimeZone', 'Europe/Rome');
    pnConfigSetVar('System.Core.VersionNum', 'Xaraya Pre - 1.0');
    pnConfigSetVar('System.Core.VersionId', 'Xaraya');
    pnConfigSetVar('System.Core.VersionSub', 'adam_baum');

    /*****************************************************************
    * Set site configuration variables
    ******************************************************************/
    pnConfigSetVar('Site.Core.TimeZone', 'Europe/Rome');
    pnConfigSetVar('Site.Core.SiteName', 'Your Site Name');
    pnConfigSetVar('Site.Core.Slogan', 'Your slogan here');
    pnConfigSetVar('Site.Core.EnableShortURLsSupport', 'false');
    // FIXME: which to use ... one config var.. or 3? it seemeth that one is better..
    pnConfigSetVar('Site.Core.DefaultModule', array('module'=>'base', 'type'=>'user', 'func'=>'main'));
    pnConfigSetVar('Site.Core.DefaultModuleName', 'base');
    pnConfigSetVar('Site.Core.DefaultModuleType', 'user');
    pnConfigSetVar('Site.Core.DefaultModuleFunction', 'main');

    pnConfigSetVar('Site.Session.SecurityLevel', 'Medium');
    pnConfigSetVar('Site.Session.Duration', 7);
    pnConfigSetVar('Site.Session.InactivityTimeout', 90);
    pnConfigSetVar('Site.Session.EnableIntranetMode', 90);
    pnConfigSetVar('Site.BL.DefaultTheme', 'installer');
    pnConfigSetVar('Site.BL.ThemesDirectory','themes');
    pnConfigSetVar('Site.MLS.TranslationsBackend', 'php');
    // FIXME: <marco> Temporary config vars, ask them at install time
    pnConfigSetVar('Site.MLS.MLSMode', 1);
    pnConfigSetVar('Site.MLS.DefaultLocale', 'en_US.iso-8859-1');
    pnConfigSetVar('Site.MLS.AllowedLocales','en_US.iso-8858-1');
    pnConfigSetVar('Site.User.AuthenticationModules','authsystem');

    // Dummy logger
    pnConfigSetVar('Site.Log.LoggerName', 'dummy');
    pnConfigSetVar('Site.Log.LoggerArgs', '');
    pnConfigSetVar('Site.Log.LogLevel', 1 /*PNLOG_LEVEL_DEBUG*/);

    /*********************************************************************
    * Here we install the allowed vars table and fill it with some
    * standard config values.
    *********************************************************************/
    $configVarsTable  = $systemPrefix . '_allowed_vars';
    /*********************************************************************
    * CREATE TABLE pn_allowed_vars (
    *  pn_id int(11) unsigned NOT NULL auto_increment,
    *  pn_name varchar(64) NOT NULL default '',
    *  pn_type varchar(64) NOT NULL default '',
    *  PRIMARY KEY  (pn_id),
    *  KEY pn_name (pn_name)
    * )
    *********************************************************************/

    $fields = array(
    'pn_id'    => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'pn_name'  => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_type' => array('type'=>'varchar','size'=>64,'null'=>false)
    );

    $query = pnDBCreateTable($configVarsTable,$fields);
    $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    // FIXME: should be unique or not?
    $index = array('name'   => 'pn_name',
                   'fields' => array('pn_name'));

    $query = pnDBCreateIndex($configVarsTable,$index);

    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $templateTagsTable = $systemPrefix . '_template_tags';
    /*********************************************************************
    * CREATE TABLE pn_template_tags (
    *  pn_id int(11) NOT NULL auto_increment,
    *  pn_name varchar(255) NOT NULL default '',
    *  pn_module varchar(255) default NULL,
    *  pn_handler varchar(255) NOT NULL default '',
    *  pn_data text,
    *  PRIMARY KEY  (pn_id)
    * )
    *********************************************************************/
    $fields = array(
    'pn_id'      => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'pn_name'    => array('type'=>'varchar','size'=>255,'null'=>false),
    'pn_module'  => array('type'=>'varchar','size'=>255,'null'=>true),
    'pn_handler' => array('type'=>'varchar','size'=>255,'null'=>false),
    'pn_data'    => array('type'=>'text')
     );

    $query = pnDBCreateTable($templateTagsTable,$fields);

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    // Load in installer API
    pnInstallAPILoad('installer','admin');
    
    /****************************************************************
    * Install users module and set up default users
    ****************************************************************/
    $res = pnInstallAPIFunc('installer',
                            'admin',
                            'initialise',
                            array('directory' => 'users',
                                  'initfunc'  => 'init'));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return NULL;
    }

    $usersTable = $systemPrefix . '_users';
    $id_anonymous = $dbconn->GenId($usersTable);
    $query = "INSERT INTO $usersTable VALUES ($id_anonymous ,'','Anonymous','','','','')";
    
    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    $id_anonymous = $dbconn->PO_Insert_ID($usersTable,'pn_uid');

    $id_admin = $dbconn->GenId($usersTable);
    $query = "INSERT INTO $usersTable VALUES ($id_admin,'Admin','Admin','none@none.com','5f4dcc3b5aa765d61d8327deb882cf99','http://www.postnuke.com','authsystem')";

    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    $id_admin = $dbconn->PO_Insert_ID($usersTable,'pn_uid');

    /***************************************************************
    * Install groups module and setup default groups
    ***************************************************************/
    $res = pnInstallAPIFunc('installer',
                            'admin',
                            'initialise',
                            array('directory' => 'groups',
                                  'initfunc'  => 'init'));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return NULL;
    }

    $groupsTable = $systemPrefix . '_groups';
    $group_users = $dbconn->GenId($groupsTable);
    $query = "INSERT INTO $groupsTable (pn_gid, pn_name) VALUES ($group_users, 'Users');";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $group_users = $dbconn->PO_Insert_ID($groupsTable,'pn_gid');

    $group_admin = $dbconn->GenId($groupsTable);
    $query = "INSERT INTO $groupsTable (pn_gid, pn_name) VALUES ($group_admin, 'Admins');";
    $dbconn->Execute($query);
    $group_admin = $dbconn->PO_Insert_ID($groupsTable,'pn_gid');

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $groupMembershipTable = $systemPrefix . '_group_membership';

    $query = "INSERT INTO $groupMembershipTable (pn_gid, pn_uid) VALUES ($group_users, $id_anonymous);";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $query = "INSERT INTO $groupMembershipTable (pn_gid, pn_uid) VALUES ($group_admin, $id_admin);";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    /**************************************************************
    * Install permissions module and setup default permissions
    **************************************************************/
    $res = pnInstallAPIFunc('installer',
                            'admin',
                            'initialise',
                            array('directory' => 'permissions',
                                  'initfunc'  => 'init'));

    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return NULL;
    }
    $groupPermsTable = $systemPrefix . '_group_perms';

    $id = $dbconn->GenId($groupPermsTable);
    $query = "INSERT INTO $groupPermsTable
             (pn_pid, pn_gid, pn_sequence, pn_realm, pn_component, pn_instance, pn_level, pn_bond)
              VALUES ($id, $group_admin, 1, 0, '.*', '.*', 800, 0);";

    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $userPermsTable = $systemPrefix . '_user_perms';

    $id = $dbconn->GenId($userPermsTable);
    $query = "INSERT INTO $userPermsTable VALUES ($id,-1,1,0,'.*','.*',200,0)";
    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $id = $dbconn->GenId($userPermsTable);
    $query = "INSERT INTO $userPermsTable VALUES ($id,$id_admin,0,0,'.*','.*',800,0)";
    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    /**************************************************************
    * Install modules table and insert the modules module
    **************************************************************/
    $res = pnInstallAPIFunc('installer',
                            'admin',
                            'initialise',
                            array('directory' => 'modules',
                                  'initfunc'  => 'init'));

    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return NULL;
    }
    $modulesTable = $systemPrefix .'_modules';
    $systemModuleStatesTable = $systemPrefix .'_module_states';

    // Install Modules module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (pn_id, pn_name, pn_regid, pn_directory, pn_version, pn_mode, pn_class, pn_category, pn_admin_capable, pn_user_capable
     ) VALUES ($seqId, 'modules', 1, 'modules', '2.02', 1, 'Core Admin', 'Global', 1, 0)";

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Set Modules Module to active
    $query = "INSERT INTO $systemModuleStatesTable (pn_regid, pn_state
              ) VALUES (1, 3)";

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Install authsystem module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (pn_id, pn_name, pn_regid, pn_directory, pn_version, pn_mode, pn_class, pn_category, pn_admin_capable, pn_user_capable
     ) VALUES ($seqId, 'authsystem', 42, 'authsystem', '0.91', 1, 'Core Utility', 'Global', 0, 0)";

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Set authsystem to active
    $query = "INSERT INTO $systemModuleStatesTable (pn_regid, pn_state
              ) VALUES (42, 3)";

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Install installer module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (pn_id, pn_name, pn_regid, pn_directory, pn_version, pn_mode, pn_class, pn_category, pn_admin_capable, pn_user_capable
     ) VALUES ('".$seqId."', 'installer', 200, 'installer', '1.0', 1, 'Core Utility', 'Global', 1, 0)";

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Set installer to active
    $query = "INSERT INTO $systemModuleStatesTable (pn_regid, pn_state
              ) VALUES (200, 3)";

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    // Fill language list(?)

    // Initialisation successful
    return true;
}

/**
 * Activate the base module
 *
 * @access public
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function base_activate()
{

    // Set up default user properties, etc.

    // load modules admin API
    pnModAPILoad('modules', 'admin');

    // load modules into *_modules table
    if (!pnModAPIFunc('modules', 'admin', 'regenerate')) {
        return NULL;
    }

    // Activate the groups module
    $res = pnModAPIFunc('modules', 'admin', 'setstate', array('regid' => pnModGetIDFromName('groups'),
                                                              'state' => PNMOD_STATE_INACTIVE));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    $res = pnModAPIFunc('modules', 'admin', 'activate', array('regid' => pnModGetIDFromName('groups')));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    // Activate the permissions module
    $res = pnModAPIFunc('modules', 'admin', 'setstate', array('regid' => pnModGetIDFromName('permissions'),
                                                              'state' => PNMOD_STATE_INACTIVE));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    $res = pnModAPIFunc('modules', 'admin', 'activate', array('regid' => pnModGetIDFromName('permissions')));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    // initialize blocks module
    $modRegId = pnModGetIDFromName('blocks');

    if (!pnModAPIFunc('modules', 'admin', 'initialise', array('regid' => $modRegId))) {
        return NULL;
    }

    if (!pnModAPIFunc('modules', 'admin', 'activate', array('regid' => $modRegId))) {
        return NULL;
    }

    // initialize & activate adminpanels module
    $res = pnModAPIFunc('modules', 'admin', 'initialise', array('regid' => pnModGetIDFromName('adminpanels')));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    $res = pnModAPIFunc('modules', 'admin', 'activate', array('regid' => pnModGetIDFromName('adminpanels')));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    // Activate the user's module
    $res = pnModAPIFunc('modules', 'admin', 'setstate', array('regid' => pnModGetIDFromName('users'),
                                                              'state' => PNMOD_STATE_INACTIVE));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    $res = pnModAPIFunc('modules', 'admin', 'activate', array('regid' => pnModGetIDFromName('users')));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    //initialise and activate base module by setting the states
    $res = pnModAPIFunc('modules', 'admin', 'setstate', array('regid' => pnModGetIDFromName('base'),                                                          'state' => PNMOD_STATE_INACTIVE));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    $res = pnModAPIFunc('modules', 'admin', 'setstate', array('regid' => pnModGetIDFromName('base'),
                                                              'state' => PNMOD_STATE_ACTIVE));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    // initialize installer module

    // Register Block types
    if (!pnBlockTypeRegister('base', 'finclude')) {
        return NULL;
    }

    $res = pnBlockTypeRegister('base', 'html');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    $res = pnBlockTypeRegister('base', 'menu');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    $res = pnBlockTypeRegister('base', 'php');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    $res = pnBlockTypeRegister('base', 'text');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    $res = pnBlockTypeRegister('base', 'thelang'); // FIXME <paul> should this be here???
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    if (pnVarIsCached('Mod.BaseInfos', 'blocks')) {
        pnVarDelCached('Mod.BaseInfos', 'blocks');
    }
    // Create default block groups/instances
    $res = pnModAPILoad('blocks', 'admin');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    $res = pnModAPIFunc('blocks', 'admin', 'create_group', array('name' => 'left'));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    $res = pnModAPIFunc('blocks', 'admin', 'create_group', array('name'     => 'right',
                                                                 'template' => 'right'));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    return true;
}
/**
 * Upgrade the base module from an old version
 *
 * @param oldVersion
 * @returns bool
 */
function base_upgrade($oldVersion)
{
    return false;
}

/**
 * Delete the base module
 *
 * @param none
 * @returns bool
 */
function base_delete()
{
    return false;
}

?>
