<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of this file: John Robeson
// Purpose of this file: extra functions for the installer
// ----------------------------------------------------------------------

//TODO: remove
function pnInstallGetTheme()
{
    return 'themes/'. PNINSTALL_THEME;
}

/**
 * Call an installer function.
 *
 * NOTE: this function is identical to
 *       pnModFunc except that it uses pnTplInstall
 *       instead of pnTplModule
 *
 * @access public
 * @param modName registered name of module
 * @param modType type of function to run
 * @param funcName specific function to run
 * @param args argument array
 * @returns mixed
 * @return The output of the function, or false on failure
 * @raise BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
 */
function pnInstallFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{
    if (empty($modName)) {
        $msg = pnML('Empty modname.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Build function name and call function
    $modFunc = "{$modName}_{$modType}_{$funcName}";
    if (!function_exists($modFunc)) {
        $msg = pnML('Module function #(1) doesn\'t exist.', $modFunc);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    $tplData = $modFunc($args);

    if (!is_array($tplData)) {
        return $tplData;
    }

    $templateName = NULL;
    if (isset($tplData['_bl_template'])) {
        $templateName = $tplData['_bl_template'];
    }

    return pnTplInstall($modName, $modType, $funcName, $tplData, $templateName);
}

function pnInstallAPIFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{
    if (empty($modName)) {
        $msg = pnML('Empty modname.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Build function name and call function
    $modAPIFunc = "{$modName}_{$modType}api_{$funcName}";
    if (!function_exists($modAPIFunc)) {
        $msg = pnML('Module API function #(1) doesn\'t exist.', $modAPIFunc);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    return $modAPIFunc($args);
}

/**
 * Loads the modType API for installer identified by modName.
 *
 * @access public
 * @param modName registered name of the module
 * @param modType type of functions to load
 * @returns bool
 * @return true on success
 * @raise BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function pnInstallAPILoad($modName, $modType = 'user')
{
    static $loadedAPICache = array();

    //pnLogMessage("pnModAPILoad: loading $modName:$modType");

    if (empty($modName)) {
        $msg = pnML('Empty modname.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($loadedAPICache["$modName$modType"])) {
        // Already loaded from somewhere else
        return true;
    }

    /*
    $modBaseInfo = pnMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back
    }

    if ($modBaseInfo['state'] != PNMOD_STATE_ACTIVE) {
        $msg = pnML('Module #(1) is not active.', $modName);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_NOT_ACTIVE',
                       new SystemException($msg));
        return;
    }
    */

    $modOsType = pnVarPrepForOS($modType);
    //$modOsDir = $modBaseInfo['osdirectory'];
    $modOsDir = 'installer';

    $osfile = "modules/$modOsDir/pn{$modOsType}api.php";
    if (!file_exists($osfile)) {
        // File does not exist
        $msg = pnML('Module file #(1) doesn\'t exist.', $osfile);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    // Load the file
    include $osfile;
    $loadedAPICache["$modName$modType"] = true;

    // Load the API translations files
   /* $res = pnMLS_loadModuleTranslations($modName, $modOsDir, $modType.'api');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back exception
    }

    // Load database info
    pnMod__loadDbInfo($modName, $modOsDir);

    // Module API loaded successfully, notify the proper event
    pnEvt_notify($modName, $modType, 'ModAPILoad', NULL);
    */
    return true;
}

/**
 * Loads the modType of installer identified by modName.
 *
 * @access public
 * @param modName - name of module to load
 * @param modType - type of functions to load
 * @returns string
 * @return true
 * @raise BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function pnInstallLoad($modName, $modType = 'user')
{
    static $loadedModuleCache = array();

    //pnLogMessage("pnModLoad: loading $modName:$modType");

    if (empty($modName)) {
        $msg = pnML('Empty modname.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($loadedModuleCache["$modName$modType"])) {
        // Already loaded from somewhere else
        return true;
    }
    /*
    $modBaseInfo = pnMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back
    }

    if ($modBaseInfo['state'] != PNMOD_STATE_ACTIVE) {
        $msg = pnML('Module #(1) is not active.', $modName);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_NOT_ACTIVE',
                       new SystemException($msg));
        return;
    }
    */
    // Load the module files
    $modOsType = pnVarPrepForOS($modType);
    //$modOsDir = $modBaseInfo['osdirectory'];
    $modOsDir = 'installer';

    $osfile = "modules/$modOsDir/pn$modOsType.php";
    if (!file_exists($osfile)) {
        // File does not exist
        $msg = pnML('Module file #(1) doesn\'t exist.', $osfile);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    // Load file
    include $osfile;
    $loadedModuleCache["$modName$modType"] = true;

    // Load the module translations files
    /* $res = pnMLS_loadModuleTranslations($modName, $modOsDir, $modType);
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back exception
    }*/

    // Load database info
    //pnMod__loadDbInfo($modName, $modOsDir);

    // Module loaded successfully, notify the proper event
    //pnEvt_notify($modName, $modType, 'ModLoad', NULL);

    return true;
}

/**
 * Turn installer output into a template
 *
 * NOTE: this function is identical to pnTplModule without pnMod_getBaseInfo
 * prolly will get removed in the future, i have it just for consistancy
 *
 * @access public
 * @param modName the module name
 * @param modType user|admin
 * @param funcName module function to template
 * @param tplData arguments for the template
 * @param templateName the specific template to call
 * @returns string
 * @return output of the template
 */
function pnTplInstall($modName, $modType, $funcName, $tplData = array(), $templateName = NULL)
{
    global $pnTpl_themeDir;

    if (!empty($templateName)) {
        $templateName = pnVarPrepForOS($templateName);
    }

    /*
    $modBaseInfo = pnMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back
    $modOsDir = $modBaseInfo['osdirectory'];
    */
    $modOsDir = 'installer';

    // Try theme template
    $sourceFileName = "$pnTpl_themeDir/modules/$modOsDir/$modType-$funcName" . (empty($templateName) ? '.pnt' : "-$templateName.pnt");
    if (!file_exists($sourceFileName)) {
        // Use internal template
        $sourceFileName = "modules/$modOsDir/pntemplates/$modType-$funcName" . (empty($templateName) ? '.pnd' : "-$templateName.pnd");
    }

    $tplData['_bl_module_name'] = $modName;
    $tplData['_bl_module_type'] = $modType;
    $tplData['_bl_module_func'] = $funcName;

    return pnTpl__executeFromFile($sourceFileName, $tplData);
}
?>
