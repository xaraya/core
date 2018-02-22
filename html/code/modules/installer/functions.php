<?php

/**
 * Call an installer function
 *
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */

/**
 * Call an installer function.
 *
 * @author John Robeson
 * @author Marcel van der Boom <marcel@hsdev.com>
 * This function is similar to xarMod::guiFunc but simplified.
 * We need this because during install we cant have the module
 * subsystem online directly, so we need a direct way of calling
 * the admin functions of the installer. The actual functions
 * called adhere to normal Xaraya module functions, so we can use
 * the installer later on when xaraya is installed
 *
 * @access public
 * @param funcName specific function to run
 * @param args argument array
 * @return string output display string
 * @throws BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
 */
function xarInstallFunc($funcName = 'main', $args = array()) {
    $modName = 'installer';
    $modType = 'admin';

    // Build function name and call function
    $modFunc = "{$modName}_{$modType}_{$funcName}";
    if (!function_exists($modFunc)) {
        // try to load it
        xarInstallLoad($funcName);
        if (!function_exists($modFunc))
            throw new FunctionNotFoundException($modFunc);
    }

    // Load the translations file
    $file = sys::code() . 'modules/' . $modName . '/xar' . $modType . '/' . strtolower($funcName) . '.php';
    if (!xarMLSLoadTranslations($file))
        return;

    $tplData = $modFunc($args);
    if (!is_array($tplData)) {
        return $tplData;
    }

    // <mrb> Why is this here?
    $templateName = NULL;
    if (isset($tplData['_bl_template'])) {
        $templateName = $tplData['_bl_template'];
    }

    return xarTpl::module($modName, $modType, $funcName, $tplData, $templateName);
}

function xarInstallAPIFunc($funcName = 'main', $args = array()) {
    $modName = 'installer';
    $modType = 'admin';


    // Build function name and call function
    $modAPIFunc = "{$modName}_{$modType}api_{$funcName}";
    if (!function_exists($modAPIFunc)) {
        // attempt to load the install api
        xarInstallAPILoad();
        // let's check for the function again to be sure
        if (!function_exists($modAPIFunc))
            throw new FunctionNotFoundException($modAPIFunc);
    }

    // Load the translations file
    $file = sys::code() . 'modules/' . $modName . '/xar' . $modType . 'api/' . strtolower($funcName) . '.php';

    if (!xarMLSLoadTranslations($file))
        return;

    return $modAPIFunc($args);
}

/**
 * Loads the modType API for installer identified by modName.
 *
 * @access public
 * @param modName registered name of the module
 * @param modType type of functions to load
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function xarInstallAPILoad() {
    static $loadedAPICache = array();

    $modName = 'installer';
    $modOsDir = 'installer';
    $modType = 'admin';

    if (isset($loadedAPICache[strtolower("$modName$modType")])) {
        // Already loaded from somewhere else
        return true;
    }

    $modOsType = xarVarPrepForOS($modType);
    $osfile = sys::code() . "modules/$modOsDir/xar{$modOsType}api.php";
    if (!file_exists($osfile))
        throw new FileNotFoundException($osfile);


    // Load the file
    include $osfile;
    $loadedAPICache[strtolower("$modName$modType")] = true;

    return true;
}

/**
 * Loads the modType of installer identified by modName.
 *
 * @access public
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function xarInstallLoad($func) {
    static $loadedModuleCache = array();

    $modName = 'installer';
    $modType = 'admin';

    if (empty($modName))
        throw new EmptyParameterException('modName');

    if (isset($loadedModuleCache[strtolower("$modName$modType")])) {
        // Already loaded from somewhere else
        return true;
    }

    // Load the module files
    $modOsType = xarVarPrepForOS($modType);
    $modOsDir = 'installer';

    $osfile = sys::code() . "modules/$modOsDir/xar$modOsType/$func.php";
    if (!file_exists($osfile))
        throw new FileNotFoundException($osfile);

    // Load file
    include $osfile;
    $loadedModuleCache[strtolower("$modName$modType")] = true;

    // Load the module translations files
    $res = xarMLSLoadTranslations($osfile);
    return true;
}

?>
