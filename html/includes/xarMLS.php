<?php
/**
 * File: $Id$
 *
 * Multi Language System
 *
 * @package multilanguage
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 * @todo Dynamic Translations
 *       Timezone and DST support
 *       Write standard core translations
 *       Complete changes as described in version 0.9 of MLS RFC
 *       Implements the request(ed) locale APIs for backend interactions
 *       See how utf-8 works for xml backend
 */

/**
 * Multilange package defines
 */
define('XARMLS_SINGLE_LANGUAGE_MODE', 1);
define('XARMLS_BOXED_MULTI_LANGUAGE_MODE', 2);
define('XARMLS_UNBOXED_MULTI_LANGUAGE_MODE', 4);

define('XARMLS_DNTYPE_CORE', 1);
define('XARMLS_DNTYPE_THEME', 2);
define('XARMLS_DNTYPE_MODULE', 3);

// This class represents the MLS environment on the site
class MLSEnvironment {
    var $mlsdata;
    var $mlsobjects;
    var $backend;
    var $domain;
    var $backend = "php";

    function MLSEnvironment() {
        $this->mlsobjects = array();
    }

    function setDomain($x) { $this->domain = $x; }

    function getSpace($x) {
        $this->space = $x;
        switch ($this->space) {
        case XARMLS_DNTYPE_MODULE:
            return "modules";
        case XARMLS_DNTYPE_THEME:
            return "themes";
        case XARMLS_DNTYPE_CORE:
            return "core";
        default:
            return NULL;
        }
    }
    function getLocale() { return $this->locale; }
    function getBackend() { return $this->backend; }
    function getDomain() { return $this->domain; }
}

/**
 * Initializes the Multi Language System
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @return bool true
 */
function xarMLS_init($args, $whatElseIsGoingLoaded)
{
// <mrb> Why do we have two formats here?
    // FIXME: use constants also for the configvars
    switch ($args['MLSMode']) {
    case 'SINGLE':
        $GLOBALS['xarMLS_mode'] = XARMLS_SINGLE_LANGUAGE_MODE;
        break;
    case 'BOXED':
        $GLOBALS['xarMLS_mode'] = XARMLS_BOXED_MULTI_LANGUAGE_MODE;
        break;
    case 'UNBOXED':
        $GLOBALS['xarMLS_mode'] = XARMLS_UNBOXED_MULTI_LANGUAGE_MODE;
        if (!function_exists('mb_http_input')) {
            // mbstring required
            xarCore_die('xarMLS_init: Mbstring PHP extension is required for UNBOXED MULTI language mode.');
        }
        break;
    default:
        // FIXME: Do we have to die ?
        xarCore_die('xarMLS_init: Unknown MLS mode: '.$args['MLSMode']);
    }

    $GLOBALS['MLS'] = new MLSEnvironment();

    $GLOBALS['xarMLS_backendName'] = $args['translationsBackend'];
    if ($GLOBALS['xarMLS_backendName'] != 'php' && $GLOBALS['xarMLS_backendName'] != 'xml') {
        xarCore_die('xarML_init: Unknown translations backend: '.$GLOBALS['xarMLS_backendName']);
    }

    $GLOBALS['xarMLS_localeDataLoader'] = new xarMLS__LocaleDataLoader();
    $GLOBALS['xarMLS_localeDataCache'] = array();

    $GLOBALS['xarMLS_currentLocale'] = '';
    $GLOBALS['xarMLS_defaultLocale'] = $args['defaultLocale'];
    $GLOBALS['xarMLS_allowedLocales'] = $args['allowedLocales'];

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
    return true;
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
    return $GLOBALS['xarMLS_mode'];
}

/**
 * Returns the site locale if running in SINGLE mode,
 * returns the site default locale if running in BOXED or UNBOXED mode
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string the site locale
 */
// TODO: check
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
 */
// TODO: check
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
 * Gets the locale data for a certain locale.
 * Locale data is an associative array, its keys are described at the top
 * of this file
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return array locale data
 * @raise LOCALE_NOT_EXIST
 */
function xarMLSLoadLocaleData($locale = NULL)
{
    if (!isset($locale)) {
        $locale = xarMLSGetCurrentLocale();
    }

    // check for locale availability
    $siteLocales = xarMLSListSiteLocales();

// TODO: figure out why we go through this function for xarModIsAvailable
//       (this one breaks on upper/lower-case issues, BTW)
    if (!in_array($locale, $siteLocales)) {
        if (preg_match('/ISO/',$locale)) {
            $locale = preg_replace('/ISO/','iso',$locale);
            if (!in_array($locale, $siteLocales)) {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'LOCALE_NOT_AVAILABLE');
                return;
            }
        } else {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'LOCALE_NOT_AVAILABLE');
            return;
        }
    }

    if (!isset($GLOBALS['xarMLS_localeDataCache'][$locale])) {
        $res = $GLOBALS['xarMLS_localeDataLoader']->load($locale);

        if (!isset($res)) return; // Throw back
        if ($res == false) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'LOCALE_NOT_EXIST');
            return;
        }
        $GLOBALS['xarMLS_localeDataCache'][$locale] = $GLOBALS['xarMLS_localeDataLoader']->getLocaleData();
    }
    return $GLOBALS['xarMLS_localeDataCache'][$locale];
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
 * *** DO NOT USE THIS FUNCTION ***
 * Return the translation associated to passed key
 * *** DO NOT USE THIS FUNCTION ***
 *
 * *** IT IS CURRENTLY DEPRECATED, USE xarMLString instead ***
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string the translation string, or the key if no translation is available
 * @deprec
 */
function xarMLByKey($key/*, ...*/)
{
    // Key must have a value and not contain spaces
    if(empty($key) || strpos($key," ")) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM');
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

/*
function xarMLGetDynamic($refid, $table_name, $fields)
{
    $table_name .= '_mldata';
    $fields = implode(',', $fields);

    $query = "SELECT $fields FROM $table_name WHERE xar_refid = $refid";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return $dbresult;
}
*/

// L10N API

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
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'localeInfo');
        return;
    }
    if (strlen($localeInfo['lang']) != 2) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'localeInfo');
        return;
    }
    $locale = strtolower($localeInfo['lang']);
    if (!empty($localeInfo['country'])) {
        if (strlen($localeInfo['country']) != 2) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'localeInfo');
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
        $locale .= '.utf-8';
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
function xarLocaleGetList($filter)
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
 * Formats a currency according to specified locale data
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string formatted currency
 */
function xarLocaleFormatCurrency($currency, $localeData = NULL)
{
    if ($localeData == NULL) $localeData = xarMLSLoadLocaleData();
    $currencySym = $localeData['/monetary/currencySymbol'];
    return $currencySym.' '.xarLocaleFormatNumber($currency, $localeData, true);
}

/**
 * Formats a number according to specified locale data
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string formatted number
 * @raise BAD_PARAM
 */
function xarLocaleFormatNumber($number, $localeData = NULL, $isCurrency = false)
{
    if (!is_numeric($number)) {
        $number = (float) $number;
    }

    if ($localeData == NULL) $localeData = xarMLSLoadLocaleData();

    if ($isCurrency == true) $bp = 'monetary';
    else $bp = 'numeric';

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

/**
 *  Wrapper to xarLocalGetFormattedDate
 *
 */
function xarLocaleGetFormattedUTCDate($length = 'short',$timestamp = null)
{
    $offset = xarMLS_userOffset() * 3600;
    if(!isset($timestamp)) {
        // get UTC timestamp
        //TODO : Take into account System.Core.TimeZone
        $timestamp = time();
    }

    // apply the offset for later manipulation
    $timestamp -= xarMLS_userOffset() * 3600;

    // pass this to the regular function
    return xarLocaleGetFormattedDate($length,$timestamp);
}

/**
 *  Grab the formated date by the user's current locale settings
 *
 *  @access public
 *  @param string $length what date locale we want (short|medium|long)
 *  @param int $timestamp optional unix timestamp in UTC to format
 */
function xarLocaleGetFormattedDate($length = 'short',$timestamp = null)
{
    static $localeData;
    $length = strtolower($length);
    $validLengths = array('short','medium','long');
    if(!in_array($length,$validLengths)) {
        return '';
    }
    // load the locale date
    if(!isset($localeData)) {
        $localeData = xarMLSLoadLocaleData();
    }
    // grab the right set of locale data
    $locale_format = $localeData["/dateFormats/$length"];
    // replace the locale formatting style with valid strftime() style
    $locale_format = str_replace('MMMM','%B',$locale_format);
    $locale_format = str_replace('MMM','%b',$locale_format);
    $locale_format = str_replace('M','%m',$locale_format);
    $locale_format = str_replace('dddd','%A',$locale_format);
    $locale_format = str_replace('ddd','%a',$locale_format);
    $locale_format = str_replace('d','%d',$locale_format);
    $locale_format = str_replace('yyyy','%Y',$locale_format);
    $locale_format = str_replace('yy','%y',$locale_format);

    return xarLocaleFormatDate($locale_format,$timestamp);
}

/**
 *  Wrapper to xarLocaleGetFormattedTime
 *
 */
function xarLocaleGetFormattedUTCTime($length = 'short',$timestamp = null)
{
    if(!isset($timestamp)) {
        // get UTC timestamp
        //TODO : Take into account System.Core.TimeZone
        $timestamp = time();
    }

    // apply the offset for later manipulation
    $timestamp -= xarMLS_userOffset() * 3600;

    // pass this to the regualr function
    return xarLocaleGetFormattedTime($length,$timestamp);
}

/**
 *  Grab the formated time by the user's current locale settings
 *
 *  @access public
 *  @param string $length what time locale we want (short|medium|long)
 *  @param int $timestamp optional unix timestamp in UTC to format
 */
function xarLocaleGetFormattedTime($length = 'short',$timestamp = null)
{
    static $localeData;
    $length = strtolower($length);
    $validLengths = array('short','medium','long');
    if(!in_array($length,$validLengths)) {
        return '';
    }
    // load the locale date
    if(!isset($localeData)) {
        $localeData = xarMLSLoadLocaleData();
    }
    // grab the right set of locale data
    $locale_format = $localeData["/timeFormats/$length"];
    // replace the locale formatting style with valid strftime() style
    $locale_format = str_replace('HH','%H',$locale_format);
    $locale_format = str_replace('hh','%I',$locale_format);
    $locale_format = str_replace('mm','%M',$locale_format);
    $locale_format = str_replace('ss','%S',$locale_format);
    $locale_format = str_replace('a','%p',$locale_format);
    $locale_format = str_replace('z','%Z',$locale_format);
    // format the single digit flags
    $locale_format = str_replace('H',sprintf('%1d',xarLocaleFormatDate('%H',$timestamp)),$locale_format);
    $locale_format = str_replace('h',sprintf('%1d',xarLocaleFormatDate('%I',$timestamp)),$locale_format);
    $locale_format = str_replace('m',sprintf('%1d',xarLocaleFormatDate('%M',$timestamp)),$locale_format);
    $locale_format = str_replace('s',sprintf('%1d',xarLocaleFormatDate('%S',$timestamp)),$locale_format);

    return xarLocaleFormatDate($locale_format,$timestamp);
}

//function xarMLS_getLocaleFormat($locale_format) {
//
//}

function xarLocaleFormatUTCDate($format = null, $time = null)
{
    if(!isset($time)) {
        $time = time();
    }
    $time -= xarMLS_userOffset() * 3600;
    return xarLocaleFormatDate($format,$time);
}

/**
 * Format a date/time according to the current locale (and/or user's preferences)
 *
 * @access public
 * @param time mixed timestamp or date string (default now)
 * @param format strftime() format to use (TODO: default locale-dependent or configurable ?)
 * @param offset timezone offset (default user timezeone)
 * @return date string
 *
 */
function xarLocaleFormatDate($format = null, $timestamp = null)
{
    if (empty($timestamp)) { // yes, null or 0 or whatever :)
//TODO: we should really get the user/site time based on timezone settings
//TODO: this will require UTC timestamps to be generated and then modified
        $timestamp = xarMLS_userTime();
    } elseif (!is_numeric($timestamp)) {
        // strtotime creates a timestamp based on the server's locale settings
        $timestamp = strtotime($timestamp);
        // we need to adjust for the server's timezone offset because
        // we'll be using the gmstrftime function later.
        // doing so will allow for the correct time to be displayed
// TODO: does this work everywhere or just on my machine???
        $timestamp += date('Z',$timestamp);
        if ($timestamp < 0) {
            return ''; // return empty string here (no exception)
        }
    } else {
        // adjust for the user's timezone offset
        $timestamp += xarMLS_userOffset() * 3600;
    }

// TODO: locale-dependent, and/or configurable by admin, and/or selectable by user ?
//       let this be handled by the xarMLS_strftime function?
    //if (empty($format)) {
    //    $format = '%a, %d %B %Y %H:%M:%S %Z';
    //    $format = '%a, %d %B %Y %H:%M %Z';
    //}

    return xarMLS_strftime($format,$timestamp);
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
    $time += xarMLS_userOffset() * 3600;
    // return the corrected timestamp
    return $time;
}

/**
 *  Returns the user's current tz offset
 *
 *  @author Roger Raymond <roger@asphyxia.com>
 *  @access protected
 *  @return int tz offset
 */
function xarMLS_userOffset()
{
    static $offset;
    if(!isset($offset)) {
        // get the correct timezone offset for this user
        if (xarUserIsLoggedIn()) {
            // TODO: cfr. dynamicdata for roles
            $offset = xarUserGetVar('timezone');
        }
        //TODO: Get site's timezone setting?
        if (!isset($offset)) {
            $offset = 0;
        }
    }
    return $offset;
}
/**
 *  Used in place of strftime() for locale translation.
 *  This function uses gmstrftime() so it should be passed
 *  a timestamp that has been modified for the user's current
 *  timezone setting.
 *
 *  @author Roger Raymond <roger@asphyxia.com>
 *  @access protected
 *  @param string $format valid format params from strftime() function\
 *  @param int $timestamp optional unix timestamp to translate
 *  @return string datetime string with locale translations
 *
 *  // supported strftime() format rules
 *  %a - abbreviated weekday name according to the current locale
 *  %A - full weekday name according to the current locale
 *  %b - abbreviated month name according to the current locale
 *  %B - full month name according to the current locale
 *  %c - preferred date and time representation for the current locale
 *  %D - same as %m/%d/%y (abbreviated date according to locale)
 *  %h - same as %b
 *  %p - either `am' or `pm' according to the given time value, or the corresponding strings for the current locale
 *  %r - time in a.m. and p.m. notation
 *  %R - time in 24 hour notation (for windows compatibility)
 *  %T - current time, equal to %H:%M:%S (for windows compatibility)
 *  %x - preferred date representation for the current locale without the time (same at %D)
 *  %X - preferred time representation for the current locale without the date
 *  %e - day of the month as a decimal number, a single digit is preceded by a space (range ' 1' to '31')
 *
 *  // TODO: unsupported strftime() format rules
 *  %Z - time zone or name or abbreviation - we should use the user or site's info for this
 */
function xarMLS_strftime($format=null,$timestamp=null)
{
    static $localeDate;
    // if we don't have a timestamp, get the user's current time
    if(!isset($timestamp)) {
        $timestamp = xarMLS_userTime();
    }

    // we need to get the correct timestamp format if we do not have one
    if(!isset($format)) {
        // check for user defined format
        /*
        if($user_defined) {
            $format =& $user_defined;
        } elseif ($admin_defined) {
            $format =& $admin_defined;
        } else {
        */
            $format = '%a, %d %B %Y %H:%M %Z';
        /*
        }
        */
    }

    // load the locale date
    // FIXME: <mrb> this can return an empty array silently
    if(!isset($localeData)) {
        $localeData = xarMLSLoadLocaleData();
    }

    // TODO
    // if no $format is provided we need to use the default for the locale

    // parse the format string
    preg_match_all('/%[a-z]/i',$format,$modifiers);

    // replace supported format rules
    foreach($modifiers[0] as $modifier) {
        switch($modifier) {
            case '%a' :
                // figure out what weekday it is
                $w = (int) gmstrftime('%w',$timestamp);
                // increment because the locales start at 1
                $w++;
                // replace the weekeday in the format string
                $format = str_replace($modifier,$localeData["/dateSymbols/weekdays/$w/short"],$format);
                // clean up
                unset($w);
                break;

            case '%A' :
                // figure out what weekday it is
                $w = (int) gmstrftime('%w',$timestamp);
                // increment because the locales start at 1
                $w++;
                // replace the weekeday in the format string
                $format = str_replace($modifier,$localeData["/dateSymbols/weekdays/$w/full"],$format);
                // clean up
                unset($w);
                break;

            case '%b' :
            case '%h' :
                // figure out what month it is
                $m = (int) gmstrftime('%m',$timestamp);
                // replace the month in the format string
                $format = str_replace($modifier,$localeData["/dateSymbols/months/$m/short"],$format);
                // clean up
                unset($m);
                break;

            case '%B' :
                // figure out what month it is
                $m = (int) gmstrftime('%m',$timestamp);
                // replace the month in the format string
                $format = str_replace($modifier,$localeData["/dateSymbols/months/$m/full"],$format);
                // clean up
                unset($m);
                break;

            case '%c' :
                // TODO: we want to display the user or site's timezone not the servers
                $fdate = xarLocaleGetFormattedUTCDate('short',$timestamp);
                $ftime = xarLocaleGetFormattedUTCTime('short',$timestamp);
                $format = str_replace($modifier,$fdate.' '.$ftime,$format);
                break;

            case '%D' :
            case '%x' :
                $format = str_replace($modifier,xarLocaleGetFormattedUTCDate('short',$timestamp),$format);
                break;

            case '%e' :
                // implement %e for windows - grab the day number and remove the preceding zero
                $e = sprintf('%1d',gmstrftime('%d',$timestamp));
                // pad with a space if necessary
                if(strlen($e) < 2) {
                    $e = '&nbsp;'.$e;
                }
                $format = str_replace($modifier,$e,$format);
                break;

            case '%r' :
                // recursively call the xarMLS_strftime function
                $format = str_replace($modifier,xarMLS_strftime('%I:%M %p',$timestamp),$format);
                break;

            case '%R' :
                // 24 hour time for windows compatibility
                $format = str_replace($modifier,gmstrftime('%H:%M',$timestamp),$format);
                break;

            case '%T' :
                // current time for windows compatibility
                $format = str_replace($modifier,gmstrftime('%H:%M:%S',$timestamp),$format);
                break;

            case '%X' :
                // TODO: we want to display the user or site's timezone not the servers
                $format = str_replace($modifier,xarLocaleGetFormattedUTCTime('short',$timestamp),$format);
                break;

            case '%Z' :
// TODO: we want to display the user or site's timezone not the servers
// TODO: we'll just push empty text for now
                $format = str_replace($modifier,'',$format);
                break;

            case '%p' :
                // figure out if it's am or pm
                $h = gmstrftime('%H',$timestamp);
                if($h > 11) {
                    // replace with PM string
                    $format = str_replace($modifier,$localeData["/dateSymbols/pm"],$format);
                } else {
                    // replace with AM string
                    $format = str_replace($modifier,$localeData["/dateSymbols/am"],$format);
                }
                break;
        }
    }
    // convert the rest of the format string and return it
    return gmstrftime($format,$timestamp);
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

    // FIXME: What is the purpose of it?
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
            ini_set('mbstring.func_overload', 7);
            mb_internal_encoding($curCharset);
        }
    }

    //if ($mode == XARMLS_BOXED_MULTI_LANGUAGE_MODE) {
    //if (substr($curCharset, 0, 9) != 'iso-8859-' &&
    //$curCharset != 'windows-1251') {
    // Do not use mbstring for single byte charsets

    //}
    //}
    header("Content-Type: text/html; charset=$curCharset");

    $alternatives = xarMLS__getLocaleAlternatives($locale);
    switch ($GLOBALS['xarMLS_backendName']) {
    case 'xml':
        include_once 'includes/xarMLSXMLBackend.php';
        $GLOBALS['xarMLS_backend'] = new xarMLS__XMLTranslationsBackend($alternatives);
        break;
    case 'php':
        $GLOBALS['xarMLS_backend'] = new xarMLS__PHPTranslationsBackend($alternatives);
        break;
    }

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
            }
        }

        if (!$GLOBALS['xarMLS_backend']->loadContext($ctxType, $ctxName)) return; // throw back
        $loadedTranslations["$dnType.$dnName.$ctxType.$ctxName"] = true;
        return true;
    }

    // FIXME: postpone
    //xarEvt_fire('MLSMissingTranslationDomain', array($dnType, $dnName));

    $loadedTranslations["$dnType.$dnName.$ctxType.$ctxName"] = false;
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
    if (!preg_match('/([a-z][a-z])(_([A-Z][A-Z]))?(@([0-9a-zA-Z]+))?(\.([0-9a-z\-]+))?/', $locale, $matches)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'locale');
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
 * @author Marco Canini <marco@xaraya.com>
 * @return string the charset
 */
function xarMLS__getSingleByteCharset($langISO2Code) {
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
 * This class loads a valid locale descriptor XML file and returns its content
 * in the form of a locale data array
 *
 * @package multilanguage
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
        $fileName = "var/locales/$locale/locale.xml";
        if (!file_exists($fileName)) {
            return false;
        }

        $this->tmpVars = array();

        $this->curData = '';
        $this->curPath = '';
        $this->localeData = array();

        // TRICK: <marco> Since this xml parser sucks, we obviously use utf-8 for utf-8 charset
        // and iso-8859-1 for other charsets, even if they're not single byte.
        // The only important thing here is to split utf-8 from other charsets.
        $charset = xarMLSGetCharsetFromLocale($locale);
        // FIXME: <marco> try, re-try and re-re-try this!
        if ($charset == 'utf-8') {
            $this->parser = xml_parser_create('utf-8');
        } else {
            $this->parser = xml_parser_create('iso-8859-1');
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
 *
 * @package multilanguage
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
        $this->spacedir = $GLOBALS['MLS']->getSpace($dnType);
        foreach ($this->locales as $locale) {
            if($this->spacedir == "core" || $this->spacedir == "xaraya") {
                $this->domainlocation  = xarCoreGetVarDirPath() . "/locales/"
                . $locale . "/" . $this->backendtype . "/" . $this->spacedir;
           }
            else {
                $this->domainlocation  = xarCoreGetVarDirPath() . "/locales/"
                . $locale . "/" . $this->backendtype . "/" . $this->spacedir . "/" . $dnName;
            }
            if (file_exists($this->domainlocation)) {
                $this->locale = $locale;
                // CHECKME: save $this->domainlocation here instead ?
                //$this->domaincache["$dnType.$dnName"] = true;
                return true;
            }
        }
        //$this->domaincache["$dnType.$dnName"] = false;
        return false;
    }

    function getDomainLocation() { return $this->domainlocation; }
    function getContextLocation() { return $this->contextlocation; }

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
            die("Bad Context:" . $ctxType);
        }

        if (!file_exists($fileName)) {
//            die("File does not exist:" . $fileName);
            return false;
        }
        return $fileName;
    }

}

/**
 * This is the default translations backend and should be used for production sites.
 * Note that it does not support the xarMLS__ReferencesBackend interface.
 * <marc> why? have changed this to be able to collapse common methods
 *
 * @package multilanguage
 */
class xarMLS__PHPTranslationsBackend extends xarMLS__ReferencesBackend
{
    function xarMLS__PHPTranslationsBackend($locales)
    {
        parent::xarMLS__ReferencesBackend($locales);
        $this->backendtype = "php";
    }

    function translate($string, $type = 0)
    {
        //FIXME stub for CR - voll - I'm crazy!!!
        $string=preg_replace('[\x0d]','',$string);

        if (isset($GLOBALS['xarML_PHPBackend_entries'][$string]))
            return $GLOBALS['xarML_PHPBackend_entries'][$string];
        else {
            if ($type == 1) {
                return $string;
            }
            else {
                return "";
            }
        }
    }

    function translateByKey($key, $type = 0)
    {
        if (isset($GLOBALS['xarML_PHPBackend_keyEntries'][$key]))
            return $GLOBALS['xarML_PHPBackend_keyEntries'][$key];
        else {
            if ($type == 1) {
                return $key;
            }
            else {
                return "";
            }
        }
    }

    function clear()
    {
        $GLOBALS['xarML_PHPBackend_entries'] = array();
        $GLOBALS['xarML_PHPBackend_keyEntries'] = array();
    }

    function bindDomain($dnType, $dnName='xaraya')
    {
        if (parent::bindDomain($dnType, $dnName)) return true;
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

            $lines = file($fileName);
            foreach ($lines as $line) {
                if ($line{0} == '#') continue;
                list($key, $value) = explode('=', $line);
                $key = trim($key);
                $value = trim($value);
                $GLOBALS['xarML_PHPBackend_keyEntries'][$key] = $value;
            }
        }
    }

    function loadContext($ctxType, $ctxName)
    {
        if (!$fileName = $this->findContext($ctxType, $ctxName)) {
//            $msg = xarML("Context type: #(1) and file name: #(2)", $ctxType, $ctxName);
//            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'CONTEXT_NOT_EXIST', new SystemException($msg));
//            return;
            return true;
        }
        include $fileName;

        return true;
    }

    function getContextNames($ctxType)
    {
        // FIXME need more global check
        if (($ctxType == 'core:') || ($ctxType == 'modules:') || ($ctxType == 'themes:')) $directory = '';
        else list($prefix,$directory) = explode(':',$ctxType);
        $this->contextlocation = $this->domainlocation . "/" . $directory;
        $ctxNames = array();
        if (!file_exists($this->contextlocation)) {
            return $ctxNames;
        }
        $dd = opendir($this->contextlocation);
        while ($fileName = readdir($dd)) {
            if (!preg_match('/^(.+)\.php$/', $fileName, $matches)) continue;
            $ctxNames[] = $matches[1];
        }
        closedir($dd);
        return $ctxNames;
    }
}

?>