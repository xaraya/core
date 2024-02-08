<?php
/**
 * Base Module Initialisation
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author Marcel van der Boom
 */
/**
 * Initialize the base module
 * 
 * @author Marcel van der Boom
 *
 * @return boolean True is init was successfull, false if failed.
 * @throws Exception Thrown if database initialization has failed
 */
function base_init()
{
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        sys::import('xaraya.tableddl');
        xarXMLInstaller::createTable('table_schema-def', 'base');
        // We're done, commit
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }
    $prefix = xarDB::getPrefix();
    // Start Configuration Unit
    sys::import('xaraya.variables');
    $systemArgs = array();
    xarVar::init($systemArgs);

    /****************************************************************
     * Set System Configuration Variables
     *****************************************************************/
    xarConfigVars::set(null, 'System.Core.VersionNum', xarCore::VERSION_NUM);
    xarConfigVars::set(null, 'System.Core.VersionId', xarCore::VERSION_ID);
    xarConfigVars::set(null, 'System.Core.VersionSub', xarCore::VERSION_SUB);
    xarConfigVars::set(null, 'System.Core.VersionRev', xarCore::VERSION_REV);
    $allowedAPITypes = array();
    /*****************************************************************
     * Set site configuration variables
     ******************************************************************/
    $allowableHTML = array (
                            '!--'=>2, 'a'=>2, 'b'=>2, 'blockquote'=>2,'br'=>2, 'center'=>2,
                            'div'=>2, 'em'=>2, 'font'=>0, 'hr'=>2, 'i'=>2, 'img'=>0, 'li'=>2,
                            'marquee'=>0, 'ol'=>2, 'p'=>2, 'pre'=> 2, 'span'=>0,'strong'=>2,
                            'tt'=>2, 'ul'=>2, 'table'=>2, 'td'=>2, 'th'=>2, 'tr'=> 2);

    xarConfigVars::set(null, 'Site.Core.AllowableHTML',$allowableHTML);
    xarConfigVars::set(null, 'Site.BL.CacheTemplates',true);
    xarConfigVars::set(null, 'Site.BL.MemCacheTemplates',false);
    xarConfigVars::set(null, 'Site.BL.ThemesDirectory','themes');
    xarConfigVars::set(null, 'Site.Core.FixHTMLEntities',true);
    xarConfigVars::set(null, 'Site.Core.TimeZone', 'Etc/UTC');
    xarConfigVars::set(null, 'Site.Core.EnableShortURLsSupport', false);
    xarConfigVars::set(null, 'Site.Core.WebserverAllowsSlashes', false);
    
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
    xarConfigVars::set(null, 'Site.Core.SecureServerPort', "443");

    xarConfigVars::set(null, 'Site.Core.LoadLegacy', false);
    xarConfigVars::set(null, 'Site.Session.SecurityLevel', 'Medium');
    xarConfigVars::set(null, 'Site.Session.Duration', 7);
    xarConfigVars::set(null, 'Site.Session.InactivityTimeout', 90);
    xarConfigVars::set(null, 'Site.Session.CookieTimeout', 30);
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
 * @author Marcel van der Boom
 * 
 * @param string $oldversion The three digit version number of the currently installed (old) version
 * @return boolean Returns true on success, false on failure. 
 */
function base_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        default:
        break;
    }
    return true;
}

/**
 * Delete this module
 *
 * @author Marcel van der Boom
 * 
 * @return boolean Always returns false. This module cannot be removed.
 */
function base_delete()
{
  //this module cannot be removed
  return false;
}
