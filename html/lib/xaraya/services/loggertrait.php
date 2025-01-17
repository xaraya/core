<?php

/**
 * Logger available via methods (WIP)
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

use xarLog;
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via LoggerTrait
 *
 * Note: this aligns with PSR-3 interface for future compatibility
 * @see \Xaraya\Bridge\Logging\LoggerBridge
 */
interface LoggerInterface extends ServiceInterface
{
    public function message(string|\Stringable $message, int $level = 0): void;

    public function variable(string $name, mixed $var, int $level = 0): void;

    /** @param mixed[] $context */
    public function emergency(string|\Stringable $message, array $context = []): void;

    /** @param mixed[] $context */
    public function alert(string|\Stringable $message, array $context = []): void;

    /** @param mixed[] $context */
    public function critical(string|\Stringable $message, array $context = []): void;

    /** @param mixed[] $context */
    public function error(string|\Stringable $message, array $context = []): void;

    /** @param mixed[] $context */
    public function warning(string|\Stringable $message, array $context = []): void;

    /** @param mixed[] $context */
    public function notice(string|\Stringable $message, array $context = []): void;

    /** @param mixed[] $context */
    public function info(string|\Stringable $message, array $context = []): void;

    /** @param mixed[] $context */
    public function debug(string|\Stringable $message, array $context = []): void;

    /** @param mixed[] $context */
    public function log(mixed $level, string|\Stringable $message, array $context = []): void;
}

/**
 * Logger available via methods
 * @template TParent of ServicesInterface
 */
trait LoggerTrait
{
    /** @use ServiceTrait<TParent> */
    use ServiceTrait;

    /** @var array<string, int> */
    protected array $mapping = [
        'emergency' => xarLog::LEVEL_EMERGENCY,
        'alert' => xarLog::LEVEL_ALERT,
        'critical' => xarLog::LEVEL_CRITICAL,
        'error' => xarLog::LEVEL_ERROR,
        'warning' => xarLog::LEVEL_WARNING,
        'notice' => xarLog::LEVEL_NOTICE,
        'info' => xarLog::LEVEL_INFO,
        'debug' => xarLog::LEVEL_DEBUG,
    ];

    public function message(string|\Stringable $message, int $level = 0): void
    {
        if (empty($level)) {
            $level = xarLog::LEVEL_DEBUG;
        }
        xarLog::message($message, $level);
    }

    public function variable(string $name, mixed $var, int $level = 0): void
    {
        if (empty($level)) {
            $level = xarLog::LEVEL_DEBUG;
        }
        xarLog::variable($name, $var, $level);
    }

    /** @param mixed[] $context */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        xarLog::message($message, xarLog::LEVEL_EMERGENCY);
    }

    /** @param mixed[] $context */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        xarLog::message($message, xarLog::LEVEL_ALERT);
    }

    /** @param mixed[] $context */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        xarLog::message($message, xarLog::LEVEL_CRITICAL);
    }

    /** @param mixed[] $context */
    public function error(string|\Stringable $message, array $context = []): void
    {
        xarLog::message($message, xarLog::LEVEL_ERROR);
    }

    /** @param mixed[] $context */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        xarLog::message($message, xarLog::LEVEL_WARNING);
    }

    /** @param mixed[] $context */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        xarLog::message($message, xarLog::LEVEL_NOTICE);
    }

    /** @param mixed[] $context */
    public function info(string|\Stringable $message, array $context = []): void
    {
        xarLog::message($message, xarLog::LEVEL_INFO);
    }

    /** @param mixed[] $context */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        xarLog::message($message, xarLog::LEVEL_DEBUG);
    }

    /** @param mixed[] $context */
    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        if (!is_numeric($level)) {
            $level = $this->mapping[$level] ?? xarLog::LEVEL_ERROR;
        } else {
            $level = (int) $level;
        }
        xarLog::message($message, $level);
    }
}

/**
 * Access xarLog::* Logger methods (message, variable, ...)
 *
 * Available methods:
 * - message()
 * - variable()
 * - emergency()
 * - alert()
 * - critical()
 * - error()
 * - warning()
 * - notice()
 * - info()
 * - debug()
 * - log()
 *
 * @template TParent of ServicesInterface
 */
class LoggerService implements LoggerInterface
{
    /** @use LoggerTrait<TParent> */
    use LoggerTrait;
}
