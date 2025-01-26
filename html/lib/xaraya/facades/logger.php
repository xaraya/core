<?php

/**
 * Make Logger Service available via facade (WIP)
 *
 * Classes that don't use ServicesInterface like xarMod(), xarUser() etc.
 * can more easily replace (most common) static xarLog::* method calls if
 * they use \Xaraya\Facades\xarLog3; instead
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

use Xaraya\Services\LoggerInterface;
use Xaraya\Services\ServiceFactory;
use sys;

sys::import('xaraya.services.logger');
sys::import('xaraya.services.servicefactory');

/**
 * Make Logger Service available via facade - xarLog3:: static methods
 * similar to traditional xarLog::* method calls
 */
class xarLog3
{
    /** @var ?LoggerInterface */
    protected static $xarLog = null;         // Access logger service with instance methods

    public static function getInstance()
    {
        self::$xarLog ??= ServiceFactory::getLoggerService(__METHOD__);
        return self::$xarLog;
    }

    public static function message(string|\Stringable $message, int $level = 0): void
    {
        self::getInstance()->message($message, $level);
    }

    public static function variable(string $name, mixed $var, int $level = 0): void
    {
        self::getInstance()->variable($name, $var, $level);
    }

    /** @param mixed[] $var */
    public static function emergency(string|\Stringable $message, array $var = []): void
    {
        self::getInstance()->emergency($message, $var);
    }

    /** @param mixed[] $var */
    public static function alert(string|\Stringable $message, array $var = []): void
    {
        self::getInstance()->alert($message, $var);
    }

    /** @param mixed[] $var */
    public static function critical(string|\Stringable $message, array $var = []): void
    {
        self::getInstance()->critical($message, $var);
    }

    /** @param mixed[] $var */
    public static function error(string|\Stringable $message, array $var = []): void
    {
        self::getInstance()->error($message, $var);
    }

    /** @param mixed[] $var */
    public static function warning(string|\Stringable $message, array $var = []): void
    {
        self::getInstance()->warning($message, $var);
    }

    /** @param mixed[] $var */
    public static function notice(string|\Stringable $message, array $var = []): void
    {
        self::getInstance()->notice($message, $var);
    }

    /** @param mixed[] $var */
    public static function info(string|\Stringable $message, array $var = []): void
    {
        self::getInstance()->info($message, $var);
    }

    /** @param mixed[] $var */
    public static function debug(string|\Stringable $message, array $var = []): void
    {
        self::getInstance()->debug($message, $var);
    }

    /** @param mixed[] $var */
    public static function log(mixed $level, string|\Stringable $message, array $var = []): void
    {
        self::getInstance()->log($level, $message, $var);
    }
}
