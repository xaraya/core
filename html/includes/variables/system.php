<?php

sys::import('variables');
interface IxarSystemVars extends IxarVars
{}

class xarSystemVars implements IxarSystemVars
{
    /**
     * Gets a core system variable
     *
     * System variables are REQUIRED to be set, if they cannot be found
     * the system cannot continue. Only use variables for this which are
     * absolutely necessary to be set. Otherwise use other types of variables
     *
     * @access protected
     * @static systemVars array
     * @param string name name of core system variable to get
     * @throws FileNotFoundException, VariableNotFoundException
     * @todo check if we need both the isCached and static
     */
    public static function get($scope, $name)
    {
        static $systemVars = null;

        if (xarCore::isCached('Core.getSystemVar', $name)) {
            return xarCore::getCached('Core.getSystemVar', $name);
        }
        if (!isset($systemVars)) {
            $fileName = xarCoreGetVarDirPath() . '/' . XARCORE_CONFIG_FILE;
            if (!file_exists($fileName)) {
                throw new FileNotFoundException($fileName);
            }
            // Make stuff from config.system.php available
            // NOTE: we can not use sys::import since the variable scope would be wrong.
            include $fileName;
            $systemVars = $systemConfiguration;
        }

        if (!isset($systemVars[$name])) {
            throw new VariableNotFoundException($name,"xarCore_getSystemVar: Unknown system variable: '#(1)'.");
        }

        xarCore::setCached('Core.getSystemVar', $name, $systemVars[$name]);

        return $systemVars[$name];
    }
    
    public static function set($scope, $name, $value)
    {
        // Not supported
        return false;
    }
    
    public static function delete($scope, $name)
    {
        // Not supported
        return false;
    }
}
?>