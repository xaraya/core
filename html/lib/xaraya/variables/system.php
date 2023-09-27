<?php
sys::import('xaraya.variables');
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
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marcel van der Boom <mrb@hsdev.com>
 **/
class xarSystemVars extends xarVars implements IxarVars
{
    private static $KEY = 'System.Variables'; // const cannot be private :-(
    private static $systemVars = null;

    /**
     * Gets a core system variable
     *
     * @param  string|null $scope base filename which holds the system variables
     * @param  string $name name of core system variable to get
     * @throws Exception
     */
    public static function get($scope, $name)
    {
        if(!isset($scope))
            $scope = sys::CONFIG;

        if (!isset(self::$systemVars[$scope]))
            self::preload($scope);

        // We need the system variable; complain if it's not there
        if (!isset(self::$systemVars[$scope][$name]))
            throw new Exception("xarSystemVars: Unknown system variable: '$name'.");

        return self::$systemVars[$scope][$name];
    }

    public static function set($scope, $name, $value)
    {
        // Allow overriding system layout if needed
        if ($scope == sys::LAYOUT) {
            self::$systemVars[$scope][$name] = $value;
            return true;
        }
        // Not supported ?
        return false;
    }

    public static function delete($scope, $name)
    {
        // Not supported ?
        return false;
    }

    private static function preload($scope)
    {
        $fileName = sys::varpath() . '/';
        if ($scope == sys::LOG)  $fileName .= 'logs/';
        $fileName .= $scope;

        // We need the file; complain if it's not there
		if (!file_exists($fileName))
            throw new Exception("The system config file '$fileName' could not be found.");

        // Make stuff from config.system.php available
        // NOTE: we can not use sys::import since the variable scope would be wrong.
        include $fileName;
        /** @phpstan-ignore-next-line */
        self::$systemVars[$scope] = $systemConfiguration;
    }
}
