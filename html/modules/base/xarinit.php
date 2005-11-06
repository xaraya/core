<?php
/**
 * Base Module Initialisation
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
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

    $index = array('name'   => 'i_'.$systemPrefix.'_session_uid',
                   'fields' => array('xar_uid'),
                   'unique' => false);

    $query = xarDBCreateIndex($sessionInfoTable,$index);

    $result =& $dbconn->Execute($query);
    if(!$result) return;

    $index = array('name'   => 'i_'.$systemPrefix.'_session_lastused',
                   'fields' => array('xar_lastused'),
                   'unique' => false);

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
    $index = array('name'   => 'i_'.$systemPrefix.'_config_name',
                   'fields' => array('xar_name'),
                   'unique' => true);

    $query = xarDBCreateIndex($configVarsTable,$index);

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
    
    $allowableHTML = array (
        '!--'=>2, 'a'=>2, 'b'=>2, 'blockquote'=>2,'br'=>2, 'center'=>2, 
        'div'=>2, 'em'=>2, 'font'=>0, 'hr'=>2, 'i'=>2, 'img'=>0, 'li'=>2,
        'marquee'=>0, 'ol'=>2, 'p'=>2, 'pre'=> 2, 'span'=>0,'strong'=>2, 
        'tt'=>2, 'ul'=>2, 'table'=>2, 'td'=>2, 'th'=>2, 'tr'=> 2);

    xarConfigSetVar('Site.Core.AllowableHTML',$allowableHTML);
    /****************************************************************
    * Set System Configuration Variables
    *****************************************************************/
    xarConfigSetVar('System.Core.TimeZone', '');
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
    xarConfigSetVar('Site.Core.FixHTMLEntities',true);
    xarConfigSetVar('Site.Core.TimeZone', '');
    xarConfigSetVar('Site.Core.EnableShortURLsSupport', false);
    // when installing via https, we assume that we want to support that :)
    $HTTPS = xarServerGetVar('HTTPS');
    /* jojodee - monitor this fix.
       Localized fix for installer where HTTPS shows incorrectly as being on in
       some environments. Fix is ok as long as we dont access directly
       outside of installer. Consider setting config vars at later point rather than here.
    */
    $REQ_URI = parse_url(xarServerGetVar('HTTP_REFERER'));
    // IIS seems to set HTTPS = off for some reason (cfr. xarServerGetProtocol)
    if (!empty($HTTPS) && $HTTPS != 'off' && $REQ_URI['scheme'] == 'https') {
        xarConfigSetVar('Site.Core.EnableSecureServer', true);
    } else {
        xarConfigSetVar('Site.Core.EnableSecureServer', false);
    }

    xarConfigSetVar('Site.Core.DefaultModuleName', 'base');
    xarConfigSetVar('Site.Core.DefaultModuleType', 'user');
    xarConfigSetVar('Site.Core.DefaultModuleFunction', 'main');
    xarConfigSetVar('Site.Core.LoadLegacy', false);
    xarConfigSetVar('Site.Session.SecurityLevel', 'Medium');
    xarConfigSetVar('Site.Session.Duration', 7);
    xarConfigSetVar('Site.Session.InactivityTimeout', 90);
    // use current defaults in includes/xarSession.php
    xarConfigSetVar('Site.Session.CookieName', '');
    xarConfigSetVar('Site.Session.CookiePath', '');
    xarConfigSetVar('Site.Session.CookieDomain', '');
    xarConfigSetVar('Site.Session.RefererCheck', '');
    xarConfigSetVar('Site.MLS.TranslationsBackend', 'xml2php');
    // FIXME: <marco> Temporary config vars, ask them at install time
    xarConfigSetVar('Site.MLS.MLSMode', 'SINGLE');
    
    // The installer should now set the default locale based on the
    // chose language, let's make sure that is true
    if(!xarConfigGetVar('Site.MLS.DefaultLocale')) {
        xarConfigSetVar('Site.MLS.DefaultLocale', 'en_US.utf-8');
        $allowedLocales = array('en_US.utf-8');
        xarConfigSetVar('Site.MLS.AllowedLocales', $allowedLocales);
    }
    // Minimal information for timezone offset handling (see also Site.Core.TimeZone)
    xarConfigSetVar('Site.MLS.DefaultTimeOffset', 0);

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
     ) VALUES (?, 'authsystem', 42, 'authsystem', '0.91.0', 1, 'Core Utility', 'Global', 0, 0)";

    $result =& $dbconn->Execute($query,array($seqId));
    if (!$result) return;
    
    // Bug #1813 - Have to use GenId to get or create the sequence for xar_id or 
    // the sequence for xar_id will not be available in PostgreSQL
    $seqId = $dbconn->GenId($systemModuleStatesTable);

    // Set authsystem to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_id, xar_regid, xar_state
              ) VALUES (?, 42, 3)";

    $result =& $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // Install base module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES (?, 'base', 68, 'base', '0.1.0', 1, 'Core Admin', 'Global', 1, 1)";

    $result =& $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // Bug #1813 - Have to use GenId to create the sequence for xar_id or 
    // the sequence for xar_id will not be available in PostgreSQL
    $seqId = $dbconn->GenId($systemModuleStatesTable);

    // Set installer to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_id, xar_regid, xar_state
              ) VALUES (?, 68, 3)";

    $result =& $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // Install installer module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES (?, 'installer', 200, 'installer', '1.0.0', 1, 'Core Utility', 'Global', 0, 0)";

    $result =& $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // Bug #1813 - Have to use GenId to create the sequence for xar_id or 
    // the sequence for xar_id will not be available in PostgreSQL
    $seqId = $dbconn->GenId($systemModuleStatesTable);

    // Set installer to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_id, xar_regid, xar_state
              ) VALUES (?, 200, 3)";

    $result =& $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // Install blocks module
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES (?, 'blocks', 13, 'blocks', '1.0.0', 1, 'Core Utility', 'Global', 1, 0)";
    $result =& $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // Bug #1813 - Have to use GenId to get or create the sequence for xar_id or 
    // the sequence for xar_id will not be available in PostgreSQL
    $seqId = $dbconn->GenId($systemModuleStatesTable);

    // Set blocks to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_id, xar_regid, xar_state
              ) VALUES (?, 13, 3)";
    $result =& $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // Install themes module
    $seqId = $dbconn->GenId($modulesTable);
    // FIXME: the theme version should not be hard-coded here.
    // Fetch it from the modules/themes/xarversion.php script
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable
     ) VALUES (?, 'themes', 70, 'themes', '1.3.1', 1, 'Core Utility', 'Global', 1, 0)";
    $result =& $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // Bug #1813 - Have to use GenId to get or create the sequence for xar_id or 
    // the sequence for xar_id will not be available in PostgreSQL
    $seqId = $dbconn->GenId($systemModuleStatesTable);

    // Set themes to active
    $query = "INSERT INTO $systemModuleStatesTable (xar_id, xar_regid, xar_state
              ) VALUES (?, 70, 3)";
    $result =& $dbconn->Execute($query,array($seqId));
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

    // TODO: is this is correct place for a default value for a modvar?
    xarModSetVar('base', 'AlternatePageTemplate', 'homepage');
    
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