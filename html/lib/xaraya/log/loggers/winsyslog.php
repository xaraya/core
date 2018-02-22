<?php
/**
 * Class to handle winsys logggin
 *
 * @package core\logging
 * @subpackage logging
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
/**
 * Windows system log
 *
 * 
*/

/**
 * Make sure the base class is available
 *
 */
sys::import('xaraya.log.loggers.xarLogger');

/**
 */
class xarLogger_winsyslog extends xarLogger_syslog 
{
    /**
     * Converts a xarLog::LEVEL* constant into a syslog LOG_* constant.
     *
     * This function exists because, under Windows, not all of the LOG_*
     * constants have unique values.  Instead, the xarLog::LEVEL_* were introduced
     * for global use, with the conversion to the LOG_* constants kept local to
     * to the syslog driver.
     *
     * @param int $level     xarLog::LEVEL_* value to convert to LOG_* value.
     *
     * @return  The LOG_* representation of $priority.
     *
     * 
     */
    function _toSyslog($level)
    {
        static $levels = array(
            xarLog::LEVEL_EMERGENCY => 1, //ERROR
            xarLog::LEVEL_ALERT     => 1, //ERROR
            xarLog::LEVEL_CRITICAL  => 1, //ERROR
            xarLog::LEVEL_ERROR     => 1, //ERROR
            xarLog::LEVEL_WARNING   => 1, //ERROR
            xarLog::LEVEL_NOTICE    => 6, //INFO
            xarLog::LEVEL_INFO      => 6, //INFO
            xarLog::LEVEL_DEBUG     => 6  //INFO
        );

        return $levels[$level];
    }
}
?>