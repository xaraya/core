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

    /*********************************************************************
    * Here we create non module associated tables
    *
    * prefix_config_vars   - system configuration variables
    * prefix_session_info  - Session table
    * prefix_template_tags - module template tag registry
    *********************************************************************/
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

    $query = pnDBCreateTable($tables['session_info'],$fields);

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

    $query = pnDBCreateTable($tables['config_vars'],$fields);

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

    $query = pnDBCreateIndex($tables['config_vars'],$index);

    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Set config vars
    pnConfigSetVar('sitename', 'Your Site Name');
    pnConfigSetVar('slogan', 'Your slogan here');

    pnConfigSetVar('seclevel', 'Medium');
    pnConfigSetVar('secmeddays', 7);
    pnConfigSetVar('secinactivemins', 90);

    pnConfigSetVar('Version_Num', 'Xaraya Pre - 1.0');
    pnConfigSetVar('Version_ID', 'Xaraya');
    pnConfigSetVar('Version_Sub', 'adam_baum');

    pnConfigSetVar('Default_Theme', 'Xaraya_Classic');

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

    $query = pnDBCreateTable($tables['template_tags'],$fields);

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
    
    $id_anonymous = $dbconn->GenId($tables['users']);
    $query = "INSERT INTO ".$tables['users']." VALUES ($id_anonymous ,'','Anonymous','','','','')";
    
    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    $id_anonymous = $dbconn->PO_Insert_ID($tables['users'],'pn_uid');

    $id_admin = $dbconn->GenId($tables['users']);
    $query = "INSERT INTO ".$tables['users']." VALUES ($id_admin,'Admin','Admin','none@none.com','5f4dcc3b5aa765d61d8327deb882cf99','http://www.postnuke.com','authsystem')";

    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    $id_admin = $dbconn->PO_Insert_ID($tables['users'],'pn_uid');

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

    $group_users = $dbconn->GenId($tables['groups']);
    $query = "INSERT INTO ".$tables['groups']." (pn_gid, pn_name) VALUES ($group_users, 'Users');";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    $group_users = $dbconn->PO_Insert_ID($tables['groups'],'pn_gid');

    $group_admin = $dbconn->GenId($tables['groups']);
    $query = "INSERT INTO ".$tables['groups']." (pn_gid, pn_name) VALUES ($group_admin, 'Admins');";
    $dbconn->Execute($query);
    $group_admin = $dbconn->PO_Insert_ID($tables['groups'],'pn_gid');

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $query = "INSERT INTO ".$tables['group_membership']." (pn_gid, pn_uid) VALUES ($group_users, $id_anonymous);";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $query = "INSERT INTO ".$tables['group_membership']." (pn_gid, pn_uid) VALUES ($group_admin, $id_admin);";
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
    
    $id = $dbconn->GenId($tables['group_perms']);
    $query = "INSERT INTO ".$tables['group_perms']."
             (pn_pid, pn_gid, pn_sequence, pn_realm, pn_component, pn_instance, pn_level, pn_bond)
              VALUES ($id, $group_admin, 1, 0, '.*', '.*', 800, 0);";
              //VALUES (1, 2, 1, 0, '.*', '.*', 800, 0);";

    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $id = $dbconn->GenId($tables['user_perms']);
    $query = "INSERT INTO ".$tables['user_perms']." VALUES ($id,-1,1,0,'.*','.*',200,0)";
    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $id = $dbconn->GenId($tables['user_perms']);
    $query = "INSERT INTO ".$tables['user_perms']." VALUES ($id,$id_admin,0,0,'.*','.*',800,0)";
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

    // Install Modules module
    $seqId = $dbconn->GenId($tables['modules']);
    $query = "INSERT INTO " . $tables['modules'] ."
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
    $query = "INSERT INTO " .$tables['system/module_states'] ." (pn_regid, pn_state
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
    $seqId = $dbconn->GenId($tables['modules']);
    $query = "INSERT INTO " . $tables['modules'] ."
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
    $query = "INSERT INTO " .$tables['system/module_states'] ." (pn_regid, pn_state
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
    $seqId = $dbconn->GenId($tables['modules']);
    $query = "INSERT INTO " . $tables['modules'] ."
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
    $query = "INSERT INTO " .$tables['system/module_states'] ." (pn_regid, pn_state
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
    $res = pnModAPIFunc('modules', 'admin', 'regenerate');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    // initialize blocks module
    $res = pnModAPIFunc('modules', 'admin', 'initialise', array('regid' => pnModGetIDFromName('blocks')));

    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    $res1 = pnModAPIFunc('modules', 'admin', 'activate', array('regid' => pnModGetIDFromName('blocks')));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
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
    $res = pnBlockTypeRegister('base', 'finclude');

    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
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
