<?php
/**
 * Trait to trace time
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
**/
trait xarTimerTrait
{
    public static $enableTimer = false;
    protected static $_timerKeep = array();
    protected static $_timerPrev = 0.0;
    protected static $_timerMult = 1000.0;  // in milliseconds
    protected static $_timerPrec = 3;

    public static function setTimer($label)
    {
        if (static::$enableTimer) {
            $now = microtime(true);
            if (empty(static::$_timerPrev)) {
                static::$_timerPrev = !empty($_SERVER['REQUEST_TIME_FLOAT']) ? (float) $_SERVER['REQUEST_TIME_FLOAT'] : 0.0;
                static::$_timerKeep[] = array('request' => static::$_timerPrev);
            }
            static::$_timerKeep[] = array($label => round(($now - static::$_timerPrev) * self::$_timerMult, self::$_timerPrec));
            static::$_timerPrev = $now;
        }
    }

    public static function getTimers()
    {
        static::$_timerPrev = !empty($_SERVER['REQUEST_TIME_FLOAT']) ? (float) $_SERVER['REQUEST_TIME_FLOAT'] : 0.0;
        static::setTimer('elapsed');
        return static::$_timerKeep;
    }
}
