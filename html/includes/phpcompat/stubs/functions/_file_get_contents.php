<?php
/**
 * File: $Id:
 * 
 * Function file_get_contents
 * 
 * @package PHP Version Compatibility Library
 * @copyright (C) 2004 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Paul Crovella
 */

/**
 * Mimics the file_get_contents() function introduced in PHP 4.3.0
 * 
 * @link http://www.php.net/manual/function.file-get-contents.php
 * @internal $resource_context not supported
 */

function _file_get_contents($filename, $use_include_path = false, $resource_context = null)
{
    $file = @fopen($filename, "rb", $use_include_path);

    if ($file === false) {
        trigger_error("file_get_contents($filename) failed to open stream: No such file or directory or Permission denied", E_USER_WARNING);
        return false;
    }

    $contents = '';
    while (!feof($file)) {
        $contents .= fread($file, 4096);
    }

    fclose($file);

    return $contents;
}

?>