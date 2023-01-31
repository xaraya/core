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

    public static $noCacheState = false;

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
     * checks if a theme is installed and its state is STATE_ACTIVE
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


// Legacy calls - import by default for now...
//sys::import('xaraya.legacy.themes');
