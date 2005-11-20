<?php

/**
 * File: $Id:
 * 
 * Function fnmatch
 * 
 * @package PHP Version Compatibility Library
 * @copyright (C) 2005 by the Xaraya Development Team/2004 The PHP group
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Jason Judge
 */

/**
 * Mimics the fnmatch() function introduced in PHP 4.3.0
 * 
 * @link http://www.php.net/manual/en/function.fnmatch.php
 */

function _fnmatch($pattern, $file)
{
    $re = preg_replace(array('/([^?*\[\]])/', '/\?/', '/\*+/'), array('\\\\$1', '.', '.*'), $pattern);
    if (substr($re, 0, 2) != '.*') {$re = '^' . $re;}
    if (substr($re, -2, 2) != '.*') {$re .= '$';}

    return (ereg($re, $file) ? true : false);
}

?>