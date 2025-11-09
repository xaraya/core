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
 * @deprecated 2.8.6 use xar::system() instead
 **/
class xarSystemVars extends xarVars implements IxarVars
{
    private static $KEY = 'System.Variables'; // const cannot be private :-(
    protected static ?SystemService $system = null;

    protected static function system()
    {
        if (!isset(self::$system)) {
            self::$system = xar::getServicesClass()->system();
        }
        return self::$system;
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
        return self::system()->getVar($scope, $name);
    }

    public static function set($scope, $name, $value)
    {
        return self::system()->setVar($scope, $name, $value);
    }

    public static function delete($scope, $name)
    {
        return self::system()->delVar($scope, $name);
    }
}
