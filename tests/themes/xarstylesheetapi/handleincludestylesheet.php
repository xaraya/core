<?php
/**
 * File: $Id$
 *
 * Handle <xar:themes-include-stylesheet ..> form field tags
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage themes
 * @author Marty Vance
 * @todo none
 */

/**
 * Handle <xar:themes-include-stylesheet ...> form field tags
 * Format : <xar:themes-include-stylesheet file="filename.css" module="modulename" type="mimetype" />
 * Default module is the module in which the tag is called; filename is mandatory.
 * Default type is "text/css".
 * If no file is found among the search paths, the entry will not be created.
 *
 * Example:
 * The following tag is included in an 'articles' template. The file 'myfile.js'
 * can be located in either themes/<current>/modules/articles/includes or
 * modules/articles/xartemplates/includes:
 *
 *    <xar:themes-include-stylesheet filename="myfile.css"/>
 *
 * @author Marty Vance
 * @param $args array containing the form field definition
 * @param $args['module'] string module name
 * @param $args['type'] string mime type of stylesheet, default 'text/css'
 * @param $args['filename'] string filename of stylesheet, including extension
 * @returns string
 * @return empty string
 */ 
function themes_stylesheetapi_handleincludestylesheet($args)
{
    extract($args);

    // Set some defaults - only attribute 'filename' is mandatory.
    if (empty($module)) {
        // No module name is supplied, default the module from the
        // current template module (not the current executing module).
        $module = '$_bl_module_name';
    }
    if (empty($type)) {
        // No mime type is supplied, default to 'text/css'
        $type = 'text/css';
    } else {
        // The module name is supplied.
        $type = addslashes($type);
    }
    if (!empty($file)) {
        $file = addslashes($file);
    }
    else{
        xarLogMessage('Include css failed: no file');
        return '';
    }

    // Get module directory.
    if (!empty($module)) {
        $moduleid = xarModGetIDFromName($module);
    }
    $modInfo = xarModGetInfo($moduleid);
    if (!isset($modInfo)) {
        xarLogMessage('Include css failed: invalid module');
        return '';
    }

    $modOsDir = $modInfo['osdirectory'];

    // Theme base directory.
    $themedir = xarTplGetThemeDir();

    // Initialise the search path.
    $searchPath = array();

    // The search path for the StyleSheet file.
    $themePath = $themedir . '/modules/' . $modOsDir . '/style/' . $file;
    $modulePath = 'modules/' . $modOsDir . '/xarstyles/' . $file;

    if (file_exists($themePath)){
        xarLogMessage("Including CSS from theme: $themedir");
        $filePath = $themePath;
    }
    else if (file_exists($modulePath)){
        xarLogMessage("Including CSS from module: $modOsDir");
        $filePath = $modulePath;
    }
    else {
        xarLogMessage("Including CSS from: FILE NOT FOUND");
        return '';
    }
    xarLogMessage("File Path = '$filePath'");
    if (!empty($filePath) && !isset($GLOBALS['xarTpl_additionalStyles'][$module.'/'.$file])) {
           return 'xarTplAddStyleLink(\'' . $module . '\', \'' . $file . '\', \'' . $type . '\');';

//         $GLOBALS['xarTpl_additionalStyles'][$module.'/'.$file] = array('filepath' => $filePath,
//                                                                        'type' => $type);
        xarLogMessage("CSS ADDED: $filePath as '".$module."/".$file."'");
        xarLogMessage(serialize($GLOBALS['xarTpl_additionalStyles']));
    }
    else{
        xarLogMessage('Include css failed: duplicate?');
    }
    return '';
}

?>
