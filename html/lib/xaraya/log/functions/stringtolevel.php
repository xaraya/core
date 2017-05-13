<?php
/**
 * @package core\logging
 * @subpackage logging
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
/**
 * Returns the defined integer representation of a string from the configuration.
 *
 * @param string $string   One of the priority level strings.
 * @return constant        The constant representing the $level string.
 */
function xarLog__stringToLevel($string)
{
    static $strings = array (
        'EMERGENCY' => xarLog::LEVEL_EMERGENCY,
        'ALERT'     => xarLog::LEVEL_ALERT,
        'CRITICAL'  => xarLog::LEVEL_CRITICAL,
        'ERROR'     => xarLog::LEVEL_ERROR,
        'WARNING'   => xarLog::LEVEL_WARNING,
        'NOTICE'    => xarLog::LEVEL_NOTICE,
        'INFO'      => xarLog::LEVEL_INFO,
        'DEBUG'     => xarLog::LEVEL_DEBUG
    );

    return $strings[$string];
}
?>