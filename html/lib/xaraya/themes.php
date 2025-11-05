<?php

/**
 * Theme handling functions
 *
 * @package core\themes
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mrb <marcel@xaraya.com>
 * @todo Most of this doesnt belong here, but in the themes module, move it away
*/

sys::import('xaraya.variables.theme');
sys::import('xaraya.services.xar');
use Xaraya\Services\Modules\InfoHelper;
use Xaraya\Services\xar;

// Themes
class ThemeNotFoundException extends NotFoundExceptions
{
    protected $message = 'A theme is missing, the theme name could not be determined in the current context';
}

interface ixarTheme
{
    public const STATE_UNINITIALISED              = 1;
    public const STATE_INACTIVE                   = 2;
    public const STATE_ACTIVE                     = 3;
    public const STATE_MISSING_FROM_UNINITIALISED = 4;
    public const STATE_UPGRADED                   = 5;
    public const STATE_ANY                        = 0;
    public const STATE_INSTALLED                  = 6;
    public const STATE_MISSING_FROM_INACTIVE      = 7;
    public const STATE_MISSING_FROM_ACTIVE        = 8;
    public const STATE_MISSING_FROM_UPGRADED      = 9;
}

/**
 * Theme handling functions
 * @see xar::theme()
 */
class xarTheme extends xarObject implements ixarTheme
{
    public static $noCacheState = false;
    protected static ?InfoHelper $infoService = null;

    protected static function info(): InfoHelper
    {
        if (!isset(self::$infoService)) {
            $infoService = xar::getServicesClass()->service('modules.info');
            assert($infoService instanceof InfoHelper);
            self::$infoService = $infoService;
        }
        return self::$infoService;
    }

    /**
     * Gets theme registry ID given its name
     */
    public static function getIDFromName($themeName, $id = 'regid')
    {
        if (empty($themeName)) {
            throw new EmptyParameterException('themeName');
        }

        $themeBaseInfo = self::info()->getBaseInfo($themeName, 'theme');
        if (empty($themeBaseInfo)) {
            return;
        } // throw back

        return $themeBaseInfo[$id];
    }

    /**
     * get registry ID for theme
     */
    public static function getRegID($themeName)
    {
        return self::info()->getRegID($themeName, 'theme');
    }

    /**
     * get information on theme
     */
    public static function getInfo($regId)
    {
        return self::info()->getInfo($regId, 'theme');
    }

    /**
     * checks if a theme is installed and its state is STATE_ACTIVE
     */
    public static function isAvailable($themeName)
    {
        return self::info()->isAvailable($themeName, 'theme');
    }

    /**
     * Get info from xartheme.php
     */
    public static function getFileInfo($themeOsDir)
    {
        return self::info()->getFileInfo($themeOsDir, 'theme');
    }

    /**
     * Load a theme's base information
     */
    public static function getBaseInfo($themeName)
    {
        return self::info()->getBaseInfo($themeName, 'theme');
    }

    public static function getNoCache()
    {
        return self::info()->noCacheTheme;
    }

    /**
     * Set noCache
     * @param bool $noCache
     * @return void
     */
    public static function setNoCache($noCache)
    {
        self::info()->noCacheTheme = (bool) $noCache;
    }
}
