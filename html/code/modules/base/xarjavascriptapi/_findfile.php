<?php
/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
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
 * @return string|void the virtual pathname for the JS file; an empty value if not found
 * @checkme: the default module should be the current *template* module, not the *request* module?
 */
function base_javascriptapi__findfile(Array $args=array())
{
    extract($args);

    // File must be supplied and may include a path.
    if (empty($filename) || $filename != strval($filename)) {
        return;
    }

    // Bug 5910: If the path has GET parameters, then move them aside for now.
    if (strpos($filename, '?') > 0) {
        list($filename, $params) = explode('?', $filename, 2);
        $params = '?' . $params;
    } else {
        $params = '';
    }

    // Use the current module if none supplied.
    if (empty($module) && empty($modid)) {
        list($module) = xarController::$request->getInfo();
    }

    // Get the module ID from the module name.
    if (empty($modid) && !empty($module)) {
        $modid = xarMod::getRegID($module);
    }

    // Get details for the module if we have a valid module id.
    if (!empty($modid)) {
        $modInfo = xarMod::getInfo($modid);

        // Get module directory if we have a valid module.
        if (!empty($modInfo)) {
            $modOsDir = $modInfo['osdirectory'];
        }
    }

    // Theme base directory.
    $themedir = xarTpl::getThemeDir();

    // Initialise the search path.
    $searchPath = array();

    // The search path for the JavaScript file.
    $searchPath[] = $themedir . '/scripts/' . $filename;
    
    // A property attribute in the tag overrides a module attribute
    if (!empty($property)) {
        $searchPath[] = $themedir . '/properties/' . $property . '/scripts/' . $filename;
        $searchPath[] = $themedir . '/properties/' . $property . '/xartemplates/includes/' . $filename;
        $searchPath[] = sys::code() . 'properties/' . $property . '/scripts/' . $filename;
        $searchPath[] = sys::code() . 'properties/' . $property . '/xartemplates/includes/' . $filename;
    } else {
        if (isset($modOsDir)) {
            $searchPath[] = $themedir . '/modules/' . $modOsDir . '/includes/' . $filename;
            $searchPath[] = $themedir . '/modules/' . $modOsDir . '/xarincludes/' . $filename;
            $searchPath[] = sys::code() . 'modules/' . $modOsDir . '/xartemplates/includes/' . $filename;
        }
    }

    foreach($searchPath as $filePath) {
        if (file_exists($filePath)) {break;}
        $filePath = '';
    }

    if (empty($filePath)) {
        return;
    }

    return $filePath . $params;
}

?>
