<?php

/**
 * Configuration variable handling
 *
 * @package core\variables
 * @subpackage variables
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marcel van der Boom <mrb@hsdev.com>
 */

sys::import('xaraya.variables');
sys::import('xaraya.services.xar');
use Xaraya\Services\xar;
use Xaraya\Services\ConfigService;

/**
 * Class to handle configuration variables
 *
 * @todo if core was module 0 this could be a whole lot simpler by derivation (or if all config variables were moved to a module)
 */
class xarConfigVars extends xarVars implements IxarVars
{
    private static $KEY = 'Config.Variables'; // const cannot be private :-(
    protected static ?ConfigService $config = null;

    protected static function config()
    {
        if (!isset(self::$config)) {
            self::$config = xar::getServicesClass()->config();
        }
        return self::$config;
    }

    /**
     * Sets a configuration variable.
     *
     * @param string|null $scope not used
     * @param  string $name the name of the variable
     * @param  mixed  $value (array,integer or string) the value of the variable
     * @return boolean true on success, or false if you're trying to set unallowed variables
     * @todo return states that it should return false if we're setting
     *       unallowed variables.. there is no such code to do that in the function
     */
    public static function set($scope, $name, $value)
    {
        return self::config()->setVar($name, $value);
    }

    /**
     * Gets a configuration variable.
     *
     * @param string|null $scope not used
     * @param string $name  the name of the variable
     * @return mixed value of the variable(string), or void if variable doesn't exist
     * @todo do we need these aliases anymore ?
     * @todo the vars which are not in the database should probably be systemvars, not configvars
     * @todo bench the preloading
     */
    public static function get($scope, $name, $value = null)
    {
        return self::config()->getVar($name, $value);
    }

    /**
     * Summary of delete
     * @param string|null $scope not used
     * @param string $name  the name of the variable
     * @return bool
     */
    public static function delete($scope, $name)
    {
        return self::config()->delVar($name);
    }

    /**
     * Cache all site configuration variables (if CoreCache.Preload is enabled for it)
     * @param string|null $source not used
     * @return void
     */
    public static function cache($source = null)
    {
        return self::config()->cacheVars($source);
    }
}
