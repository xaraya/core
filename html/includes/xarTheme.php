<?php
/**
 * File: $Id$
 *
 * Theme handling functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage themes
 * @author mrb <marcel@xaraya.com> (this tag means responsible person)
*/



// Theme Function Wrappers
// FIXME: This should be done better integrated
//        We have no redundancy at least with xarMod.php now, but it's still a bit messy


/**
 * get a theme variable
 *
 * @access public
 * @param themeName The name of the theme
 * @param name The name of the variable
 * @return mixed The value of the variable or void if variable doesn't exist
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarThemeGetVar($themeName, $name, $prep = NULL)
{
    if (empty($themeName)) {
        $msg = xarML('Empty themeName (#(1)).', $themeName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    return xarVar__GetVarByAlias($themeName, $name, $uid = NULL, $prep, $type = 'themevar');
}

/**
 * set a theme variable
 *
 * @access public
 * @param themeName The name of the theme
 * @param name The name of the variable
 * @param value The value of the variable
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarThemeSetVar($themeName, $name, $prime = NULL, $value, $description='')
{
    if (empty($themeName)) {
        $msg = xarML('Empty themeName (#(1)).', $themeName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    return xarVar__SetVarByAlias($themeName, $name, $value, $prime, $description, $uid = NULL, $type = 'themevar');
}


/**
 * delete a theme variable
 *
 * @access public
 * @param themeName The name of the theme
 * @param name The name of the variable
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarThemeDelVar($themeName, $name)
{
    if (empty($themeName)) {
        $msg = xarML('Empty themeName (#(1)).', $themeName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));return;
    }

    return xarVar__DelVarByAlias($themeName, $name, $uid = NULL, $type = 'themevar');
}

/**
 * Gets theme registry ID given its name
 *
 * @access public
 * @param themeName The name of the theme
 * @return xarModGetIDFromName for processing
 * @raise DATABASE_ERROR, BAD_PARAM, THEME_NOT_EXIST
 */
function xarThemeGetIDFromName($themeName)
{
    return xarModGetIDFromName($themeName, $type = 'theme');
}

/**
 * get information on theme
 *
 * @access public
 * @param themeRegId theme id
 * @return array array of theme information
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function xarThemeGetInfo($regId)
{
    return xarModGetInfo($regId, $type = 'theme');
}

/**
 * Call a theme API function.
 *
 * Using the theme name, func, and optional arguments
 * builds a function name by joining them together
 * and using the optional arguments as parameters
 * like so:
 * Ex: themenameapi_modFunc($args);
 *
 * @access public
 * @param themeName string registered name of theme
 * @param funcName string specific function to run
 * @param args array arguments to pass to the function
 * @param throwException boolean optional flag to throw an exception if the function doesn't exist or not (default = 1)
 * @return mixed The output of the function, or false on failure
 * @raise BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
 * @todo *cough* what's this?? this really needs to go, no go IMNSHO
 */
// function xarThemeAPIFunc($themeName, $funcName, $args = array(), $throwException = 1)
// {
//     if (empty($themeName)) {
//         //die("$modName, $modType, $funcName");
//         xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
//         return;
//     }
//     if (empty($funcName)) {
//         xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'funcName');
//         return;
//     }

//     // Build function name and call function
//     $funcName = strtolower($funcName);
//     $themeAPIFunc = "{$themeName}api_{$funcName}";

//     $fileName = xarConfigGetVar('Site.BL.ThemesDirectory'). '/' . $themeName . '/xarthemeapi.php';

//     if (!file_exists($fileName)) {
//         $msg = xarML('Theme API function #(1) does not exist.', $modAPIFunc);
//         xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST', new SystemException($msg));
//         return;
//     } else {
//          ob_start();
//          $r = require_once $fileName;
//          $error_msg = strip_tags(ob_get_contents());
//          ob_end_clean();

//          if (empty($r) || !$r) {
//             $msg = xarML("Could not load theme api file: [#(1)].\n\n Error Caught:\n #(2)", $fileName, $error_msg);
//             xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST', new SystemException($msg));
//             return;
//          }
//     }

//     return $themeAPIFunc($args);
// }


/**
 * load database definition for a theme
 *
 * @param themeName name of theme to load database definition for
 * @param themeDir directory that theme is in (if known)
 * @return xarModDBInfoLoad for processing.
 */
function xarThemeDBInfoLoad($themeName, $themeDir = NULL)
{
    return xarModDBInfoLoad($themeName, $themeDir, $type = 'theme');
}


/**
 * Gets the displayable name for the passed themeName.
 * The displayble name is sensible to user language.
 *
 * @access public
 * @param themeName registered name of theme
 * @return string the displayable name
 */
function xarThemeGetDisplayableName($themeName)
{
    // The theme display name is language sensitive, so it's fetched through xarMLByKey
    // <andyv> it was a good idea, but didnt work with xarMLByKey because it didnt like spaces in the names
    // see bug 720 as a reference to this problem
    // TODO: need to think of something that actually works.. changed for xarML for now
    // <MrB> that's the right way, am i missing something?
    return xarML($themeName);
}

/**
 * checks if a theme is installed and its state is XARTHEME_STATE_ACTIVE
 *
 * @access public
 * @param themeName registered name of theme
 * @return bool true if the theme is available, false if not
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarThemeIsAvailable($themeName)
{
    return xarModIsAvailable($themeName, $type = 'theme');
}


// PROTECTED FUNCTIONS

/**
 * Get info from xartheme.php
 *
 * @access protected
 * @param themeOSdir the theme's directory
 * @return xarMod_getFileInfo for processing
 */
function xarTheme_getFileInfo($themeOsDir)
{
    return xarMod_getFileInfo($themeOsDir, $type = 'theme');
}

/**
 * Load a theme's base information
 *
 * @access protected
 * @param themeName the theme's name
 * @return to xarMod__getBaseInfo for processing
 */
function xarTheme_getBaseInfo($themeName)
{
    return xarMod_getBaseInfo($themeName, $type = 'theme');
}

/**
 * Get all theme variables for a particular theme
 *
 * @access protected
 * @return array an array of theme variables
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarTheme_getVarsByTheme($themeName)
{
    return xarMod_getVarsByModule($name, $type = 'theme');
}

/**
 * Get all theme variables with a particular name
 *
 * @access protected
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarTheme_getVarsByName($name)
{
    return xarMod_getVarsByName($name, $type = 'theme');
}

/**
 * Get the theme's current state
 *
 * @param themeRegId the theme's registered id
 * @param themeThemee the theme's site mode
 * @return to xarMod__getState for processing
 */
function xarTheme_getState($themeRegId, $themeMode)
{
    return xarMod_getState($themeRegId, $themeMode, $type = 'theme');
}
?>