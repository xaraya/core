<?php
/**
 * Theme handling functions
 *
 * @package core\themes
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
 * @param themeName The name of the theme
 * @return theme RegID for processing
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
 * @param themeRegId theme id
 * @return array array of theme information
 */
function xarThemeGetInfo($regId) { return xarTheme::getInfo($regId); }

/**
 * checks if a theme is installed and its state is XARTHEME_STATE_ACTIVE
 *
 * @uses xarTheme::isAvailable()
 * @deprecated
 * @param themeName registered name of theme
 * @return boolean true if the theme is available, false if not
 */
function xarThemeIsAvailable($themeName) { return xarTheme::isAvailable($themeName); }

// PROTECTED FUNCTIONS

/**
 * Get info from xartheme.php
 *
 * @uses xarTheme::getFileInfo()
 * @deprecated
 * @param themeOSdir the theme's directory
 * @return xarMod::getFileInfo for processing
 * @todo move to own class so we can protect it
 */
function xarTheme_getFileInfo($themeOsDir) { return xarTheme::getFileInfo($themeOsDir); }

/**
 * Load a theme's base information
 *
 * @uses xarTheme::getBaseInfo()
 * @deprecated
 * @param themeName the theme's name
 * @return to xarMod__getBaseInfo for processing
 */
function xarTheme_getBaseInfo($themeName) { return xarTheme::getBaseInfo($themeName); }

/**
 * Get all theme variables for a particular theme
 *
 * @uses xarTheme::getVarsByTheme()
 * @deprecated
 * @return array an array of theme variables
 */
function xarTheme_getVarsByTheme($themeName)
{
    return xarTheme::getVarsByTheme($themeName);
}

/**
 * Theme handling functions
 */
class xarTheme extends xarObject
{
    const STATE_UNINITIALISED              = 1;
    const STATE_INACTIVE                   = 2;
    const STATE_ACTIVE                     = 3;
    const STATE_MISSING_FROM_UNINITIALISED = 4;
    const STATE_UPGRADED                   = 5;
    const STATE_ANY                        = 0;
    const STATE_INSTALLED                  = 6;
    const STATE_MISSING_FROM_INACTIVE      = 7;
    const STATE_MISSING_FROM_ACTIVE        = 8;
    const STATE_MISSING_FROM_UPGRADED      = 9;

    /**
     * Gets theme registry ID given its name
     */
    public static function getIDFromName($themeName,$id='regid')
    {
        if (empty($themeName)) throw new EmptyParameterException('themeName');

        $themeBaseInfo = xarMod::getBaseInfo($themeName, 'theme');
        if (!isset($themeBaseInfo)) return; // throw back

        return $themeBaseInfo[$id];
    }

    /**
     * get information on theme
     */
    public static function getInfo($regId)
    {
        return xarMod::getInfo($regId, $type = 'theme');
    }

    /**
     * checks if a theme is installed and its state is XARTHEME_STATE_ACTIVE
     */
    public static function isAvailable($themeName)
    {
        return xarMod::isAvailable($themeName, $type = 'theme');
    }

    /**
     * Get info from xartheme.php
     */
    public static function getFileInfo($themeOsDir)
    {
        return xarMod::getFileInfo($themeOsDir, $type = 'theme');
    }

    /**
     * Load a theme's base information
     */
    public static function getBaseInfo($themeName)
    {
        return xarMod::getBaseInfo($themeName, $type = 'theme');
    }

    /**
     * Get all theme variables for a particular theme
     */
    public static function getVarsByTheme($themeName)
    {
        // TODO: we would need to return all mod item vars here where:
        // mod  = themes
        // item = the theme
        // For now, return the vars of the themes module
        return xarModVars::preload('themes');
    }
}

