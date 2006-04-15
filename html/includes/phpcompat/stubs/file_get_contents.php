<?php
/**
 * Stub file_get_contents
 *
 * @package PHP Version Compatibility Library
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Paul Crovella
 */
/**
 * Stub for the file_get_contents() function
 *
 * @see _file_get_contents()
 */

function file_get_contents($filename, $use_include_path = false, $resource_context = null)
{
    require_once dirname(__FILE__) . '/functions/_file_get_contents.php';
    return _file_get_contents($filename, $use_include_path, $resource_context);
}

?>