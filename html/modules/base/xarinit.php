<?php
/**
 * File: $Id: s.xarinit.php 1.106 03/10/05 07:47:51-04:00 John.Cox@mcnabb. $
 *
 * Base initialization functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage installer
 * @author Paul Rosania
 */


/**
 * Load Table Maintainance API
 */
xarDBLoadTableMaintenanceAPI();

/**
 * Initialise the base module
 *
 * @return bool
 * @raise DATABASE_ERROR
 */
function base_init()
{
    // Get database information
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $systemPrefix = xarDBGetSystemTablePrefix();

    /*********************************************************************
    * First we create the meta-table that will contain the definition of
    * all Xaraya tables
    *********************************************************************/
    $tablesTable = $systemPrefix . '_tables';
    /*********************************************************************
    * CREATE TABLE xar_tables (
    *   xar_tableid int(11) NOT NULL auto_increment,
    *   xar_table varchar(100) NOT NULL default '',
    *   xar_field varchar(100) NOT NULL default '',
    *   xar_type varchar(100) NOT NULL default '',
    *   xar_size varchar(100) NOT NULL default '',
    *   xar_default varchar(255) NOT NULL default '',
    *   xar_null tinyint(1) default NULL,
    *   xar_unsigned tinyint(1) default NULL,
    *   xar_increment tinyint(1) default NULL,
    *   xar_primary_key tinyint(1) default NULL,
    *   PRIMARY KEY  (xar_tableid)
    * )
    *********************************************************************/
    $fields = array(
    'xar_tableid'     => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'xar_table'       => array('type'=>'varchar','size'=>64,'default'=>'','null'=>false),
    'xar_field'       => array('type'=>'varchar','size'=>64,'default'=>'','null'=>false),
    'xar_type'        => array('type'=>'varchar','size'=>64,'default'=>'','null'=>false),
    'xar_size'        => array('type'=>'varchar','size'=>64,'default'=>'','null'=>false),
    'xar_default'     => array('type'=>'varchar','size'=>254,'default'=>'','null'=>false),
    'xar_null'        => array('type'=>'integer','size'=>'tiny','default'=>'0','null'=>false),
    'xar_unsigned'    => array('type'=>'integer','size'=>'tiny','default'=>'0','null'=>false),
    'xar_increment'   => array('type'=>'integer','size'=>'tiny','default'=>'0','null'=>false),
    'xar_primary_key' => array('type'=>'integer','size'=>'tiny','default'=>'0','null'=>false)
    );
    // xar_width,
    // xar_decimals,

    $query = xarDBCreateTable($tablesTable,$fields);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    /*********************************************************************
    * Here we create non module associated tables
    *
    * prefix_config_vars   - system configuration variables
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

    $index = array('name'   => 'i_'.$systemPrefix.'_base_uid',
                   'fields' => array('xar_uid'));

    $query = xarDBCreateIndex($sessionInfoTable,$index);

    $result =& $dbconn->Execute($query);
    if(!$result) return;

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

    // config var name should be unique
    $index = array('name'   => 'i_'.$systemPrefix.'_base_name',
                   'fields' => array('xar_name'),
                   'unique' => true);

    $query = xarDBCreateIndex($configVarsTable,$index);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $config_id = $dbconn->GenId($configVarsTable);
    $query = "INSERT INTO $configVarsTable VALUES ($config_id,'Site.Core.AllowableHTML','a:25:{s:3:\"!--\";s:1:\"2\";s:1:\"a\";s:1:\"2\";s:1:\"b\";s:1:\"2\";s:10:\"blockquote\";s:1:\"2\";s:2:\"br\";s:1:\"2\";s:6:\"center\";s:1:\"2\";s:3:\"div\";s:1:\"2\";s:2:\"em\";s:1:\"2\";s:4:\"font\";i:0;s:2:\"hr\";s:1:\"2\";s:1:\"i\";s:1:\"2\";s:3:\"img\";i:0;s:2:\"li\";s:1:\"2\";s:7:\"marquee\";i:0;s:2:\"ol\";s:1:\"2\";s:1:\"p\";s:1:\"2\";s:3:\"pre\";s:1:\"2\";s:4:\"span\";i:0;s:6:\"strong\";s:1:\"2\";s:2:\"tt\";s:1:\"2\";s:2:\"ul\";s:1:\"2\";s:5:\"table\";s:1:\"2\";s:2:\"td\";s:1:\"2\";s:2:\"th\";s:1:\"2\";s:2:\"tr\";s:1:\"2\";}')";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    include_once 'includes/xarConfig.php';

    // Start Configuration Unit
    $systemArgs = array();
    // change this loadlevel to the proper level
    $whatToLoad = XARCORE_SYSTEM_ADODB;
    xarConfig_init($systemArgs, $whatToLoad);
    // Start Variable Utils
    xarVar_init($systemArgs, $whatToLoad);

    /****************************************************************
    * Set System Configuration Variables
    *****************************************************************/
    xarConfigSetVar('System.Core.TimeZone', 'US/New York');
    xarConfigSetVar('System.Core.VersionNum', XARCORE_VERSION_NUM);
    xarConfigSetVar('System.Core.VersionId', XARCORE_VERSION_ID);
    xarConfigSetVar('System.Core.VersionSub', XARCORE_VERSION_SUB);
    $allowedAPITypes = array();
    xarConfigSetVar('System.Core.AllowedAPITypes',$allowedAPITypes);
    /*****************************************************************
    * Set site configuration variables
    ******************************************************************/
    xarConfigSetVar('Site.BL.ThemesDirectory','themes');
    xarConfigSetVar('Site.BL.CacheTemplates',true);
    xarConfigSetVar('Site.Core.FixHTMLEntities',false);
    xarConfigSetVar('Site.Core.TimeZone', 'US/New York');
    xarConfigSetVar('Site.Core.EnableShortURLsSupport', false);
    xarConfigSetVar('Site.Core.EnableSecureServer', false);
    xarConfigSetVar('Site.Core.DefaultModuleName', 'base');
    xarConfigSetVar('Site.Core.DefaultModuleType', 'user');
    xarConfigSetVar('Site.Core.DefaultModuleFunction', 'main');
    xarConfigSetVar('Site.Core.LoadLegacy', true);
    xarConfigSetVar('Site.Session.SecurityLevel', 'Medium');
    xarConfigSetVar('Site.Session.Duration', 7);
    xarConfigSetVar('Site.Session.InactivityTimeout', 90);
    xarConfigSetVar('Site.MLS.TranslationsBackend', 'xml');
    // FIXME: <marco> Temporary config vars, ask them at install time
    xarConfigSetVar('Site.MLS.MLSMode', 'SINGLE');
    
    // The installer should now set the default locale based on the
    // chose language, let's make sure that is true
    if(!xarConfigGetVar('Site.MLSDefaultLocale')) {
        xarConfigSetVar('Site.MLS.DefaultLocale', 'en_US.iso-8859-1');
        $allowedLocales = array('en_US.iso-8859-1');
        xarConfigSetVar('Site.MLS.AllowedLocles', $allowedLocales);
    }
    
    $authModules = array('authsystem');
    xarConfigSetVar('Site.User.AuthenticationModules',$authModules);

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
    // FIXME: MrB - replace xar_module with xar_modid asap
    $query = xarDBCreateTable($templateTagsTable,$fields);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // {ML_dont_parse 'includes/xarMod.php'}
    include_once 'includes/xarMod.php';

    // Start Modules Support
    $systemArgs = array('enableShortURLsSupport' => false,
                        'generateXMLURLs' => false);
    xarMod_init($systemArgs, $whatToLoad);

    /**************************************************************
    * Install modules table and insert the modules module
    **************************************************************/
    if (!xarInstallAPIFunc('initialise',
                           array('directory' => 'modules', 'initfunc'  => 'init'))) {
        return;
    }
    
    /****************************************************************
    * Install roles module and set up default roles
    ****************************************************************/
    if (!xarInstallAPIFunc('initialise',
                           array('directory' => 'roles',
                                 'initfunc'  => 'init'))) {
        return NULL;
    }

    /**************************************************************
    * Install privileges module and setup default privileges
    **************************************************************/
    if (!xarInstallAPIFunc('initialise',
                           array('directory' => 'privileges',
                                 'initfunc'  => 'init'))) {
        return NULL;
    }

    $modulesTable = $systemPrefix .'_modules';
    $systemModuleStatesTable = $systemPrefix .'_module_states';

    // Install authsystem module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES ($seqId, 'authsystem', 42, 'authsystem', '0.91.0', 1, 'Core Utility', 'Global', 0, 0)";

    $result =& $dbconn->Execute($query);
    if (!$result) return;
    
    // Bug #1813 - Have to use GenId to get or create the sequence for xar_id or 
    // the sequence for xar_id will not be available in PostgreSQL
    $seqId = $dbconn->GenId($systemModuleStatesTable);

    // Set authsystem to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_id, xar_regid, xar_state
              ) VALUES (" . $seqId . ", 42, 3)";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Install installer module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES ('".$seqId."', 'installer', 200, 'installer', '1.0.0', 1, 'Core Utility', 'Global', 0, 0)";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Bug #1813 - Have to use GenId to create the sequence for xar_id or 
    // the sequence for xar_id will not be available in PostgreSQL
    $seqId = $dbconn->GenId($systemModuleStatesTable);

    // Set installer to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_id, xar_regid, xar_state
              ) VALUES (" . $seqId . ", 200, 3)";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Install blocks module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES ('".$seqId."', 'blocks', 13, 'blocks', '1.0.0', 1, 'Core Utility', 'Global', 1, 0)";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Bug #1813 - Have to use GenId to get or create the sequence for xar_id or 
    // the sequence for xar_id will not be available in PostgreSQL
    $seqId = $dbconn->GenId($systemModuleStatesTable);

    // Set blocks to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_id, xar_regid, xar_state
              ) VALUES (" . $seqId . ", 13, 3)";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Install themes module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES ('".$seqId."', 'themes', 70, 'themes', '1.3.0', 1, 'Core Utility', 'Global', 1, 0)";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Bug #1813 - Have to use GenId to get or create the sequence for xar_id or 
    // the sequence for xar_id will not be available in PostgreSQL
    $seqId = $dbconn->GenId($systemModuleStatesTable);

    // Set themes to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_id, xar_regid, xar_state
              ) VALUES (" . $seqId . ", 70, 3)";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    /**************************************************************
    * Install the blocks module
    **************************************************************/
    // FIXME: the installation of the blocks module depends on the modules module
    // to be present, doh !
    if (!xarInstallAPIFunc('initialise',
                           array('directory'=>'blocks', 'initfunc'=>'init'))) {
        return;
    }

    if (!xarInstallAPIFunc('initialise',
                           array('directory'=>'themes', 'initfunc'=>'init'))) {
        return;
    }

    // Fill language list(?)

    // TODO: move this to some common place in Xaraya ?
    // Register BL user tags
    // Include a JavaScript file in a page
    xarTplRegisterTag(
        'base', 'base-include-javascript', array(),
        'base_javascriptapi_handlemodulejavascript'
    );
    // Render JavaScript in a page
    xarTplRegisterTag(
        'base', 'base-render-javascript', array(),
        'base_javascriptapi_handlerenderjavascript'
    );

    
    // Initialisation successful
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
    switch($oldVersion) {
    case '0.1':
        // compatability upgrade, nothing to be done
        break;
    }
    return true;
}

/**
 * Delete the base module
 *
 * @param none
 * @returns bool
 */
function base_delete()
{
  //this module cannot be removed
  return false;
}

?>