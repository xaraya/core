<?php

/**
 * Multi Language System
 *
 * @package core\multilanguage
 * @category Xaraya Web Applications Framework
 * @version 2.8.6
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

use Xaraya\Services\MultiLanguageService;
use Xaraya\Services\xar;

interface ixarMLS
{
    public const SINGLE_LANGUAGE_MODE          = 'SINGLE';
    public const BOXED_MULTI_LANGUAGE_MODE     = 'BOXED';
    public const UNBOXED_MULTI_LANGUAGE_MODE   = 'UNBOXED';
    public const DNTYPE_CORE       = 1;
    public const DNTYPE_THEME      = 2;
    public const DNTYPE_MODULE     = 3;
    public const DNTYPE_PROPERTY   = 4;
    public const DNTYPE_BLOCK      = 5;
    public const DNTYPE_OBJECT     = 6;
}

/**
 * Multilanguage System Class
 *
 * @deprecated 2.8.6 use xar::mls() instead
**/
class xarMLS extends xarObject implements ixarMLS
{
    protected static ?MultiLanguageService $mlsService = null;

    protected static function mls(): MultiLanguageService
    {
        if (!isset(self::$mlsService)) {
            $xar = xar::getServicesClass();
            self::$mlsService = $xar->mls();
        }
        return self::$mlsService;
    }

    /**
     * Initializes the Multi Language System
     *
     * @throws Exception
     * @return boolean true
     */
    public static function init(array $args = [])
    {
        // static cache for migration
        self::$mlsService = null;
        return self::mls()->init($args);
    }

    public static function getConfig()
    {
        return self::mls()->getConfig();
    }

    /**
     * Gets the current MLS mode
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return string MLS Mode
     */
    public static function getMode()
    {
        return self::mls()->getMode();
    }

    /**
     * Summary of setMode
     * @param string $mode
     * @return void
     */
    public static function setMode($mode)
    {
        return self::mls()->setMode($mode);
    }

    public static function getBackendName()
    {
        return self::mls()->getBackendName();
    }

    /**
     * Returns the site locale if running in SINGLE mode,
     * returns the site default locale if running in BOXED or UNBOXED mode
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return string the site locale
     */
    public static function getSiteLocale()
    {
        return self::mls()->getSiteLocale();
    }

    /**
     * Returns an array of locales available in the site
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array<mixed> of locales
     */
    public static function listSiteLocales()
    {
        return self::mls()->listSiteLocales();
    }

    /**
     * Gets the current locale
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return string current locale
     */
    public static function getCurrentLocale()
    {
        return self::mls()->getCurrentLocale();
    }

    /**
     * Gets the charset component from a locale
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return string|void the charset name
     */
    public static function getCharsetFromLocale($locale)
    {
        return xarLocale::getCharsetFromLocale($locale);
    }

    // I18N API

    /**
     * Translates a string
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return string the translated string, or the original string if no translation is available
     */
    public static function translate($rawstring, ...$args)
    {
        return self::mls()->translate($rawstring, ...$args);
    }

    /**
     * Return the translation associated to passed key
     *
     * @author Marco Canini <marco@xaraya.com>
     * @throws BadParameterException
     * @return string the translation string, or the key if no translation is available
     */
    public static function translateByKey($key, ...$args)
    {
        return self::mls()->translateByKey($key, ...$args);
    }

    // L10N API (Localisation)

    /**
     * Gets the locale info for the specified locale string.
     * Info is an array composed by the 'lang', 'country', 'specializer' and 'charset' items.
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array<mixed> locale info
     */
    public static function localeGetInfo($locale)
    {
        return self::parseLocaleString($locale);
    }

    /**
     * Gets the locale string for the specified locale info.
     * Info is an array composed by the 'lang', 'country', 'specializer' and 'charset' items.
     *
     * @author Marco Canini <marco@xaraya.com>
     * @throws BadParameterException
     * @return string locale string
     */
    public static function localeGetString($localeInfo)
    {
        return xarLocale::getLocaleString($localeInfo);
    }

    /**
     * Gets a list of locale string which met the specified filter criteria.
     * Filter criteria are set as item of $filter parameter, they can be one or more of the following:
     * lang, country, specializer, charset.
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array<mixed> locale list
     */
    public static function localeGetList($filter = [])
    {
        return self::mls()->localeGetList($filter);
    }

    /**
     *  Returns a valid timestamp for the current user.  It will
     *  make adjustments for timezone and should be used in gmstrftime
     *  or gmdate functions only.
     *
     *  @author Roger Raymond <roger@asphyxia.com>
     *  @return int unix timestamp.
     */
    public static function userTime($time = null, $flag = 1)
    {
        return self::mls()->userTime($time, $flag);
    }

    /**
     *  Returns the user's current tz offset (+ daylight saving) in hours
     *
     *  @author Roger Raymond <roger@asphyxia.com>
     *  @param int $timestamp optional unix timestamp that we want to get the offset for
     *  @return float tz offset + possible daylight saving adjustment
     */
    public static function userOffset($timestamp = null)
    {
        return self::mls()->userOffset($timestamp);
    }

    /**
     * Sets current locale
     *
     * @author Marco Canini <marco@xaraya.com>
     * @param string $locale site locale
     */
    public static function setCurrentLocale($locale)
    {
        return self::mls()->setCurrentLocale($locale);
    }

    /**
     * Create URL-friendly slug for string and locale - basic version
     * @see \Symfony\Component\String\Slugger\AsciiSlugger
     */
    public static function getSlug(string $text, string $separator = '_', ?string $locale = null): string
    {
        return self::mls()->getSlug($text, $separator, $locale);
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
     * @todo version.php type files support
     * @todo xar(whatever)api.php type files support? (javascript for example)
     * @todo do we want core per file support?
     **/
    public static function loadTranslations($path)
    {
        return self::mls()->loadTranslations($path);
    }

    /**
     * Gets a list of alternatives for a certain locale.
     * The first alternative is the locale itself
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array<mixed>|void alternative locales
     */
    private static function getLocaleAlternatives($locale)
    {
        return xarLocale::getLocaleAlternatives($locale);
    }

    /**
     * Parses a locale string into an associative array composed of
     * lang, country, specializer and charset keys
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array<mixed> parsed locale
     */
    public static function parseLocaleString($locale)
    {
        return xarLocale::parseLocaleString($locale);
    }

    /**
     * Create directories tree
     *
     * @author Volodymyr Metenchuk <voll@xaraya.com>
     * @return boolean true
     * @deprecated 2.8.6 moved to \PHPBackendGenerator::mkdirr()
     */
    public static function mkdirr($path)
    {
        return \PHPBackendGenerator::mkdirr($path);
    }

    /**
     * Check directory writability and create directory if it doesn't exist
     *
     * @author Volodymyr Metenchuk <voll@xaraya.com>
     * @access protected
     * @return bool true
     * @deprecated 2.8.6 moved to \PHPBackendGenerator::iswritable()
     */
    public static function iswritable($directory = null)
    {
        return \PHPBackendGenerator::iswritable($directory);
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
    private static $domains = [
        ixarMLS::DNTYPE_CORE     => ['context_type_prefix' => 'xaraya',     'context_type_text' => 'core'],
        ixarMLS::DNTYPE_THEME    => ['context_type_prefix' => 'themes',     'context_type_text' => 'theme'],
        ixarMLS::DNTYPE_MODULE   => ['context_type_prefix' => 'modules',    'context_type_text' => 'module'],
        ixarMLS::DNTYPE_PROPERTY => ['context_type_prefix' => 'properties', 'context_type_text' => 'property'],
        ixarMLS::DNTYPE_BLOCK    => ['context_type_prefix' => 'blocks',     'context_type_text' => 'block'],
        ixarMLS::DNTYPE_OBJECT   => ['context_type_prefix' => 'objects',    'context_type_text' => 'object'],
    ];
    private static $current_domain_type = ixarMLS::DNTYPE_CORE;

    /**
     * Initializes the Context Class
     *
     * @throws Exception
     * @return boolean true
     */
    public static function init(array $args = [])
    {
        return true;
    }

    public static function setDomainType($domainType_id = ixarMLS::DNTYPE_CORE)
    {
        self::$current_domain_type = $domainType_id;
    }

    public static function getContextFromPath($path = '', $themeBaseDir = 'themes')
    {
        $domainType = 0;
        // @todo be able to handle standard files from other locations, e.g. from /vendor/ with composer
        if (strpos($path, sys::lib()) === 0) {
            $domainType = ixarMLS::DNTYPE_CORE;
            $path = substr($path, strlen(sys::lib()));
        } elseif (strpos($path, $themeBaseDir) === 0) {
            $domainType = ixarMLS::DNTYPE_THEME;
        } elseif (strpos($path, sys::code()) === 0) {
            // This is a module, property or block file
            $path = substr($path, strlen(sys::code()));
            if (strpos($path, 'modules') === 0) {
                $domainType = ixarMLS::DNTYPE_MODULE;
            } elseif (strpos($path, 'properties') === 0) {
                $domainType = ixarMLS::DNTYPE_PROPERTY;
            } elseif (strpos($path, 'blocks') === 0) {
                $domainType = ixarMLS::DNTYPE_BLOCK;
            }
        } else {
            // some non-standard file from another location, e.g. from var/processes for workflows
            $domainType = 0;
            return false;
        }

        // Get a structured representation of the rest of the path
        $pathElements = explode("/", $path);

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
        if (!empty($pathElements)) {
            $pathElements[0] = preg_replace('/^xar(.+)/', '$1', $pathElements[0]);
            $contextType .= implode("/", $pathElements);
        }

        $contextArray = [
            $domainType,
            $domainName,
            $contextType,
            $contextName,
        ];
        return $contextArray;
    }

    public static function getContextTypePrefix($domainType = null)
    {
        if (empty($domainType)) {
            $domainType = self::$current_domain_type;
        }
        $current_domain = self::$domains[$domainType];
        return $current_domain['context_type_prefix'];
    }

    public static function getContextTypeText($domainType = null)
    {
        if (empty($domainType)) {
            $domainType = self::$current_domain_type;
        }
        $current_domain = self::$domains[$domainType];
        return $current_domain['context_type_text'];
    }

    public static function getContextTypeComponents($contextType = null)
    {
        $parts = explode(':', $contextType);

        // Check the validity of the prefix
        $good = false;
        foreach (self::$domains as $domain) {
            if ($domain['context_type_prefix'] == $parts[0]) {
                continue;
            }
            $good = true;
        }
        if (!$good) {
            xarCore::exit("Incorrect context prefix " . $parts[0]);
            return;
        }

        // Remove any empty chars in the directory
        $parts[1] = trim($parts[1]);

        // Return the prefix and directory
        return $parts;
    }

    public static function getDomainPath($domainType, $locale, $backendType, $domainName = "xaraya")
    {
        $prefix = self::getContextTypePrefix($domainType);
        $domainpath  = sys::varpath() . "/locales/" . $locale . "/" . $backendType . "/" . $prefix;

        if (!in_array($prefix, ["xaraya","objects"])) {
            $domainpath  .= "/" . $domainName;
        }
        return $domainpath;
    }
}

// TODO: reminder for if/when we drop the legacy functions or switch to namespaces someday
if (!function_exists('xarML')) {
    /**
     * Summary of xarML
     * @param mixed $rawstring
     * @param mixed $args
     * @return mixed
     */
    function xarML($rawstring, ...$args)
    {
        return call_user_func_array(['xarMLS', 'translate'], func_get_args());
    }
}
