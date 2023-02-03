<?php
/**
 * PSR-3 LoggerInterface compatible bridge to xarLog::message() for Symfony & other packages
 *
 * Note: this is for external packages to log messages to the standard Xaraya xarLog loggers.
 * If you want to work the other way around and report xarLog messages to a PSR-3 logger,
 * please extend one of the xarLogger classes in lib/xaraya/log/loggers
 *
 * sys::import('xaraya.bridge.logging');
 * use Xaraya\Bridge\Logging\LoggerBridge;
 *
 * $logger = new LoggerBridge();
 * // some package class expecting a logger compatible with LoggerInterface
 * $mypackage->setLogger($logger);
 */

namespace Xaraya\Bridge\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use xarLog;

class LoggerBridge extends AbstractLogger implements LoggerInterface
{
    private $mapping = [
        LogLevel::EMERGENCY => xarLog::LEVEL_EMERGENCY,  // 'emergency'
        LogLevel::ALERT     => xarLog::LEVEL_ALERT,      // 'alert'
        LogLevel::CRITICAL  => xarLog::LEVEL_CRITICAL,   // 'critical'
        LogLevel::ERROR     => xarLog::LEVEL_ERROR,      // 'error'
        LogLevel::WARNING   => xarLog::LEVEL_WARNING,    // 'warning'
        LogLevel::NOTICE    => xarLog::LEVEL_NOTICE,     // 'notice'
        LogLevel::INFO      => xarLog::LEVEL_INFO,       // 'info'
        LogLevel::DEBUG     => xarLog::LEVEL_DEBUG,      // 'debug'
    ];

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string|\Stringable $message
     * @param array $context
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        xarLog::message($this->interpolate($message, $context), $this->mapping[$level] ?? xarLog::LEVEL_INFO);
    }

    /**
     * Interpolates context values into the message placeholders.
     * Source: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
     */
    public function interpolate($message, array $context = [])
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            // check that the value can be cast to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}
