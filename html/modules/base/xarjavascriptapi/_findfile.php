<?php
/**
 * Base JavaScript management functions
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Base JavaScript management functions
 * Find the path for a JavaScript file.
 *
 * @author Jason Judge
 * @param $args['module'] module name; or
 * @param $args['moduleid'] module ID (deprecated)
 * @param $args['modid'] module ID
 * @param $args['filename'] file name
 * @returns the virtual pathname for the JS file; an empty value if not found
 * @return sring
 */
function base_javascriptapi__findfile($args)
{
    extract($args);

    // File must be supplied and may include a path.
    if (empty($filename) || $filename != strval($filename)) {
        return;
    }

    // Use the current module if none supplied.
    if (empty($module) && empty($modid)) {
        list($module) = xarRequestGetInfo();
    }

    // Get the module ID from the module name.
    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }

    // Get details for the module if we have a valid module id.
    if (!empty($modid)) {
        $modInfo = xarModGetInfo($modid);

        // Get module directory if we have a valid module.
        if (!empty($modInfo)) {
            $modOsDir = $modInfo['osdirectory'];
        }
    }

    // Theme base directory.
    $themedir = xarTplGetThemeDir();

    // Initialise the search path.
    $searchPath = array();

    // The search path for the JavaScript file.
    $searchPath[] = $themedir . '/scripts/' . $filename;
    if (isset($modOsDir)) {
        $searchPath[] = $themedir . '/modules/' . $modOsDir . '/includes/' . $filename;
        $searchPath[] = $themedir . '/modules/' . $modOsDir . '/xarincludes/' . $filename;
        $searchPath[] = 'modules/' . $modOsDir . '/xartemplates/includes/' . $filename;
    }

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