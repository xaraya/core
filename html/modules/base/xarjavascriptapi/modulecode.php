<?php

/**
 * File: $Id$
 *
 * Base JavaScript management functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @author Jason Judge
 * @todo none
 */


/**
 * Include a module JavaScript link in a page.
 *
 * @author Jason Judge
 * @param $args['module'] module name; or
 * @param $args['moduleid'] module ID
 * @param $args['filename'] file name
 * @param $args['position'] position on the page; generally 'head' or 'body'
 * @returns true=success; null=fail
 * @return boolean
 */
function base_javascriptapi_modulecode($args)
{
    extract($args);

    // Default the position to the head.
    if (empty($position)) {
        $position = 'head';
    }

    $filePath = xarModAPIfunc('base', 'javascript', '_findfile', &$args);

    if (empty($filePath)) {
        return;
    }

    // Read the file.
    $fp = fopen($filePath, 'rb');

    if (! $fp) {
        return;
    }

    $code = fread($fp, filesize($filePath));
    fclose($fp);

    return xarTplAddJavaScript($position, 'code', $code, $filePath);
}

?>
