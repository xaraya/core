<?php
/**
 * Base Module Initialisation
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author Marcel van der Boom
 */

/**
 * Load Table Maintainance API
 */
sys::import('xaraya.tableddl');
/**
 * Initialise the base module
 *
 * @return bool
 * @throws DATABASE_ERROR
 */
function base_init()
{
    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();

    $prefix = xarDB::getPrefix();

    // Creating the first part inside a transaction
    try {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();

        /*********************************************************************
         * Here we create non module associated tables
         *
         * prefix_session_info  - Session table
         * prefix_module_vars   - system configuration variables
         *********************************************************************/
        $sessionInfoTable = $prefix . '_session_info';
        /*********************************************************************
         * CREATE TABLE xar_session_info (
         *  id        varchar(32) NOT NULL,
         *  ipaddr    varchar(20) NOT NULL,
         *  first_use integer NOT NULL default '0',
         *  last_use  integer NOT NULL default '0',
         *  role_id   integer unsigned NOT NULL,
         *  vars      blob,
         *  remember  boolean default false,
         *  PRIMARY KEY  (id)
         * )
         *********************************************************************/
        $fields = array('id'        => array('type'=>'varchar','size'=>32   ,'null'=>false, 'charset' => $charset, 'primary_key'=>true),
                        'ip_addr'   => array('type'=>'varchar','size'=>20   ,'null'=>false, 'charset' => $charset),
                        'first_use' => array('type'=>'integer','unsigned'=>true,'null'=>false,'default'=>'0'),
                        'last_use'  => array('type'=>'integer','unsigned'=>true,'null'=>false,'default'=>'0'),
                        'role_id'   => array('type'=>'integer','unsigned'=>true, 'null'=>false),
                        'vars'      => array('type'=>'blob'   ,'null'=>true),
                        'remember'  => array('type'=>'boolean', 'default'=>  false)
                        );
        $query = xarDBCreateTable($sessionInfoTable,$fields);
        $dbconn->Execute($query);

        $index = array('name'   => $prefix.'_session_role_id',
                       'fields' => array('role_id'),
                       'unique' => false);
        $query = xarDBCreateIndex($sessionInfoTable,$index);
        $dbconn->Execute($query);

        $index = array('name'   => $prefix.'_session_last_use',
                       'fields' => array('last_use'),
                       'unique' => false);

        $query = xarDBCreateIndex($sessionInfoTable,$index);
        $dbconn->Execute($query);

        /*********************************************************************
         * Here we install the module variables table and set some default
         * variables
         *********************************************************************/

        $modVarsTable  = $prefix . '_module_vars';
        /*********************************************************************
         * CREATE TABLE xar_module_vars (
         *  id        integer unsigned NOT NULL auto_increment,
         *  module_id integer unsigned default NULL,
         *  name      varchar(64) NOT NULL,
         *  value     longtext,
         *  PRIMARY KEY  (id),
         *  KEY (name)
         * )
         *********************************************************************/

        $fields = array(
                        'id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'increment' => true, 'primary_key' => true),
                        'module_id' => array('type'=>'integer','unsigned'=>true,'null'=>true),
                        'name'      => array('type'=>'varchar','size'=>64,'null'=>false, 'charset' => $charset),
                        'value'     => array('type'=>'text','size'=>'long', 'charset' => $charset)
                        );

        $query = xarDBCreateTable($modVarsTable,$fields);
        $dbconn->Execute($query);

        // config var name should be unique in scope
        // TODO: nameing of index is now confusing, see above.
        $index = array('name'   => $prefix.'_config_name',
                       'fields' => array('name', 'module_id'),
                       'unique' => true);

        $query = xarDBCreateIndex($modVarsTable,$index);
        $dbconn->Execute($query);

        $index = array('name' => $prefix . '_module_vars_module_id',
                       'fields' => array('module_id'));
        $query = xarDBCreateIndex($modVarsTable, $index);
        $dbconn->Execute($query);

        $index = array('name' => $prefix . '_module_vars_name',
                       'fields' => array('name'));
        $query = xarDBCreateIndex($modVarsTable, $index);
        $dbconn->Execute($query);

        // Let's commit this, since we're gonna do some other stuff
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    // Start Configuration Unit
    sys::import('xaraya.variables');
    $systemArgs = array();
    xarVar_init($systemArgs);

    $allowableHTML = array (
                            '!--'=>2, 'a'=>2, 'b'=>2, 'blockquote'=>2,'br'=>2, 'center'=>2,
                            'div'=>2, 'em'=>2, 'font'=>0, 'hr'=>2, 'i'=>2, 'img'=>0, 'li'=>2,
                            'marquee'=>0, 'ol'=>2, 'p'=>2, 'pre'=> 2, 'span'=>0,'strong'=>2,
                            'tt'=>2, 'ul'=>2, 'table'=>2, 'td'=>2, 'th'=>2, 'tr'=> 2);

    xarConfigVars::set(null, 'Site.Core.AllowableHTML',$allowableHTML);
    /****************************************************************
     * Set System Configuration Variables
     *****************************************************************/
    xarConfigVars::set(null, 'System.Core.TimeZone', 'Etc/UTC');
    xarConfigVars::set(null, 'System.Core.VersionNum', xarCore::VERSION_NUM);
    xarConfigVars::set(null, 'System.Core.VersionId', xarCore::VERSION_ID);
    xarConfigVars::set(null, 'System.Core.VersionSub', xarCore::VERSION_SUB);
    $allowedAPITypes = array();
    /*****************************************************************
     * Set site configuration variables
     ******************************************************************/
    xarConfigVars::set(null, 'Site.BL.CacheTemplates',true);
    xarConfigVars::set(null, 'Site.BL.MemCacheTemplates',false);
    xarConfigVars::set(null, 'Site.BL.ThemesDirectory','themes');
    xarConfigVars::set(null, 'Site.Core.FixHTMLEntities',true);
    xarConfigVars::set(null, 'Site.Core.TimeZone', 'Etc/UTC');
    xarConfigVars::set(null, 'Site.Core.EnableShortURLsSupport', false);
    // when installing via https, we assume that we want to support that :)
    $HTTPS = xarServer::getVar('HTTPS');
    /* jojodee - monitor this fix.
     Localized fix for installer where HTTPS shows incorrectly as being on in
     some environments. Fix is ok as long as we dont access directly
     outside of installer. Consider setting config vars at later point rather than here.
    */
    $REQ_URI = parse_url(xarServer::getVar('HTTP_REFERER'));
    // IIS seems to set HTTPS = off for some reason (cfr. xarServer::getProtocol)
    if (!empty($HTTPS) && $HTTPS != 'off' && $REQ_URI['scheme'] == 'https') {
        xarConfigVars::set(null, 'Site.Core.EnableSecureServer', true);
    } else {
        xarConfigVars::set(null, 'Site.Core.EnableSecureServer', false);
    }

    xarConfigVars::set(null, 'Site.Core.LoadLegacy', false);
    xarConfigVars::set(null, 'Site.Session.SecurityLevel', 'Medium');
    xarConfigVars::set(null, 'Site.Session.Duration', 7);
    xarConfigVars::set(null, 'Site.Session.InactivityTimeout', 90);
    // use current defaults in includes/xarSession.php
    xarConfigVars::set(null, 'Site.Session.CookieName', '');
    xarConfigVars::set(null, 'Site.Session.CookiePath', '');
    xarConfigVars::set(null, 'Site.Session.CookieDomain', '');
    xarConfigVars::set(null, 'Site.Session.RefererCheck', '');
    xarConfigVars::set(null, 'Site.MLS.TranslationsBackend', 'xml2php');
    // FIXME: <marco> Temporary config vars, ask them at install time
    xarConfigVars::set(null, 'Site.MLS.MLSMode', 'SINGLE');

    // The installer should now set the default locale based on the
    // chosen language, let's make sure that is true
    xarConfigVars::get(null, 'Site.MLS.DefaultLocale','en_US.utf-8');
    $allowedLocales = array('en_US.utf-8');
    xarConfigVars::set(null, 'Site.MLS.AllowedLocales', $allowedLocales);

    // Minimal information for timezone offset handling (see also Site.Core.TimeZone)
    xarConfigVars::set(null, 'Site.MLS.DefaultTimeOffset', 0);

    $authModules = array('authsystem');
    xarConfigVars::set(null, 'Site.User.AuthenticationModules',$authModules);

    // Start Modules Support
    $systemArgs = array('enableShortURLsSupport' => false,
                        'generateXMLURLs' => false);
    xarMod::init($systemArgs);

    // Installation complete; check for upgrades
    return base_upgrade('2.0.0');
}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @returns bool
 */
function base_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0.0':
      break;
    }
    return true;
}

/**
 * Delete this module
 *
 * @return bool
 */
function base_delete()
{
  //this module cannot be removed
  return false;
}

?>