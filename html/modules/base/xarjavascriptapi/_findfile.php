<?php

/**
 * File: $Id$
 *
 * Base JavaScript management functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @author Jason Judge
 * @todo none
 */


/**
 * Find the path for a JavaScript file.
 *
 * @author Jason Judge
 * @param $args['module'] module name; or
 * @param $args['moduleid'] module ID
 * @param $args['filename'] file name
 * @returns the virtual pathname for the JS file; an empty value if not found
 * @return sring
 */
function base_javascriptapi__findfile($args)
{
    extract($args);

    // Use the current module if none supplied.
    if (empty($module)) {
        list($module) = xarRequestGetInfo();
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
    $searchPath[] = $themedir . '/modules/' . $modOsDir . '/includes/' . $filename;
    $searchPath[] = $themedir . '/modules/' . $modOsDir . '/xarincludes/' . $filename;
    $searchPath[] = 'modules/' . $modOsDir . '/xartemplates/includes/' . $filename;

    foreach($searchPath as $filePath) {
        if (file_exists($filePath)) {break;}
        $filePath = '';
    }
    if (empty($filePath)) {
        return;
    }

    return $filePath;
}

?>
