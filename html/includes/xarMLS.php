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

/**
 * Initialise the Multi Language System
 * @access private
 * @returns bool
 * @return true on success
 */
function xarMLS_init($args)
{
    global $xarMLS_mode, $xarMLS_backend;
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

    $backendName = $args['translationsBackend'];
    switch ($backendName) {
        case 'xml':
            $xarMLS_backend = new xarMLS__XMLTranslationsBackend();
            break;
        case 'php':
            $xarMLS_backend = new xarMLS__PHPTranslationsBackend();
            break;
        default:
            xarCore_die('xarML_init: Unknown translations backend: '.$backendName);
    }

    $xarMLS_localeDataLoader = new xarMLS__LocaleDataLoader();
    $xarMLS_localeDataCache = array();

    $xarMLS_defaultLocale = $args['defaultLocale'];
    $xarMLS_allowedLocales = $args['allowedLocales'];

    // TODO: <marco> Choose how to detect current locale!

    //$xarMLS_currentLocale = $xarMLS_defaultLocale;

    // Register MLS events
    xarEvt_registerEvent('MLSMissingTranslationString');
    xarEvt_registerEvent('MLSMissingTranslationKey');
    xarEvt_registerEvent('MLSMissingTranslationContext');

    return true;
}

/**
 * Gets Locale Mode TODO <johnny> marco please describe this function
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
 * @raise LOCALE_NOT_FOUNT
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
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'LOCALE_NOT_FOUND',
                       new SystemException($msg));
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
 * Gets the current locale TODO: <marco> please check this description
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
    if (empty($locale)) {
        $msg = xarML('Empty locale.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
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
function xarML($string)
{
    global $xarMLS_backend;

    if (isset($xarMLS_backend)) {
        $trans = $xarMLS_backend->translate($string);
    } else {
        // This happen in rare cases when xarML is called before xarMLInit was called
        $trans = $string;
    }
    if (!isset($trans) || $trans == '') {
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
function xarMLByKey($key)
{
    global $xarMLS_backend;

    if (isset($xarMLS_backend)) {
        $trans = $xarMLS_backend->translateByKey($key);
    } else {
        // This happen in rare cases when xarMLByKey is called before xarMLInit was called
        $trans = $key;
    }
    if (!isset($trans) || $trans == '') {
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
    $dbresult = $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
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
 * @access private
 * @param locale site locale
 */
function xarMLS_setCurrentLocale($locale)
{
    global $xarMLS_currentLocale;

    $mode = xarMLSGetMode();
    switch ($mode) {
        case XARMLS_SINGLE_LANGUAGE_MODE:
            $locale  = xarMLSGetSiteLocale();
            break;
        case XARMLS_UNBOXED_MULTI_LANGUAGE_MODE:
        case XARMLS_BOXED_MULTI_LANGUAGE_MODE:
            // check for locale availability
            $siteLocales = xarMLSListSiteLocales();
            if (!in_array($locale, $locales)) {
                // Locale not available, use the default
                $locale = xarMLSGetSiteLocale();
            }
    }
    if ($locale != $xarMLS_currentLocale) {
        // Adjust new current locale
        $xarMLS_currentLocale = $locale;

        if ($mode == XARMLS_UNBOXED_MULTI_LANGUAGE_MODE) {
            $curCharset = xarMLSGetCharsetFromLocale($locale);
            if (substr($curCharset, 0, 9) != 'iso-8859-' &&
                $curCharset != 'koi8-r') {
                // Do not use mbstring for single byte charsets
                ini_set('mbstring.func_overload', 7);
                mb_internal_encoding(strtoupper($curCharset));
            }
        }

        // Load core translations
        xarMLS_loadTranslations('core', 'xaraya', 'locales', 'file', 'core');

        // Load global language defines
        $localeData = xarMLSLoadLocaleData($locale);
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
function xarMLS_loadModuleTranslations($modName, $modOsDir, $modType)
{
    global $xarMLS_backend;
    static $loadedCommons = array();

    if (!isset($loadedCommons[$modName])) {
        $loadedCommons[$modName] = true;
        $res = xarMLS_loadModuleTranslations($modName, $modOsDir, 'common');
        if (!isset($res)) {
            return; // throw back
        }
    }

    $locale = xarMLSGetCurrentLocale();

    $alternatives = xarMLS__getLocaleAlternatives($locale);
    foreach ($alternatives as $testLocale) {
        if ($modName == 'translations') $testLocale = 'it';
        $ctx = array('type' => 'module',
                     'name' => $modName,
                     'baseDir' => 'modules/'.$modOsDir,
                     'subtype' => 'file',
                     'subname' => 'xar'.$modType,
                     'locale' => $testLocale);

        $res = $xarMLS_backend->load($ctx);
        if (!isset($res)) {
            return; // throw back
        }
        if ($res == true) {
            return true;
        }
        xarEvt_fire('MLSMissingTranslationContext', $ctx);
    }

    // No valid translations set loaded, try with old style translations

    /* Old style language packs */
    $localeData = xarMLSLoadLocaleData($locale);
    if (!isset($localeData)) return; // throw back
    $lang = $localeData['/language/iso3code'];
    $fileName = "modules/$modOsDir/xarlang/$lang/$modType.php";
    if (file_exists($fileName)) {
        include $fileName;
    } else {
        // Oh, bad thing guys
        // Now since all this will become obsolete we can load eng and don't care of details
        $fileName = "modules/$modOsDir/xarlang/eng/$modType.php";
        if (file_exists($fileName)) {
            include $fileName;
        }
    }

    return false;
}
*/
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
function xarMLS_loadTranslations($type, $name, $baseDir, $subtype, $subname)
{
    global $xarMLS_backend;
    static $loadedCommons = array();

    if ($type == 'module') {
        // Handle in a special way the module type
        // for which it's necessary to load common translations
        if (!isset($loadedCommons[$name])) {
            $loadedCommons[$name] = true;
            if (xarMLS_loadTranslations($type, $name, $baseDir, 'file', 'common') === NULL) return;
        }
    }

    $locale = xarMLSGetCurrentLocale();
    $testLocale = $locale;
    //$alternatives = xarMLS__getLocaleAlternatives($locale);
    //foreach ($alternatives as $testLocale) {
        if ($name == 'translations') $testLocale = 'it';
        $ctx = array('type' => $type,
                     'name' => $name,
                     'baseDir' => $baseDir,
                     'subtype' => $subtype,
                     'subname' => $subname,
                     'locale' => $testLocale);
if($subtype == 'template')
xarLogVariable('ctx', $ctx);
        if ($xarMLS_backend->hasContext($ctx, $loadCtx)) {
            if (!$xarMLS_backend->load($loadCtx)) return;
            return true;
        } else {
            xarEvt_fire('MLSMissingTranslationContext', $ctx);
        }
    //}

    // No valid translations set loaded, try with old style translations

    /* Old style language packs */
    // TODO: <marco> Recode if we want to keep old language packs
    /*
    $localeData = xarMLSLoadLocaleData($locale);
    if (!isset($localeData)) return; // throw back
    $lang = $localeData['/language/iso3code'];
    $fileName = "modules/$modOsDir/xarlang/$lang/$modType.php";
    if (file_exists($fileName)) {
        include $fileName;
    } else {
        // Oh, bad thing guys
        // Now since all this will become obsolete we can load eng and don't care of details
        $fileName = "modules/$modOsDir/xarlang/eng/$modType.php";
        if (file_exists($fileName)) {
            include $fileName;
        }
    }
    */
    return false;
}
/*
function xarMLS_loadTemplateTranslations($tplName, $baseOsDir)
{
    global $xarMLS_backend;

    $locale = xarMLSGetCurrentLocale();
    $ctx = array('name' => $tplName,
                 'baseDir' => $baseOsDir,
                 'type' => 'template',
                 'locale' => $locale);
    $res = $xarMLS_backend->load($ctx);
    if (!isset($res)) {
        return; // throw back
    }
    if ($res == true) {
        return true;
    }
    xarEvt_fire('MLSMissingTranslationContext', $ctx);
    return false;
}
/*
function xarMLS_loadBlockTranslations($blockName, $modOsDir)
{
    global $xarMLS_backend;

    $locale = xarMLSGetCurrentLocale();
    $ctx = array('name' => $blockName,
                 'baseDir' => 'modules/'.$modOsDir,
                 'type' => 'block',
                 'locale' => $locale);
    $res = $xarMLS_backend->load($ctx);
    if (!isset($res)) {
        return; // throw back
    }
    if ($res == true) {
        return true;
    }
    xarEvt_fire('MLSMissingTranslationContext', $ctx);

    // Old Style of loading the block language files
    $localeData = xarMLSLoadLocaleData($locale);
    if (!isset($localeData)) return; // throw back
    $lang = $localeData['/language/iso3code'];
    $osname = xarVarPrepForOS($blockName);
    $fileName = "modules/$modOsDir/xarlang/$lang/$osname.php";
    if (file_exists($fileName)) {
        include $fileName;
    } else {
        // Oh, bad thing guys
        // Now since all this will become obsolete we can load eng and don't care of details
        $fileName = "modules/$modOsDir/xarlang/eng/$osname.php";
        if (file_exists($fileName)) {
            include $fileName;
        }
    }

    return false;
}
*/
function xarMLS_convertFromInput($var, $method)
{
    // FIXME: <marco> Can we trust browsers?
    if (xarMLSGetMode() == XARMLS_SINGLE_LANGUAGE_MODE ||
        !function_exists('mb_http_input')) {
        return $var;
    }
    // Cookies must contain only US-ASCII characters
    $inputCharset = strtolower(mb_http_input($method));
    $curCharset = xarMLGetCurrentCharset();
    if ($inputCharset != $curCharset) {
        $var = mb_convert_encoding($var, $curCharset, $inputCharset);
    }
    return $var;
}

// PRIVATE FUNCTIONS

function xarMLS__setup($args)
{
    $mode = $args['MLSMode'];
    if (function_exists('mb_http_input')) {
        $curCharset = xarMLSGetCharsetFromLocale(xarMLSGetCurrentLocale());
        if (substr($curCharset, 0, 9) != 'iso-8859-' &&
            $curCharset != 'koi8-r') {
            // Do not use mbstring for single byte charsets
            ini_set('mbstring.func_overload', 7);
            mb_internal_encoding(strtoupper($curCharset));
        }
    } else {
        if ($mode == XARMLS_UNBOXED_MULTI_LANGUAGE_MODE) {
            // mbstring reuired
            xarCore_die('xarMLS__setup: Mbstring PHP extension is required for UNBOXED MULTI language mode.');
        }
    }
}

function xarMLS__convertFromCharset($var, $charset)
{
    // FIXME: <marco> Can we trust browsers?
    if (xarMLSGetMode() == XARMLS_SINGLE_LANGUAGE_MODE ||
        !function_exists('mb_convert_encoding')) return $var;
    $curCharset = xarMLGetCurrentCharset();
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
    $parsedLocale = xarMLS__parseLocaleString($locale);
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
function xarMLS__parseLocaleString($locale)
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
        $msg = xarML('Invalid locale.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    if (empty($res['charset'])) {
        $res['charset'] = '.'.xarMLS__getSingleByteCharset($res['lang']);
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
function xarMLS__getSingleByteCharset($langISO2Code) {
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
        $charset = xarMLSGetCharsetFromLocale($locale);
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
     * Checks if this backend supports a scpecified translation context.
     * If success the loadCtx parameter is set to the load context value.
     */
    function hasContext($translationCtx, &$loadCtx)
    { die('abstract'); }
    /**
     * Loads a set of translations into the backend. This set is identified
     * by a load context that can be aquired by hasContext static method.
     */
    function load($loadCtx)
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
 * Implements a concrete translations backend based on the XML language.
 * All xml files are encoded in UTF-8. This backend is useful only when
 * running PostNuke in the multi-language mode (UTF-8).
 */
class xarMLS__XMLTranslationsBackend extends xarMLS__ReferencesBackend
{
    var $curEntry;
    var $curData;

    var $parser;

    var $trans = array(); // where translations are kept
    var $transEntries = array(); // mapping for string-based translations
    var $transKeyEntries = array(); // mapping for key-based translations

    var $transInd = 0;
    var $transKeyInd = 0;

    function hasContext($translationCtx, &$loadCtx)
    {
        // Only module typed contexts are allowed
        //if ($translationCtx['type'] != 'module') return false;

        $fileName = "$translationCtx[baseDir]/xarlang/xml/$translationCtx[locale]/";
        switch ($translationCtx['subtype']) {
            case 'file':
                $fileName .= 'xar'.$translationCtx['subname'];
            break;
            case 'template':
                $fileName .= "templates/$translationCtx[subname]";
            break;
            case 'block':
                $fileName .= "blocks/$translationCtx[subname]";
        }
        $fileName .= '.xml';

        if (!file_exists($fileName)) {
            return false;
        }
        $loadCtx = $fileName;
        return true;
    }

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

    function load($loadCtx)
    {
        $this->curData = '';

        $this->parser = xml_parser_create('UTF-8');
        xml_set_object($this->parser, $this);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_element_handler($this->parser, "beginElement", "endElement");
        xml_set_character_data_handler($this->parser, "characterData");

        $fp = fopen($loadCtx, 'r');

        while ($data = fread($fp, 4096)) {
            if (!xml_parse($this->parser, $data, feof($fp))) {
                // NOTE: <marco> Of course don't use xarML here!
                $errstr = xml_error_string(xml_get_error_code($this->parser));
                $line = xml_get_current_line_number($this->parser);
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'TRANSLATION_EXCEPTION',
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
 * Note that it does not support the xarMLS__ReferencesBackend interface.
 */
class xarMLS__PHPTranslationsBackend extends xarMLS__TranslationsBackend
{
    function hasContext($translationCtx, &$loadCtx)
    {
        // Only module typed contexts are allowed
        //if ($translationCtx['type'] != 'module') return false;

        $fileName = "$translationCtx[baseDir]/xarlang/php/$translationCtx[locale]/";
        switch ($translationCtx['subtype']) {
            case 'file':
                $fileName .= 'xar'.$translationCtx['subname'];
            break;
            case 'template':
                $fileName .= "templates/$translationCtx[subname]";
            break;
            case 'block':
                $fileName .= "blocks/$translationCtx[subname]";
        }
        $fileName .= '.php';

        if (!file_exists($fileName)) {
            return false;
        }
        $loadCtx = $fileName;
        return true;
    }

    function translate($string)
    {
        global $xarML_PHPBackend_entries;
        if (isset($xarML_PHPBackend_entries[$string]))
            return $xarML_PHPBackend_entries[$string];
    }

    function translateByKey($key)
    {
        global $xarML_PHPBackend_keyEntries;
        if (isset($xarML_PHPBackend_keyEntries[$key]))
            return $xarML_PHPBackend_keyEntries[$key];
    }

    function load($loadCtx)
    {
        include $loadCtx;

        return true;
    }

}


