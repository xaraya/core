<?php

/**
 * Trait to trace time and record steps taken
 *
 * Usage:
 * ```
 * use Xaraya\Tools\TimerInterface;
 * use Xaraya\Tools\TimerTrait;
 *
 * class myFancyClass implements TimerInterface
 * {
 *     use TimerTrait;  // activate with self::enableTimer(true)
 *
 *     public function __construct()
 *     {
 *         self::enableTimer(true);
 *         // ...
 *         self::setTimer('contructed');
 *     }
 *
 *     public function getResultWithTimer($what)
 *     {
 *         // ... get result with timer ...
 *         self::setTimer('start result');
 *         // some lengthy operation(s) in myFancyClass
 *         $result = $this->getResult($what);
 *         self::setTimer('stop result');
 *
 *         // ... add timer information to result ...
 *         if (self::enableTimer()) {
 *             $result['timer'] = self::getTimers();
 *         }
 *         return $result;
 *     }
 * }
 * ```
 * @package core\tools
 * @subpackage tools
 * @category Xaraya Web Applications Framework
 * @version 2.5.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Tools;

use Xaraya\Services\xar;

/**
 * For documentation purposes only - available via TimerTrait
 */
interface TimerInterface
{
    public static function enableTimer(?bool $enable = null): bool;
    public static function setTimer(string $label): void;
    /** @return list<array<string, float>> */
    public static function getTimers(): array;
    /** @param array<mixed> $args */
    public static function wrapTimer(string $label, callable $callback, ...$args): mixed;
}

/**
 * Trait to trace time and record steps taken
 */
trait TimerTrait
{
    public static bool $enableTimer = false;  // activate with self::enableTimer(true)
    /** @var list<array<string, float>> */
    protected static array $_timerKeep = [];
    protected static float $_timerPrev = 0.0;
    protected static float $_timerMult = 1000.0;  // in milliseconds
    protected static int $_timerPrec = 3;

    /**
     * Get or set enableTimer
     */
    public static function enableTimer(?bool $enable = null): bool
    {
        if (isset($enable)) {
            static::$enableTimer = $enable;
        }
        return static::$enableTimer;
    }

    public static function setTimer(string $label): void
    {
        if (!static::$enableTimer) {
            return;
        }
        $now = microtime(true);
        if (empty(static::$_timerPrev)) {
            $start = xar::req()->getServerVar('REQUEST_TIME_FLOAT');
            static::$_timerPrev = !empty($start) ? (float) $start : 0.0;
            static::$_timerKeep[] = ['request' => static::$_timerPrev];
        }
        static::$_timerKeep[] = [$label => round(($now - static::$_timerPrev) * self::$_timerMult, self::$_timerPrec)];
        static::$_timerPrev = $now;
    }

    /**
     * Summary of getTimers
     * @return list<array<string, float>>
     */
    public static function getTimers(): array
    {
        if (!static::$enableTimer) {
            return [];
        }
        $start = xar::req()->getServerVar('REQUEST_TIME_FLOAT');
        static::$_timerPrev = !empty($start) ? (float) $start : 0.0;
        static::setTimer('elapsed');
        return static::$_timerKeep;
    }

    /**
     * Utility method to set timer on callback function
     * @param string $label
     * @param callable $callback
     * @param array<mixed> $args
     * @return mixed
     */
    public static function wrapTimer(string $label, callable $callback, ...$args): mixed
    {
        static::setTimer("start $label");
        $result = call_user_func($callback, ...$args);
        static::setTimer("stop $label");
        return $result;
    }
}
