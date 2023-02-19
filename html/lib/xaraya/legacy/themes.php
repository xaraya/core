<?php
/**
 * Theme handling functions
 *
 * @package core\themes\legacy
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mrb <marcel@xaraya.com>
 * @todo Most of this doesnt belong here, but in the themes module, move it away
*/

sys::import('xaraya.variables.theme');
/**
 * Wrapper functions to support Xaraya 1 API
 *
 * @uses xarThemeVars::get()
 * @deprecated
**/
function xarThemeGetVar($themeName, $name, $prep = NULL)                           {   return xarThemeVars::get($themeName, $name); }
//function xarThemeSetVar($themeName, $name, $prime = NULL, $value, $description='') {   return xarThemeVars::set($themeName, $name, $value); }
//function xarThemeDelVar($themeName, $name)                                         {   return xarThemeVars::delete($themeName, $name); }


/**
 * Gets theme registry ID given its name
 * 
 * @uses xarTheme::getIDFromName()
 * @deprecated
 * @param string themeName The name of the theme
 * @return int theme RegID for processing
 * @throws EmptyParameterException
 */
function xarThemeGetIDFromName($themeName,$id='regid')
{
    return xarTheme::getIDFromName($themeName, $id);
}

/**
 * get information on theme
 *
 * @uses xarTheme::getInfo()
 * @deprecated
 * @param int themeRegId theme id
 * @return array array of theme information
 */
function xarThemeGetInfo($regId) { return xarTheme::getInfo($regId); }

/**
 * checks if a theme is installed and its state is XARTHEME_STATE_ACTIVE
 *
 * @uses xarTheme::isAvailable()
 * @deprecated
 * @param string themeName registered name of theme
 * @return boolean true if the theme is available, false if not
 */
function xarThemeIsAvailable($themeName) { return xarTheme::isAvailable($themeName); }

// PROTECTED FUNCTIONS

/**
 * Get info from xartheme.php
 *
 * @uses xarTheme::getFileInfo()
 * @deprecated
 * @param string themeOSdir the theme's directory
 * @return xarMod::getFileInfo for processing
 * @todo move to own class so we can protect it
 */
function xarTheme_getFileInfo($themeOsDir) { return xarTheme::getFileInfo($themeOsDir); }

/**
 * Load a theme's base information
 *
 * @uses xarTheme::getBaseInfo()
 * @deprecated
 * @param string themeName the theme's name
 * @return array xarMod__getBaseInfo for processing
 */
function xarTheme_getBaseInfo($themeName) { return xarTheme::getBaseInfo($themeName); }

/**
 * Get all theme variables for a particular theme
 *
 * @uses xarTheme::getVarsByTheme()
 * @deprecated
 * @return array|bool an array of theme variables
 */
function xarTheme_getVarsByTheme($themeName)
{
    return xarTheme::getVarsByTheme($themeName);
}
