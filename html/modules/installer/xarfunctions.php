<?php
/**
 * File: $Id$
 *
 * Extra functions for the installer
 *
 * @package Xaraya
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * @subpackage installer
 * @author John Robeson
 * @author Marcel van der Boom <marcel@hsdev.com>
 */

/**
 * Call an installer function.
 *
 * This function is similar to xarModFunc but simplified. 
 * We need this because during install we cant have the module
 * subsystem online directly, so we need a direct way of calling
 * the admin functions of the installer. The actual functions
 * called adhere to normal Xaraya module functions, so we can use
 * the installer later on when xaraya is installed
 *
 * @access public
 * @param funcName specific function to run
 * @param args argument array
 * @returns mixed
 * @return The output of the function, or false on failure
 * @raise BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
 */
function xarInstallFunc($funcName = 'main', $args = array())
{
    $modName = 'installer';
    $modType = 'admin';

    // Build function name and call function
    $modFunc = "{$modName}_{$modType}_{$funcName}";
    if (!function_exists($modFunc)) {
        // try to load it
        xarInstallLoad();
        if(!function_exists($modFunc)) {
            $msg = xarML('Module function #(1) does not exist.', $modFunc);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                            new SystemException($msg));
            return;
        }
    }

    // Load the translations file
    if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:'.$modType, $funcName) === NULL) return;

    $tplData = $modFunc($args);
    if (!is_array($tplData)) {
        return $tplData;
    }

    // <mrb> Why is this here?
    $templateName = NULL;
    if (isset($tplData['_bl_template'])) {
        $templateName = $tplData['_bl_template'];
    }

    return xarTplModule($modName, $modType, $funcName, $tplData, $templateName);
}

function xarInstallAPIFunc($funcName = 'main', $args = array())
{
    $modName = 'installer';
    $modType = 'admin';

    // Build function name and call function
    $modAPIFunc = "{$modName}_{$modType}api_{$funcName}";
    if (!function_exists($modAPIFunc)) {
        // attempt to load the install api
        xarInstallAPILoad();
        // let's check for the function again to be sure
        if (!function_exists($modAPIFunc)) {
            $msg = xarML('Module API function #(1) does not exist.', $modAPIFunc);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                            new SystemException($msg));
            return;
        }
    }

    // Load the translations file
    if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:'.$modType.'api', $funcName) === NULL) return;

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
function xarInstallAPILoad()
{
    static $loadedAPICache = array();

    $modName    = 'installer';
    $modOsDir   = 'installer';
    $modType  = 'admin';

    if (isset($loadedAPICache[strtolower("$modName$modType")])) {
        // Already loaded from somewhere else
        return true;
    }

    $modOsType = xarVarPrepForOS($modType);

    $osfile = "modules/$modOsDir/xar{$modOsType}api.php";
    if (!file_exists($osfile)) {
        // File does not exist
        $msg = xarML('Module file #(1) does not exist.', $osfile);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    // Load the file
    include $osfile;
    $loadedAPICache[strtolower("$modName$modType")] = true;

    return true;
}

/**
 * Loads the modType of installer identified by modName.
 *
 * @access public
 * @returns string
 * @return true
 * @raise BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function xarInstallLoad()
{
    static $loadedModuleCache = array();

    $modName = 'installer';
    $modType = 'admin';

    if (empty($modName)) {
        $msg = xarML('Empty modname.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($loadedModuleCache[strtolower("$modName$modType")])) {
        // Already loaded from somewhere else
        return true;
    }
   
    // Load the module files
    $modOsType = xarVarPrepForOS($modType);
    $modOsDir = 'installer';

    $osfile = "modules/$modOsDir/xar$modOsType.php";
    if (!file_exists($osfile)) {
        // File does not exist
        $msg = xarML('Module file #(1) does not exist.', $osfile);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    // Load file
    include $osfile;
    $loadedModuleCache[strtolower("$modName$modType")] = true;

    // Load the module translations files
    $res = xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:', $modType);
    if (!isset($res) && xarCurrentErrorType() != XAR_NO_EXCEPTION) return; // throw back exception
 
    return true;
}

?>
