<?php

/**
 * Logger available via methods (WIP)
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

use xarLog;

/**
 * For documentation purposes only - available via LoggerTrait
 *
 * Note: this aligns with PSR-3 interface for future compatibility
 * @see \Xaraya\Bridge\Logging\LoggerBridge
 */
interface LoggerInterface extends ServiceInterface
{
    public const SLICE = 'logger';

    public function message(string|\Stringable $message, int $level = 0): void;

    public function variable(string $name, mixed $var, int $level = 0): void;

    /** @param mixed[] $var */
    public function emergency(string|\Stringable $message, array $var = []): void;

    /** @param mixed[] $var */
    public function alert(string|\Stringable $message, array $var = []): void;

    /** @param mixed[] $var */
    public function critical(string|\Stringable $message, array $var = []): void;

    /** @param mixed[] $var */
    public function error(string|\Stringable $message, array $var = []): void;

    /** @param mixed[] $var */
    public function warning(string|\Stringable $message, array $var = []): void;

    /** @param mixed[] $var */
    public function notice(string|\Stringable $message, array $var = []): void;

    /** @param mixed[] $var */
    public function info(string|\Stringable $message, array $var = []): void;

    /** @param mixed[] $var */
    public function debug(string|\Stringable $message, array $var = []): void;

    /** @param mixed[] $var */
    public function log(mixed $level, string|\Stringable $message, array $var = []): void;
}

/**
 * Logger available via methods
 */
trait LoggerTrait
{
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

    /**
     * Initialize service class
     * @param array<string, mixed> $config
     * @uses \xarLog::init()
     */
    public function init(array $config = []): bool
    {
        return xarLog::init($config);
    }

    /**
     * Get configuration
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        // @todo do something with xarLog::configFile() here?
        return [];
    }

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

    /** @param mixed[] $var */
    public function emergency(string|\Stringable $message, array $var = []): void
    {
        $this->log(xarLog::LEVEL_EMERGENCY, $message, $var);
    }

    /** @param mixed[] $var */
    public function alert(string|\Stringable $message, array $var = []): void
    {
        $this->log(xarLog::LEVEL_ALERT, $message, $var);
    }

    /** @param mixed[] $var */
    public function critical(string|\Stringable $message, array $var = []): void
    {
        $this->log(xarLog::LEVEL_CRITICAL, $message, $var);
    }

    /** @param mixed[] $var */
    public function error(string|\Stringable $message, array $var = []): void
    {
        $this->log(xarLog::LEVEL_ERROR, $message, $var);
    }

    /** @param mixed[] $var */
    public function warning(string|\Stringable $message, array $var = []): void
    {
        $this->log(xarLog::LEVEL_WARNING, $message, $var);
    }

    /** @param mixed[] $var */
    public function notice(string|\Stringable $message, array $var = []): void
    {
        $this->log(xarLog::LEVEL_NOTICE, $message, $var);
    }

    /** @param mixed[] $var */
    public function info(string|\Stringable $message, array $var = []): void
    {
        $this->log(xarLog::LEVEL_INFO, $message, $var);
    }

    /** @param mixed[] $var */
    public function debug(string|\Stringable $message, array $var = []): void
    {
        $this->log(xarLog::LEVEL_DEBUG, $message, $var);
    }

    /** @param mixed[] $var */
    public function log(mixed $level, string|\Stringable $message, array $var = []): void
    {
        if (!is_numeric($level)) {
            $level = $this->mapping[$level] ?? xarLog::LEVEL_ERROR;
        } else {
            $level = (int) $level;
        }
        if (!empty($var)) {
            xarLog::variable($message, $var, $level);
            return;
        }
        xarLog::message($message, $level);
    }
}

/**
 * Access xarLog::* Logger methods (message, variable, ...)
 *
 * Available methods:
 * - emergency($message, $var = [])
 * - alert($message, $var = [])
 * - critical($message, $var = [])
 * - error($message, $var = [])
 * - warning($message, $var = [])
 * - notice($message, $var = [])
 * - info($message, $var = [])
 * - debug($message, $var = [])
 * - log($message, $var = [])
 * - message($message, $level = xarLog::LEVEL_DEBUG) - original xarLog::message() using $level param
 * - variable($message, $var, $level = xarLog::LEVEL_DEBUG) - original xarLog::variable() using $level param
 *
 */
class LoggerService implements LoggerInterface
{
    use LoggerTrait;
}
