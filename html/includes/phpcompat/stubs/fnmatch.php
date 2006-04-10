<?php
/**
 * File: $Id:
 * 
 * Stub file_get_contents
 * 
 * @package PHP Version Compatibility Library
 * @copyright (C) 2005 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Jason Judge
 */

/**
 * Stub for the fnmatch() function
 * 
 * @see _fnmatch()
 */

function fnmatch($pattern, $string)
{
    require_once dirname(__FILE__) . '/functions/_fnmatch.php';
    return _fnmatch($pattern, $string);
}

?>