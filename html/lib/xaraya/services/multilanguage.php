<?php

/**
 * MultiLanguage available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use ixarMLS;
use xarCore;
use XarDateTime;
use xarLocale;
use xarMLSContext;
use sys;
use BadParameterException;
use Exception;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via MultiLanguageTrait
 */
interface MultiLanguageInterface extends ServiceInterface
{
    public const SLICE = 'multilanguage';

    public const SINGLE_LANGUAGE_MODE = ixarMLS::SINGLE_LANGUAGE_MODE;

    /**
     * Returns the site locale if running in SINGLE mode,
     * returns the site default locale if running in BOXED or UNBOXED mode
     */
    public function getSiteLocale(): string;

    /**
     * Returns an array of locales available in the site
     * @return array<mixed> of locales
     */
    public function listSiteLocales(): array;

    /**
     * Get the current locale or empty if not defined yet
     */
    public function getCurrentLocale(): string;

    /**
     * Set current locale
     */
    public function setCurrentLocale(string $locale): bool;

    /**
     * Get the current MLS mode
     */
    public function getMode(): mixed;

    public function setMode($mode): void;

    public function getBackendName(): mixed;

    //public function getEncoding(): xarCharset;

    /**
     * Get the charset component from a locale
     */
    public function getCharsetFromLocale(string $locale): string;

    /**
     * Load locale data
     * @param ?string $locale
     * @return array<mixed> locale data
     */
    public function loadLocale(?string $locale = null): array;

    /**
     * Format a date/time according to the current locale
     * @param ?string $format
     * @param mixed $timestamp
     * @param bool $addoffset
     * @return string
     */
    public function formatDate(?string $format = null, mixed $timestamp = null, bool $addoffset = true): string;

    /**
     * Get formatted date according to the current locale
     * @param string $length
     * @param mixed $timestamp
     * @param bool $addoffset
     * @return string
     */
    public function getFormattedDate(string $length = 'short', mixed $timestamp = null, bool $addoffset = true): string;

    /**
     * Get formatted time according to the current locale
     * @param string $length
     * @param mixed $timestamp
     * @param bool $addoffset
     * @return string
     */
    public function getFormattedTime(string $length = 'short', mixed $timestamp = null, bool $addoffset = true): string;

    public function localeGetInfo($locale): array;

    public function localeGetList($filter = []): array;

    /**
     * Get timestamp adjusted to current user timezone
     * @return int
     */
    public function userTime($time = null, $flag = 1): int;

    public function userOffset($timestamp = null): int;

    /**
     * Translate string with optional arguments
     * @param string $rawstring
     * @param mixed ...$args
     */
    public function translate($rawstring, ...$args): string;

    public function translateByKey($key, ...$args): mixed;

    public function getSlug(string $text, string $separator = '_', ?string $locale = null): string;

    /**
     * Load translations for a file by path
     * @param string $path
     * @return bool
     */
    public function loadTranslations(string $path): bool;

    /**
     * Load translations for a module function or method
     * @param string $modName
     * @param string $modType (incl. funcType)
     * @param string $funcName
     * @return bool
     */
    public function loadModuleTranslations(string $modName, string $modType, string $funcName): bool;

    /**
     * Load translations for a data object property
     * @param string $objectName
     * @param string $propertyName
     * @return bool
     */
    public function loadObjectTranslations(string $objectName, string $propertyName): bool;

    public function mkdirr($path): bool;

    public function iswritable($directory = null): bool|int;
}

/**
 * MultiLanguage available via methods
 */
trait MultiLanguageTrait
{
    use ServiceTrait;

    public $mode              = ixarMLS::SINGLE_LANGUAGE_MODE;
    public $backendName       = 'xml2php';
    public $currentLocale     = '';
    public $defaultLocale     = 'en_US.utf-8';
    public $allowedLocales    = ['en_US.utf-8'];
    //public $newEncoding       = null;
    public $defaultTimeZone   = 'UTC';
    public $defaultTimeOffset = 0;
    public $backend           = null;
    protected bool $initialized = false;

    /**
     * Initialize service class
     * @param array<string, mixed> $config
     */
    public function init(array $config = []): bool
    {
        if (empty($args)) {
            if ($this->initialized) {
                return true;
            }
            $args = $this->getConfig();
        }
        switch ($args['MLSMode']) {
            case ixarMLS::SINGLE_LANGUAGE_MODE:
            case ixarMLS::BOXED_MULTI_LANGUAGE_MODE:
                $this->mode = $args['MLSMode'];
                break;
            case ixarMLS::UNBOXED_MULTI_LANGUAGE_MODE:
                $this->mode = $args['MLSMode'];
                if (!function_exists('mb_http_input')) {
                    // mbstring required
                    throw new Exception('xar::mls()->init: Mbstring PHP extension is required for UNBOXED MULTI language mode.');
                }
                break;
            default:
                $this->mode = ixarMLS::BOXED_MULTI_LANGUAGE_MODE;
                //throw new Exception('xar::mls()->init: Unknown MLS mode: '.$args['MLSMode']);
        }
        $this->backendName = $args['translationsBackend'];

        $this->currentLocale = '';

        $this->defaultLocale = $args['defaultLocale'];
        $this->allowedLocales = $args['allowedLocales'];

        //$this->newEncoding = new xarCharset();

        $this->defaultTimeZone = !empty($args['defaultTimeZone'])
                                     ? $args['defaultTimeZone'] : @date_default_timezone_get();
        $this->defaultTimeOffset = $args['defaultTimeOffset'] ?? 0;

        // Set the timezone
        date_default_timezone_set($this->defaultTimeZone);

        // Register MLS events
        // These should be done before the xar::mls()->setCurrentLocale function
        // These are now registered during base module init
        // @CHECKME: <chris> grep -R xarEvents::notify . finds no results
        // It appears these events are never raised ?
        // In addition, these seem more like exceptions than 'events' ?
        //xarEvents::register('MLSMissingTranslationString');
        //xarEvents::register('MLSMissingTranslationKey');
        //xarEvents::register('MLSMissingTranslationDomain');

        // FIXME: this was previously conditional on User subsystem initialisation,
        // but in the 2.x flow we need it earlier apparently, so made this unconditional
        // *AND* commented out the assertion on running this once per request lower
        // in this file. We need to investigate this better after the MLS refactoring
        $this->setCurrentLocale($args['defaultLocale']);
        $this->initialized = true;
        return true;
    }

    /**
     * Get configuration
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $xar = $this->getParent();
        // FIXME: Site.MLS.MLSMode is null during install
        try {
            $systemArgs = [
                'MLSMode'             => $xar->config()->getVar('Site.MLS.MLSMode'),
                //'translationsBackend' => $xar->config()->getVar('Site.MLS.TranslationsBackend'),
                'translationsBackend' => 'xml2php',
                'defaultLocale'       => $xar->config()->getVar('Site.MLS.DefaultLocale'),
                'allowedLocales'      => $xar->config()->getVar('Site.MLS.AllowedLocales'),
                'defaultTimeZone'     => $xar->config()->getVar('Site.Core.TimeZone'),
                'defaultTimeOffset'   => $xar->config()->getVar('Site.MLS.DefaultTimeOffset'),
            ];
        } catch (\VariableNotFoundException) {
            $systemArgs = [
                'MLSMode'             => $this->mode,
                'translationsBackend' => $this->backendName,
                'defaultLocale'       => $this->defaultLocale,
                'allowedLocales'      => $this->allowedLocales,
                'defaultTimeZone'     => $this->defaultTimeZone,
                'defaultTimeOffset'   => $this->defaultTimeOffset,
            ];
        }
        return $systemArgs;
    }

    /**
     * Returns the site locale if running in SINGLE mode,
     * returns the site default locale if running in BOXED or UNBOXED mode
     */
    public function getSiteLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * Returns an array of locales available in the site
     * @return array<mixed> of locales
     */
    public function listSiteLocales(): array
    {
        $mode = $this->getMode();
        if ($mode == ixarMLS::SINGLE_LANGUAGE_MODE) {
            return [$this->defaultLocale];
        } else {
            return $this->allowedLocales;
        }
    }

    /**
     * Get the current locale or empty if not defined in xar::user()->init() yet
     */
    public function getCurrentLocale(): string
    {
        return $this->currentLocale;
    }

    /**
     * Set current locale
     */
    public function setCurrentLocale(string $locale): bool
    {
        // Only refresh if we need to
        if ($this->getCurrentLocale() == $locale) {
            return true;
        }
        $xar = $this->getParent();

        $xar->log()->info("Changing the default locale from " . $this->getCurrentLocale() . " to " . $locale);

        static $called = 0;

        // FIXME: during initialisation, the current locale was set, and it gets called
        // again during user subsystem initialisation, we have to provide better defaults
        // if we really want this to run only once.

        $called++;

        $mode = $this->getMode();
        switch ($mode) {
            case ixarMLS::SINGLE_LANGUAGE_MODE:
                $locale  = $this->getSiteLocale();
                break;
            case ixarMLS::UNBOXED_MULTI_LANGUAGE_MODE:
            case ixarMLS::BOXED_MULTI_LANGUAGE_MODE:
                // check for locale availability
                $siteLocales = $this->listSiteLocales();
                if (!in_array($locale, $siteLocales)) {
                    // Locale not available, use the default
                    $locale = $this->getSiteLocale();
                    $xar->log()->info("Falling back to default locale: $locale");
                }
        }

        // Set current locale
        $this->currentLocale = $locale;

        $curCharset = xarLocale::getCharsetFromLocale($locale);
        if ($mode == ixarMLS::UNBOXED_MULTI_LANGUAGE_MODE) {
            assert($curCharset == "utf-8");
            // To be able to continue, we set the mode to BOXED
            if ($curCharset != "utf-8") {
                $xar->log()->info("Resetting MLS mode to BOXED");
                $xar->config()->setVar('Site.MLS.MLSMode', ixarMLS::BOXED_MULTI_LANGUAGE_MODE);
            } else {
                if (!xarCore::funcIsDisabled('ini_set')) {
                    ini_set('mbstring.func_overload', 7);
                }
                mb_internal_encoding($curCharset);
            }
        }

        //if ($mode == ixarMLS::BOXED_MULTI_LANGUAGE_MODE) {
        //if (substr($curCharset, 0, 9) != 'iso-8859-' &&
        //$curCharset != 'windows-1251') {
        // Do not use mbstring for single byte charsets

        //}
        //}

        $alternatives = xarLocale::getLocaleAlternatives($locale);
        switch ($this->backendName) {
            case 'xml':
                sys::import('xaraya.mlsbackends.xml');
                $this->backend = new \xarMLS__XMLTranslationsBackend($alternatives);
                break;
            case 'php':
                sys::import('xaraya.mlsbackends.php');
                $this->backend = new \xarMLS__PHPTranslationsBackend($alternatives);
                break;
            case 'xml2php':
                sys::import('xaraya.mlsbackends.xml2php');
                $this->backend = new \xarMLS__XML2PHPTranslationsBackend($alternatives);
                break;
        }

        // Load core translations
        $this->_loadTranslations(ixarMLS::DNTYPE_CORE, 'xaraya', 'core:', 'core');
        return true;
    }

    /**
     * Get the current MLS mode
     */
    public function getMode(): mixed
    {
        return $this->mode ?? ixarMLS::BOXED_MULTI_LANGUAGE_MODE;
    }

    public function setMode($mode): void
    {
        $this->mode = $mode;
    }

    public function getBackendName(): mixed
    {
        return $this->backendName;
    }

    /**
     * Get the charset component from a locale
     */
    public function getCharsetFromLocale(string $locale): string
    {
        return xarLocale::getCharsetFromLocale($locale);
    }

    /**
     * Load locale data
     * @param ?string $locale
     * @return array<mixed> locale data
     */
    public function loadLocale(?string $locale = null): array
    {
        $locale ??= $this->getCurrentLocale();
        return xarLocale::loadData($locale);
    }

    /**
     * Format a date/time according to the current locale
     * @param ?string $format
     * @param mixed $timestamp
     * @param bool $addoffset
     * @return string
     * @todo move dependency on xar::mls()->userOffset() to caller
     */
    public function formatDate(?string $format = null, mixed $timestamp = null, bool $addoffset = true): string
    {
        return xarLocale::formatDate($format, $timestamp, $addoffset);
    }

    /**
     * Get formatted date according to the current locale
     * @param string $length
     * @param mixed $timestamp
     * @param bool $addoffset
     * @return string
     * @todo move dependency on xar::mls()->userOffset() to caller
     */
    public function getFormattedDate(string $length = 'short', mixed $timestamp = null, bool $addoffset = true): string
    {
        return xarLocale::getFormattedDate($length, $timestamp, $addoffset);
    }

    /**
     * Get formatted time according to the current locale
     * @param string $length
     * @param mixed $timestamp
     * @param bool $addoffset
     * @return string
     * @todo move dependency on xar::mls()->userOffset() to caller
     */
    public function getFormattedTime(string $length = 'short', mixed $timestamp = null, bool $addoffset = true): string
    {
        return xarLocale::getFormattedTime($length, $timestamp, $addoffset);
    }

    /**
     * Gets the locale info for the specified locale string.
     * Info is an array composed by the 'lang', 'country', 'specializer' and 'charset' items.
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array<mixed> locale info
     */
    public function localeGetInfo($locale): array
    {
        return xarLocale::parseLocaleString($locale);
    }

    /**
     * Gets the locale string for the specified locale info.
     * Info is an array composed by the 'lang', 'country', 'specializer' and 'charset' items.
     *
     * @author Marco Canini <marco@xaraya.com>
     * @throws BadParameterException
     * @return string locale string
     */
    protected function localeGetString($localeInfo): string
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
    public function localeGetList($filter = []): array
    {
        $locales = $this->listSiteLocales();
        return xarLocale::filterLocaleList($locales, $filter);
    }

    /**
     * Get timestamp adjusted to current user timezone
     * @return int
     */
    public function userTime($time = null, $flag = 1): int
    {
        // get the current UTC time
        if (!isset($time)) {
            $time = time();
        }
        if ($flag) {
            $time += $this->userOffset($time) * 3600;
        }
        // return the corrected timestamp
        return $time;
    }

    public function userOffset($timestamp = null): int
    {
        sys::import('xaraya.structures.datetime');
        $datetime = new XarDateTime();
        $datetime->setTimeStamp($timestamp);
        $xar = $this->getParent();
        if ($xar->user()->isLoggedIn()) {
            $usertz = $xar->mod('roles')->getUserVar('usertimezone');
        } else {
            $usertz = $xar->config()->getVar('Site.Core.TimeZone');
        }
        $useroffset = $datetime->getTZOffset($usertz);

        return intdiv($useroffset, 3600);
    }

    /**
     * Translate string with optional arguments
     * @param string $rawstring
     * @param mixed ...$args
     */
    public function translate($rawstring, ...$args): string
    {
        // if an empty string is passed in, just return an empty string. it's
        // the most sensible thing to do
        $string = trim($rawstring ?? '');
        if ($string == '') {
            return $rawstring;
        }

        $start = strpos($rawstring, $string);
        $prefix = substr($rawstring, 0, $start);
        $suffix = substr($rawstring, $start + strlen($string));

        // Make sure string is sane
        // - hex 0D -> ''
        // - space around newline -> ' '
        // - multiple newlines -> 1 newline
        //    $string = preg_replace(array('[\x0d]','/[\t ]+/','/\s*\n\s*\/'), array('',' ',"\n"),$string);

        if (isset($this->backend)) {
            $trans = $this->backend->translate($string, 1);
        } else {
            // This happen in rare cases when xarML is called before $this->init has been called
            $trans = $string;
        }

        if (empty($trans)) {
            // FIXME: postpone
            //xarEvents::notify('MLSMissingTranslationString', $string);
            $trans = $string;
        }
        if (!empty($args)) {
            if (is_array($args[0])) {
                $args = $args[0];
            } // Only the first argument is considered if it's an array
            $trans = $this->bindVariables($trans, $args);
        }

        return $prefix . $trans . $suffix;
    }

    public function translateByKey($key, ...$args): mixed
    {
        // Key must have a value and not contain spaces
        if (empty($key) || strpos($key, " ")) {
            throw new BadParameterException('key');
        }

        if (isset($this->backend)) {
            $trans = $this->backend->translateByKey($key);
        } else {
            // This happen in rare cases when $this->translateByKey is called before $this->init has been called
            $trans = $key;
        }
        if (empty($trans)) {
            // FIXME: postpone
            //xarEvents::notify('MLSMissingTranslationKey', $key);
            $trans = $key;
        }
        if (!empty($args)) {
            if (is_array($args[0])) {
                $args = $args[0];
            } // Only the first argument is considered if it's an array
            $trans = $this->bindVariables($trans, $args);
        }

        return $trans;
    }

    /**
     * Create URL-friendly slug for string and locale - basic version
     * @see \Symfony\Component\String\Slugger\AsciiSlugger
     */
    public function getSlug(string $text, string $separator = '_', ?string $locale = null): string
    {
        $text = str_replace(' ', $separator, $text);
        return rawurlencode($text);
    }

    /**
     * Load translations for a file by path
     * @param string $path
     * @return bool
     */
    public function loadTranslations(string $path): bool
    {
        $xar = $this->getParent();
        $xar->log()->debug("MLS: Loading translations for the path: $path");
        // @todo with migration to module class methods, it doesn't matter if the old path still exists
        //if(!file_exists($path)) {
        //    $xar->log()->warning("MLS: Failed loading translations for a non-existing path ($path)");
        //    return true;
        //}

        $domainArray = xarMLSContext::getContextFromPath($path);
        if (empty($domainArray)) {
            // some non-standard file from another location, e.g. from var/processes for workflows
            return true;
        }
        $domainType = $domainArray[0];

        // If this is a core file, get the translations and bail
        if ($domainType == ixarMLS::DNTYPE_CORE) {
            $translations = $this->_loadTranslations(ixarMLS::DNTYPE_CORE, 'xaraya', 'core:', 'core');
            return $translations;
        }

        // Themes can override other domain types
        if ($domainType == ixarMLS::DNTYPE_THEME) {
            $possibleOverride = true;
        } else {
            $possibleOverride = false;
        }

        // Ok, based on possible overrides, we load internal only, or interal plus overrides
        $ok = false;
        if ($possibleOverride) {
            $ok = $this->_loadTranslations(ixarMLS::DNTYPE_MODULE, $domainArray[1], $domainArray[2], $domainArray[3]);
        }
        // And load the determined stuff
        // @todo: should we check for success on *both*, where is the exception here? further up the tree?
        $ok = $this->_loadTranslations($domainType, $domainArray[1], $domainArray[2], $domainArray[3]);
        return $ok;
    }

    /**
     * Load translations for a module function or method
     * @param string $modName
     * @param string $modType (incl. $funcType)
     * @param string $funcName
     * @return bool
     */
    public function loadModuleTranslations(string $modName, string $modType, string $funcName): bool
    {
        //$this->_loadTranslations(ixarMLS::DNTYPE_MODULE, $modOsDir, 'modules:', 'version');
        //if ($this->_loadTranslations(ixarMLS::DNTYPE_MODULE, $modName, 'modules:' . $modType . $funcType, $funcName) === null) {
        //if ($this->_loadTranslations(ixarMLS::DNTYPE_MODULE, $modName, 'modules:', $modType) === null) {
        return $this->_loadTranslations(ixarMLS::DNTYPE_MODULE, $modName, 'modules:' . $modType, $funcName);
    }

    /**
     * Load translations for a data object property
     * @param string $objectName
     * @param string $propertyName
     * @return bool
     */
    public function loadObjectTranslations(string $objectName, string $propertyName): bool
    {
        return $this->_loadTranslations(ixarMLS::DNTYPE_OBJECT, 'object', 'objects:' . $objectName, $propertyName);
    }

    /**
     * Loads translations for the specified context
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return boolean|void
     */
    protected function _loadTranslations($domainType, $domainName, $contextType, $contextName)
    {
        static $loadedCommons = [];
        static $loadedTranslations = [];
        $xar = $this->getParent();

        $xar->log()->debug("MLS: Loading translations for the context " . "$domainType,$domainName,$contextType,$contextName");

        if (!isset($this->backend)) {
            $xar->log()->warning("xar::mls(): No translation backend was selected for " . "$domainType,$domainName,$contextType,$contextName");
            return false;
        }
        if (empty($this->currentLocale)) {
            $xar->log()->warning("xar::mls(): No current locale was selected");
            return false;
        }

        // only load each translation once
        if (isset($loadedTranslations["$domainType.$domainName.$contextType.$contextName"])) {
            return $loadedTranslations["$domainType.$domainName.$contextType.$contextName"];
        }

        if ($this->backend->bindDomain($domainType, $domainName)) {
            switch ($domainType) {
                case ixarMLS::DNTYPE_THEME:
                    // Load common translations
                    if (!isset($loadedCommons[$domainName . 'theme'])) {
                        $loadedCommons[$domainName . 'theme'] = true;
                        if (!$this->backend->loadContext('themes:', 'common')) {
                            return;
                        }
                    }
                    break;
                case ixarMLS::DNTYPE_MODULE:
                    // Handle in a special way the module type
                    // for which it's necessary to load common translations
                    if (!isset($loadedCommons[$domainName . 'module'])) {
                        $loadedCommons[$domainName . 'module'] = true;
                        if (!$this->backend->loadContext('modules:', 'common')) {
                            return;
                        }
                        if (!$this->backend->loadContext('modules:', 'version')) {
                            return;
                        }
                    }
                    break;
                case ixarMLS::DNTYPE_PROPERTY:
                    // Load common translations
                    if (!isset($loadedCommons[$domainName . 'property'])) {
                        $loadedCommons[$domainName . 'property'] = true;
                        if (!$this->backend->loadContext('properties:', 'common')) {
                            return;
                        }
                    }
                    break;
                case ixarMLS::DNTYPE_BLOCK:
                    // Load common translations
                    if (!isset($loadedCommons[$domainName . 'block'])) {
                        $loadedCommons[$domainName . 'block'] = true;
                        if (!$this->backend->loadContext('blocks:', 'common')) {
                            return;
                        }
                    }
                    break;
                case ixarMLS::DNTYPE_OBJECT:
                    // Load common translations
                    if (!isset($loadedCommons[$domainName . 'object'])) {
                        $loadedCommons[$domainName . 'object'] = true;
                        if (!$this->backend->loadContext('objects:', 'common')) {
                            return;
                        }
                    }
                    break;
            }

            if (!$this->backend->loadContext($contextType, $contextName)) {
                return;
            }
            $loadedTranslations["$domainType.$domainName.$contextType.$contextName"] = true;
            return true;
        } else {
            // FIXME: postpone
            //xarEvents::notify('MLSMissingTranslationDomain', array($domainType, $domainName));

            $loadedTranslations["$domainType.$domainName.$contextType.$contextName"] = false;
            return false;
        }
    }

    /**
     * @deprecated 2.4.1 not used
     */
    protected function convertFromInput($var, $method)
    {
        // FIXME: <marco> Can we trust browsers?
        if ($this->getMode() == ixarMLS::SINGLE_LANGUAGE_MODE
            || !function_exists('mb_http_input')) {
            return $var;
        }
        // CHECKME: check this code
        return $var;
        /**
        // Cookies must contain only US-ASCII characters
        $inputCharset = strtolower(mb_http_input($method));
        $curCharset = $this->getCharsetFromLocale($this->getCurrentLocale());
        if ($inputCharset != $curCharset) {
            $var = mb_convert_encoding($var, $curCharset, $inputCharset);
        }
        return $var;
         */
    }

    /**
     * @deprecated 2.4.1 not used
     */
    protected function convertFromCharset($var, $charset)
    {
        // FIXME: <marco> Can we trust browsers?
        if ($this->getMode() == ixarMLS::SINGLE_LANGUAGE_MODE
            || !function_exists('mb_convert_encoding')) {
            return $var;
        }
        $curCharset = xarLocale::getCharsetFromLocale($this->getCurrentLocale());
        $var = mb_convert_encoding($var, $curCharset, $charset);
        return $var;
    }

    protected function bindVariables($string, $args)
    {
        // FIXME: <marco> Consider to use strtr to do the same, can we?
        $i = 1;
        foreach ($args as $var) {
            $search = "#($i)";
            $string = str_replace($search, $var ?? '', $string);
            $i++;
        }
        return $string;
    }

    /**
     * @deprecated 2.8.6 use xarLocale::getLocaleAlternatives()
     */
    protected function getLocaleAlternatives($locale): array
    {
        return xarLocale::getLocaleAlternatives($locale);
    }

    /**
     * @deprecated 2.8.6 use xarLocale::parseLocaleString()
     */
    protected function parseLocaleString($locale)
    {
        return xarLocale::parseLocaleString($locale);
    }

    /**
     * @deprecated 2.4.1 not used
     */
    protected function getSingleByteCharset($langISO2Code)
    {
        return xarLocale::getSingleByteCharset($langISO2Code);
    }

    /**
     * @deprecated 2.8.6 moved to \PHPBackendGenerator::mkdirr()
     */
    public function mkdirr($path): bool
    {
        return \PHPBackendGenerator::mkdirr($path);
    }

    /**
     * @deprecated 2.8.6 moved to \PHPBackendGenerator::iswritable()
     */
    public function iswritable($directory = null): bool|int
    {
        return \PHPBackendGenerator::iswritable($directory);
    }
}

/**
 * Access xarMLS::* Multi-Language System methods (translate, ...)
 *
 * Available methods:
 * - getCurrentLocale()
 * - getCharsetFromLocale()
 * - loadLocale()
 * - formatDate()
 * - getFormattedDate()
 * - getFormattedTime()
 * - translate()
 * - loadTranslations()
 * - loadModuleTranslations()
 * - loadObjectTranslations()
 * - ...
 *
 */
class MultiLanguageService implements MultiLanguageInterface
{
    use MultiLanguageTrait;
}
