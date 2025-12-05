<?php

/**
 * Themes available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.9.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarTheme;
use EmptyParameterException;
use Exception;

/**
 * For documentation purposes only - available via ThemesTrait
 */
interface ThemesInterface extends ServiceInterface
{
    public const SLICE = 'themes';

    /**
     * Gets theme registry ID given its name
     */
    public function getIDFromName($themeName, $id = 'regid');

    /**
     * get registry ID for theme
     */
    public function getRegID($themeName);

    /**
     * get information on theme
     */
    public function getInfo($regId);

    /**
     * checks if a theme is installed and its state is STATE_ACTIVE
     */
    public function isAvailable($themeName);

    /**
     * Get info from xartheme.php
     */
    public function getFileInfo($themeOsDir);

    /**
     * Load a theme's base information
     */
    public function getBaseInfo($themeName);

    public function getNoCache();

    /**
     * Set noCache
     * @param bool $noCache
     * @return void
     */
    public function setNoCache($noCache);

    /**
     * Get a theme variable
     * @param  string $themeName The name of the theme
     * @param  string $varName  The name of the variable
     * @return mixed The value of the variable or void if variable doesn't exist
     * @deprecated 2.4.1 not used except in kingston theme pages
     */
    public function getVar($themeName, $varName);
}

/**
 * Themes available via methods
 */
trait ThemesTrait
{
    use ServiceTrait;

    private ?Modules\InfoHelper $infoHelper = null;

    public function getInfoHelper(): Modules\InfoHelper
    {
        $this->infoHelper ??= $this->getServicesClass()->service('modules.info');
        return $this->infoHelper;
    }

    /**
     * Gets theme registry ID given its name
     */
    public function getIDFromName($themeName, $id = 'regid')
    {
        if (empty($themeName)) {
            throw new EmptyParameterException('themeName');
        }

        $themeBaseInfo = $this->getInfoHelper()->getBaseInfo($themeName, 'theme');
        if (empty($themeBaseInfo)) {
            return;
        } // throw back

        return $themeBaseInfo[$id];
    }

    /**
     * get registry ID for theme
     */
    public function getRegID($themeName)
    {
        return $this->getInfoHelper()->getRegID($themeName, 'theme');
    }

    /**
     * get information on theme
     */
    public function getInfo($regId)
    {
        return $this->getInfoHelper()->getInfo($regId, 'theme');
    }

    /**
     * checks if a theme is installed and its state is STATE_ACTIVE
     */
    public function isAvailable($themeName)
    {
        return $this->getInfoHelper()->isAvailable($themeName, 'theme');
    }

    /**
     * Get info from xartheme.php
     */
    public function getFileInfo($themeOsDir)
    {
        return $this->getInfoHelper()->getFileInfo($themeOsDir, 'theme');
    }

    /**
     * Load a theme's base information
     */
    public function getBaseInfo($themeName)
    {
        return $this->getInfoHelper()->getBaseInfo($themeName, 'theme');
    }

    public function getNoCache()
    {
        return $this->getInfoHelper()->noCacheTheme;
    }

    /**
     * Set noCache
     * @param bool $noCache
     * @return void
     */
    public function setNoCache($noCache)
    {
        $this->getInfoHelper()->noCacheTheme = (bool) $noCache;
    }

    /**
     * Get a theme variable
     * @param  string $themeName The name of the theme
     * @param  string $varName  The name of the variable
     * @return mixed The value of the variable or void if variable doesn't exist
     * @deprecated 2.4.1 not used except in kingston theme pages
     */
    public function getVar($themeName, $varName)
    {
        try {
            $themeBaseInfo = $this->getInfoHelper()->getBaseInfo($themeName, 'theme');
            $varvalue = $themeBaseInfo['configuration'][$varName];
            return $varvalue;
        } catch (Exception $e) {
            return null;
        }
    }
}

/**
 * Access xarTheme::* methods (isAvailable, getInfo, ...)
 *
 * Available methods:
 * - isAvailable()
 * - getInfo()
 * - ...
 */
class ThemesService implements ThemesInterface
{
    use ThemesTrait;
}
