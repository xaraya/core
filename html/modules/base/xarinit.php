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
xarDBLoadTableMaintenanceAPI();

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
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $systemPrefix = xarDBGetSystemTablePrefix();


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
    * CREATE TABLE xar_session_info (
    *  xar_sessid varchar(32) NOT NULL default '',
    *  xar_ipaddr varchar(20) NOT NULL default '',
    *  xar_firstused int(11) NOT NULL default '0',
    *  xar_lastused int(11) NOT NULL default '0',
    *  xar_uid int(11) NOT NULL default '0',
    *  xar_vars blob,
    *  xar_remembersess int(1) default '0',
    *  PRIMARY KEY  (xar_sessid)
    * )
    *********************************************************************/
    $fields = array(
    'xar_sessid'       => array('type'=>'varchar','size'=>32,'null'=>false,'primary_key'=>true),
    'xar_ipaddr'       => array('type'=>'varchar','size'=>20,'null'=>false),
    'xar_firstused'    => array('type'=>'integer','null'=>false,'default'=>'0'),
    'xar_lastused'     => array('type'=>'integer','null'=>false,'default'=>'0'),
    'xar_uid'          => array('type'=>'integer','null'=>false,'default'=>'0'),
    'xar_vars'         => array('type'=>'blob'),
    'xar_remembersess' => array('type'=>'integer','size'=>'tiny','default'=>'0')
    );

    $query = xarDBCreateTable($sessionInfoTable,$fields);

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        // we can't do this here !!!!!
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    /*********************************************************************
    * Here we install the configuration table and set some default
    * configuration variables
    *********************************************************************/
    $configVarsTable  = $systemPrefix . '_config_vars';
    /*********************************************************************
    * CREATE TABLE xar_config_vars (
    *  xar_id int(11) unsigned NOT NULL auto_increment,
    *  xar_name varchar(64) NOT NULL default '',
    *  xar_value longtext,
    *  PRIMARY KEY  (xar_id),
    *  KEY xar_name (xar_name)
    * )
    *********************************************************************/

    $fields = array(
    'xar_id'    => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'xar_name'  => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_value' => array('type'=>'text','size'=>'long')
    );

    $query = xarDBCreateTable($configVarsTable,$fields);
    $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    // FIXME: should be unique or not?
    $index = array('name'   => 'xar_name',
                   'fields' => array('xar_name'));

    $query = xarDBCreateIndex($configVarsTable,$index);

    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    $config_id = $dbconn->GenId($configVarsTable);
    $query = "INSERT INTO $configVarsTable VALUES ($config_id,'Site.Core.AllowableHTML','a:25:{s:3:\"!--\";s:1:\"2\";s:1:\"a\";s:1:\"2\";s:1:\"b\";s:1:\"2\";s:10:\"blockquote\";s:1:\"2\";s:2:\"br\";s:1:\"2\";s:6:\"center\";s:1:\"2\";s:3:\"div\";s:1:\"2\";s:2:\"em\";s:1:\"2\";s:4:\"font\";i:0;s:2:\"hr\";s:1:\"2\";s:1:\"i\";s:1:\"2\";s:3:\"img\";i:0;s:2:\"li\";s:1:\"2\";s:7:\"marquee\";i:0;s:2:\"ol\";s:1:\"2\";s:1:\"p\";s:1:\"2\";s:3:\"pre\";s:1:\"2\";s:4:\"span\";i:0;s:6:\"strong\";s:1:\"2\";s:2:\"tt\";s:1:\"2\";s:2:\"ul\";s:1:\"2\";s:5:\"table\";s:1:\"2\";s:2:\"td\";s:1:\"2\";s:2:\"th\";s:1:\"2\";s:2:\"tr\";s:1:\"2\";}')";
    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    xarCoreInit(XARCORE_SYSTEM_CONFIGURATION);
    /****************************************************************
    * Set System Configuration Variables
    *****************************************************************/
    xarConfigSetVar('System.Core.TimeZone', 'Europe/Rome');
    xarConfigSetVar('System.Core.VersionNum', 'Xaraya Pre - 1.0');
    xarConfigSetVar('System.Core.VersionId', 'Xaraya');
    xarConfigSetVar('System.Core.VersionSub', 'adam_baum');

    /*****************************************************************
    * Set site configuration variables
    ******************************************************************/
    xarConfigSetVar('Site.Core.TimeZone', 'Europe/Rome');
    xarConfigSetVar('Site.Core.SiteName', 'Your Site Name');
    xarConfigSetVar('Site.Core.Slogan', 'Your slogan here');
    xarConfigSetVar('Site.Core.EnableShortURLsSupport', 'false');
    // FIXME: which to use ... one config var.. or 3? it seemeth that one is better..
    xarConfigSetVar('Site.Core.DefaultModule', array('module'=>'base', 'type'=>'user', 'func'=>'main'));
    xarConfigSetVar('Site.Core.DefaultModuleName', 'base');
    xarConfigSetVar('Site.Core.DefaultModuleType', 'user');
    xarConfigSetVar('Site.Core.DefaultModuleFunction', 'main');

    xarConfigSetVar('Site.Session.SecurityLevel', 'Medium');
    xarConfigSetVar('Site.Session.Duration', 7);
    xarConfigSetVar('Site.Session.InactivityTimeout', 90);
    xarConfigSetVar('Site.Session.EnableIntranetMode', 90);
    xarConfigSetVar('Site.BL.DefaultTheme', 'installer');
    xarConfigSetVar('Site.BL.ThemesDirectory','themes');
    xarConfigSetVar('Site.MLS.TranslationsBackend', 'php');
    // FIXME: <marco> Temporary config vars, ask them at install time
    xarConfigSetVar('Site.MLS.MLSMode', 1);
    xarConfigSetVar('Site.MLS.DefaultLocale', 'en_US.iso-8859-1');
    xarConfigSetVar('Site.MLS.AllowedLocales','en_US.iso-8858-1');
    xarConfigSetVar('Site.User.AuthenticationModules','authsystem');

    // Dummy logger
    xarConfigSetVar('Site.Log.LoggerName', 'dummy');
    xarConfigSetVar('Site.Log.LoggerArgs', '');
    xarConfigSetVar('Site.Log.LogLevel', 1 /*XARLOG_LEVEL_DEBUG*/);

    /*********************************************************************
    * Here we install the allowed vars table and fill it with some
    * standard config values.
    *********************************************************************/
    $configVarsTable  = $systemPrefix . '_allowed_vars';
    /*********************************************************************
    * CREATE TABLE xar_allowed_vars (
    *  xar_id int(11) unsigned NOT NULL auto_increment,
    *  xar_name varchar(64) NOT NULL default '',
    *  xar_type varchar(64) NOT NULL default '',
    *  PRIMARY KEY  (xar_id),
    *  KEY xar_name (xar_name)
    * )
    *********************************************************************/

    $fields = array(
    'xar_id'    => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'xar_name'  => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_type' => array('type'=>'varchar','size'=>64,'null'=>false)
    );

    $query = xarDBCreateTable($configVarsTable,$fields);
    $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    // FIXME: should be unique or not?
    $index = array('name'   => 'i_xar_name',
                   'fields' => array('xar_name'));

    $query = xarDBCreateIndex($configVarsTable,$index);

    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Insert Allowed Vars
    $htmltags = array('!--',
                  'a',
                  'abbr',
                  'acronym',
                  'address',
		          'applet',
		          'area',
                  'b',
		          'base',
		          'basefont',
		          'bdo',
                  'big',
                  'blockquote',
                  'br',
		          'button',
                  'caption',
                  'center',
                  'cite',
                  'code',
		          'col',
		          'colgroup',
		          'del',
                  'dfn',
		          'dir',
                  'div',
                  'dl',
                  'dd',
                  'dt',
                  'em',
                  'embed',
		          'fieldset',
                  'font',
		          'form',
                  'h1',
                  'h2',
                  'h3',
                  'h4',
                  'h5',
                  'h6',
                  'hr',
                  'i',
                  'iframe',
                  'img',
		          'input',
		          'ins',
		          'kbd',
		          'label',
		          'legend',
                  'li',
		          'map',
                  'marquee',
		          'menu',
		          'nobr',
                  'object',
                  'ol',
		          'optgroup',
		          'option',
                  'p',
                  'param',
                  'pre',
                  'q',
                  's',
                  'samp',
                  'script',
		          'select',
                  'small',
                  'span',
                  'strike',
                  'strong',
                  'sub',
                  'sup',
                  'table',
		          'tbody',
                  'td',
		          'textarea',
		          'tfoot',
                  'th',
		          'thead',
                  'tr',
		          'tt',
                  'u',
		          'ul',
		          'var');
    
    foreach ($htmltags as $htmltag) {
        $id_configvar = $dbconn->GenId($configVarsTable);
        $query = "INSERT INTO $configVarsTable VALUES ($id_configvar,'$htmltag','html')";
        $dbconn->Execute($query);
        if ($dbconn->ErrorNo() != 0) {
            $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return NULL;
        }
    }

    $censortags = array('fuck',
                  'fucked',
                  'motherfucker',
                  'pussy',
                  'cock',
		          'cunt',
		          'cocksucker',
                  'cum');
    
    foreach ($censortags as $censortag) {
        $id_configvar = $dbconn->GenId($configVarsTable);
        $query = "INSERT INTO $configVarsTable VALUES ($id_configvar,'$censortag','censored')";
        $dbconn->Execute($query);
        if ($dbconn->ErrorNo() != 0) {
            $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return NULL;
        }
    }



    $templateTagsTable = $systemPrefix . '_template_tags';
    /*********************************************************************
    * CREATE TABLE xar_template_tags (
    *  xar_id int(11) NOT NULL auto_increment,
    *  xar_name varchar(255) NOT NULL default '',
    *  xar_module varchar(255) default NULL,
    *  xar_handler varchar(255) NOT NULL default '',
    *  xar_data text,
    *  PRIMARY KEY  (xar_id)
    * )
    *********************************************************************/
    $fields = array(
    'xar_id'      => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'xar_name'    => array('type'=>'varchar','size'=>255,'null'=>false),
    'xar_module'  => array('type'=>'varchar','size'=>255,'null'=>true),
    'xar_handler' => array('type'=>'varchar','size'=>255,'null'=>false),
    'xar_data'    => array('type'=>'text')
     );

    $query = xarDBCreateTable($templateTagsTable,$fields);

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    // Load in installer API
    xarInstallAPILoad('installer','admin');
    
    /****************************************************************
    * Install users module and set up default users
    ****************************************************************/
    $res = xarInstallAPIFunc('installer',
                            'admin',
                            'initialise',
                            array('directory' => 'users',
                                  'initfunc'  => 'init'));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return NULL;
    }

    $usersTable = $systemPrefix . '_users';
    $id_anonymous = $dbconn->GenId($usersTable);
    $query = "INSERT INTO $usersTable VALUES ($id_anonymous ,'','Anonymous','','','','')";
    
    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    $id_anonymous = $dbconn->PO_Insert_ID($usersTable,'xar_uid');

    $id_admin = $dbconn->GenId($usersTable);
    $query = "INSERT INTO $usersTable VALUES ($id_admin,'Admin','Admin','none@none.com','5f4dcc3b5aa765d61d8327deb882cf99','http://www.xaraya.com','authsystem')";

    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    $id_admin = $dbconn->PO_Insert_ID($usersTable,'xar_uid');

    /***************************************************************
    * Install groups module and setup default groups
    ***************************************************************/
    $res = xarInstallAPIFunc('installer',
                            'admin',
                            'initialise',
                            array('directory' => 'groups',
                                  'initfunc'  => 'init'));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return NULL;
    }

    $groupsTable = $systemPrefix . '_groups';
    $group_users = $dbconn->GenId($groupsTable);
    $query = "INSERT INTO $groupsTable (xar_gid, xar_name) VALUES ($group_users, 'Users');";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $group_users = $dbconn->PO_Insert_ID($groupsTable,'xar_gid');

    $group_admin = $dbconn->GenId($groupsTable);
    $query = "INSERT INTO $groupsTable (xar_gid, xar_name) VALUES ($group_admin, 'Admins');";
    $dbconn->Execute($query);
    $group_admin = $dbconn->PO_Insert_ID($groupsTable,'xar_gid');

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $groupMembershipTable = $systemPrefix . '_group_membership';

    $query = "INSERT INTO $groupMembershipTable (xar_gid, xar_uid) VALUES ($group_users, $id_anonymous);";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $query = "INSERT INTO $groupMembershipTable (xar_gid, xar_uid) VALUES ($group_admin, $id_admin);";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    /**************************************************************
    * Install permissions module and setup default permissions
    **************************************************************/
    $res = xarInstallAPIFunc('installer',
                            'admin',
                            'initialise',
                            array('directory' => 'permissions',
                                  'initfunc'  => 'init'));

    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return NULL;
    }
    $groupPermsTable = $systemPrefix . '_group_perms';

    $id = $dbconn->GenId($groupPermsTable);
    $query = "INSERT INTO $groupPermsTable
             (xar_pid, xar_gid, xar_sequence, xar_realm, xar_component, xar_instance, xar_level, xar_bond)
              VALUES ($id, $group_admin, 1, 0, '.*', '.*', 800, 0);";

    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $userPermsTable = $systemPrefix . '_user_perms';

    $id = $dbconn->GenId($userPermsTable);
    $query = "INSERT INTO $userPermsTable VALUES ($id,-1,1,0,'.*','.*',200,0)";
    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $id = $dbconn->GenId($userPermsTable);
    $query = "INSERT INTO $userPermsTable VALUES ($id,$id_admin,0,0,'.*','.*',800,0)";
    $dbconn->Execute($query);
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    /**************************************************************
    * Install modules table and insert the modules module
    **************************************************************/
    $res = xarInstallAPIFunc('installer',
                            'admin',
                            'initialise',
                            array('directory' => 'modules',
                                  'initfunc'  => 'init'));

    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return NULL;
    }
    $modulesTable = $systemPrefix .'_modules';
    $systemModuleStatesTable = $systemPrefix .'_module_states';

    // Install Modules module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES ($seqId, 'modules', 1, 'modules', '2.02', 1, 'Core Admin', 'Global', 1, 0)";

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Set Modules Module to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_regid, xar_state
              ) VALUES (1, 3)";

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Install authsystem module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES ($seqId, 'authsystem', 42, 'authsystem', '0.91', 1, 'Core Utility', 'Global', 0, 0)";

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Set authsystem to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_regid, xar_state
              ) VALUES (42, 3)";

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Install installer module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES ('".$seqId."', 'installer', 200, 'installer', '1.0', 1, 'Core Utility', 'Global', 1, 0)";

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Set installer to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_regid, xar_state
              ) VALUES (200, 3)";

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
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
    xarModAPILoad('modules', 'admin');

    // load modules into *_modules table
    if (!xarModAPIFunc('modules', 'admin', 'regenerate')) {
        return NULL;
    }

    // Activate the groups module
    $res = xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => xarModGetIDFromName('groups'),
                                                              'state' => XARMOD_STATE_INACTIVE));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }
    $res = xarModAPIFunc('modules', 'admin', 'activate', array('regid' => xarModGetIDFromName('groups')));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    // Activate the permissions module
    $res = xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => xarModGetIDFromName('permissions'),
                                                              'state' => XARMOD_STATE_INACTIVE));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }
    $res = xarModAPIFunc('modules', 'admin', 'activate', array('regid' => xarModGetIDFromName('permissions')));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    // initialize blocks module
    $modRegId = xarModGetIDFromName('blocks');

    if (!xarModAPIFunc('modules', 'admin', 'initialise', array('regid' => $modRegId))) {
        return NULL;
    }

    if (!xarModAPIFunc('modules', 'admin', 'activate', array('regid' => $modRegId))) {
        return NULL;
    }

    // initialize & activate adminpanels module
    $res = xarModAPIFunc('modules', 'admin', 'initialise', array('regid' => xarModGetIDFromName('adminpanels')));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    $res = xarModAPIFunc('modules', 'admin', 'activate', array('regid' => xarModGetIDFromName('adminpanels')));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    // Activate the user's module
    $res = xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => xarModGetIDFromName('users'),
                                                              'state' => XARMOD_STATE_INACTIVE));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }
    $res = xarModAPIFunc('modules', 'admin', 'activate', array('regid' => xarModGetIDFromName('users')));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    //initialise and activate base module by setting the states
    $res = xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => xarModGetIDFromName('base'),                                                          'state' => XARMOD_STATE_INACTIVE));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }
    $res = xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => xarModGetIDFromName('base'),
                                                              'state' => XARMOD_STATE_ACTIVE));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }
    // initialize installer module

    // Register Block types
    if (!xarBlockTypeRegister('base', 'finclude')) {
        return NULL;
    }

    $res = xarBlockTypeRegister('base', 'html');
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    $res = xarBlockTypeRegister('base', 'menu');
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    $res = xarBlockTypeRegister('base', 'php');
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    $res = xarBlockTypeRegister('base', 'text');
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }
    $res = xarBlockTypeRegister('base', 'thelang'); // FIXME <paul> should this be here???
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    if (xarVarIsCached('Mod.BaseInfos', 'blocks')) {
        xarVarDelCached('Mod.BaseInfos', 'blocks');
    }
    // Create default block groups/instances
    $res = xarModAPILoad('blocks', 'admin');
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    $res = xarModAPIFunc('blocks', 'admin', 'create_group', array('name' => 'left'));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    $res = xarModAPIFunc('blocks', 'admin', 'create_group', array('name'     => 'right',
                                                                 'template' => 'right'));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
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
