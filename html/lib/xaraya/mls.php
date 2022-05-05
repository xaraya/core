<?php
/**
 * Multi Language System
 *
 * @package core\multilanguage
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini <marco@xaraya.com>
 * @author Roger Raymond <roger@asphyxia.com>
 * @author Marcel van der Boom <mrb@hsdev.com>
 * @author Volodymyr Metenchuk <voll@xaraya.com>
 * @author Marc Lutolf
 * @todo Dynamic Translations
 * @todo Timezone and DST support (default offset is supported now)
 * @todo Write standard core translations
 * @todo Complete changes as described in version 0.9 of MLS RFC
 * @todo Implements the request(ed) locale APIs for backend interactions
 * @todo See how utf-8 works for xml backend
 */

sys::import('xaraya.locales');
sys::import('xaraya.transforms.xarCharset');
sys::import('xaraya.mlsbackends.reference');

/**
 * Multilanguage System Class
 *
 * @package core\multilanguage
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
**/
class xarMLS extends xarObject
{
    const SINGLE_LANGUAGE_MODE          = 'SINGLE';
    const BOXED_MULTI_LANGUAGE_MODE     = 'BOXED';
    const UNBOXED_MULTI_LANGUAGE_MODE   = 'UNBOXED';
    const DNTYPE_CORE       = 1;
    const DNTYPE_THEME      = 2;
    const DNTYPE_MODULE     = 3;
    const DNTYPE_PROPERTY   = 4;
    const DNTYPE_BLOCK      = 5;
    const DNTYPE_OBJECT     = 6;

    public static $mode              = self::SINGLE_LANGUAGE_MODE;
    public static $backendName       = 'xml2php';
    public static $localeDataLoader  = null;
    public static $localeDataCache   = array();
    public static $currentLocale     = '';
    public static $defaultLocale     = 'en_US.utf-8';
    public static $allowedLocales    = array('en_US.utf-8');
    public static $newEncoding       = null;
    public static $defaultTimeZone   = 'UTC';
    public static $defaultTimeOffset = 0;
    public static $backend           = null;

    /**
     * Initializes the Multi Language System
     *
     * @throws Exception
     * @return boolean true
     */
    static public function init(array $args = array())
    {
        if (empty($args)) {
            $args = self::getConfig();
        }
        switch ($args['MLSMode']) {
        case self::SINGLE_LANGUAGE_MODE:
        case self::BOXED_MULTI_LANGUAGE_MODE:
		self::$mode = $args['MLSMode'];
            break;
        case self::UNBOXED_MULTI_LANGUAGE_MODE:
		self::$mode = $args['MLSMode'];
            if (!function_exists('mb_http_input')) {
                // mbstring required
                throw new Exception('xarMLS::init: Mbstring PHP extension is required for UNBOXED MULTI language mode.');
            }
            break;
        default:
	    self::$mode = self::BOXED_MULTI_LANGUAGE_MODE;
            //throw new Exception('xarMLS::init: Unknown MLS mode: '.$args['MLSMode']);
        }
	self::$backendName = $args['translationsBackend'];
    
        // USERLOCALE FIXME Delete after new backend testing
	self::$localeDataLoader = new xarMLS__LocaleDataLoader();
	self::$localeDataCache = array();
    
	self::$currentLocale = '';
    
	self::$defaultLocale = $args['defaultLocale'];
	self::$allowedLocales = $args['allowedLocales'];
    
	self::$newEncoding = new xarCharset;
    
	self::$defaultTimeZone = !empty($args['defaultTimeZone']) ?
                                 $args['defaultTimeZone'] : @date_default_timezone_get();
	self::$defaultTimeOffset = isset($args['defaultTimeOffset']) ?
                                   $args['defaultTimeOffset'] : 0;
    
        // Set the timezone
        date_default_timezone_set(self::$defaultTimeZone);
    
        // Register MLS events
        // These should be done before the xarMLS::setCurrentLocale function
        // These are now registered during base module init
        // @CHECKME: <chris> grep -R xarEvents::trigger . finds no results
        // It appears these events are never raised ?
        // In addition, these seem more like exceptions than 'events' ?
        //xarEvents::register('MLSMissingTranslationString');
        //xarEvents::register('MLSMissingTranslationKey');
        //xarEvents::register('MLSMissingTranslationDomain');
    
        // TODO: reminder for if/when we drop the legacy functions or switch to namespaces someday
        if (!function_exists('xarML')) {
            function xarML($rawstring/*, ...*/)
            {
                return call_user_func_array(array('xarMLS', 'translate'), func_get_args());
            }
	}

        // FIXME: this was previously conditional on User subsystem initialisation,
        // but in the 2.x flow we need it earlier apparently, so made this unconditional
        // *AND* commented out the assertion on running this once per request lower
        // in this file. We need to investigate this better after the MLS refactoring
        self::setCurrentLocale($args['defaultLocale']);
        return true;
    }

    static function getConfig()
    {
        // FIXME: Site.MLS.MLSMode is NULL during install
        $systemArgs = array('MLSMode'             => xarConfigVars::get(null, 'Site.MLS.MLSMode'),
    //                      'translationsBackend' => xarConfigVars::get(null, 'Site.MLS.TranslationsBackend'),
                            'translationsBackend' => 'xml2php',
                            'defaultLocale'       => xarConfigVars::get(null, 'Site.MLS.DefaultLocale'),
                            'allowedLocales'      => xarConfigVars::get(null, 'Site.MLS.AllowedLocales'),
                            'defaultTimeZone'     => xarConfigVars::get(null, 'Site.Core.TimeZone'),
                            'defaultTimeOffset'   => xarConfigVars::get(null, 'Site.MLS.DefaultTimeOffset'),
                            );
        return $systemArgs;
    }

    /**
     * Gets the current MLS mode
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return integer MLS Mode
     */
    static public function getMode()
    {
        return isset(self::$mode) ? self::$mode : self::BOXED_MULTI_LANGUAGE_MODE;
    }

    /**
     * Returns the site locale if running in SINGLE mode,
     * returns the site default locale if running in BOXED or UNBOXED mode
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return string the site locale
     */
    static public function getSiteLocale() { return self::$defaultLocale; }

    /**
     * Returns an array of locales available in the site
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array of locales
     */
    static public function listSiteLocales()
    {
        $mode = self::getMode();
        if ($mode == xarMLS::SINGLE_LANGUAGE_MODE) {
            return array(self::$defaultLocale);
        } else {
            return self::$allowedLocales;
        }
    }

    /**
     * Gets the current locale
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return string current locale
     */
    static public function getCurrentLocale() { return self::$currentLocale; }

    /**
     * Gets the charset component from a locale
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return string the charset name
     * @throws BAD_PARAM
     */
    static public function getCharsetFromLocale($locale)
    {
        if (!$parsedLocale = self::parseLocaleString($locale)) return; // throw back
        return $parsedLocale['charset'];
    }

    // I18N API
    
    /**
     * Translates a string
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return string the translated string, or the original string if no translation is available
     */
    static public function translate($rawstring/*, ...*/)
    {
        // if an empty string is passed in, just return an empty string. it's
        // the most sensible thing to do
        $string = trim($rawstring);
        if($string == '') return $rawstring;
        
        $start = strpos($rawstring, $string);
        $prefix = substr($rawstring,0,$start);
        $suffix = substr($rawstring,$start+strlen($string));
    
        // Make sure string is sane
        // - hex 0D -> ''
        // - space around newline -> ' '
        // - multiple newlines -> 1 newline
    //    $string = preg_replace(array('[\x0d]','/[\t ]+/','/\s*\n\s*/'), array('',' ',"\n"),$string);

        if (isset(self::$backend)) {
            $trans = self::$backend->translate($string,1);
        } else {
            // This happen in rare cases when xarML is called before self::init has been called
            $trans = $string;
        }
    
        if (empty($trans)) {
            // FIXME: postpone
            //xarEvt_fire('MLSMissingTranslationString', $string);
            $trans = $string;
        }
        if (func_num_args() > 1) {
            $args = func_get_args();
            if (is_array($args[1])) $args = $args[1]; // Only the second argument is considered if it's an array
            else array_shift($args); // Drop $string argument
            $trans = self::bindVariables($trans, $args);
        }
    
        return $prefix . $trans . $suffix;
    }

    /**
     * Return the translation associated to passed key
     *
     * @author Marco Canini <marco@xaraya.com>
     * @throws BadParameterException
     * @return string the translation string, or the key if no translation is available
     */
    static public function translateByKey($key/*, ...*/)
    {
        // Key must have a value and not contain spaces
        if(empty($key) || strpos($key," ")) throw new BadParameterException('key');
    
        if (isset(self::$backend)) {
            $trans = self::$backend->translateByKey($key);
        } else {
            // This happen in rare cases when xarMLS::translateByKey is called before self::init has been called
            $trans = $key;
        }
        if (empty($trans)) {
            // FIXME: postpone
            //xarEvt_fire('MLSMissingTranslationKey', $key);
            $trans = $key;
        }
        if (func_num_args() > 1) {
            $args = func_get_args();
            if (is_array($args[1])) $args = $args[1]; // Only the second argument is considered if it's an array
            else array_shift($args); // Unset $string argument
            $trans = self::bindVariables($trans, $args);
        }
    
        return $trans;
    }

    // L10N API (Localisation)
    
    /**
     * Gets the locale info for the specified locale string.
     * Info is an array composed by the 'lang', 'country', 'specializer' and 'charset' items.
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array locale info
     */
    static public function localeGetInfo($locale) { return self::parseLocaleString($locale); }

    /**
     * Gets the locale string for the specified locale info.
     * Info is an array composed by the 'lang', 'country', 'specializer' and 'charset' items.
     *
     * @author Marco Canini <marco@xaraya.com>
     * @throws BadParameterException
     * @return string locale string
     */
    static public function localeGetString($localeInfo)
    {
        if (!isset($localeInfo['lang']) ||
            !isset($localeInfo['country']) ||
            !isset($localeInfo['specializer']) ||
            !isset($localeInfo['charset'])) {
            throw new BadParameterException('localeInfo');
        }
        if (strlen($localeInfo['lang']) != 2) throw new BadParameterException('localeInfo');
    
        $locale = strtolower($localeInfo['lang']);
        if (!empty($localeInfo['country'])) {
            if (strlen($localeInfo['country']) != 2) throw new BadParameterException('localeInfo');
    
            $locale .= '_'.strtoupper($localeInfo['country']);
        }
        if (!empty($localeInfo['charset'])) {
            $locale .= '.'.$localeInfo['charset'];
        } else {
            $locale .= '.utf-8';
        }
        if (!empty($localeInfo['specializer'])) {
            $locale .= '@'.$localeInfo['specializer'];
        }
        return $locale;
    }

    /**
     * Gets a list of locale string which met the specified filter criteria.
     * Filter criteria are set as item of $filter parameter, they can be one or more of the following:
     * lang, country, specializer, charset.
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array locale list
     */
    static public function localeGetList($filter=array())
    {
        $list = array();
        $locales = self::listSiteLocales();
        foreach ($locales as $locale) {
            $l = self::parseLocaleString($locale);
            if (isset($filter['lang']) && $filter['lang'] != $l['lang']) continue;
            if (isset($filter['country']) && $filter['country'] != $l['country']) continue;
            if (isset($filter['specializer']) && $filter['specializer'] != $l['specializer']) continue;
            if (isset($filter['charset']) && $filter['charset'] != $l['charset']) continue;
            $list[] = $locale;
        }
        return $list;
    }

    /**
     *  Returns a valid timestamp for the current user.  It will
     *  make adjustments for timezone and should be used in gmstrftime
     *  or gmdate functions only.
     *
     *  @author Roger Raymond <roger@asphyxia.com>
     *  @return int unix timestamp.
     */
    static public function userTime($time=null,$flag=1)
    {
        // get the current UTC time
        if (!isset($time)) {
            $time = time();
        }
        if ($flag) $time += self::userOffset($time) * 3600;
        // return the corrected timestamp
        return $time;
    }

    /**
     *  Returns the user's current tz offset (+ daylight saving) in hours
     *
     *  @author Roger Raymond <roger@asphyxia.com>
     *  @param int $timestamp optional unix timestamp that we want to get the offset for
     *  @return float tz offset + possible daylight saving adjustment
     */
    static public function userOffset($timestamp = null)
    {
        sys::import('xaraya.structures.datetime');
        $datetime = new XarDateTime();
        $datetime->setTimeStamp($timestamp);
        if (xarUser::isLoggedIn()) {
            $usertz = xarModItemVars::get('roles','usertimezone',xarSession::getVar('role_id'));
        } else {
            $usertz = xarConfigVars::get(null, 'Site.Core.TimeZone');
        }
        $useroffset = $datetime->getTZOffset($usertz);
    
        return $useroffset/3600;
    }

    /**
     * Sets current locale
     *
     * @author Marco Canini <marco@xaraya.com>
     * @param locale site locale
     */
    static public function setCurrentLocale($locale)
    {
        xarLog::message("Changing the default locale from ". self::getCurrentLocale() . " to " . $locale, xarLog::LEVEL_INFO);
        
        // Only refresh if we need to
        if (self::getCurrentLocale() == $locale) return true;
        
        static $called = 0;
    
        // FIXME: during initialisation, the current locale was set, and it gets called
        // again during user subsystem initialisation, we have to provide better defaults
        // if we really want this to run only once.
    
        $called++;
    
        $mode = self::getMode();
        switch ($mode) {
        case xarMLS::SINGLE_LANGUAGE_MODE:
                $locale  = self::getSiteLocale();
                break;
        case xarMLS::UNBOXED_MULTI_LANGUAGE_MODE:
        case xarMLS::BOXED_MULTI_LANGUAGE_MODE:
            // check for locale availability
            $siteLocales = self::listSiteLocales();
            if (!in_array($locale, $siteLocales)) {
                // Locale not available, use the default
                $locale = self::getSiteLocale();
                xarLog::message("Falling back to default locale: $locale", xarLog::LEVEL_INFO);
            }
        }

        // Set current locale
	self::$currentLocale = $locale;
    
        $curCharset = self::getCharsetFromLocale($locale);
        if ($mode == xarMLS::UNBOXED_MULTI_LANGUAGE_MODE) {
            assert($curCharset == "utf-8");
            // To be able to continue, we set the mode to BOXED
            if ($curCharset != "utf-8") {
                xarLog::message("Resetting MLS mode to BOXED", xarLog::LEVEL_INFO);
                xarConfigVars::set(null, 'Site.MLS.MLSMode', self::BOXED_MULTI_LANGUAGE_MODE);
            } else {
                if (!xarCore::funcIsDisabled('ini_set')) ini_set('mbstring.func_overload', 7);
                mb_internal_encoding($curCharset);
            }
        }
    
        //if ($mode == xarMLS::BOXED_MULTI_LANGUAGE_MODE) {
        //if (substr($curCharset, 0, 9) != 'iso-8859-' &&
        //$curCharset != 'windows-1251') {
        // Do not use mbstring for single byte charsets
    
        //}
        //}
    
        $alternatives = self::getLocaleAlternatives($locale);
        switch (self::$backendName) {
        case 'xml':
            sys::import('xaraya.mlsbackends.xml');
	    self::$backend = new xarMLS__XMLTranslationsBackend($alternatives);
            break;
        case 'php':
            sys::import('xaraya.mlsbackends.php');
	    self::$backend = new xarMLS__PHPTranslationsBackend($alternatives);
            break;
        case 'xml2php':
            sys::import('xaraya.mlsbackends.xml2php');
	    self::$backend = new xarMLS__XML2PHPTranslationsBackend($alternatives);
            break;
        }

        // Load core translations
        self::_loadTranslations(xarMLS::DNTYPE_CORE, 'xaraya', 'core:', 'core');
        return true;
    }

    /**
     * Loads translations for the specified context
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return boolean
     */
    static public function _loadTranslations($domainType, $domainName, $contextType, $contextName)
    {
        static $loadedCommons = array();
        static $loadedTranslations = array();
    
        xarLog::message("MLS: Loading translations for the context ". "$domainType,$domainName,$contextType,$contextName", xarLog::LEVEL_DEBUG);

        if (!isset(self::$backend)) {
            xarLog::message("xarMLS: No translation backend was selected for ". "$domainType,$domainName,$contextType,$contextName", xarLog::LEVEL_WARNING);
            return false;
        }
        if (empty(self::$currentLocale)) {
            xarLog::message("xarMLS: No current locale was selected", xarLog::LEVEL_WARNING);
            return false;
        }
    
        // only load each translation once
        if (isset($loadedTranslations["$domainType.$domainName.$contextType.$contextName"])) {
            return $loadedTranslations["$domainType.$domainName.$contextType.$contextName"];
        }

        if (self::$backend->bindDomain($domainType, $domainName)) {
            switch ($domainType) {
                case xarMLS::DNTYPE_THEME:
                    // Load common translations
                    if (!isset($loadedCommons[$domainName.'theme'])) {
                        $loadedCommons[$domainName.'theme'] = true;
                        if (!self::$backend->loadContext('themes:', 'common')) return;
                    }
                break;
                case xarMLS::DNTYPE_MODULE:
                    // Handle in a special way the module type
                    // for which it's necessary to load common translations
                    if (!isset($loadedCommons[$domainName.'module'])) {
                        $loadedCommons[$domainName.'module'] = true;
                        if (!self::$backend->loadContext('modules:', 'common')) return;
                        if (!self::$backend->loadContext('modules:', 'version')) return;
                    }
                break;
                case xarMLS::DNTYPE_PROPERTY:
                    // Load common translations
                    if (!isset($loadedCommons[$domainName.'property'])) {
                        $loadedCommons[$domainName.'property'] = true;
                        if (!self::$backend->loadContext('properties:', 'common')) return;
                    }
                break;
                case xarMLS::DNTYPE_BLOCK:
                    // Load common translations
                    if (!isset($loadedCommons[$domainName.'block'])) {
                        $loadedCommons[$domainName.'block'] = true;
                        if (!self::$backend->loadContext('blocks:', 'common')) return;
                    }
                break;
                case xarMLS::DNTYPE_OBJECT:
                    // Load common translations
                    if (!isset($loadedCommons[$domainName.'object'])) {
                        $loadedCommons[$domainName.'object'] = true;
                        if (!self::$backend->loadContext('objects:', 'common')) return;
                    }
                break;
            }

            if (!self::$backend->loadContext($contextType, $contextName)) return;
            $loadedTranslations["$domainType.$domainName.$contextType.$contextName"] = true;
            return true;
        } else {
            // FIXME: postpone
            //xarEvt_fire('MLSMissingTranslationDomain', array($domainType, $domainName));
    
            $loadedTranslations["$domainType.$domainName.$contextType.$contextName"] = false;
            return false;
        }
    }

    /**
     * Load relevant translations for a specified relatvive path (be it file or directory)
     *
     * @author Marcel van der Boom <mrb@hsdev.com>
     * @return boolean true on success, false on failure
     * @todo slowly add more intelligence for more scopes. (core, version, init?)
     * @todo static hash on path to prevent double loading?
     * @todo is directory support needed? i.e. modules/base/ load all for base module? or how does this work?
     * @todo pnFile.php type files support needed?
     * @todo xarversion.php type files support
     * @todo xar(whatever)api.php type files support? (javascript for example)
     * @todo do we want core per file support?
     **/
    static public function loadTranslations($path)
    {
        xarLog::message("MLS: Loading translations for the path: $path", xarLog::LEVEL_DEBUG);
        if(!file_exists($path)) {
            xarLog::message("MLS: Failed loading translations for a non-existing path ($path)", xarLog::LEVEL_WARNING);
            //die($path);
            return true;
        }
    
        $domainArray = xarMLSContext::getContextFromPath($path);
        if(empty($domainArray)) {
            // some non-standard file from another location, e.g. from var/processes for workflows
            return true;
        }
        $domainType = $domainArray[0];
        
        // If this is a core file, get the translations and bail
        if ($domainType == xarMLS::DNTYPE_CORE) {
            $translations = self::_loadTranslations(xarMLS::DNTYPE_CORE, 'xaraya', 'core:', 'core');
            return $translations;        
        }

        // Themes can override other domain types
        if ($domainType == xarMLS::DNTYPE_THEME) {
            $possibleOverride = true;
        } else {
            $possibleOverride = false;
        }

        // Ok, based on possible overrides, we load internal only, or interal plus overrides
        $ok = false;
        if($possibleOverride) {
            $ok= self::_loadTranslations(xarMLS::DNTYPE_MODULE, $domainArray[1], $domainArray[2], $domainArray[3]);
        }
        // And load the determined stuff
        // @todo: should we check for success on *both*, where is the exception here? further up the tree?
        $ok = self::_loadTranslations($domainType, $domainArray[1], $domainArray[2], $domainArray[3]);
        return $ok;
    }

    static public function convertFromInput($var, $method)
    {
        // FIXME: <marco> Can we trust browsers?
        if (self::getMode() == xarMLS::SINGLE_LANGUAGE_MODE ||
            !function_exists('mb_http_input')) {
            return $var;
        }
        // CHECKME: check this code
        return $var;
        // Cookies must contain only US-ASCII characters
        $inputCharset = strtolower(mb_http_input($method));
        $curCharset = self::getCharsetFromLocale(self::getCurrentLocale());
        if ($inputCharset != $curCharset) {
            $var = mb_convert_encoding($var, $curCharset, $inputCharset);
        }
        return $var;
    }

    // CHECKME: is this used anywhere?
    static private function xarMLS__convertFromCharset($var, $charset)
    {
        // FIXME: <marco> Can we trust browsers?
        if (self::getMode() == xarMLS::SINGLE_LANGUAGE_MODE ||
            !function_exists('mb_convert_encoding')) return $var;
        $curCharset = self::getCharsetFromLocale(self::getCurrentLocale());
        $var = mb_convert_encoding($var, $curCharset, $charset);
        return $var;
    }

    static private function bindVariables($string, $args)
    {
        // FIXME: <marco> Consider to use strtr to do the same, can we?
        $i = 1;
        foreach($args as $var) {
            $search = "#($i)";
            $string = str_replace($search, $var, $string);
            $i++;
        }
        return $string;
    }

    /**
     * Gets a list of alternatives for a certain locale.
     * The first alternative is the locale itself
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array alternative locales
     */
    static private function getLocaleAlternatives($locale)
    {
        if (!$parsedLocale = self::parseLocaleString($locale)) return; // throw back
        extract($parsedLocale); // $lang, $country, $charset
    
        $alternatives = array($locale);
        if (!empty($country) && !empty($specializer)) $alternatives[] = $lang.'_'.$country.'.'.$charset;
        if (!empty($country) && empty($specializer)) $alternatives[] = $lang.'.'.$charset;
    
        return $alternatives;
    }

    /**
     * Parses a locale string into an associative array composed of
     * lang, country, specializer and charset keys
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array parsed locale
     */
    static public function parseLocaleString($locale)
    {
        $res = array('lang'=>'', 'country'=>'', 'specializer'=>'', 'charset'=>'utf-8');
        // Match the locales standard format  : en_US.iso-8859-1
        // Thus: language code lowercase(2), country code uppercase(2), encoding lowercase(1+)
        if (!preg_match('/([a-z][a-z])(_([A-Z][A-Z]))?(\.([0-9a-z\-]+))?(@([0-9a-zA-Z]+))?/', $locale, $matches)) {
            throw new BadParameterException('locale');
        }
    
        $res['lang'] = $matches[1];
        if (!empty($matches[3])) $res['country'] = $matches[3];
        if (!empty($matches[5])) $res['charset'] = $matches[5];
        if (!empty($matches[7])) $res['specializer'] = $matches[7];
    
        return $res;
    }

    /**
     * Gets the single byte charset most typically used in the Web for the
     * requested language
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return string the charset
     * @todo   Dont hardcode this
     */
    // CHECKME: is this used anywhere?
    static private function xarMLS__getSingleByteCharset($langISO2Code)
    {
        static $charsets = array(
            'af' => 'iso-8859-1', 'sq' => 'iso-8859-1',
            'ar' => 'iso-8859-6',  'eu' => 'iso-8859-1',  'bg' => 'iso-8859-5',
            'be' => 'iso-8859-5',  'ca' => 'iso-8859-1',  'hr' => 'iso-8859-2',
            'cs' => 'iso-8859-2',  'da' => 'iso-8859-1',  'nl' => 'iso-8859-1',
            'en' => 'iso-8859-1',  'eo' => 'iso-8859-3',  'et' => 'iso-8859-15',
            'fo' => 'iso-8859-1',  'fi' => 'iso-8859-1',  'fr' => 'iso-8859-1',
            'gl' => 'iso-8859-1',  'de' => 'iso-8859-1',  'el' => 'iso-8859-7',
            'iw' => 'iso-8859-8',  'hu' => 'iso-8859-2',  'is' => 'iso-8859-1',
            'ga' => 'iso-8859-1',  'it' => 'iso-8859-1',  //'ja' => '',
            'lv' => 'iso-8859-13', 'lt' => 'iso-8859-13', 'mk' => 'iso-8859-5',
            'mt' => 'iso-8859-3',  'no' => 'iso-8859-1',  'pl' => 'iso-8859-2',
            'pt' => 'iso-8859-1',  'ro' => 'iso-8859-2',  'ru' => 'windows-1251',
            'gd' => 'iso-8859-1',  'sr' => 'iso-8859-2',  'sk' => 'iso-8859-2',
            'sl' => 'iso-8859-2',  'es' => 'iso-8859-1',  'sv' => 'iso-8859-1',
            'tr' => 'iso-8859-9',  'uk' => 'iso-8859-5'
        );
    
        return @$charsets[$langISO2Code];
    }


    /**
     * Create directories tree
     *
     * @author Volodymyr Metenchuk <voll@xaraya.com>
     * @return boolean true
     */
    static public function mkdirr($path)
    {
        // Check if directory already exists
        if (is_dir($path) || empty($path)) {
            return true;
        }
    
        // Crawl up the directory tree
        $next_path = substr($path, 0, strrpos($path, '/'));
        if (self::mkdirr($next_path)) {
            if (!file_exists($path)) {
                $madeDir = @mkdir($path, 0700);
                if (!$madeDir) {
                    $msg = xarML("The directories under #(1) must be writeable by PHP.", $next_path);
                    xarLog::message($msg, xarLog::LEVEL_ERROR);
                    // throw new PermissionException?
                }
                return $madeDir;
            }
        }
        return false;
    }

    /**
     * Check directory writability and create directory if it doesn't exist
     *
     * @author Volodymyr Metenchuk <voll@xaraya.com>
     * @access protected
     * @return bool true
     */
    static public function iswritable($directory=NULL)
    {
        if ($directory == NULL) {
            $directory = getcwd();
        }
    
        if (file_exists($directory)) {
            if (!is_dir($directory)) return false;
            $isWritable = true;
            $isWritable &= is_writable($directory);
            $handle = opendir($directory);
            while ($isWritable && (false !== ($filename = readdir($handle)))) {
                if (($filename != ".") && ($filename != "..") && ($filename != "SCCS")) {
                    if (is_dir($directory."/".$filename)) {
                        $isWritable &= is_writable($directory."/".$filename);
                        $isWritable &= self::iswritable($directory."/".$filename);
                    } else {
                        $isWritable &= is_writable($directory."/".$filename);
                    }
                }
            }
            return $isWritable;
        } else {
            $isWritable = self::mkdirr($directory);
            return $isWritable;
        }
    }
}

/**
 * Multilanguage Context Class
 *
 * @package core\multilanguage
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
**/
class xarMLSContext extends xarObject
{
    static private $domains = array(
                xarMLS::DNTYPE_CORE     => array('context_type_prefix' => 'xaraya',     'context_type_text' => 'core'),
                xarMLS::DNTYPE_THEME    => array('context_type_prefix' => 'themes',     'context_type_text' => 'theme'),
                xarMLS::DNTYPE_MODULE   => array('context_type_prefix' => 'modules',    'context_type_text' => 'module'),
                xarMLS::DNTYPE_PROPERTY => array('context_type_prefix' => 'properties', 'context_type_text' => 'property'),
                xarMLS::DNTYPE_BLOCK    => array('context_type_prefix' => 'blocks',     'context_type_text' => 'block'),
                xarMLS::DNTYPE_OBJECT   => array('context_type_prefix' => 'objects',    'context_type_text' => 'object'),
                            );
    static private $current_domain_type = xarMLS::DNTYPE_CORE;
    
    /**
     * Initializes the Context Class
     *
     * @throws Exception
     * @return boolean true
     */
    static public function init(array $args = array())
    {
        return true;
    }
    
    static public function setDomainType($domainType_id=xarMLS::DNTYPE_CORE)
    {
        self::$current_domain_type = $domainType_id;
    }
    
    static public function getContextFromPath($path='')
    {
        // @todo be able to handle standard files from other locations, e.g. from /vendor/ with composer
        if (strpos($path, sys::lib()) === 0) {
            $domainType = xarMLS::DNTYPE_CORE;
            $path = substr($path, strlen(sys::lib()));
        } elseif (strpos($path, xarTpl::getBasedir()) === 0) {
            $domainType = xarMLS::DNTYPE_THEME;
        } elseif (strpos($path, sys::code()) === 0) {
            // This is a module, property or block file
            $path = substr($path, strlen(sys::code()));
            if (strpos($path, 'modules') === 0) {
                $domainType = xarMLS::DNTYPE_MODULE;
            } elseif (strpos($path, 'properties') === 0) {
                $domainType = xarMLS::DNTYPE_PROPERTY;
            } elseif (strpos($path, 'blocks') === 0) {
                $domainType = xarMLS::DNTYPE_BLOCK;
            }
        } else {
            // some non-standard file from another location, e.g. from var/processes for workflows
            $domainType = 0;
            return false;
        }

        // Get a structured representation of the rest of the path
        $pathElements = explode("/",$path);
    
        // Determine domainName
        // The specifics within that Type are in the next element, overridden or not
        // NOTE: $pathElements changes here!
        $domainName = array_shift($pathElements);
    
        // Determine contextName, which is just the basename of the file without extension it seems
        $contextName = preg_replace('/^(xar)?(.+)\..*$/', '$2', array_pop($pathElements));
    
        // Determine the contextType: bein by getting its prefix
        $contextType = self::getContextTypePrefix($domainType);
        $contextType .= ":";
        
        // Determine contextType further if needed (i.e. more path components are there)
        // Peek into the first element and unwind the rest of the path elements into $ctxType
        // xartemplates -> templates, xarblocks -> blocks, xarproperties -> properties etc.
        // NOTE: pnFile.php type files support needed?
        if(!empty($pathElements)) {
            $pathElements[0] = preg_replace('/^xar(.+)/','$1',$pathElements[0]);
            $contextType .= implode("/",$pathElements);
        }
        
        $contextArray = array(
                            $domainType,
                            $domainName,
                            $contextType,
                            $contextName,
                            );
        return $contextArray;
    }

    static public function getContextTypePrefix($domainType=null)
    {
        if (empty($domainType)) $domainType = self::$current_domain_type;
        $current_domain = self::$domains[$domainType];
        return $current_domain['context_type_prefix'];
    }

    static public function getContextTypeText($domainType=null)
    {
        if (empty($domainType)) $domainType = self::$current_domain_type;
        $current_domain = self::$domains[$domainType];
        return $current_domain['context_type_text'];
    }

    static public function getContextTypeComponents($contextType=null)
    {
        $parts = explode(':', $contextType);
        
        // Check the validity of the prefix
        $good = false;
        foreach (self::$domains as $domain){
            if ($domain['context_type_prefix'] == $parts[0]) continue;
            $good = true;
        }
        if (!$good) die("Incorrect context prefix " . $parts[0]);
        
        // Remove any empty chars in the directory
        $parts[1] = trim($parts[1]);
        
        // Return the prefix and directory
        return $parts;
    }
    
    static public function getDomainPath($domainType, $locale, $backendType, $domainName="xaraya")
    {
        $prefix = self::getContextTypePrefix($domainType);
        $domainpath  = sys::varpath() . "/locales/" . $locale . "/" . $backendType . "/" . $prefix;

        if (!in_array($prefix, array("xaraya","objects"))) {
            $domainpath  .= "/" . $domainName;
        }
        return $domainpath;
    }
}


// Legacy calls - import by default for now...
sys::import('xaraya.legacy.mls');
