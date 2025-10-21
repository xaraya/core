<?php

/**
 * Make MultiLanguage Service available via facade (WIP)
 *
 * Classes that don't use ServicesInterface like xarMod(), xarUser() etc.
 * can more easily replace (most common) static xarMLS::* method calls if
 * they use \Xaraya\Facades\xarMLS3; instead
 *
 * @package core\facades
 * @subpackage facades
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Facades;

use Xaraya\Services\MultiLanguageInterface;
use Xaraya\Services\ServiceFactory;
use sys;

sys::import('xaraya.services.multilanguage');
sys::import('xaraya.services.servicefactory');

/**
 * Make MultiLanguage Service available via facade - xarMLS3:: static methods
 * similar to traditional xarMLS::* method calls
 * @deprecated 2.8.2 use xar::mls()->* instead
 */
class xarMLS3
{
    /** @var ?MultiLanguageInterface */
    protected static $xarMLS = null;         // Access multilanguage service with instance methods

    public static function getInstance(): MultiLanguageInterface
    {
        self::$xarMLS ??= ServiceFactory::getMultiLanguageService(__METHOD__);
        return self::$xarMLS;
    }

    /**
     * Get the current locale or empty if not defined in xarUser::init() yet
     */
    public static function getCurrentLocale(): string
    {
        return self::getInstance()->getCurrentLocale();
    }

    /**
     * Translate string with optional arguments
     * @param string $rawstring
     * @param mixed ...$args
     */
    public static function translate($rawstring, ...$args): string
    {
        return self::getInstance()->translate($rawstring, ...$args);
    }

    /**
     * Load translations for a file by path
     */
    public static function loadTranslations(string $path): bool
    {
        return self::getInstance()->loadTranslations($path);
    }

    /**
     * Load translations for a module function or method
     * @param string $modName
     * @param string $modType (incl. $funcType)
     * @param string $funcName
     * @return bool
     */
    public static function loadModuleTranslations(string $modName, string $modType, string $funcName): bool
    {
        return self::getInstance()->loadModuleTranslations($modName, $modType, $funcName);
    }

    /**
     * Load translations for a data object property
     */
    public static function loadObjectTranslations(string $objectName, string $propertyName): bool
    {
        return self::getInstance()->loadObjectTranslations($objectName, $propertyName);
    }
}
