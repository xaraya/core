<?php

/**
 * @package core\logging
 * @subpackage logging
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

/**
 * Returns the defined integer representation of a string from the configuration.
 *
 * @param string $string   One of the priority level strings.
 * @return int             The constant representing the $level string.
 * @deprecated 2.8.4 use xarLogger::stringToLevel() instead
 */
function xarLog__stringToLevel($string)
{
    return xarLogger::stringToLevel($string);
}
