<?php
/**
 * Theme handling functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage themes
 * @author mrb <marcel@xaraya.com> (this tag means responsible person)
 * @todo Most of this doesnt belong here, but in the themes module, move it away
*/

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
    if (empty($themeName)) throw new EmptyParameterException('themename');

    $itemid = xarThemeGetIDFromName($themeName,'systemid');
    $modVarName = $themeName . '_' . $name;
    return xarVar__GetVarByAlias('themes', $modVarName, $itemid, $prep, $type = 'moditemvar');
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
    if (empty($themeName)) throw new EmptyParameterException('themename');

    $itemid = xarThemeGetIDFromName($themeName,'systemid');
    $modVarName = $themeName . '_' . $name;
    // Make sure we set it as modvar first
    // TODO: this sucks
    if(!xarModGetVar('themes',$modVarName)) {
        xarModSetVar('themes',$modVarName,$value);
    }
    return xarVar__SetVarByAlias('themes', $modVarName, $value, $prime, $description, $itemid, $type = 'moditemvar');
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
    if (empty($themeName)) throw new EmptyParameterException('themename');

    $itemid = xarThemeGetIDFromName($themeName,'systemid');
    $modVarName = $themeName . '_' . $name;
    return xarVar__DelVarByAlias('themes', $modVarName, $itemid, $type = 'moditemvar');
}

/**
 * Gets theme registry ID given its name
 *
 * @access public
 * @param themeName The name of the theme
 * @return xarModGetIDFromName for processing
 * @raise DATABASE_ERROR, BAD_PARAM, THEME_NOT_EXIST
 */
function xarThemeGetIDFromName($themeName,$id='regid')
{
    if (empty($themeName)) throw new EmptyParameterException('themeName');

    $themeBaseInfo = xarMod_getBaseInfo($themeName, 'theme');
    if (!isset($themeBaseInfo)) return; // throw back

    return $themeBaseInfo[$id];
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
    // The theme display name is language sensitive,
    // so it's fetched through xarML.
    // TODO: need to think of something that actually works.
    return $themeName;
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
    // This is wrong, get them for themes module for now
    //return xarMod_getVarsByModule($themeName, $type = 'theme');
    return xarMod_getVarsByModule('themes');
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
 * @todo we dont need this
 */
function xarTheme_getState($themeRegId, $themeMode)
{
    return xarMod::getState($themeRegId, $themeMode, $type = 'theme');
}
?>
