<?php

/**
 * Call an installer function
 *
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */

class xarInstall extends xarObject
{
    /**
     * Call an installer function.
     *
     * @author John Robeson
     * @author Marcel van der Boom <marcel@hsdev.com>
     * This function is similar to xar::mod()->guiFunc but simplified.
     * We need this because during install we cant have the module
     * subsystem online directly, so we need a direct way of calling
     * the admin functions of the installer. The actual functions
     * called adhere to normal Xaraya module functions, so we can use
     * the installer later on when xaraya is installed
     *
     * @access public
     * @param string $funcName specific function to run
     * @param array<string, mixed> $args argument array
     * @return string|void output display string
     * @throws FunctionNotFoundException
     */
    public static function func($funcName = 'main', $args = [])
    {
        $modName = 'installer';
        $modType = 'admin';

        // Get module class for installer module
        $namespace = 'Xaraya\\Modules\\' . ucfirst($modName);
        $className = $namespace . '\\Module';
        if (!class_exists($className)) {
            // try to load it
            xarInstall::load($funcName);
            if (!class_exists($className)) {
                throw new ClassNotFoundException($className);
            }
        }
        $module = new $className($modName);
        // Get callable method for modType gui funcName
        $modFunc = $module->getCallableMethod($modType, $funcName);
        if (empty($modFunc)) {
            throw new FunctionNotFoundException($funcName);
        }

        // Load the translations file
        $file = sys::code() . 'modules/' . $modName . '/xar' . $modType . '/' . strtolower($funcName) . '.php';
        if (!xarMLS::loadTranslations($file)) {
            return;
        }

        $tplData = $modFunc($args);
        if (!is_array($tplData)) {
            return $tplData;
        }

        $templateName = '';
        if (isset($tplData['_bl_template'])) {
            $templateName = $tplData['_bl_template'];
        }

        return xarTpl::module($modName, $modType, $funcName, $tplData, $templateName);
    }

    /**
     * support module class methods
     */
    public static function apiFunc($funcName = 'main', $args = [])
    {
        $modName = 'installer';
        $modType = 'admin';

        // Get module class for installer module
        $namespace = 'Xaraya\\Modules\\' . ucfirst($modName);
        $className = $namespace . '\\Module';
        if (!class_exists($className)) {
            // attempt to load the install api
            xarInstall::apiLoad();
            if (!class_exists($className)) {
                throw new ClassNotFoundException($className);
            }
        }
        $module = new $className($modName);
        // Get callable method for modType api funcName
        $modAPIFunc = $module->getCallableMethod($modType . 'api', $funcName);
        if (empty($modAPIFunc)) {
            throw new FunctionNotFoundException($funcName);
        }

        // Load the translations file
        $file = sys::code() . 'modules/' . $modName . '/xar' . $modType . 'api/' . strtolower($funcName) . '.php';
        if (!xarMLS::loadTranslations($file)) {
            return;
        }

        return $modAPIFunc($args);
    }

    /**
     * Loads the modType API for installer identified by modName.
     *
     * @access public
     * @param string modName registered name of the module
     * @param string modType type of functions to load
     * @return boolean true on success, false on failure
     * @throws FileNotFoundException
     */
    public static function apiLoad()
    {
        static $loadedAPICache = [];

        $modName    = 'installer';
        $modOsDir   = 'installer';
        $modType  = 'admin';

        if (isset($loadedAPICache[strtolower("$modName$modType")])) {
            // Already loaded from somewhere else
            return true;
        }
        // Use autoload() for module class methods
        sys::autoload();

        $loadedAPICache[strtolower("$modName$modType")] = true;

        return true;
    }

    /**
     * Loads the modType of installer identified by modName.
     *
     * @access public
     * @return boolean true on success, false on failure
     * @throws EmptyParameterException
     */
    public static function load($func)
    {
        static $loadedModuleCache = [];

        $modName = 'installer';
        $modType = 'admin';

        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        if (isset($loadedModuleCache[strtolower("$modName$modType")])) {
            // Already loaded from somewhere else
            return true;
        }
        // Use autoload() for module class methods
        sys::autoload();

        // Load the module files
        $modOsType = xarVarPrep::forOS($modType);
        $modOsDir = 'installer';

        $osfile = sys::code() . "modules/$modOsDir/xar$modOsType/$func.php";

        $loadedModuleCache[strtolower("$modName$modType")] = true;

        // Load the module translations files
        $res = xarMLS::loadTranslations($osfile);
        return true;
    }
}
