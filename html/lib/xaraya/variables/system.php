<?php
sys::import('variables');
/**
 * Class to handle system variables
 * 
 * These variables come from a config file, typically config.system.php
 * in the var dir. Most, if not all are REQUIRED. This file should not depend
 * on anything else but that file and xarCore.php.
 *
 * @package variables
 * @author Marcel van der Boom <mrb@hsdev.com>
**/
class xarSystemVars extends xarVars implements IxarVars
{
    private static $KEY = 'System.Variables'; // const cannot be private :-(
    private static $systemVars = null;
        
    /**
     * Gets a core system variable
     *
     * @param  string $scope base filename which holds the system variables
     * @param  string $name name of core system variable to get
     * @throws Exception
     */
    public static function get($scope, $name)
    {
        if(!isset($scope))
            $scope = sys::CONFIG;
            
        if (!isset(self::$systemVars)) 
            self::preload($scope);

        if (!isset(self::$systemVars[$name])) 
            throw new Exception("xarSystemVars: Unknown system variable: '$name'.");

        return self::$systemVars[$name];
    }
    
    public static function set($scope, $name, $value)
    {
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
        $fileName = sys::varpath() . '/' . $scope;
        if (!file_exists($fileName)) 
            throw new Exception("The system config file '$fileName' could not be found.");

        // Make stuff from config.system.php available
        // NOTE: we can not use sys::import since the variable scope would be wrong.
        include $fileName;
        self::$systemVars = $systemConfiguration;
    }
}
?>