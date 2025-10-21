<?php

/**
 * Make Config Service available via facade (WIP)
 *
 * Classes that don't use ServicesInterface like xarMod(), xarUser() etc.
 * can more easily replace (most common) static xarConfigVars::* method calls if
 * they use \Xaraya\Facades\xarConfig3; instead
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

use Xaraya\Services\ConfigInterface;
use Xaraya\Services\ServiceFactory;
use sys;

sys::import('xaraya.services.config');
sys::import('xaraya.services.servicefactory');

/**
 * Make Config Service available via facade - xarConfig3:: static methods
 * similar to traditional xarConfigVars::* method calls
 * @deprecated 2.8.2 use xar::config()->* instead
 */
class xarConfig3
{
    /** @var ?ConfigInterface */
    protected static $xarConfig = null;         // Access config service with instance methods

    public static function getInstance(): ConfigInterface
    {
        self::$xarConfig ??= ServiceFactory::getConfigService(__METHOD__);
        return self::$xarConfig;
    }

    /**
     * Get config variable
     */
    public static function getVar(string $varName, mixed $value = null): mixed
    {
        return self::getInstance()->getVar($varName, $value);
    }

    /**
     * Set config variable
     */
    public static function setVar(string $varName, mixed $value): bool
    {
        return self::getInstance()->setVar($varName, $value);
    }

    /**
     * Delete config variable
     */
    public static function delVar(string $varName): bool
    {
        return self::getInstance()->delVar($varName);
    }

    /**
     * Cache config variables
     */
    public static function cache(): void
    {
        self::getInstance()->cache();
    }
}
