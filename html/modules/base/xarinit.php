<?php
/**
 * Base Module Initialisation
 *
 * @package modules
 * @copyright (C) 2005-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 * @author Marcel van der Boom
 */

/**
 * Load Table Maintainance API
 */
xarDBLoadTableMaintenanceAPI();

/**
 * Initialise the base module
 *
 * @return bool
 * @throws DATABASE_ERROR
 */
function base_init()
{
    // Get database information
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $systemPrefix = xarDBGetSystemTablePrefix();

    // Creating the first part inside a transaction
    try {
        $dbconn->begin();

        /*********************************************************************
         * Here we create non module associated tables
         *
         * prefix_session_info  - Session table
         * prefix_module_vars   - system configuration variables
         * prefix_template_tags - module template tag registry
         *********************************************************************/
        $sessionInfoTable = $systemPrefix . '_session_info';
        /*********************************************************************
         * CREATE TABLE xar_session_info (
         *  id        varchar(32) NOT NULL,
         *  ipaddr    varchar(20) NOT NULL default '',
         *  first_use integer NOT NULL default '0',
         *  last_use  integer NOT NULL default '0',
         *  role_id   integer NOT NULL default '0',
         *  vars      blob,
         *  remember  int(1) default '0',
         *  PRIMARY KEY  (id)
         * )
         *********************************************************************/
        $fields = array('id'        => array('type'=>'varchar','size'=>32   ,'null'=>false,'primary_key'=>true),
                        'ip_addr'   => array('type'=>'varchar','size'=>20   ,'null'=>false),
                        'first_use' => array('type'=>'integer','null'=>false,'default'=>'0'),
                        'last_use'  => array('type'=>'integer','null'=>false,'default'=>'0'),
                        'role_id'   => array('type'=>'integer','null'=>false,'default'=>'0'),
                        'vars'      => array('type'=>'blob'   ,'null'=>true),
                        'remember'  => array('type'=>'integer','size'=>1    ,'default'=>'0')
                        );

        $query = xarDBCreateTable($sessionInfoTable,$fields);
        $dbconn->Execute($query);

        $index = array('name'   => 'i_'.$systemPrefix.'_session_role_id',
                       'fields' => array('role_id'),
                       'unique' => false);
        $query = xarDBCreateIndex($sessionInfoTable,$index);
        $dbconn->Execute($query);

        $index = array('name'   => 'i_'.$systemPrefix.'_session_last_use',
                       'fields' => array('last_use'),
                       'unique' => false);

        $query = xarDBCreateIndex($sessionInfoTable,$index);
        $dbconn->Execute($query);

        /*********************************************************************
         * Here we install the configuration table and set some default
         * configuration variables
         *********************************************************************/
        // TODO: we now use module_vars, but namewise it would be better to use config_vars here
        // TODO: revisit this when we know its all working out, for now, minimal change.
        $configVarsTable  = $systemPrefix . '_module_vars';
        /*********************************************************************
         * CREATE TABLE xar_module_vars (
         *  id        integer NOT NULL auto_increment,
         *  module_id integer NOT NULL default '0',
         *  name      varchar(64) NOT NULL default '',
         *  value     longtext,
         *  PRIMARY KEY  (id),
         *  KEY (name)
         * )
         *********************************************************************/

        $fields = array('id'        => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
                        'module_id' => array('type'=>'integer','null'=>true,'increment'=>false),
                        'name'      => array('type'=>'varchar','size'=>64,'null'=>false),
                        'value'     => array('type'=>'text','size'=>'long')
                        );

        $query = xarDBCreateTable($configVarsTable,$fields);
        $dbconn->Execute($query);

        // config var name should be unique in scope
        // TODO: nameing of index is now confusing, see above.
        $index = array('name'   => 'i_'.$systemPrefix.'_config_name',
                       'fields' => array('name', 'module_id'),
                       'unique' => true);

        $query = xarDBCreateIndex($configVarsTable,$index);
        $dbconn->Execute($query);

        $index = array('name' => 'i_' . $systemPrefix . '_module_vars_module_id',
                       'fields' => array('module_id'));
        $query = xarDBCreateIndex($configVarsTable, $index);
        $dbconn->Execute($query);

        $index = array('name' => 'i_' . $systemPrefix . '_module_vars_name',
                       'fields' => array('name'));
        $query = xarDBCreateIndex($configVarsTable, $index);
        $dbconn->Execute($query);

        // Let's commit this, since we're gonna do some other stuff
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    // Start Configuration Unit
    sys::import('xaraya.xarConfig');
    $systemArgs = array();
    // change this loadlevel to the proper level
    $whatToLoad = XARCORE_SYSTEM_DATABASE;
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
    xarConfigSetVar('System.Core.TimeZone', 'Etc/UTC');
    xarConfigSetVar('System.Core.VersionNum', XARCORE_VERSION_NUM);
    xarConfigSetVar('System.Core.VersionId', XARCORE_VERSION_ID);
    xarConfigSetVar('System.Core.VersionSub', XARCORE_VERSION_SUB);
    $allowedAPITypes = array();
    /*****************************************************************
     * Set site configuration variables
     ******************************************************************/
    xarConfigSetVar('Site.BL.ThemesDirectory','themes');
    xarConfigSetVar('Site.BL.CacheTemplates',true);
    xarConfigSetVar('Site.BL.CompilerVersion','XAR_BL_USE_XSLT');
    xarConfigSetVar('Site.Core.FixHTMLEntities',true);
    xarConfigSetVar('Site.Core.TimeZone', 'Etc/UTC');
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
     *  id        integer NOT NULL auto_increment,
     *  name      varchar(255) NOT NULL default '',
     *  module_id integer default 0,
     *  handler   varchar(255) NOT NULL default '',
     *  data      text,
     *  PRIMARY KEY (id)
     * )
     *********************************************************************/
    $fields = array('id'      => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
                    'name'    => array('type'=>'varchar','size'=>255,'null'=>false),
                    'module_id'   => array('type'=>'integer','null'=>false,'default'=>'0'),
                    'handler' => array('type'=>'varchar','size'=>255,'null'=>false),
                    'data'    => array('type'=>'text')
                    );
    $query = xarDBCreateTable($templateTagsTable,$fields);
    $dbconn->Execute($query);

    // Start Modules Support
    $systemArgs = array('enableShortURLsSupport' => false,
                        'generateXMLURLs' => false);
    xarMod::init($systemArgs);

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
 * @return bool false, as this module cannot be removed
 */
function base_delete()
{
  //this module cannot be removed
  return false;
}

?>
