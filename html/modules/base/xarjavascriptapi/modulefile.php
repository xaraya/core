<?php

/**
 * File: $Id$
 *
 * Base User version management functions
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
function base_javascriptapi_modulefile($args)
{
    extract($args);

    // Use the current module if none supplied.
    if (empty($module)) {
        list($module) = xarRequestGetInfo();
    }

    // Default the position to the head.
    if (empty($position)) {
        $position = 'head';
    }

    // File must not be a path.
    if (empty($filename) || $filename != basename($filename)) {
        return;
    }

    // Get module directory.
    if (empty($moduleid) && !empty($module)) {
        $moduleid = xarModGetIDFromName($module);
    }

    $modInfo = xarModGetInfo($moduleid);
    if (!isset($modInfo)) {
        return;
    }
    $modOsDir = $modInfo['osdirectory'];

    // Theme base directory.
    $themedir = xarTplGetThemeDir();

    // Initialise the search path.
    $searchPath = array();

    // The search path for the JavaScript file.
    $searchPath[] = $themedir . '/modules/' . $modOsDir . '/javascript/' . $filename;
    $searchPath[] = $themedir . '/modules/' . $modOsDir . '/xarjavascript/' . $filename;
    $searchPath[] = 'modules/' . $modOsDir . '/xarjavascript/' . $filename;

    foreach($searchPath as $filePath) {
        // TODO: do we need to convert the path for non-*nix operating systems?
        if (file_exists($filePath)) {break;}
        $filePath = '';
    }
    if (empty($filePath)) {
        return;
    }

    return xarTplAddJavaScript($position, 'src', $filePath, $filePath);
}

?>
