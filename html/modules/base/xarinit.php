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



/**
 * Initialise the base module
 *
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function base_init()
{
    // Start the database
    xarCoreInit(XARCORE_SYSTEM_ADODB);

    //Load Table Maintainance API
    xarDBLoadTableMaintenanceAPI();

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

    $result =& $dbconn->Execute($query);
    if (!$result) return;

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

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // FIXME: should be unique or not?
    $index = array('name'   => 'xar_name',
                   'fields' => array('xar_name'));

    $query = xarDBCreateIndex($configVarsTable,$index);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $config_id = $dbconn->GenId($configVarsTable);
    $query = "INSERT INTO $configVarsTable VALUES ($config_id,'Site.Core.AllowableHTML','a:25:{s:3:\"!--\";s:1:\"2\";s:1:\"a\";s:1:\"2\";s:1:\"b\";s:1:\"2\";s:10:\"blockquote\";s:1:\"2\";s:2:\"br\";s:1:\"2\";s:6:\"center\";s:1:\"2\";s:3:\"div\";s:1:\"2\";s:2:\"em\";s:1:\"2\";s:4:\"font\";i:0;s:2:\"hr\";s:1:\"2\";s:1:\"i\";s:1:\"2\";s:3:\"img\";i:0;s:2:\"li\";s:1:\"2\";s:7:\"marquee\";i:0;s:2:\"ol\";s:1:\"2\";s:1:\"p\";s:1:\"2\";s:3:\"pre\";s:1:\"2\";s:4:\"span\";i:0;s:6:\"strong\";s:1:\"2\";s:2:\"tt\";s:1:\"2\";s:2:\"ul\";s:1:\"2\";s:5:\"table\";s:1:\"2\";s:2:\"td\";s:1:\"2\";s:2:\"th\";s:1:\"2\";s:2:\"tr\";s:1:\"2\";}')";
    $result =& $dbconn->Execute($query);
    if (!$result) return;
    
    // PRE-SETUP so that xarCoreInit will work 
    xarInstallConfigSetVar('Site.BL.DefaultTheme','installer');
    xarInstallConfigSetVar('Site.BL.ThemesDirectory','themes');
    xarInstallConfigSetVar('Site.BL.CacheTemplates','true');
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
    xarConfigSetVar('Site.Session.EnableIntranetMode', false);
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
    $allowedVarsTable  = $systemPrefix . '_allowed_vars';
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

    $query = xarDBCreateTable($allowedVarsTable,$fields);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // FIXME: should be unique or not?
    $index = array('name'   => 'i_xar_name',
                   'fields' => array('xar_name'));

    $query = xarDBCreateIndex($allowedVarsTable,$index);

    $result =& $dbconn->Execute($query);
    if (!$result) return;
    
    $id_allowedvar = $dbconn->GenId($allowedVarsTable);
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
        $query = "INSERT INTO $allowedVarsTable VALUES ($id_allowedvar,'$htmltag','html')";
        $result =& $dbconn->Execute($query);
        if (!$result) return;
    }

    $censoredWords = array('fuck',
                           'fucked',
                           'motherfucker',
                           'pussy',
                           'cock',
                           'cunt',
                           'cocksucker',
                           'cum');

    foreach ($censoredWords as $censoredWord) {
        $query = "INSERT INTO $allowedVarsTable VALUES ($id_allowedvar,'$censoredWord','censored')";
        $result =& $dbconn->Execute($query);
        if (!$result) return;
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

    $result =& $dbconn->Execute($query);
    if (!$result) return;
    
    // Load in installer API
    if (!xarInstallAPILoad('installer','admin')) {
        return NULL;
    }
    
    /****************************************************************
    * Install users module and set up default users
    ****************************************************************/
    if (!xarInstallAPIFunc('installer',
                           'admin',
                           'initialise',
                           array('directory' => 'users',
                                 'initfunc'  => 'init'))) {
        return NULL;
    }

    $usersTable = $systemPrefix . '_users';
    $id_anonymous = $dbconn->GenId($usersTable);
    $query = "INSERT INTO $usersTable VALUES ($id_anonymous ,'','Anonymous','','','','','','','')";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id_anonymous = $dbconn->PO_Insert_ID($usersTable,'xar_uid');

    $id_admin = $dbconn->GenId($usersTable);
    $query = "INSERT INTO $usersTable VALUES ($id_admin,'Admin','Admin','none@none.com','5f4dcc3b5aa765d61d8327deb882cf99','http://www.xaraya.com','','','3','authsystem')";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id_admin = $dbconn->PO_Insert_ID($usersTable,'xar_uid');

    /***************************************************************
    * Install groups module and setup default groups
    ***************************************************************/
    if (!xarInstallAPIFunc('installer',
                           'admin',
                           'initialise',
                           array('directory' => 'groups',
                                 'initfunc'  => 'init'))) {
        return NULL;
    }

    $groupsTable = $systemPrefix . '_groups';
    $group_users = $dbconn->GenId($groupsTable);
    $query = "INSERT INTO $groupsTable (xar_gid, xar_name) VALUES ($group_users, 'Users');";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $group_users = $dbconn->PO_Insert_ID($groupsTable,'xar_gid');

    $group_admin = $dbconn->GenId($groupsTable);
    $query = "INSERT INTO $groupsTable (xar_gid, xar_name) VALUES ($group_admin, 'Admins');";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $group_admin = $dbconn->PO_Insert_ID($groupsTable,'xar_gid');
    $groupMembershipTable = $systemPrefix . '_group_membership';

    $query = "INSERT INTO $groupMembershipTable (xar_gid, xar_uid) VALUES ($group_users, $id_anonymous);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $query = "INSERT INTO $groupMembershipTable (xar_gid, xar_uid) VALUES ($group_admin, $id_admin);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    /**************************************************************
    * Install permissions module and setup default permissions
    **************************************************************/
    if (!xarInstallAPIFunc('installer',
                           'admin',
                           'initialise',
                           array('directory' => 'permissions',
                                 'initfunc'  => 'init'))) {
        return NULL;
    }
    $groupPermsTable = $systemPrefix . '_group_perms';

    $id = $dbconn->GenId($groupPermsTable);
    $query = "INSERT INTO $groupPermsTable
             (xar_pid, xar_gid, xar_sequence, xar_realm, xar_component, xar_instance, xar_level, xar_bond)
              VALUES ($id, $group_admin, 1, 0, '.*', '.*', 800, 0);";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $userPermsTable = $systemPrefix . '_user_perms';

    $id = $dbconn->GenId($userPermsTable);
    $query = "INSERT INTO $userPermsTable VALUES ($id,-1,1,0,'.*','.*',200,0)";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($userPermsTable);
    $query = "INSERT INTO $userPermsTable VALUES ($id,$id_admin,0,0,'.*','.*',800,0)";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    /**************************************************************
    * Install the blocks module
    **************************************************************/
    if (!xarInstallAPIFunc('installer', 'admin', 'initialise',
	                       array('directory'=>'blocks', 'initfunc'=>'init'))) {
	    return;
	}
    /**************************************************************
    * Install modules table and insert the modules module
    **************************************************************/
    if (!xarInstallAPIFunc('installer',
                           'admin',
                           'initialise',
                           array('directory' => 'modules',
                                 'initfunc'  => 'init'))) {
        return NULL;
    }
    $modulesTable = $systemPrefix .'_modules';
    $systemModuleStatesTable = $systemPrefix .'_module_states';

    // Install Modules module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES ($seqId, 'modules', 1, 'modules', '2.02', 1, 'Core Admin', 'Global', 1, 0)";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Set Modules Module to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_regid, xar_state
              ) VALUES (1, 3)";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Install authsystem module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES ($seqId, 'authsystem', 42, 'authsystem', '0.91', 1, 'Core Utility', 'Global', 0, 0)";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Set authsystem to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_regid, xar_state
              ) VALUES (42, 3)";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Install installer module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES ('".$seqId."', 'installer', 200, 'installer', '1.0', 1, 'Core Utility', 'Global', 0, 0)";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Set installer to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_regid, xar_state
              ) VALUES (200, 3)";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

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
    if (!xarModAPILoad('modules', 'admin')) {
        return NULL;
    }

    // load modules into *_modules table
    if (!xarModAPIFunc('modules', 'admin', 'regenerate')) {
        return NULL;
    }

    // Activate the groups module
    if (!xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => xarModGetIDFromName('groups'),
                                                              'state' => XARMOD_STATE_INACTIVE))) {
        return;
    }
    if (!xarModAPIFunc('modules', 'admin', 'activate', array('regid' => xarModGetIDFromName('groups')))) {
        return;
    }

    // Activate the permissions module
    if (!xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => xarModGetIDFromName('permissions'),
                                                              'state' => XARMOD_STATE_INACTIVE))) {
        return;
    }
    if (!xarModAPIFunc('modules', 'admin', 'activate', array('regid' => xarModGetIDFromName('permissions')))) {
        return;
    }

    // initialize blocks module
    $modRegId = xarModGetIDFromName('blocks');

/*    if (!xarModAPIFunc('modules', 'admin', 'initialise', array('regid' => $modRegId))) {
        return NULL;
    }*/

    if (!xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => $modRegId,
                                                              'state' => XARMOD_STATE_INACTIVE))) {
        return;
    }
    if (!xarModAPIFunc('modules', 'admin', 'activate', array('regid' => $modRegId))) {
        return NULL;
    }

    // initialize & activate adminpanels module
    if (!xarModAPIFunc('modules', 'admin', 'initialise', array('regid' => xarModGetIDFromName('adminpanels')))) {
        return NULL;
    }


    if (!xarModAPIFunc('modules', 'admin', 'activate', array('regid' => xarModGetIDFromName('adminpanels')))) {
        return NULL;
    }

    // Activate the user's module
    if (!xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => xarModGetIDFromName('users'),
                                                              'state' => XARMOD_STATE_INACTIVE))) {
        return;
    }

    if (!xarModAPIFunc('modules', 'admin', 'activate', array('regid' => xarModGetIDFromName('users')))) {
        return;
    }

    //initialise and activate base module by setting the states
    if (!xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => xarModGetIDFromName('base'),
                                                             'state' => XARMOD_STATE_INACTIVE))) {
        return;
    }
    if (!xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => xarModGetIDFromName('base'),
                                                              'state' => XARMOD_STATE_ACTIVE))) {
        return;
    }

    // initialize installer module

    // Register Block types
    $blocks = array('finclude','html','menu','php','rss','text');

    foreach ($blocks as $block) {

        if (!xarBlockTypeRegister('base', $block)) {
            return NULL;
        }
    }

    //$res = xarBlockTypeRegister('base', 'thelang'); // FIXME <paul> should this be here???
    //if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
    //    return;
    //}

    if (xarVarIsCached('Mod.BaseInfos', 'blocks')) {
        xarVarDelCached('Mod.BaseInfos', 'blocks');
    }

    // Create default block groups/instances
    if (!xarModAPILoad('blocks', 'admin')) {
        return NULL;
    }

    if (!xarModAPIFunc('blocks', 'admin', 'create_group', array('name' => 'left'))) {
        return NULL;
    }

    if (!xarModAPIFunc('blocks', 'admin', 'create_group', array('name'     => 'right',
                                                                'template' => 'right'))) {
        return NULL;
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