<?php
// File: $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2001 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: Multi Language System
// ----------------------------------------------------------------------

/* TODO:
 * This is the list of things that need to be done:
 * Dynamic Translations
 * Timezone and DST support
 * Patch module loader to use pnML_Load, integrate old style language pack into pnML_Load
 * Write standard core translations
 * Integrate ML support into BL
 * Translation context is changed, change translations module properly
 * Complete changes as described in version 0.9 of MLS RFC
 * Implements the request(ed) locale APIs for backend interactions
 * See how utf-8 works for xml backend
 * finish phpdoc tags
 */

define('PNMLS_SINGLE_LANGUAGE_MODE', 1);
define('PNMLS_BOXED_MULTI_LANGUAGE_MODE', 2);
define('PNMLS_UNBOXED_MULTI_LANGUAGE_MODE', 4);

/**
 * Initialise the Multi Language System
 * @access private
 * @returns bool
 * @return true on success
 */
function pnMLS_init($args)
{
    global $pnMLS_mode, $pnMLS_backend;
    global $pnMLS_localeDataLoader, $pnMLS_localeDataCache;
    global $pnMLS_currentLocale, $pnMLS_defaultLocale, $pnMLS_allowedLocales;

    switch ($args['MLSMode']) {
        case 'SINGLE':
            $pnMLS_mode = PNMLS_SINGLE_LANGUAGE_MODE;
            break;
        case 'BOXED':
            $pnMLS_mode = PNMLS_BOXED_MULTI_LANGUAGE_MODE;
            break;
        case 'UNBOXED':
            $pnMLS_mode = PNMLS_UNBOXED_MULTI_LANGUAGE_MODE;
            if (!function_exists('mb_http_input')) {
                // mbstring required
                die('pnMLS_init: Mbstring PHP extension is required for UNBOXED MULTI language mode.');
            }
            break;
        default:
            die('pnMLS_init: Unknown MLS mode: '.$args['MLSMode']);
    }

    $backendName = $args['translationsBackend'];
    switch ($backendName) {
        case 'xml':
            $pnMLS_backend = new pnMLS__XMLTranslationsBackend();
            break;
        case 'php':
            $pnMLS_backend = new pnMLS__PHPTranslationsBackend();
            break;
        default:
            die('pnML_init: Unknown translations backend: '.$backendName);
    }

    $pnMLS_localeDataLoader = new pnMLS__LocaleDataLoader();
    $pnMLS_localeDataCache = array();

    $pnMLS_defaultLocale = $args['defaultLocale'];
    $pnMLS_allowedLocales = $args['allowedLocales'];

    // TODO: <marco> Choose how to detect current locale!

    //$pnMLS_currentLocale = $pnMLS_defaultLocale;

    // Register MLS events
    pnEvt_registerEvent('MLSMissingTranslationString');
    pnEvt_registerEvent('MLSMissingTranslationKey');
    pnEvt_registerEvent('MLSMissingTranslationContext');

    return true;
}

/**
 * Gets Locale Mode TODO <johnny> marco please describe this function
 *
 * @access public
 * @returns string
 * @return MLS Mode
 */
function pnMLSGetMode()
{
    global $pnMLS_mode;

    return $pnMLS_mode;
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
function pnMLSGetSiteLocale()
{
    global $pnMLS_defaultLocale;

    return $pnMLS_defaultLocale;
}

/**
 * Returns an array of locales available in the site
 *
 * @access public
 * @returns array
 * @return array of locales
 */
// TODO: check
function pnMLSListSiteLocales()
{
    global $pnMLS_defaultLocale, $pnMLS_allowedLocales;
    $mode = pnMLSGetMode();
    if ($mode == PNMLS_SINGLE_LANGUAGE_MODE) {
        return array($pnMLS_defaultLocale);
    } else {
        return $pnMLS_allowedLocales;
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
 * @raise LOCALE_NOT_FOUNT
 */
function pnMLSLoadLocaleData($locale = NULL)
{
    global $pnMLS_localeDataLoader, $pnMLS_localeDataCache;
    if (!isset($locale)) {
        $locale = pnMLSGetCurrentLocale();
    }

    // check for locale availability
    $siteLocales = pnMLSListSiteLocales();
    if (!in_array($locale, $siteLocales)) {
        $msg = pnML('Unavailable locale.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'LOCALE_NOT_FOUND',
                       new SystemException($msg));
        return;
    }

    if (!isset($pnMLS_localeDataCache[$locale])) {
        $res = $pnMLS_localeDataLoader->load($locale);
        if (!isset($res)) return; // Throw back
        if ($res == false) {
            $msg = pnML('Cannot find the requested locale (#(1)).', $locale);
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'LOCALE_NOT_FOUND',
            new SystemException($msg));
            return;
        }
        $pnMLS_localeDataCache[$locale] = $pnMLS_localeDataLoader->getLocaleData();
    }
    return $pnMLS_localeDataCache[$locale];
}
/**
 * Gets the current locale TODO: <marco> please check this description
 *
 * @access public
 * @returns
 * @return current locale
 */

function pnMLSGetCurrentLocale()
{
    global $pnMLS_currentLocale;

    return $pnMLS_currentLocale;
}

/**
 * Gets the charset component from a locale
 *
 * @access public
 * @returns string
 * @return the charset name
 * @raise BAD_PARAM
 */

function pnMLSGetCharsetFromLocale($locale)
{
    if (empty($locale)) {
        $msg = pnML('Empty locale.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    $parsedLocale = pnMLS__parseLocaleString($locale);
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
function pnML($string)
{
    global $pnMLS_backend;

    if (isset($pnMLS_backend)) {
        $trans = $pnMLS_backend->translate($string);
    } else {
        // This happen in rare cases when pnML is called before pnMLInit was called
        $trans = $string;
    }
    if (!isset($trans) || $trans == '') {
        pnEvt_fire('MLSMissingTranslationString', $string);
        $trans = $string;
    }
    if (func_num_args() > 1) {
        $args = func_get_args();
        unset($args[0]); // Unset $string argument
        if (is_array($args[1])) $args = $args[1]; // Only the second argument is considered if it's an array
        $trans = pnMLS__bindVariables($trans, $args);
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
function pnMLByKey($key)
{
    global $pnMLS_backend;

    if (isset($pnMLS_backend)) {
        $trans = $pnMLS_backend->translateByKey($key);
    } else {
        // This happen in rare cases when pnMLByKey is called before pnMLInit was called
        $trans = $key;
    }
    if (!isset($trans) || $trans == '') {
        pnEvt_fire('MLSMissingTranslationKey', $key);
        $trans = $key;
    }
    if (func_num_args() > 1) {
        $args = func_get_args();
        unset($args[0]); // Unset $string argument
        if (is_array($args[1])) $args = $args[1]; // Only the second argument is considered if it's an array
        $trans = pnMLS__bindVariables($trans, $args);
    }

    return $trans;
}

/*
function pnMLGetDynamic($refid, $table_name, $fields)
{
    $table_name .= '_mldata';
    $fields = implode(',', $fields);

    $query = "SELECT $fields FROM $table_name WHERE pn_refid = $refid";
    $dbresult = $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__."(".__LINE__."): Database error while querying: $query"));
        return;
    }

    return $dbresult;
}
*/

// L10N API

/**
 * Formats a currency according to specified locale data
 *
 * @access public
 * @returns string
 * @return formatted currency
 */
function pnLocaleFormatCurrency($currency, $localeData = NULL)
{
    if (!isset($localeData)) {
        $localeData = pnMLSLoadLocaleData();
    }
    $currencySym = $localeData['/monetary/currencySymbol'];
    return $currencySym.' '.pnLocaleFormatNumber($currency, $localeData, true);
}

/**
 * Formats a number according to specified locale data
 *
 * @access public
 * @returns string
 * @return formatted number
 * @raise BAD_PARAM
 */
function pnLocaleFormatNumber($number, $localeData = NULL, $isCurrency = false)
{
    if (!is_numeric($number)) {
        $msg = pnML('Number is not of numeric type.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    if (!isset($localeData)) {
        $localeData = pnMLSLoadLocaleData();
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
 * @access private
 * @param locale site locale
 */
function pnMLS_setCurrentLocale($locale)
{
    global $pnMLS_currentLocale;

    $mode = pnMLSGetMode();
    switch ($mode) {
        case PNMLS_SINGLE_LANGUAGE_MODE:
            $locale  = pnMLSGetSiteLocale();
            break;
        case PNMLS_UNBOXED_MULTI_LANGUAGE_MODE:
        case PNMLS_BOXED_MULTI_LANGUAGE_MODE:
            // check for locale availability
            $siteLocales = pnMLSListSiteLocales();
            if (!in_array($locale, $locales)) {
                // Locale not available, use the default
                $locale = pnMLSGetSiteLocale();
            }
    }
    if ($locale != $pnMLS_currentLocale) {
        // Adjust new current locale
        $pnMLS_currentLocale = $locale;

        if ($mode == PNMLS_UNBOXED_MULTI_LANGUAGE_MODE) {
            $curCharset = pnMLSGetCharsetFromLocale($locale);
            if (substr($curCharset, 0, 9) != 'iso-8859-' &&
                $curCharset != 'koi8-r') {
                // Do not use mbstring for single byte charsets
                ini_set('mbstring.func_overload', 7);
                mb_internal_encoding(strtoupper($curCharset));
            }
        }

        // Load core translations
        pnMLS_loadModuleTranslations('base', 'base', 'core');

        // Load global language defines
        $localeData = pnMLSLoadLocaleData($locale);
        if (!isset($localeData)) return; // throw back
        $lang = $localeData['/language/iso3code'];
        $fileName = "language/$lang/global.php";
        if (file_exists($fileName)) {
            include $fileName;
        } else {
            // Oh, bad thing guys
            // Now since all this will become obsolete we can load eng and don't care of details
            $fileName = "language/eng/global.php";
            if (file_exists($fileName)) {
                include $fileName;
            }
        }
    }
}

/**
 * Loads module translations
 *
 * @access private
 * @param modName module name
 * @param modOnDir module directory
 * @param modType module type
 * @returns bool
 * @return
 */
function pnMLS_loadModuleTranslations($modName, $modOsDir, $modType)
{
    global $pnMLS_backend;
    static $loadedCommons = array();

    if (!isset($loadedCommons[$modName])) {
        $loadedCommons[$modName] = true;
        $res = pnMLS_loadModuleTranslations($modName, $modOsDir, 'common');
        if (!isset($res)) {
            return; // throw back
        }
    }

    $locale = pnMLSGetCurrentLocale();

    $alternatives = pnMLS__getLocaleAlternatives($locale);
    foreach ($alternatives as $testLocale) {
        $ctx = array('name' => $modName,
                     'baseDir' => 'modules/'.$modOsDir,
                     'type' => $modType,
                     'locale' => $testLocale);

        $res = $pnMLS_backend->load($ctx);
        if (!isset($res)) {
            return; // throw back
        }
        if ($res == true) {
            return true;
        }
        pnEvt_fire('MLSMissingTranslationContext', $ctx);
    }

    // No valid translations set loaded, try with old style translations

    /* Old style language packs */
    $localeData = pnMLSLoadLocaleData($locale);
    if (!isset($localeData)) return; // throw back
    $lang = $localeData['/language/iso3code'];
    $fileName = "modules/$modOsDir/pnlang/$lang/$modType.php";
    if (file_exists($fileName)) {
        include $fileName;
    } else {
        // Oh, bad thing guys
        // Now since all this will become obsolete we can load eng and don't care of details
        $fileName = "modules/$modOsDir/pnlang/eng/$modType.php";
        if (file_exists($fileName)) {
            include $fileName;
        }
    }

    return false;
}

function pnMLS_loadTemplateTranslations($tplName, $baseOsDir)
{
    global $pnMLS_backend;

    $locale = pnMLSGetCurrentLocale();
    $ctx = array('name' => $tplName,
                 'baseDir' => $baseOsDir,
                 'type' => 'template',
                 'locale' => $locale);
    $res = $pnMLS_backend->load($ctx);
    if (!isset($res)) {
        return; // throw back
    }
    if ($res == true) {
        return true;
    }
    pnEvt_fire('MLSMissingTranslationContext', $ctx);
    return false;
}

function pnMLS_loadBlockTranslations($blockName, $modOsDir)
{
    global $pnMLS_backend;

    $locale = pnMLSGetCurrentLocale();
    $ctx = array('name' => $blockName,
                 'baseDir' => 'modules/'.$modOsDir,
                 'type' => 'block',
                 'locale' => $locale);
    $res = $pnMLS_backend->load($ctx);
    if (!isset($res)) {
        return; // throw back
    }
    if ($res == true) {
        return true;
    }
    pnEvt_fire('MLSMissingTranslationContext', $ctx);

    // Old Style of loading the block language files
    $localeData = pnMLSLoadLocaleData($locale);
    if (!isset($localeData)) return; // throw back
    $lang = $localeData['/language/iso3code'];
    $osname = pnVarPrepForOS($blockName);
    $fileName = "modules/$modOsDir/pnlang/$lang/$osname.php";
    if (file_exists($fileName)) {
        include $fileName;
    } else {
        // Oh, bad thing guys
        // Now since all this will become obsolete we can load eng and don't care of details
        $fileName = "modules/$modOsDir/pnlang/eng/$osname.php";
        if (file_exists($fileName)) {
            include $fileName;
        }
    }

    return false;
}

function pnMLS_convertFromInput($var, $method)
{
    // FIXME: <marco> Can we trust browsers?
    if (pnMLSGetMode() == PNMLS_SINGLE_LANGUAGE_MODE ||
        !function_exists('mb_http_input')) {
        return $var;
    }
    // Cookies must contain only US-ASCII characters
    $inputCharset = strtolower(mb_http_input($method));
    $curCharset = pnMLGetCurrentCharset();
    if ($inputCharset != $curCharset) {
        $var = mb_convert_encoding($var, $curCharset, $inputCharset);
    }
    return $var;
}

// PRIVATE FUNCTIONS

function pnMLS__setup($args)
{
    $mode = $args['MLSMode'];
    if (function_exists('mb_http_input')) {
        $curCharset = pnMLSGetCharsetFromLocale(pnMLSGetCurrentLocale());
        if (substr($curCharset, 0, 9) != 'iso-8859-' &&
            $curCharset != 'koi8-r') {
            // Do not use mbstring for single byte charsets
            ini_set('mbstring.func_overload', 7);
            mb_internal_encoding(strtoupper($curCharset));
        }
    } else {
        if ($mode == PNMLS_UNBOXED_MULTI_LANGUAGE_MODE) {
            // mbstring reuired
            die('pnMLS__setup: Mbstring PHP extension is required for UNBOXED MULTI language mode.');
        }
    }
}

function pnMLS__convertFromCharset($var, $charset)
{
    // FIXME: <marco> Can we trust browsers?
    if (pnMLSGetMode() == PNMLS_SINGLE_LANGUAGE_MODE ||
        !function_exists('mb_convert_encoding')) return $var;
    $curCharset = pnMLGetCurrentCharset();
    $var = mb_convert_encoding($var, $curCharset, $charset);
    return $var;
}

function pnMLS__bindVariables($string, $args)
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
function pnMLS__getLocaleAlternatives($locale)
{
    $parsedLocale = pnMLS__parseLocaleString($locale);
    if (!isset($parsedLocale)) return; // throw back
    extract($parsedLocale); // $lang, $country, $charset

    $alternatives = array($locale);
    if (isset($country)) {
        $alternatives[] = $lang.$country.$charset;
    }
    $alternatives[] = $lang.$charset;

    return $alternatives;
}

/**
 * Parses a locale string into an associative array composed of
 * lang, country, specializer and charset keys
 *.
 * @returns array
 * @return parsed locale
 */
function pnMLS__parseLocaleString($locale)
{
    $size = strlen($locale);
    $cur_pos = 0;
    $seps = array('_', '@', '.');

    while ($cur_pos < $size) {
        $next_pos = $size;
        while (true) {
            $sep = array_shift($seps);
            if (empty($sep)) break;
            $tmp_pos = strpos($locale, $sep, $cur_pos + 1);
            if ($tmp_pos !== false) {
                $next_pos = $tmp_pos;
                break;
            }
        }

        $len = $next_pos - $cur_pos;
        $word = substr($locale, $cur_pos, $len);

        if (empty($old_sep))
            $res['lang'] = $word;
        elseif ($old_sep == '_')
            $res['country'] = $word;
        elseif ($old_sep == '@')
            $res['specializer'] = $word;
        elseif ($old_sep == '.')
            $res['charset'] = $word;

        $old_sep = $sep;
        $cur_pos = $cur_pos + $len;
    }

    if (empty($res['lang'])) {
        $msg = pnML('Invalid locale.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    if (empty($res['charset'])) {
        $res['charset'] = '.'.pnMLS__getSingleByteCharset($res['lang']);
        if ($res['charset'] == '.') $res['charset'] = '.utf-8';
        $locale .= $res['charset'];
    }
    return $res;
}

/**
 * Gets the single byte charset most typically used in the Web for the
 * requested language
 *.
 * @returns string
 * @return the charset
 */
function pnMLS__getSingleByteCharset($langISO2Code) {
    static $charsets = array('af' => 'iso-8859-1', 'sq' => 'iso-8859-1',
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
    'pt' => 'iso-8859-1',  'ro' => 'iso-8859-2',  'ru' => 'koi8-r',
    'gd' => 'iso-8859-1',  'sr' => 'iso-8859-2',  'sk' => 'iso-8859-2',
    'sl' => 'iso-8859-2',  'es' => 'iso-8859-1',  'sv' => 'iso-8859-1',
    'tr' => 'iso-8859-9',  'uk' => 'iso-8859-5');
    return @$charsets[$langISO2Code];
}

// MLS CLASSES

/**
 * This class loads a valid locale descriptor XML file and returns its content
 * in the form of a locale data array
 */
class pnMLS__LocaleDataLoader
{
    var $curData;
    var $curPath;

    var $parser;

    var $localeData;

    var $attribsStack = array();

    var $tmpVars;

    function load($locale)
    {
        $fileName = "locales/$locale.pld";
        if (!file_exists($fileName)) {
            return false;
        }

        $this->tmpVars = array();

        $this->curData = '';
        $this->curPath = '';
        $this->localeData = array();

        // TRICK: <marco> Since this xml parser sucks, we obviously use UTF-8 for utf-8 charset
        // and ISO-8859-1 for other charsets, even if they're not singl byte.
        // The only important thing here is to split utf-8 from other charsets.
        $charset = pnMLSGetCharsetFromLocale($locale);
        // FIXME: <marco> try, re-try and re-re-try this!
        if ($charset == 'utf-8') {
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
                pnExceptionSet(PN_SYSTEM_EXCEPTION, 'XML_PARSER_ERROR',
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
class pnMLS__TranslationsBackend
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
     * Loads a set of translations into the backend. This set is identified
     * by a translation context that contains an object name, base directory,
     * type and locale.
     */
    function load($translationCtx)
    { die('abstract'); }
}

/*
 * This abstract class inherits from pnMLS__TranslationsBackend and provides
 * a powerful access to metadata associated to every translation entry.
 * A translation entry is an array that contains not only the translation,
 * but also the a list of references where it appears in the source by
 * reporting the file name and the line number.
 */
class pnMLS__ReferencesBackend extends pnMLS__TranslationsBackend
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
 * Implements a concrete translations backend based on the XML language.
 * All xml files are encoded in UTF-8. This backend is useful only when
 * running PostNuke in the multi-language mode (UTF-8).
 */
class pnMLS__XMLTranslationsBackend extends pnMLS__ReferencesBackend
{
    var $curEntry;
    var $curData;

    var $parser;

    var $trans = array(); // where translations are kept
    var $transEntries = array(); // mapping for string-based translations
    var $transKeyEntries = array(); // mapping for key-based translations

    var $transInd = 0;
    var $transKeyInd = 0;

    function translate($string)
    {
        if (!isset($this->transEntries[$string])) {
            return;
        }
        $ind = $this->transEntries[$string];
        return $this->trans[$ind]['translation'];
    }

    function translateByKey($key)
    {
        if (!isset($this->transKeyEntries[$key])) {
            return;
        }
        $ind = $this->transKeyEntries[$key];
        return $this->trans[$ind]['translation'];
    }

    function load($translationCtx)
    {
        list($ostype, $oslocale) = pnVarPrepForOS($translationCtx['type'],
                                                  $translationCtx['locale']);

        $fileName = "$translationCtx[baseDir]/pnlang/xml/$oslocale/";
        if ($ostype == 'block') {
            $fileName .= 'blocks/'.pnVarPrepForOS($translationCtx['name']).'.xml';
        } elseif ($ostype == 'template') {
            $fileName .= 'templates/'.pnVarPrepForOS($translationCtx['name']).'.xml';
        } else {
            $fileName .= "pn$ostype.xml";
        }
        if (!file_exists($fileName)) {
            return false;
        }
        $this->curData = '';

        $this->parser = xml_parser_create('UTF-8');
        xml_set_object($this->parser, $this);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_element_handler($this->parser, "beginElement", "endElement");
        xml_set_character_data_handler($this->parser, "characterData");

        if (!($fp = fopen($fileName, 'r'))) {
            return;
        }

        while ($data = fread($fp, 4096)) {
            if (!xml_parse($this->parser, $data, feof($fp))) {
                // NOTE: <marco> Of course don't use pnML here!
                $errstr = xml_error_string(xml_get_error_code($this->parser));
                $line = xml_get_current_line_number($this->parser);
                pnExceptionSet(PN_SYSTEM_EXCEPTION, 'TRANSLATION_EXCEPTION',
                               new SystemException("XML parser error in $fileName: $errstr at line $line."));
                return;
            }
        }

        xml_parser_free($this->parser);

        return true;
    }

    function getEntry($string)
    {
        if (!isset($this->transEntries[$string])) {
            return;
        }
        $ind = $this->transEntries[$string];
        return $this->trans[$ind];
    }

    function getEntryByKey($key)
    {
        if (!isset($this->transKeyEntries[$key])) {
            return;
        }
        $ind = $this->transKeyEntries[$key];
        return $this->trans[$ind];
    }

    function getTransientId($string)
    {
        if (!isset($this->transEntries[$string])) {
            return;
        }
        return $this->transEntries[$string];
    }

    function lookupTransientId($transientId)
    {
        if (!isset($this->trans[(int) $transientId])) {
            return;
        }
        return $this->trans[(int) $transientId];
    }

    function enumTranslations($reset = false)
    {
        if ($reset == true) {
            $this->transInd = 0;
        }
        $count = count($this->trans);
        if ($this->transInd == $count) {
            return false;
        }
        while ($this->transInd < $count) {
            if (isset($this->trans[$this->transInd]['string'])) {
                $res = array($this->trans[$this->transInd]['string'], $this->trans[$this->transInd]['translation']);
                $this->transInd++;
                return $res;
            }
            $this->transInd++;
        }
        return false;
    }

    function enumKeyTranslations($reset = false)
    {
        if ($reset == true) {
            $this->transKeyInd = 0;
        }
        $count = count($this->trans);
        if ($this->transKeyInd == $count) {
            return false;
        }
        while ($this->transKeyInd < $count) {
            if (isset($this->trans[$this->transKeyInd]['key'])) {
                $res = array($this->trans[$this->transKeyInd]['key'], $this->trans[$this->transKeyInd]['translation']);
                $this->transKeyInd++;
                return $res;
            }
            $this->transKeyInd++;
        }
        return false;
    }

    function beginElement($parser, $tag, $attribs)
    {
        if (strpos($tag, ':') !== false) {
            list($ns, $tag) = explode(':', $tag);
        }
        if ($tag == 'entry' || $tag == 'keyEntry') {
            $this->curEntry = array();
            $this->curEntry['references'] = array();
        } elseif ($tag == 'reference') {
            $reference['file'] = $attribs['file'];
            $reference['line'] = $attribs['line'];
            $this->curEntry['references'][] = $reference;
        }
        /*elseif ($tag == 'original') {
            $this->curEntry['original'] = array();
            $this->curEntry['original']['file'] = $attribs['file'];
            $this->curEntry['original']['xpath'] = $attribs['xpath'];
        }*/
    }

    function endElement($parser, $tag)
    {
        if (strpos($tag, ':') !== false) {
            list($ns, $tag) = explode(':', $tag);
        }
        if ($tag == 'entry') {
            $string = $this->curEntry['string'];
            $this->trans[] = $this->curEntry;
            $this->transEntries[$string] = count($this->trans) - 1;
        } elseif ($tag == 'keyEntry') {
            $key = $this->curEntry['key'];
            $this->trans[] = $this->curEntry;
            $this->transKeyEntries[$key] = count($this->trans) - 1;
        } elseif ($tag == 'string') {
            $this->curEntry['string'] = trim($this->curData);
            //$this->curEntry['string'] = utf8_decode(trim($this->curData));
        } elseif ($tag == 'key') {
            $this->curEntry['key'] = trim($this->curData);
        } elseif ($tag == 'translation') {
            $this->curEntry['translation'] = trim($this->curData);
            //$this->curEntry['translation'] = utf8_decode(trim($this->curData));
        }
        $this->curData = '';
    }

    function characterData($parser, $data)
    {
        // FIXME <marco> consider to replace \n,\r with ''
        $this->curData .= $data;
    }

}

/**
 * This is the default translations backend and should be used for production sites.
 * Note that it does not support the pnMLS__ReferencesBackend interface.
 */
class pnMLS__PHPTranslationsBackend extends pnMLS__TranslationsBackend
{
    function translate($string)
    {
        global $pnML_PHPBackend_entries;
        return @$pnML_PHPBackend_entries[$string];
    }

    function translateByKey($key)
    {
        global $pnML_PHPBackend_keyEntries;
        return @$pnML_PHPBackend_keyEntries[$key];
    }

    function load($translationCtx)
    {
        list($ostype, $oslocale) = pnVarPrepForOS($translationCtx['type'],
                                                  $translationCtx['locale']);

        $fileName = "$translationCtx[baseDir]/pnlang/php/$oslocale/";
        if ($ostype == 'block') {
            $fileName .= 'blocks/'.pnVarPrepForOS($translationCtx['name']).'.php';
        } elseif ($ostype == 'template') {
            $fileName .= 'templates/'.pnVarPrepForOS($translationCtx['name']).'.php';
        } else {
            $fileName .= "pn$ostype.php";
        }
        if (!file_exists($fileName)) {
            return false;
        }

        include $fileName;

        return true;
    }

}


?>