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

function xarInstallConfigSetVar($name, $value)
{
    if (empty($name)) {
        $msg = xarML('Empty name.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // see if the variable has already been set
    /*$oldValue = xarConfigGetVar($name);
    $mustInsert = false;
    if (!isset($oldValue)) {
        if (xarExceptionMajor()) return; // thorw back
        $mustInsert = true;
    }*/

    list($dbconn) = xarDBGetConn();
    //$tables = xarDBGetTables();
    //$config_varsTable = $tables['config_vars'];
    $config_varsTable = xarDBGetSystemTablePrefix() . '_config_vars';

    //Here we serialize the configuration variables
    //so they can effectively contain more than one value
    $value = serialize($value);

    //Here we insert the value if it's new
    //or update the value if it already exists
    //if ($mustInsert == true) {
        //Insert
        $seqId = $dbconn->GenId($config_varsTable);
        $query = "INSERT INTO $config_varsTable
                  (xar_id,
                   xar_name,
                   xar_value)
                  VALUES ('$seqId',
                          '" . xarVarPrepForStore($name) . "',
                          '" . xarVarPrepForStore($value). "')";
    /*} else {
         //Update
         $query = "UPDATE $config_varsTable
                   SET xar_value='" . xarVarPrepForStore($value) . "'
                   WHERE xar_name='" . xarVarPrepForStore($name) . "'";
    }*/

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    //Update configuration variables
    //xarVarSetCached('Config.Variables', $name, $value);

    return true;
}

/**
 * Call an installer function.
 *
 * NOTE: this function is identical to
 *       xarModFunc except that it uses xarTplInstall
 *       instead of xarTplModule
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
function xarInstallFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{
    if (empty($modName)) {
        $msg = xarML('Empty modname.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Build function name and call function
    $modFunc = "{$modName}_{$modType}_{$funcName}";
    if (!function_exists($modFunc)) {
        $msg = xarML('Module function #(1) doesn\'t exist.', $modFunc);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
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

    return xarTplInstall($modName, $modType, $funcName, $tplData, $templateName);
}

function xarInstallAPIFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{
    if (empty($modName)) {
        $msg = xarML('Empty modname.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Build function name and call function
    $modAPIFunc = "{$modName}_{$modType}api_{$funcName}";
    if (!function_exists($modAPIFunc)) {
        $msg = xarML('Module API function #(1) doesn\'t exist.', $modAPIFunc);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
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
function xarInstallAPILoad($modName, $modType = 'user')
{
    static $loadedAPICache = array();

    //xarLogMessage("xarModAPILoad: loading $modName:$modType");

    if (empty($modName)) {
        $msg = xarML('Empty modname.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($loadedAPICache["$modName$modType"])) {
        // Already loaded from somewhere else
        return true;
    }

    /*
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back
    }

    if ($modBaseInfo['state'] != XARMOD_STATE_ACTIVE) {
        $msg = xarML('Module #(1) is not active.', $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_ACTIVE',
                       new SystemException($msg));
        return;
    }
    */

    $modOsType = xarVarPrepForOS($modType);
    //$modOsDir = $modBaseInfo['osdirectory'];
    $modOsDir = 'installer';

    $osfile = "modules/$modOsDir/xar{$modOsType}api.php";
    if (!file_exists($osfile)) {
        // File does not exist
        $msg = xarML('Module file #(1) doesn\'t exist.', $osfile);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    // Load the file
    include $osfile;
    $loadedAPICache["$modName$modType"] = true;

    // Load the API translations files
   /* $res = xarMLS_loadModuleTranslations($modName, $modOsDir, $modType.'api');
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return; // throw back exception
    }

    // Load database info
    xarMod__loadDbInfo($modName, $modOsDir);

    // Module API loaded successfully, notify the proper event
    xarEvt_notify($modName, $modType, 'ModAPILoad', NULL);
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
function xarInstallLoad($modName, $modType = 'user')
{
    static $loadedModuleCache = array();

    //xarLogMessage("xarModLoad: loading $modName:$modType");

    if (empty($modName)) {
        $msg = xarML('Empty modname.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($loadedModuleCache["$modName$modType"])) {
        // Already loaded from somewhere else
        return true;
    }
    /*
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back
    }

    if ($modBaseInfo['state'] != XARMOD_STATE_ACTIVE) {
        $msg = xarML('Module #(1) is not active.', $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_ACTIVE',
                       new SystemException($msg));
        return;
    }
    */
    // Load the module files
    $modOsType = xarVarPrepForOS($modType);
    //$modOsDir = $modBaseInfo['osdirectory'];
    $modOsDir = 'installer';

    $osfile = "modules/$modOsDir/xar$modOsType.php";
    if (!file_exists($osfile)) {
        // File does not exist
        $msg = xarML('Module file #(1) doesn\'t exist.', $osfile);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    // Load file
    include $osfile;
    $loadedModuleCache["$modName$modType"] = true;

    // Load the module translations files
    /* $res = xarMLS_loadModuleTranslations($modName, $modOsDir, $modType);
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return; // throw back exception
    }*/

    // Load database info
    //xarMod__loadDbInfo($modName, $modOsDir);

    // Module loaded successfully, notify the proper event
    //xarEvt_notify($modName, $modType, 'ModLoad', NULL);

    return true;
}

/**
 * Turn installer output into a template
 *
 * NOTE: this function is identical to xarTplModule without xarMod_getBaseInfo
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
function xarTplInstall($modName, $modType, $funcName, $tplData = array(), $templateName = NULL)
{
    global $xarTpl_themeDir;

    if (!empty($templateName)) {
        $templateName = xarVarPrepForOS($templateName);
    }

    /*
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back
    $modOsDir = $modBaseInfo['osdirectory'];
    */
    $modOsDir = 'installer';

    // Try theme template
    $sourceFileName = "$xarTpl_themeDir/modules/$modOsDir/$modType-$funcName" . (empty($templateName) ? '.xt' : "-$templateName.xt");
    if (!file_exists($sourceFileName)) {
        // Use internal template
        $sourceFileName = "modules/$modOsDir/xartemplates/$modType-$funcName" . (empty($templateName) ? '.xd' : "-$templateName.xd");
    }

    $tplData['_bl_module_name'] = $modName;
    $tplData['_bl_module_type'] = $modType;
    $tplData['_bl_module_func'] = $funcName;

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}
?>