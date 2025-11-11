<?php

/**
 * Handle module installer functions
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base;

use Xaraya\Modules\InstallerClass;
use xarCore;
use xarXMLInstaller;
use Exception;

/**
 * Handle module installer functions
 *
 * @internal replaced base_*() function calls with $this->*() calls
 * @extends InstallerClass<Module>
 */
class Installer extends InstallerClass
{
    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * Initialize the base module
     * @author Marcel van der Boom
     * @return bool True is init was successfull, false if failed.
     * @throws \Exception Thrown if database initialization has failed
     */
    public function init()
    {
        $dbconn = $this->db()->getConn();
        try {
            $dbconn->begin();
            xarXMLInstaller::createTable('table_schema-def', 'base');
            // We're done, commit
            $dbconn->commit();
        } catch (Exception $e) {
            $dbconn->rollback();
            throw $e;
        }
        $prefix = $this->db()->getPrefix();
        // Start Configuration Unit
        $systemArgs = [];
        $this->var()->init($systemArgs);

        /****************************************************************
         * Set System Configuration Variables
         *****************************************************************/
        $this->config()->setVar('System.Core.VersionNum', xarCore::VERSION_NUM);
        $this->config()->setVar('System.Core.VersionId', xarCore::VERSION_ID);
        $this->config()->setVar('System.Core.VersionSub', xarCore::VERSION_SUB);
        $this->config()->setVar('System.Core.VersionRev', xarCore::VERSION_REV);
        $allowedAPITypes = [];
        /*****************************************************************
         * Set site configuration variables
         ******************************************************************/
        $allowableHTML =  [
            '!--' => 2, 'a' => 2, 'b' => 2, 'blockquote' => 2,'br' => 2, 'center' => 2,
            'div' => 2, 'em' => 2, 'font' => 0, 'hr' => 2, 'i' => 2, 'img' => 0, 'li' => 2,
            'marquee' => 0, 'ol' => 2, 'p' => 2, 'pre' => 2, 'span' => 0,'strong' => 2,
            'tt' => 2, 'ul' => 2, 'table' => 2, 'td' => 2, 'th' => 2, 'tr' => 2];

        $this->config()->setVar('Site.Core.AllowableHTML', $allowableHTML);
        $this->config()->setVar('Site.BL.CacheTemplates', true);
        $this->config()->setVar('Site.BL.MemCacheTemplates', false);
        $this->config()->setVar('Site.BL.ThemesDirectory', 'themes');
        $this->config()->setVar('Site.Core.FixHTMLEntities', true);
        $this->config()->setVar('Site.Core.TimeZone', 'Etc/UTC');
        $this->config()->setVar('Site.Core.EnableShortURLsSupport', false);
        $this->config()->setVar('Site.Core.WebserverAllowsSlashes', false);

        // when installing via https, we assume that we want to support that :)
        $HTTPS = $this->req()->getServerVar('HTTPS');
        /* jojodee - monitor this fix.
         Localized fix for installer where HTTPS shows incorrectly as being on in
         some environments. Fix is ok as long as we dont access directly
         outside of installer. Consider setting config vars at later point rather than here.
        */
        $REQ_URI = parse_url($this->req()->getServerVar('HTTP_REFERER'));
        // IIS seems to set HTTPS = off for some reason (cfr. xar::req()->getProtocol)
        if (!empty($HTTPS) && $HTTPS != 'off' && $REQ_URI['scheme'] == 'https') {
            $this->config()->setVar('Site.Core.EnableSecureServer', true);
        } else {
            $this->config()->setVar('Site.Core.EnableSecureServer', false);
        }
        $this->config()->setVar('Site.Core.SecureServerPort', "443");

        $this->config()->setVar('Site.Session.SecurityLevel', 'Medium');
        $this->config()->setVar('Site.Session.Duration', 7);
        $this->config()->setVar('Site.Session.InactivityTimeout', 90);
        $this->config()->setVar('Site.Session.CookieTimeout', 30);
        // use current defaults in includes/xarSession.php
        $this->config()->setVar('Site.Session.CookieName', '');
        $this->config()->setVar('Site.Session.CookiePath', '');
        $this->config()->setVar('Site.Session.CookieDomain', '');
        $this->config()->setVar('Site.Session.RefererCheck', '');
        $this->config()->setVar('Site.MLS.TranslationsBackend', 'xml2php');
        // FIXME: <marco> Temporary config vars, ask them at install time
        $this->config()->setVar('Site.MLS.MLSMode', 'SINGLE');

        // The installer should now set the default locale based on the
        // chosen language, let's make sure that is true
        $this->config()->getVar('Site.MLS.DefaultLocale', 'en_US.utf-8');
        $allowedLocales = ['en_US.utf-8'];
        $this->config()->setVar('Site.MLS.AllowedLocales', $allowedLocales);

        // Minimal information for timezone offset handling (see also Site.Core.TimeZone)
        $this->config()->setVar('Site.MLS.DefaultTimeOffset', 0);

        $authModules = ['authsystem'];
        $this->config()->setVar('Site.User.AuthenticationModules', $authModules);

        // Start Modules Support
        $systemArgs = [
            'enableShortURLsSupport' => false,
            'generateXMLURLs'        => false,
        ];
        // @todo do we want to reset ctl() here?
        //$this->ctl()->init($systemArgs);
        $this->mod()->init($systemArgs);

        // Installation complete; check for upgrades
        return $this->upgrade('2.0.0');
    }

    /**
     * Upgrade this module from an old version
     * @author Marcel van der Boom
     * @param string $oldversion The three digit version number of the currently installed (old) version
     * @return bool Returns true on success, false on failure.
     */
    public function upgrade($oldversion)
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
     * @author Marcel van der Boom
     * @return bool Always returns false. This module cannot be removed.
     */
    public function delete()
    {
        //this module cannot be removed
        return false;
    }
}
