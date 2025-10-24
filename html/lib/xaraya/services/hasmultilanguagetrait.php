<?php

/**
 * Make MultiLanguage Service available via trait (WIP)
 *
 * Classes that don't use ServicesInterface like Query(), cache storage etc.
 * can more easily replace (most common) static xarMLS::* method calls if
 * they use \Xaraya\Services\HasMultiLanguageTrait; instead
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use sys;

sys::import('xaraya.services.multilanguage');
sys::import('xaraya.services.servicefactory');

/**
 * Make MultiLanguage Service available via trait - $this->mls() instance method
 * aligned with method in Core Services Interface
 * @deprecated 2.8.3 use xar::mls() or xar::ml() instead
 */
trait HasMultiLanguageTrait
{
    /** @var ?MultiLanguageInterface */
    protected $xarMLS = null;         // Access multilanguage service with instance methods

    /**
     * Access multilanguage service
     */
    protected function mls(): MultiLanguageInterface
    {
        $this->xarMLS ??= xar::mls();
        return $this->xarMLS;
    }

    /**
     * Translate string with optional arguments
     * = short-hand version for $this->mls()->translate()
     * @param string $rawstring
     * @param mixed ...$args
     */
    public function ml($rawstring, ...$args): string
    {
        return $this->mls()->translate($rawstring, ...$args);
    }
}
