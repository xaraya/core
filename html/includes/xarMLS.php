<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: Multi Language System
// ----------------------------------------------------------------------

/* TODO:
 * This is the list of things that need to be done:
 * Dynamic Translations
 * Timezone and DST support
 * Patch module loader to use xarML_Load, integrate old style language pack into xarML_Load
 * Write standard core translations
 * Integrate ML support into BL
 * Translation context is changed, change translations module properly
 * Complete changes as described in version 0.9 of MLS RFC
 * Implements the request(ed) locale APIs for backend interactions
 * See how utf-8 works for xml backend
 * finish phpdoc tags
 */

define('XARMLS_SINGLE_LANGUAGE_MODE', 1);
define('XARMLS_BOXED_MULTI_LANGUAGE_MODE', 2);
define('XARMLS_UNBOXED_MULTI_LANGUAGE_MODE', 4);

define('XARMLS_DNTYPE_CORE', 1);
define('XARMLS_DNTYPE_THEME', 2);
define('XARMLS_DNTYPE_MODULE', 3);
define('XARMLS_CTXTYPE_FILE', 1);
define('XARMLS_CTXTYPE_TEMPLATE', 2);
define('XARMLS_CTXTYPE_BLOCK', 3);

/**
 * Initialise the Multi Language System
 * @access private
 * @returns bool
 * @return true on success
 */
function xarMLS_init($args, $whatElseIsGoingLoaded)
{
    global $xarMLS_mode, $xarMLS_backendName;
    global $xarMLS_localeDataLoader, $xarMLS_localeDataCache;
    global $xarMLS_currentLocale, $xarMLS_defaultLocale, $xarMLS_allowedLocales;

    switch ($args['MLSMode']) {
        case 'SINGLE':
            $xarMLS_mode = XARMLS_SINGLE_LANGUAGE_MODE;
            break;
        case 'BOXED':
            $xarMLS_mode = XARMLS_BOXED_MULTI_LANGUAGE_MODE;
            break;
        case 'UNBOXED':
            $xarMLS_mode = XARMLS_UNBOXED_MULTI_LANGUAGE_MODE;
            if (!function_exists('mb_http_input')) {
                // mbstring required
                xarCore_die('xarMLS_init: Mbstring PHP extension is required for UNBOXED MULTI language mode.');
            }
            break;
        default:
            xarCore_die('xarMLS_init: Unknown MLS mode: '.$args['MLSMode']);
    }

    $xarMLS_backendName = $args['translationsBackend'];
    if ($xarMLS_backendName != 'php' && $xarMLS_backendName != 'xml') {
        xarCore_die('xarML_init: Unknown translations backend: '.$backendName);
    }

    $xarMLS_localeDataLoader = new xarMLS__LocaleDataLoader();
    $xarMLS_localeDataCache = array();

    $xarMLS_currentLocale = '';
    $xarMLS_defaultLocale = $args['defaultLocale'];
    $xarMLS_allowedLocales = $args['allowedLocales'];

    if ($whatElseIsGoingLoaded & XARCORE_SYSTEM_USER) {
        // The User System won't be started
        // MLS will use the default locale
        xarMLS_setCurrentLocale($xarMLS_defaultLocale);
    }

    // Register MLS events
    xarEvt_registerEvent('MLSMissingTranslationString');
    xarEvt_registerEvent('MLSMissingTranslationKey');
    xarEvt_registerEvent('MLSMissingTranslationContext');

    return true;
}

/**
 * Gets the current MLS mode
 *
 * @access public
 * @returns string
 * @return MLS Mode
 */
function xarMLSGetMode()
{
    global $xarMLS_mode;

    return $xarMLS_mode;
}

/**
 * Returns the site locale if running in SINGLE mode,
 * returns the site default locale if running in BOXED or UNBOXED mode
 *
 * @access public
 * @returns string
 * @return the site locale
 */
// TODO: check
function xarMLSGetSiteLocale()
{
    global $xarMLS_defaultLocale;

    return $xarMLS_defaultLocale;
}

/**
 * Returns an array of locales available in the site
 *
 * @access public
 * @returns array
 * @return array of locales
 */
// TODO: check
function xarMLSListSiteLocales()
{
    global $xarMLS_defaultLocale, $xarMLS_allowedLocales;
    $mode = xarMLSGetMode();
    if ($mode == XARMLS_SINGLE_LANGUAGE_MODE) {
        return array($xarMLS_defaultLocale);
    } else {
        return $xarMLS_allowedLocales;
    }
}

/**
 * Gets the locale data for a certain locale.
 * Locale data is an associative array, its keys are described at the top
 * of this file
 *
 * @access public
 * @returns array
 * @return locale data
 * @raise LOCALE_NOT_FOUND
 */
function xarMLSLoadLocaleData($locale = NULL)
{
    global $xarMLS_localeDataLoader, $xarMLS_localeDataCache;
    if (!isset($locale)) {
        $locale = xarMLSGetCurrentLocale();
    }

    // check for locale availability
    $siteLocales = xarMLSListSiteLocales();
    if (!in_array($locale, $siteLocales)) {
        $msg = xarML('Unavailable locale.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'LOCALE_NOT_AVAILABLE', new SystemException($msg));
        return;
    }

    if (!isset($xarMLS_localeDataCache[$locale])) {
        $res = $xarMLS_localeDataLoader->load($locale);
        if (!isset($res)) return; // Throw back
        if ($res == false) {
            $msg = xarML('Cannot find the requested locale (#(1)).', $locale);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'LOCALE_NOT_FOUND',
            new SystemException($msg));
            return;
        }
        $xarMLS_localeDataCache[$locale] = $xarMLS_localeDataLoader->getLocaleData();
    }
    return $xarMLS_localeDataCache[$locale];
}
/**
 * Gets the current locale
 *
 * @access public
 * @returns
 * @return current locale
 */

function xarMLSGetCurrentLocale()
{
    global $xarMLS_currentLocale;

    return $xarMLS_currentLocale;
}

/**
 * Gets the charset component from a locale
 *
 * @access public
 * @returns string
 * @return the charset name
 * @raise BAD_PARAM
 */

function xarMLSGetCharsetFromLocale($locale)
{
    assert('!empty($locale)');
    
    $parsedLocale = xarMLS__parseLocaleString($locale);
    if (!isset($parsedLocale)) return; // throw back
    return $parsedLocale['charset'];
}

// I18N API

/**
 * Translate a string
 *
 * @access public
 * @returns string
 * @return the translated string, or the original string if no translation is available
 */
function xarML($string/*, ...*/)
{
    global $xarMLS_backend;
    
    assert('!empty($string)');

    if (isset($xarMLS_backend)) {
        $trans = $xarMLS_backend->translate($string);
    } else {
        // This happen in rare cases when xarML is called before xarMLS_init has been called
        $trans = $string;
    }
    if (empty($trans)) {
        xarEvt_fire('MLSMissingTranslationString', $string);
        $trans = $string;
    }
    if (func_num_args() > 1) {
        $args = func_get_args();
        unset($args[0]); // Unset $string argument
        if (is_array($args[1])) $args = $args[1]; // Only the second argument is considered if it's an array
        $trans = xarMLS__bindVariables($trans, $args);
    }

    return $trans;
}

/**
 * Return the translation associated to passed key
 *
 * @access public
 * @returns string
 * @return the translation string, or the key if no translation is available
 */
function xarMLByKey($key/*, ...*/)
{
    global $xarMLS_backend;

    //assert('!empty($key) && strpos($key, " ") === false');

    if (isset($xarMLS_backend)) {
        $trans = $xarMLS_backend->translateByKey($key);
    } else {
        // This happen in rare cases when xarMLByKey is called before xarMLS_init has been called
        $trans = $key;
    }
    if (empty($trans)) {
        xarEvt_fire('MLSMissingTranslationKey', $key);
        $trans = $key;
    }
    if (func_num_args() > 1) {
        $args = func_get_args();
        unset($args[0]); // Unset $string argument
        if (is_array($args[1])) $args = $args[1]; // Only the second argument is considered if it's an array
        $trans = xarMLS__bindVariables($trans, $args);
    }

    return $trans;
}

/*
function xarMLGetDynamic($refid, $table_name, $fields)
{
    $table_name .= '_mldata';
    $fields = implode(',', $fields);

    $query = "SELECT $fields FROM $table_name WHERE xar_refid = $refid";
    $result = $dbconn->Execute($query);
    if (!$result) return;

    return $dbresult;
}
*/

// L10N API

/**
 * Gets the locale info for the specified locale string.
 * Info is an array composed by the 'lang', 'country', 'specializer' and 'charset' items.
 *
 * @access public
 * @returns array
 * @return locale info
 */
function xarLocaleGetInfo($locale)
{
    return xarMLS__parseLocaleString($locale);
}

/**
 * Gets the locale string for the specified locale info.
 * Info is an array composed by the 'lang', 'country', 'specializer' and 'charset' items.
 *
 * @access public
 * @returns string
 * @return locale string
 */
function xarLocaleGetString($localeInfo)
{
    assert('isset($localeInfo["lang"]) && isset($localeInfo["country"]) &&
            isset($localeInfo["specializer"]) && isset($localeInfo["charset"])');

    if (strlen($localeInfo['lang']) != 2) {
        $msg = xarML('Invalid lang.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }
    $locale = strtolower($localeInfo['lang']);
    if (!empty($localeInfo['country'])) {
        if (strlen($localeInfo['country']) != 2) {
            $msg = xarML('Invalid country.');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return;
        }
        $locale .= '_'.strtoupper($localeInfo['country']);
    }
    if (!empty($localeInfo['specializer'])) {
        $locale .= '@'.$localeInfo['specializer'];
    }
    if (!empty($localeInfo['charset'])) {
        $locale .= '.'.$localeInfo['charset'];
    } else {
        $locale .= '.UTF-8';
    }
    return $locale;
}

/**
 * Gets a list of locale string which met the specified filter criteria.
 * Filter criteria are set as item of $filter parameter, they can be one or more of the following:
 * lang, country, specializer, charset.
 *
 * @access public
 * @returns array
 * @return locale list
 */
function xarLocaleGetList($filter)
{
    assert('is_array($filter)');

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
 * Formats a currency according to specified locale data
 *
 * @access public
 * @returns string
 * @return formatted currency
 */
function xarLocaleFormatCurrency($currency, $localeData = NULL)
{
    if (!isset($localeData)) {
        $localeData = xarMLSLoadLocaleData();
    }
    $currencySym = $localeData['/monetary/currencySymbol'];
    return $currencySym.' '.xarLocaleFormatNumber($currency, $localeData, true);
}

/**
 * Formats a number according to specified locale data
 *
 * @access public
 * @returns string
 * @return formatted number
 * @raise BAD_PARAM
 */
function xarLocaleFormatNumber($number, $localeData = NULL, $isCurrency = false)
{
    if (!is_numeric($number)) {
        $msg = xarML('Number is not of numeric type.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    if (!isset($localeData)) {
        $localeData = xarMLSLoadLocaleData();
    }
    if ($isCurrency == true) {
        $bp = 'monetary';
    } else {
        $bp = 'numeric';
    }
    $groupSize = $localeData["/$bp/groupingSize"];
    $groupSep = $localeData["/$bp/groupingSeparator"];
    $decSep = $localeData["/$bp/decimalSeparator"];
    $decSepShown = $localeData["/$bp/isDecimalSeparatorAlwaysShown"];
    $maxFractDigits = $localeData["/$bp/fractionDigits/maximum"];
    $minFractDigits = $localeData["/$bp/fractionDigits/minimum"];

    $zeroDigit = $localeData['/decimalSymbols/zeroDigit'];
    $minusSign = $localeData['/decimalSymbols/minusSign'];

    if ($number < 0) {
        $number = -1 * $number;
        $minus = true;
    }

    $str_num = (string) $number; // Convert to string

    if (($dsep_pos = strpos($str_num, '.')) !== false) {
        $int_part = substr($str_num, 0, $dsep_pos);
        $dec_part = substr($str_num, $dsep_pos + 1);
    } else {
        $int_part = $str_num;
    }
    // FIXME: <marco> Do we really need the maximum integer digits?
    $int_part_len = strlen($int_part);
    if ($groupSize > 0) {
        $sepNum = (int) ($int_part_len / $groupSize);
        $firstSkip = $int_part_len - ($sepNum * $groupSize);

        $str_num = '';

        $pos = $firstSkip;
        while ($pos < $int_part_len) {
            $str_num .= $groupSep . substr($int_part, $pos, $groupSize);
            $pos += $groupSize;
        }
        if ($firstSkip > 0) {
            $str_num = substr($int_part, 0, $firstSkip) . $str_num;
        } else {
            $str_num = substr($str_num, 1);
        }
    } else {
        $str_num = $int_part;
    }

    if (isset($dec_part) || $decSepShown) {
        $str_num .= $decSep;
        if (!isset($dec_part)) {
            for ($i = 0; $i < $minFractDigits; $i++) $str_num .= '0';
        } else {
            $dec_part_len = strlen($dec_part);
            if ($dec_part_len < $minFractDigits) {
                for ($i = 0; $i < $minFractDigits - $dec_part_len; $i++) $dec_part .= '0';
            } elseif ($dec_part_len > $maxFractDigits) {
                // FIXME: <marco> Do we need round here?
                $dec_part = substr($dec_part, 0, $maxFractDigits - $dec_part_len); // Note negative length
            }
            $str_num .= $dec_part;
        }
    }

    if (isset($minus)) {
        $str_num = $minusSign . $str_num;
    }

    if ($zeroDigit != '0') {
        $str_num = str_replace('0', $zeroDigit, $str_num);
    }

    return $str_num;
}

// PROTECTED FUNCTIONS

/**
 * Sets current locale
 *
 * @access protected
 * @param locale site locale
 */
function xarMLS_setCurrentLocale($locale)
{
    global $xarMLS_currentLocale, $xarMLS_backendName, $xarMLS_backend;
    
    assert('!empty($locale)');

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
            }
    }
    if ($locale == $xarMLS_currentLocale) return; // Nothing to do

    // Adjust new current locale
    $xarMLS_currentLocale = $locale;

    $curCharset = xarMLSGetCharsetFromLocale($locale);
    if ($mode == XARMLS_UNBOXED_MULTI_LANGUAGE_MODE) {
        assert('$curCharset == "UTF-8"');
        ini_set('mbstring.func_overload', 7);
        mb_internal_encoding($curCharset);
    }
    //if ($mode == XARMLS_BOXED_MULTI_LANGUAGE_MODE) {
        //if (substr($curCharset, 0, 9) != 'iso-8859-' &&
        //$curCharset != 'koi8-r') {
            // Do not use mbstring for single byte charsets
            
        //}
    //}
    header("Content-Type: text/html; charset=$curCharset");

    $alternatives = xarMLS__getLocaleAlternatives($locale);
    switch ($xarMLS_backendName) {
        case 'xml':
        include_once 'includes/xarMLSXMLBackend.php';
        $xarMLS_backend = new xarMLS__XMLTranslationsBackend($alternatives);
        break;
        case 'php':
        $xarMLS_backend = new xarMLS__PHPTranslationsBackend($alternatives);
        break;
    }

    // Load core translations
    xarMLS_loadTranslations(XARMLS_DNTYPE_CORE, 'xaraya', XARMLS_CTXTYPE_FILE, 'core');

    //xarMLSLoadLocaleData($locale);
}

/**
 * Loads translations for the specified context
 *
 * @access protected
 * @param translationCtx
 * @param modOnDir module directory
 * @param modType module type
 * @returns bool
 * @return
 */
function xarMLS_loadTranslations($dnType, $dnName, $ctxType, $ctxName)
{
    global $xarMLS_backend;
    static $loadedCommons = array();

    if ($xarMLS_backend->bindDomain($dnType, $dnName)) {
        
        if ($dnType == XARMLS_DNTYPE_MODULE) {
            // Handle in a special way the module type
            // for which it's necessary to load common translations
            if (!isset($loadedCommons[$dnName])) {
                $loadedCommons[$dnName] = true;
                if (!$xarMLS_backend->loadContext(XARMLS_CTXTYPE_FILE, 'common')) return; // throw back
            }
        }

        if (!$xarMLS_backend->loadContext($ctxType, $ctxName)) return; // throw back
        return true;
    }

    xarEvt_fire('MLSMissingTranslationDomain', array($dnType, $dnName));

    return false;
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
 * @returns array
 * @return array of alternative locales
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
 * @returns array
 * @return parsed locale
 */
function xarMLS__parseLocaleString($locale)
{
    assert('!empty($locale)');
    
    $res = array('lang'=>'', 'country'=>'', 'specializer'=>'', 'charset'=>'UTF-8');
    if (!preg_match('/([a-z][a-z])(_([A-Z][A-Z]))?(@([0-9a-zA-Z]+))?(\.([0-9A-Z\-]+))?/', $locale, $matches)) {
        $msg = xarML('Invalid locale.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    $res['lang'] = $matches[1];
    if (!empty($matches[3])) $res['country'] = $matches[3];
    if (!empty($matches[5])) $res['specializer'] = $matches[5];
    if (!empty($matches[7])) $res['charset'] = $matches[7];

    return $res;
}

/**
 * Gets the single byte charset most typically used in the Web for the
 * requested language
 *
 * @returns string
 * @return the charset
 */
function xarMLS__getSingleByteCharset($langISO2Code) {
    static $charsets = array('af' => 'ISO-8859-1', 'sq' => 'ISO-8859-1',
    'ar' => 'ISO-8859-6',  'eu' => 'ISO-8859-1',  'bg' => 'ISO-8859-5',
    'be' => 'ISO-8859-5',  'ca' => 'ISO-8859-1',  'hr' => 'ISO-8859-2',
    'cs' => 'ISO-8859-2',  'da' => 'ISO-8859-1',  'nl' => 'ISO-8859-1',
    'en' => 'ISO-8859-1',  'eo' => 'ISO-8859-3',  'et' => 'ISO-8859-15',
    'fo' => 'ISO-8859-1',  'fi' => 'ISO-8859-1',  'fr' => 'ISO-8859-1',
    'gl' => 'ISO-8859-1',  'de' => 'ISO-8859-1',  'el' => 'ISO-8859-7',
    'iw' => 'ISO-8859-8',  'hu' => 'ISO-8859-2',  'is' => 'ISO-8859-1',
    'ga' => 'ISO-8859-1',  'it' => 'ISO-8859-1',  //'ja' => '',
    'lv' => 'ISO-8859-13', 'lt' => 'ISO-8859-13', 'mk' => 'ISO-8859-5',
    'mt' => 'ISO-8859-3',  'no' => 'ISO-8859-1',  'pl' => 'ISO-8859-2',
    'pt' => 'ISO-8859-1',  'ro' => 'ISO-8859-2',  'ru' => 'KOI8-R',
    'gd' => 'ISO-8859-1',  'sr' => 'ISO-8859-2',  'sk' => 'ISO-8859-2',
    'sl' => 'ISO-8859-2',  'es' => 'ISO-8859-1',  'sv' => 'ISO-8859-1',
    'tr' => 'ISO-8859-9',  'uk' => 'ISO-8859-5');
    return @$charsets[$langISO2Code];
}

// MLS CLASSES

/**
 * This class loads a valid locale descriptor XML file and returns its content
 * in the form of a locale data array
 */
class xarMLS__LocaleDataLoader
{
    var $curData;
    var $curPath;

    var $parser;

    var $localeData;

    var $attribsStack = array();

    var $tmpVars;

    function load($locale)
    {
        $fileName = "locales/$locale/locale.xml";
        if (!file_exists($fileName)) {
            return false;
        }

        $this->tmpVars = array();

        $this->curData = '';
        $this->curPath = '';
        $this->localeData = array();

        // TRICK: <marco> Since this xml parser sucks, we obviously use UTF-8 for utf-8 charset
        // and ISO-8859-1 for other charsets, even if they're not single byte.
        // The only important thing here is to split utf-8 from other charsets.
        $charset = xarMLSGetCharsetFromLocale($locale);
        // FIXME: <marco> try, re-try and re-re-try this!
        if ($charset == 'UTF-8') {
            $this->parser = xml_parser_create('UTF-8');
        } else {
            $this->parser = xml_parser_create('ISO-8859-1');
        }
        xml_set_object($this->parser, $this);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_element_handler($this->parser, "beginElement", "endElement");
        xml_set_character_data_handler($this->parser, "characterData");

        if (!($fp = fopen($fileName, 'r'))) {
            return false;
        }

        while ($data = fread($fp, 4096)) {
            if (!xml_parse($this->parser, $data, feof($fp))) {
                $errstr = xml_error_string(xml_get_error_code($this->parser));
                $line = xml_get_current_line_number($this->parser);
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'XML_PARSER_ERROR',
                               new SystemException("XML parser error in $fileName: $errstr at line $line."));
                return;
            }
        }

        xml_parser_free($this->parser);

        return true;
    }

    function getLocaleData()
    {
        return $this->localeData;
    }

    function beginElement($parser, $tag, $attribs)
    {
        if (strpos($tag, ':') !== false) {
            list($ns, $tag) = explode(':', $tag);
        }
        $this->attribsStack[] = $attribs;
        if (isset($this->tmpVars['calledOnce'])) {
            $this->curPath .= '/'.$tag;
        } else {
            // Avoid to get prefixed the /description to path
            $this->tmpVars['calledOnce'] = true;
        }
    }

    function endElement($parser, $tag)
    {
        if (strpos($tag, ':') !== false) {
            list($ns, $tag) = explode(':', $tag);
        }
        $attribs = array_pop($this->attribsStack);
        $handler = $tag.'TagHandler';
        if (method_exists($this, $handler)) {
            list($new_path, $value) = $this->$handler($this->curPath, $attribs, $this->curData);
        } else {
            $value = $this->curData;
            $new_path = $this->curPath;
        }
        if (is_array($value)) {
            foreach ($value as $add_path => $real_value) {
                $this->localeData[$new_path.'/'.$add_path] = $real_value;
            }
        } else {
            $this->localeData[$new_path] = $value;
        }
        $this->curPath = substr($this->curPath, 0, (-1 * strlen($tag)) - 1);

        $this->curData = '';
    }

    function characterData($parser, $data)
    {
        // FIXME: <marco> consider to replace \n,\r with ''
        $this->curData .= trim($data);
    }

    function maximumTagHandler($path, $attribs, $content)
    {
        return array($path, (int) $content);
    }

    function minimumTagHandler($path, $attribs, $content)
    {
        return array($path, (int) $content);
    }

    function groupingSizeTagHandler($path, $attribs, $content)
    {
        return array($path, (int) $content);
    }

    function isDecimalSeparatorAlwaysShownTagHandler($path, $attribs, $content)
    {
        if ($content == 'true') {
            $value = true;
        } else {
            $value = false;
        }
        return array($path, $value);
    }

    function monthTagHandler($path, $attribs, $content)
    {
        if (isset($this->tmpVars['monthNum'])) {
            $monthNum = $this->tmpVars['monthNum'];
        } else {
            $monthNum = 1;
        }
        $this->tmpVars['monthNum'] = $monthNum + 1;
        $path = substr($path, 0, -6); // Strip the /month at the end
        $value = array($monthNum.'/full' => $attribs['full'],
                       $monthNum.'/short' => $attribs['short']);
        return array($path, $value);
    }

    function weekdayTagHandler($path, $attribs, $content)
    {
        if (isset($this->tmpVars['weekdayNum'])) {
            $weekdayNum = $this->tmpVars['weekdayNum'];
        } else {
            $weekdayNum = 1;
        }
        $this->tmpVars['weekdayNum'] = $weekdayNum + 1;
        $path = substr($path, 0, -8); // Strip the /weekday at the end
        $value = array($weekdayNum.'/full' => $attribs['full'],
                       $weekdayNum.'/short' => $attribs['short']);
        return array($path, $value);
    }

}

// TODO: <marco> check if it's faster without the extends thing
/**
 * This is the abstract base class from which every concrete translations backend
 * must inherit.
 * It defines a simple interface used by the Multi Language System to fetch both
 * string and key based translations.
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

/*
 * This abstract class inherits from xarMLS__TranslationsBackend and provides
 * a powerful access to metadata associated to every translation entry.
 * A translation entry is an array that contains not only the translation,
 * but also the a list of references where it appears in the source by
 * reporting the file name and the line number.
 */
class xarMLS__ReferencesBackend extends xarMLS__TranslationsBackend
{
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
}

/**
 * This is the default translations backend and should be used for production sites.
 * Note that it does not support the xarMLS__ReferencesBackend interface.
 */
class xarMLS__PHPTranslationsBackend extends xarMLS__TranslationsBackend
{
    var $locales;

    function xarMLS__PHPTranslationsBackend($locales)
    {
        $this->locales = $locales;
    }

    function translate($string)
    {
        global $xarML_PHPBackend_entries;
        if (isset($xarML_PHPBackend_entries[$string]))
            return $xarML_PHPBackend_entries[$string];
        //return @$xarML_PHPBackend_entries[$string];
    }

    function translateByKey($key)
    {
        global $xarML_PHPBackend_keyEntries;
        if (isset($xarML_PHPBackend_keyEntries[$key]))
            return $xarML_PHPBackend_keyEntries[$key];
        //return @$xarML_PHPBackend_keyEntries[$key];
    }

    function clear()
    {
        global $xarML_PHPBackend_entries;
        global $xarML_PHPBackend_keyEntries;
        $xarML_PHPBackend_entries = array();
        $xarML_PHPBackend_keyEntries = array();
    }

    function bindDomain($dnType, $dnName)
    {
        switch ($dnType) {
            case XARMLS_DNTYPE_MODULE:
            $dirName = "modules/$dnName/";
            break;
            case XARMLS_DNTYPE_THEME:
            $dirName = "themes/$dnName/";
            break;
            case XARMLS_DNTYPE_CORE:
            $dirName = 'core/';
        }
        foreach ($this->locales as $locale) {
            $this->baseDir = "locales/$locale/php/$dirName";
            if (file_exists($this->baseDir)) return true;
        }
        if ($dnType == XARMLS_DNTYPE_MODULE) {
            $this->loadKEYS($dnName);
        }
        return false;
    }

    function loadKEYS($dnName)
    {
        $modBaseInfo = xarMod_getBaseInfo($dnName);
        $fileName = "modules/$modBaseInfo[directory]/KEYS";
        if (file_exists($fileName)) {
            global $xarML_PHPBackend_keyEntries;

            $lines = file($fileName);
            foreach ($lines as $line) {
                if ($line{0} == '#') continue;
                list($key, $value) = explode('=', $line);
                $key = trim($key);
                $value = trim($value);
                $xarML_PHPBackend_keyEntries[$key] = $value;
            }
        }
    }

    function findContext($ctxType, $ctxName)
    {
        switch ($ctxType) {
            case XARMLS_CTXTYPE_FILE:
            $fileName = $ctxName;
            break;
            case XARMLS_CTXTYPE_TEMPLATE:
            $fileName = "templates/$ctxName";
            break;
            case XARMLS_CTXTYPE_BLOCK:
            $fileName = "blocks/$ctxName";
            break;
        }
        $fileName .= '.php';
        if (!file_exists($this->baseDir.$fileName)) return false;
        return $this->baseDir.$fileName;
    }

    function hasContext($ctxType, $ctxName)
    {
        return $this->findContext($ctxType, $ctxName) != false;
    }

    function loadContext($ctxType, $ctxName)
    {
        if (!$fileName = $this->findContext($ctxType, $ctxName)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'CONTEXT_NOT_FOUND', new SystemException($ctxType.': '.$ctxName));
            return;
        }
        include $fileName;

        return true;
    }

    function getContextNames($ctxType)
    {
        $dirName = $this->baseDir;
        switch ($ctxType) {
            case XARMLS_CTXTYPE_TEMPLATE:
            $dirName .= 'templates';
            break;
            case XARMLS_CTXTYPE_BLOCK:
            $dirName .= 'blocks';
            break;
        }
        $ctxNames = array();
        $dd = opendir($dirName);
        while ($fileName = readdir($dd)) {
            if (!preg_match('/^(.+)\.php$/', $fileName, $matches)) continue;
            $ctxNames[] = $matches[1];
        }
        closedir($dd);
        return $ctxNames;
    }
}


