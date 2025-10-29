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
 *  Helper function for variable logging
 * @param array<string, mixed> $array
 * @deprecated 2.8.4 use xarLog::dumpVariable() instead
 */
function xarLog__dumpVariable(array $array)
{
    return xarLog::dumpVariable($array);
}
