<?php
/**
 * Multi Language System
 *
 * @package multilanguage
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 * @todo Dynamic Translations
 *       Timezone and DST support (default offset is supported now)
 *       Write standard core translations
 *       Complete changes as described in version 0.9 of MLS RFC
 *       Implements the request(ed) locale APIs for backend interactions
 *       See how utf-8 works for xml backend
 */

/**
 * Multilange package defines
 */
define('XARMLS_SINGLE_LANGUAGE_MODE', 'SINGLE');
define('XARMLS_BOXED_MULTI_LANGUAGE_MODE', 'BOXED');
define('XARMLS_UNBOXED_MULTI_LANGUAGE_MODE', 'UNBOXED');


define('XARMLS_DNTYPE_CORE', 1);
define('XARMLS_DNTYPE_THEME', 2);
define('XARMLS_DNTYPE_MODULE', 3);

require_once dirname(__FILE__)."/xarLocale.php";
require_once dirname(__FILE__)."/transforms/xarCharset.php";

/**
 * Initializes the Multi Language System
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @return bool true
 */
function xarMLS_init($args, $whatElseIsGoingLoaded)
{
    switch ($args['MLSMode']) {
    case XARMLS_SINGLE_LANGUAGE_MODE:
    case XARMLS_BOXED_MULTI_LANGUAGE_MODE:
        $GLOBALS['xarMLS_mode'] = $args['MLSMode'];
        break;
    case XARMLS_UNBOXED_MULTI_LANGUAGE_MODE:
        $GLOBALS['xarMLS_mode'] = $args['MLSMode'];
        if (!function_exists('mb_http_input')) {
            // mbstring required
            xarCore_die('xarMLS_init: Mbstring PHP extension is required for UNBOXED MULTI language mode.');
        }
        break;
    default:
        xarCore_die('xarMLS_init: Unknown MLS mode: '.$args['MLSMode']);
    }
    $GLOBALS['xarMLS_backendName'] = $args['translationsBackend'];
/* TODO: delete after new backend testing
    if ($GLOBALS['xarMLS_backendName'] != 'php' && $GLOBALS['xarMLS_backendName'] != 'xml' && $GLOBALS['xarMLS_backendName'] != 'xml2php') {
        xarCore_die('xarML_init: Unknown translations backend: '.$GLOBALS['xarMLS_backendName']);
    }
*/
    // USERLOCALE FIXME Delete after new backend testing
    $GLOBALS['xarMLS_localeDataLoader'] = new xarMLS__LocaleDataLoader();
    $GLOBALS['xarMLS_localeDataCache'] = array();

    $GLOBALS['xarMLS_currentLocale'] = '';
    $GLOBALS['xarMLS_defaultLocale'] = $args['defaultLocale'];
    $GLOBALS['xarMLS_allowedLocales'] = $args['allowedLocales'];

    $GLOBALS['xarMLS_newEncoding'] = new xarCharset;

    $GLOBALS['xarMLS_defaultTimeZone'] = isset($args['defaultTimeZone']) ?
                                         $args['defaultTimeZone'] : '';
    $GLOBALS['xarMLS_defaultTimeOffset'] = isset($args['defaultTimeOffset']) ?
                                           $args['defaultTimeOffset'] : 0;

    // Register MLS events
    // These should be done before the xarMLS_setCurrentLocale function
    xarEvt_registerEvent('MLSMissingTranslationString');
    xarEvt_registerEvent('MLSMissingTranslationKey');
    xarEvt_registerEvent('MLSMissingTranslationDomain');

    if (!($whatElseIsGoingLoaded & XARCORE_SYSTEM_USER)) {
        // The User System won't be started
        // MLS will use the default locale
        xarMLS_setCurrentLocale($args['defaultLocale']);
    }

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarMLS__shutdown_handler');
    return true;
}

/**
 * Shutdown handler for the MLS subsystem
 *
 * @access private
 */
function xarMLS__shutdown_handler()
{
    //xarLogMessage("xarMLS shutdown handler");
}

/**
 * Gets the current MLS mode
 *
 * @access public
 * @author Marco Canini <marco@xaraya.com>
 * @return integer MLS Mode
 */
function xarMLSGetMode()
{
    if (isset($GLOBALS['xarMLS_mode'])){
        return $GLOBALS['xarMLS_mode'];
    } else {
        return 'BOXED';
    }
}

/**
 * Returns the site locale if running in SINGLE mode,
 * returns the site default locale if running in BOXED or UNBOXED mode
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string the site locale
 * @todo   check
 */
function xarMLSGetSiteLocale()
{
    return $GLOBALS['xarMLS_defaultLocale'];
}

/**
 * Returns an array of locales available in the site
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return array of locales
 * @todo   check
 */
function xarMLSListSiteLocales()
{
    $mode = xarMLSGetMode();
    if ($mode == XARMLS_SINGLE_LANGUAGE_MODE) {
        return array($GLOBALS['xarMLS_defaultLocale']);
    } else {
        return $GLOBALS['xarMLS_allowedLocales'];
    }
}

/**
 * Gets the current locale
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string current locale
 */
function xarMLSGetCurrentLocale()
{
    return $GLOBALS['xarMLS_currentLocale'];
}

/**
 * Gets the charset component from a locale
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string the charset name
 * @raise BAD_PARAM
 */
function xarMLSGetCharsetFromLocale($locale)
{
    if (!$parsedLocale = xarMLS__parseLocaleString($locale)) return; // throw back
    return $parsedLocale['charset'];
}

// I18N API

/**
 * Translates a string
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string the translated string, or the original string if no translation is available
 */
function xarML($string/*, ...*/)
{
    // if an empty string is passed in, just return an empty string. it's
    // the most sensible thing to do
    if(empty($string)) return '';

    // Make sure string is sane
    $string=preg_replace('[\x0d]','',$string);
    // Delete extra whitespaces and spaces around newline
    $string = preg_replace('/[\t ]+/',' ',$string);
    $string = preg_replace('/\s*\n\s*/',"\n",$string);

    if (isset($GLOBALS['xarMLS_backend'])) {
        $trans = $GLOBALS['xarMLS_backend']->translate($string,1);
    } else {
        // This happen in rare cases when xarML is called before xarMLS_init has been called
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
        $trans = xarMLS__bindVariables($trans, $args);
    }

    return $trans;
}

/**
 * Return the translation associated to passed key
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string the translation string, or the key if no translation is available
 */
function xarMLByKey($key/*, ...*/)
{
    // Key must have a value and not contain spaces
    if(empty($key) || strpos($key," ")) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM');
        return;
    }


    if (isset($GLOBALS['xarMLS_backend'])) {
        $trans = $GLOBALS['xarMLS_backend']->translateByKey($key);
    } else {
        // This happen in rare cases when xarMLByKey is called before xarMLS_init has been called
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
        $trans = xarMLS__bindVariables($trans, $args);
    }

    return $trans;
}

// L10N API (Localisation)

/**
 * Gets the locale info for the specified locale string.
 * Info is an array composed by the 'lang', 'country', 'specializer' and 'charset' items.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return array locale info
 */
function xarLocaleGetInfo($locale)
{
    return xarMLS__parseLocaleString($locale);
}

/**
 * Gets the locale string for the specified locale info.
 * Info is an array composed by the 'lang', 'country', 'specializer' and 'charset' items.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string locale string
 */
function xarLocaleGetString($localeInfo)
{
    if (!isset($localeInfo['lang']) || !isset($localeInfo['country']) || !isset($localeInfo['specializer']) || !isset($localeInfo['charset'])) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'localeInfo');
        return;
    }
    if (strlen($localeInfo['lang']) != 2) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'localeInfo');
        return;
    }
    $locale = strtolower($localeInfo['lang']);
    if (!empty($localeInfo['country'])) {
        if (strlen($localeInfo['country']) != 2) {
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'localeInfo');
            return;
        }
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
 * @access public
 * @return array locale list
 */
function xarLocaleGetList($filter=array())
{
    $list = array();
    $locales = xarMLSListSiteLocales();
    foreach ($locales as $locale) {
        $l = xarMLS__parseLocaleString($locale);
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
 *  @access protected
 *  @return int unix timestamp.
 */
function xarMLS_userTime($time=null)
{
    // get the current UTC time
    if (!isset($time)) {
        $time = time();
    }
    $time += xarMLS_userOffset($time) * 3600;
    // return the corrected timestamp
    return $time;
}

/**
 *  Returns the user's current tz offset (+ daylight saving) in hours
 *
 *  @author Roger Raymond <roger@asphyxia.com>
 *  @access protected
 *  @param int $timestamp optional unix timestamp that we want to get the offset for
 *  @return float tz offset + possible daylight saving adjustment
 */
function xarMLS_userOffset($timestamp = null)
{
    static $offset; // minimal information for timezone offset handling
    static $timezone; // more information for daylight saving

    if (!isset($offset)) {
    // CHECKME: use dynamicdata for roles, module user variable and/or session variable
    //          (see also 'locale' in xarUserGetNavigationLocale())
        // get the correct timezone offset for this user
        if (xarUserIsLoggedIn()) {
            $offset = xarUserGetVar('timezone');
            // get the actual timezone for the user (in addition to the timezone offset)
            if (isset($offset) && !is_numeric($offset)) {
                $info = @unserialize($offset);
                if (!empty($info) && is_array($info)) {
                    $offset = isset($info['offset']) ? $info['offset'] : null;
                    $timezone = isset($info['timezone']) ? $info['timezone'] : null;
                }
            }
        }
        if (!isset($offset)) {
            // use default time offset for this site
            $offset = $GLOBALS['xarMLS_defaultTimeOffset'];
            // use default timezone for this site
            $timezone = $GLOBALS['xarMLS_defaultTimeZone'];
        }
    }
    // this will depend on the current $timestamp
    if (isset($timestamp) && !empty($timezone) && function_exists('xarModAPIFunc')) {
        $adjust = xarModAPIFunc('base','user','dstadjust',
                                array('timezone' => $timezone,
                                      // pass the timestamp *with* the offset
                                      'time'     => $timestamp + $offset * 3600));
        //echo $adjust;
    } else {
        $adjust = 0;
    }
    return $offset + $adjust;
}

// PROTECTED FUNCTIONS

/**
 * Sets current locale
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @param locale site locale
 */
function xarMLS_setCurrentLocale($locale)
{
    static $called = 0;

    assert('$called == 0; // Can only be called once during a page request');
    $called++;

    $mode = xarMLSGetMode();
    switch ($mode) {
    case XARMLS_SINGLE_LANGUAGE_MODE:
            $locale  = xarMLSGetSiteLocale();
            break;
    case XARMLS_UNBOXED_MULTI_LANGUAGE_MODE:
    case XARMLS_BOXED_MULTI_LANGUAGE_MODE:
        // check for locale availability
        $siteLocales = xarMLSListSiteLocales();
        if (!in_array($locale, $siteLocales)) {
            // Locale not available, use the default
            $locale = xarMLSGetSiteLocale();
            xarLogMessage("WARNING: falling back to default locale: $locale");
        }
    }
    // Set current locale
    $GLOBALS['xarMLS_currentLocale'] = $locale;

    $curCharset = xarMLSGetCharsetFromLocale($locale);
    if ($mode == XARMLS_UNBOXED_MULTI_LANGUAGE_MODE) {
        assert('$curCharset == "utf-8"; // Resetting MLS Mode to BOXED');
        // To be able to continue, we set the mode to BOXED
        if ($curCharset != "utf-8") {
            xarLogMessage("Resetting MLS mode to BOXED");
            xarConfigSetVar('Site.MLS.MLSMode','BOXED');
        } else {
            if (!xarFuncIsDisabled('ini_set')) ini_set('mbstring.func_overload', 7);
            mb_internal_encoding($curCharset);
        }
    }

    //if ($mode == XARMLS_BOXED_MULTI_LANGUAGE_MODE) {
    //if (substr($curCharset, 0, 9) != 'iso-8859-' &&
    //$curCharset != 'windows-1251') {
    // Do not use mbstring for single byte charsets

    //}
    //}

    $alternatives = xarMLS__getLocaleAlternatives($locale);
/* TODO: delete after new backend testing
    switch ($GLOBALS['xarMLS_backendName']) {
    case 'xml':
        include_once 'includes/xarMLSXMLBackend.php';
        $GLOBALS['xarMLS_backend'] = new xarMLS__XMLTranslationsBackend($alternatives);
        break;
    case 'php':
        include_once 'includes/xarMLSPHPBackend.php';
        $GLOBALS['xarMLS_backend'] = new xarMLS__PHPTranslationsBackend($alternatives);
        break;
    case 'xml2php':
*/
        include_once 'includes/xarMLSXML2PHPBackend.php';
        $GLOBALS['xarMLS_backend'] = new xarMLS__XML2PHPTranslationsBackend($alternatives);

/*
        break;
    }
*/
    // Load core translations
    xarMLS_loadTranslations(XARMLS_DNTYPE_CORE, 'xaraya', 'core:', 'core');

    //xarMLSLoadLocaleData($locale);
}

/**
 * Loads translations for the specified context
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @return bool
 */
function xarMLS_loadTranslations($dnType, $dnName, $ctxType, $ctxName)
{
    static $loadedCommons = array();
    static $loadedTranslations = array();

    if (!isset($GLOBALS['xarMLS_backend'])) {
        xarLogMessage("xarMLS: No translation backend was selected for ". "$dnType.$dnName.$ctxType.$ctxName");
        return false;
    }
    if (empty($GLOBALS['xarMLS_currentLocale'])) {
        xarLogMessage("xarMLS: No current locale was selected");
        return false;
    }

    // only load each translation once
    if (isset($loadedTranslations["$dnType.$dnName.$ctxType.$ctxName"])) {
        return $loadedTranslations["$dnType.$dnName.$ctxType.$ctxName"];
    }

    if ($GLOBALS['xarMLS_backend']->bindDomain($dnType, $dnName)) {
        if ($dnType == XARMLS_DNTYPE_MODULE) {
            // Handle in a special way the module type
            // for which it's necessary to load common translations
            if (!isset($loadedCommons[$dnName])) {
                $loadedCommons[$dnName] = true;
                if (!$GLOBALS['xarMLS_backend']->loadContext('modules:', 'common')) return; // throw back
                if (!$GLOBALS['xarMLS_backend']->loadContext('modules:', 'version')) return; // throw back
            }
        }
        if ($dnType == XARMLS_DNTYPE_THEME) {
            // Load common translations
            if (!isset($loadedCommons[$dnName])) {
                $loadedCommons[$dnName] = true;
                if (!$GLOBALS['xarMLS_backend']->loadContext('themes:', 'common')) return; // throw back
            }
        }

        if (!$GLOBALS['xarMLS_backend']->loadContext($ctxType, $ctxName)) return; // throw back
        $loadedTranslations["$dnType.$dnName.$ctxType.$ctxName"] = true;
        return true;
    } else {
        // FIXME: postpone
        //xarEvt_fire('MLSMissingTranslationDomain', array($dnType, $dnName));

        $loadedTranslations["$dnType.$dnName.$ctxType.$ctxName"] = false;
        return false;
    }
}


function xarMLS_convertFromInput($var, $method)
{
    // FIXME: <marco> Can we trust browsers?
    if (xarMLSGetMode() == XARMLS_SINGLE_LANGUAGE_MODE ||
        !function_exists('mb_http_input')) {
        return $var;
    }
    // CHECKME: check this code
    return $var;
    // Cookies must contain only US-ASCII characters
    $inputCharset = strtolower(mb_http_input($method));
    $curCharset = xarMLSGetCharsetFromLocale(xarMLSGetCurrentLocale());
    if ($inputCharset != $curCharset) {
        $var = mb_convert_encoding($var, $curCharset, $inputCharset);
    }
    return $var;
}

// PRIVATE FUNCTIONS

function xarMLS__convertFromCharset($var, $charset)
{
    // FIXME: <marco> Can we trust browsers?
    if (xarMLSGetMode() == XARMLS_SINGLE_LANGUAGE_MODE ||
        !function_exists('mb_convert_encoding')) return $var;
    $curCharset = xarMLSGetCharsetFromLocale(xarMLSGetCurrentLocale());
    $var = mb_convert_encoding($var, $curCharset, $charset);
    return $var;
}

function xarMLS__bindVariables($string, $args)
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
function xarMLS__getLocaleAlternatives($locale)
{
    if (!$parsedLocale = xarMLS__parseLocaleString($locale)) return; // throw back
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
function xarMLS__parseLocaleString($locale)
{
    $res = array('lang'=>'', 'country'=>'', 'specializer'=>'', 'charset'=>'utf-8');
    // Match the locales standard format  : en_US.iso-8859-1
    // Thus: language code lowercase(2), country code uppercase(2), encoding lowercase(1+)
    if (!preg_match('/([a-z][a-z])(_([A-Z][A-Z]))?(\.([0-9a-z\-]+))?(@([0-9a-zA-Z]+))?/', $locale, $matches)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'locale');
        return;
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
function xarMLS__getSingleByteCharset($langISO2Code)
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

// MLS CLASSES

/**
 * This is the abstract base class from which every concrete translations backend
 * must inherit.
 * It defines a simple interface used by the Multi Language System to fetch both
 * string and key based translations.
 *
 * @package multilanguage
 * @todo    interface once php5 is there
 */
class xarMLS__TranslationsBackend
{
    /**
     * Gets the string based translation associated to the string param.
     */
    function translate($string)
    { die('abstract'); }
    /**
     * Gets the key based translation associated to the key param.
     */
    function translateByKey($key)
    { die('abstract'); }
    /**
     * Unloads loaded translations.
     */
    function clear()
    { die('abstract'); }
    /**
     * Binds the backend to the specified domain.
     */
    function bindDomain($dnType, $dnName)
    { die('abstract'); }
    /**
     * Checks if this backend supports a scpecified translation context.
     */
    function hasContext($ctxType, $ctxName)
    { die('abstract'); }
    /**
     * Loads a set of translations into the backend.
     */
    function loadContext($ctxType, $ctxName)
    { die('abstract'); }
    /**
     * Gets available context names for the specified context type
     */
    function getContextNames($ctxType)
    { die('abstract'); }


}

/**
 * This abstract class inherits from xarMLS__TranslationsBackend and provides
 * a powerful access to metadata associated to every translation entry.
 * A translation entry is an array that contains not only the translation,
 * but also the a list of references where it appears in the source by
 * reporting the file name and the line number.
 *
 * @package multilanguage
 */
class xarMLS__ReferencesBackend extends xarMLS__TranslationsBackend
{
    var $locales;
    var $locale;
    var $domainlocation;
    var $contextlocation;
    var $backendtype;
    var $space;
    var $spacedir;
    var $domaincache;

    function xarMLS__ReferencesBackend($locales)
    {
        $this->locales = $locales;
        $this->domaincache = array();
    }
    /**
     * Gets a translation entry for a string based translation.
     */
    function getEntry($string)
    { die('abstract'); }
    /**
     * Gets a translation entry for a key based translation.
     */
    function getEntryByKey($key)
    { die('abstract'); }
    /**
     * Gets a transient identifier (integer) that is guaranteed to identify
     * the translation entry for the string based translation in the next HTTP request.
     */
    function getTransientId($string)
    { die('abstract'); }
    /**
     * Gets the translation entry identified by the passed transient identifier.
     */
    function lookupTransientId($transientId)
    { die('abstract'); }
    /**
     * Enumerates every string based translation, use the reset param to restart the enumeration.
     */
    function enumTranslations($reset = false)
    { die('abstract'); }
    /**
     * Enumerates every key based translation, use the reset param to restart the enumeration.
     */
    function enumKeyTranslations($reset = false)
    { die('abstract'); }

    function bindDomain($dnType, $dnName)
    {
        // only bind each domain once (?)
        //if (isset($this->domaincache["$dnType.$dnName"])) {
        // CHECKME: make sure we can cache this (e.g. set $this->domainlocation here first ?)
        //    return $this->domaincache["$dnType.$dnName"];
        //}

        switch ($dnType) {
        case XARMLS_DNTYPE_MODULE:
            $this->spacedir = "modules";
            break;
        case XARMLS_DNTYPE_THEME:
            $this->spacedir = "themes";
            break;
        case XARMLS_DNTYPE_CORE:
            $this->spacedir = "core";
            break;
        default:
            $this->spacedir = NULL;
            break;
        }

        foreach ($this->locales as $locale) {
            if($this->spacedir == "core" || $this->spacedir == "xaraya") {
                $this->domainlocation  = xarCoreGetVarDirPath() . "/locales/"
                . $locale . "/" . $this->backendtype . "/" . $this->spacedir;
            } else {
                $this->domainlocation  = xarCoreGetVarDirPath() . "/locales/"
                . $locale . "/" . $this->backendtype . "/" . $this->spacedir . "/" . $dnName;
            }

            if (file_exists($this->domainlocation)) {
                $this->locale = $locale;
                // CHECKME: save $this->domainlocation here instead ?
                //$this->domaincache["$dnType.$dnName"] = true;
                return true;
            } elseif ($GLOBALS['xarMLS_backendName'] == 'xml2php') {
                $this->locale = $locale;
                // CHECKME: save $this->domainlocation here instead ?
                //$this->domaincache["$dnType.$dnName"] = true;
                return true;
            }
        }

        //$this->domaincache["$dnType.$dnName"] = false;
        return false;
    }

    function getDomainLocation()
    { return $this->domainlocation; }

    function getContextLocation()
    { return $this->contextlocation; }

    function hasContext($ctxType, $ctxName)
    {
        return $this->findContext($ctxType, $ctxName) != false;
    }

    function findContext($ctxType, $ctxName)
    {
        if (strpos($ctxType, 'modules:') !== false) {
            list ($ctxPrefix,$ctxDir) = explode(":", $ctxType);
            $fileName = $this->getDomainLocation() . "/$ctxDir/$ctxName." . $this->backendtype;
        } elseif (strpos($ctxType, 'themes:') !== false) {
            list ($ctxPrefix,$ctxDir) = explode(":", $ctxType);
            $fileName = $this->getDomainLocation() . "/$ctxDir/$ctxName." . $this->backendtype;
        } elseif (strpos($ctxType, 'core:') !== false) {
            $fileName = $this->getDomainLocation() . "/". $ctxName . "." . $this->backendtype;
        } else {
            die("Bad Context: " . $ctxType);
        }
        $fileName = str_replace('//','/',$fileName);
        if (!file_exists($fileName)) {
//            die("File does not exist: " . $fileName);
            return false;
        }
        return $fileName;
    }

}

/**
 * Create directories tree
 *
 * @author Volodymyr Metenchuk <voll@xaraya.com>
 * @access protected
 * @return bool true
 */
function xarMLS__mkdirr($path)
{
    // Check if directory already exists
    if (is_dir($path) || empty($path)) {
        return true;
    }
         
    // Crawl up the directory tree
    $next_path = substr($path, 0, strrpos($path, '/'));
    if (xarMLS__mkdirr($next_path)) {
        if (!file_exists($path)) {
            $result = @mkdir($path, 0700);
            if (!$result) {
                $msg = xarML("The directories under #(1) must be writeable by PHP.", $next_path);
                xarLogMessage($msg);
                // xarErrorSet(XAR_USER_EXCEPTION, 'WrongPermissions', new DefaultUserException($msg));
            }
            return $result;
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
function xarMLS__iswritable($directory=NULL)
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
                    $isWritable &= xarMLS__iswritable($directory."/".$filename);
                } else {
                    $isWritable &= is_writable($directory."/".$filename);
                }
            }
        }
        return $isWritable;
    } else {
        $isWritable = xarMLS__mkdirr($directory);
        return $isWritable;
    }
}
                                                    
?>
