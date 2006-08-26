<?php
/**
 * Theme handling functions
 *
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
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
 * @throws EmptyParameterException
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
 * @throws EmptyParameterException
 */
function xarThemeSetVar($themeName, $name, $prime = NULL, $value, $description='')
{
    if (empty($themeName)) throw new EmptyParameterException('themename');

    $itemid = xarThemeGetIDFromName($themeName,'systemid');
    $modVarName = $themeName . '_' . $name;
    // Make sure we set it as modvar first
    // TODO: this sucks
    if(!xarModVars::get('themes',$modVarName)) {
        xarModVars::set('themes',$modVarName,$value);
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
 * @throws EmptyParameterException
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
 * @return theme RegID for processing
 * @throws EmptyParameterException
 */
function xarThemeGetIDFromName($themeName,$id='regid')
{
    if (empty($themeName)) throw new EmptyParameterException('themeName');

    $themeBaseInfo = xarMod::getBaseInfo($themeName, 'theme');
    if (!isset($themeBaseInfo)) return; // throw back

    return $themeBaseInfo[$id];
}

/**
 * get information on theme
 *
 * @access public
 * @param themeRegId theme id
 * @return array array of theme information
 * @throws DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function xarThemeGetInfo($regId)
{
    return xarMod::getInfo($regId, $type = 'theme');
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
    // Just for consistency we do this now, but this just returns true, nothing more
    return xarMod::loadDbInfo($themeName, $themeDir, $type = 'theme');
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
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarThemeIsAvailable($themeName)
{
    return xarMod::isAvailable($themeName, $type = 'theme');
}


// PROTECTED FUNCTIONS

/**
 * Get info from xartheme.php
 *
 * @access protected
 * @param themeOSdir the theme's directory
 * @return xarMod::getFileInfo for processing
 * @todo move to own class so we can protect it
 */
function xarTheme_getFileInfo($themeOsDir)
{
    return xarMod::getFileInfo($themeOsDir, $type = 'theme');
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
    return xarMod::getBaseInfo($themeName, $type = 'theme');
}

/**
 * Get all theme variables for a particular theme
 *
 * @access protected
 * @return array an array of theme variables
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarTheme_getVarsByTheme($themeName)
{
    // TODO: we would need to return all mod item vars here where:
    // mod  = themes
    // item = the theme
    // For now, return the vars of the themes module
    return xarModVars::load('themes');
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
