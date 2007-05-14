<?php
/**
 * Theme handling functions
 *
 * @package lib
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage themes
 * @author mrb <marcel@xaraya.com>
 * @todo Most of this doesnt belong here, but in the themes module, move it away
*/

sys::import('xaraya.variables.theme');
function xarThemeGetVar($themeName, $name, $prep = NULL)
{   return xarThemeVars::get($themeName, $name); }

function xarThemeSetVar($themeName, $name, $prime = NULL, $value, $description='')
{   return xarThemeVars::set($themeName, $name, $value); }

function xarThemeDelVar($themeName, $name)
{   return xarThemeVars::delete($themeName, $name); }


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
 */
function xarThemeGetInfo($regId)
{
    return xarMod::getInfo($regId, $type = 'theme');
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
 */
function xarTheme_getVarsByTheme($themeName)
{
    // TODO: we would need to return all mod item vars here where:
    // mod  = themes
    // item = the theme
    // For now, return the vars of the themes module
    return xarModVars::load('themes');
}

?>
