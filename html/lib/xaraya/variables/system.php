<?php

sys::import('xaraya.variables');
use Xaraya\Services\xar;
use Xaraya\Services\SystemService;

/**
 * Class to handle system variables
 *
 * These variables come from a config file, typically config.system.php
 * in the var dir. Most, if not all are REQUIRED. This file should not depend
 * on anything else but that file and xarCore.php.
 *
 * @package core\variables
 * @subpackage variables
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marcel van der Boom <mrb@hsdev.com>
 * @deprecated 2.8.6 use xar::sysConfig() instead
 **/
class xarSystemVars extends xarVars implements IxarVars
{
    private static $KEY = 'System.Variables'; // const cannot be private :-(
    protected static ?SystemService $sysConfig = null;

    protected static function sysConfig()
    {
        if (!isset(self::$sysConfig)) {
            self::$sysConfig = xar::getServicesClass()->sysConfig();
        }
        return self::$sysConfig;
    }

    /**
     * Gets a core system variable
     *
     * @param  string|null $scope base filename which holds the system variables
     * @param  string $name name of core system variable to get
     * @throws Exception
     */
    public static function get($scope, $name)
    {
        $scope ??= sys::CONFIG;
        if ($scope !== sys::CONFIG) {
            $sysConfig = xar::getServicesClass()->sysConfig($scope);
            return $sysConfig->getVar($name);
        }
        return self::sysConfig()->getVar($name);
    }

    public static function set($scope, $name, $value)
    {
        $scope ??= sys::CONFIG;
        if ($scope !== sys::CONFIG) {
            $sysConfig = xar::getServicesClass()->sysConfig($scope);
            return $sysConfig->setVar($name, $value);
        }
        return self::sysConfig()->setVar($name, $value);
    }

    public static function delete($scope, $name)
    {
        $scope ??= sys::CONFIG;
        if ($scope !== sys::CONFIG) {
            $sysConfig = xar::getServicesClass()->sysConfig($scope);
            return $sysConfig->getVar($name);
        }
        return self::sysConfig()->delVar($name);
    }
}
