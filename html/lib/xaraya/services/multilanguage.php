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
}

/**
 * Access xarMLS::* Multi-Language System methods (translate, ...)
 *
 * Available methods:
 * - translate()
 * - ...
 *
 */
class MultiLanguageService implements MultiLanguageInterface
{
    use MultiLanguageTrait;
}
