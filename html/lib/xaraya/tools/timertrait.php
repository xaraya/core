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
 *     use TimerTrait;  // activate with $this->enableTimer(true)
 *
 *     public function __construct()
 *     {
 *         $this->enableTimer(true);
 *         // ...
 *         $this->setTimer('contructed');
 *     }
 *
 *     public function getResultWithTimer($what)
 *     {
 *         // ... get result with timer ...
 *         $this->setTimer('start result');
 *         // some lengthy operation(s) in myFancyClass
 *         $result = $this->getResult($what);
 *         $this->setTimer('stop result');
 *
 *         // ... add timer information to result ...
 *         if ($this->enableTimer()) {
 *             $result['timer'] = $this->getTimers();
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

use Xaraya\Services\RequestService;

/**
 * For documentation purposes only - available via TimerTrait
 */
interface TimerInterface
{
    public function enableTimer(?bool $enable = null): bool;
    public function setTimer(string $label): void;
    /** @return list<array<string, float>> */
    public function getTimers(): array;
    /** @param array<mixed> $args */
    public function wrapTimer(string $label, callable $callback, ...$args): mixed;
}

/**
 * Trait to trace time and record steps taken
 */
trait TimerTrait
{
    public bool $enableTimer = false;  // activate with $this->enableTimer(true)
    /** @var list<array<string, float>> */
    protected array $_timerKeep = [];
    protected float $_timerPrev = 0.0;
    protected float $_timerMult = 1000.0;  // in milliseconds
    protected int $_timerPrec = 3;
    protected ?RequestService $_reqService = null;

    protected function _req(): RequestService
    {
        if (!isset($this->_reqService)) {
            // @checkme assume WithServicesClass here
            $xar = $this->getServicesClass();
            $this->_reqService = $xar->req();
        }
        return $this->_reqService;
    }

    /**
     * Get or set enableTimer
     */
    public function enableTimer(?bool $enable = null): bool
    {
        if (isset($enable)) {
            $this->enableTimer = $enable;
        }
        return $this->enableTimer;
    }

    public function setTimer(string $label): void
    {
        if (!$this->enableTimer) {
            return;
        }
        $now = microtime(true);
        if (empty($this->_timerPrev)) {
            $start = $this->_req()->getServerVar('REQUEST_TIME_FLOAT');
            $this->_timerPrev = !empty($start) ? (float) $start : 0.0;
            $this->_timerKeep[] = ['request' => $this->_timerPrev];
        }
        $this->_timerKeep[] = [$label => round(($now - $this->_timerPrev) * $this->_timerMult, $this->_timerPrec)];
        $this->_timerPrev = $now;
    }

    /**
     * Summary of getTimers
     * @return list<array<string, float>>
     */
    public function getTimers(): array
    {
        if (!$this->enableTimer) {
            return [];
        }
        $start = $this->_req()->getServerVar('REQUEST_TIME_FLOAT');
        $this->_timerPrev = !empty($start) ? (float) $start : 0.0;
        $this->setTimer('elapsed');
        return $this->_timerKeep;
    }

    /**
     * Utility method to set timer on callback function
     * @param string $label
     * @param callable $callback
     * @param array<mixed> $args
     * @return mixed
     */
    public function wrapTimer(string $label, callable $callback, ...$args): mixed
    {
        $this->setTimer("start $label");
        $result = call_user_func($callback, ...$args);
        $this->setTimer("stop $label");
        return $result;
    }
}
