<?php
/**
 * Logging Facilities (legacy)
 *
 * @package core\logging\legacy
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

// Legacy calls

/**
 * Legacy call
 * @uses xarLog::configFile()
 * @deprecated
 */
function xarLogConfigFile()
{   
    return xarLog::configFile(); 
}

/**
 * Legacy call
 * @uses xarLog::configReadable()
 * @deprecated
 */
function xarLogConfigReadable()
{   
    return xarLog::configReadable(); 
}

/**
 * Legacy call
 * @uses xarLog::fallbackFile()
 * @deprecated
 */
function xarLogFallbackFile()
{   
    return xarLog::fallbackFile(); 
}

/**
 * Legacy call
 * @uses xarLog::fallbackPossible()
 * @deprecated
 */
function xarLogFallbackPossible()
{   
    return xarLog::fallbackPossible(); 
}

/**
 * Legacy call
 * @uses xarLog::message()
 * @deprecated
 */
function xarLogMessage($message, $level = '')
{   
    if (empty($level)) $level = xarLog::LEVEL_DEBUG;
    return xarLog::message($message, $level); 
}

/**
 * Legacy call
 * @uses xarLog::variable()
 * @deprecated
 */
function xarLogVariable($name, $var, $level = '')
{   
    if (empty($level)) $level = xarLog::LEVEL_DEBUG;
    return xarLog::variable($name, $var, $level); 
}
