<?php

/**
 * MultiLanguage available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarMLS;
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via MultiLanguageTrait
 */
interface MultiLanguageInterface extends ServiceInterface
{
    /**
     * Translate string with optional arguments
     * @param string $rawstring
     * @param mixed ...$args
     */
    public function translate($rawstring, ...$args): string;

    /**
     * Load translations for a file by path
     * @param string $path
     * @return bool
     */
    public function loadTranslations(string $path): bool;

    /**
     * Load translations for a data object property
     * @param string $objectName
     * @param string $propertyName
     * @return bool
     */
    public function loadObjectTranslations(string $objectName, string $propertyName): bool;
}

/**
 * MultiLanguage available via methods
 */
trait MultiLanguageTrait
{
    use ServiceTrait;

    /**
     * Translate string with optional arguments
     * @uses xarMLS::translate()
     * @param string $rawstring
     * @param mixed ...$args
     */
    public function translate($rawstring, ...$args): string
    {
        return xarMLS::translate($rawstring, ...$args);
    }

    /**
     * Load translations for a file by path
     * @uses xarMLS::loadTranslations()
     * @param string $path
     * @return bool
     */
    public function loadTranslations(string $path): bool
    {
        return xarMLS::loadTranslations($path);
    }

    /**
     * Load translations for a data object property
     * @param string $objectName
     * @param string $propertyName
     * @return bool
     */
    public function loadObjectTranslations(string $objectName, string $propertyName): bool
    {
        return xarMLS::_loadTranslations(xarMLS::DNTYPE_OBJECT, 'object', 'objects:' . $objectName, $propertyName);
    }
}

/**
 * Access xarMLS::* Multi-Language System methods (translate, ...)
 *
 * Available methods:
 * - translate()
 * - loadTranslations()
 * - loadObjectTranslations()
 * - ...
 *
 */
class MultiLanguageService implements MultiLanguageInterface
{
    use MultiLanguageTrait;
}
