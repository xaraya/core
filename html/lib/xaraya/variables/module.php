<?php

/**
 * Module variable handling
 *
 * @package core\variables
 * @subpackage variables
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

use Xaraya\Services\Modules\VarsHelper;
use Xaraya\Services\xar;

/**
 * Build upon IxarVars to define interface for ModVars
 */
interface IxarModVars extends IxarVars
{
    public static function getID($scope, $name);
    public static function delete_all($scope);
}

/**
 * Class to handle module variables
 * @deprecated 2.8.4 use xar::mod()->*Var() instead
 */
class xarModVars extends xarVars implements IxarModVars
{
    protected static ?VarsHelper $modvars = null;

    protected static function modvars()
    {
        if (!isset(self::$modvars)) {
            $modvars = xar::getServicesClass()->service('modules.vars');
            assert($modvars instanceof VarsHelper);
            self::$modvars = $modvars;
        }
        return self::$modvars;
    }

    /**
     * Get a module variable
     *
     * @param  string $scope The name of the module
     * @param  string $name  The name of the variable
     * @param  mixed  $value If a default value should be returned, it can be passed in.
     * @return mixed The value of the variable or void if variable doesn't exist
     * @throws EmptyParameterException
     */
    public static function get($scope, $name, $default = null)
    {
        // @checkme this doesn't support a default value - check in caller
        return self::modvars()->get($scope, $name) ?? $default;
    }

    /**
     * PreLoad all module variables for a particular module
     *
     * @param  string $scope Module name
     * @return bool|void true on success
     * @throws EmptyParameterException
     * @todo  This has some duplication with config.php
     */
    public static function preload($scope)
    {
        return self::modvars()->preload($scope);
    }

    /**
     * Cache all module variables for a particular module (if CoreCache.Preload is enabled for it)
     * @param  string $scope Module name
     * @param ?string $source
     * @return void
     */
    public static function cache($scope, $source = null)
    {
        return self::modvars()->cache($scope, $source);
    }

    /**
     * Set a module variable
     *
     * @param  string $scope The name of the module
     * @param  string $name  The name of the variable
     * @param  mixed  $value The value of the variable
     * @return bool true on success
     * @throws EmptyParameterException
     * @todo  We could delete the item vars for the module with the new value to save space?
     */
    public static function set($scope, $name, $value)
    {
        return self::modvars()->set($scope, $name, $value);
    }

    /**
     * Delete a module variable
     *
     * @param  string $scope The name of the module
     * @param  string $name  The name of the variable
     * @return bool true on success
     * @throws EmptyParameterException
     * @todo Add caching for item variables?
     */
    public static function delete($scope, $name)
    {
        return self::modvars()->delete($scope, $name);
    }

    /**
     * Delete all module variables
     *
     * @param  string $scope The name of the module
     * @return bool true on success
     * @throws EmptyParameterException, SQLException
     * @todo Add caching for item variables?
     */
    public static function delete_all($scope)
    {
        return self::modvars()->flush($scope);
    }

    /**
     * Support function for xarMod*UserVar functions
     *
     * private function which delivers a module user variable
     * id based on the module name and the variable name
     *
     * @param  string $scope The name of the module
     * @param  string $name  The name of the variable
     * @return int|void identifier for the variable
     * @throws EmptyParameterException
     * @see xar::mod()->getUserVar(), xar::mod()->setUserVar(), xar::mod()->delUserVar()
     */
    public static function getID($scope, $name)
    {
        return self::modvars()->getID($scope, $name);
    }
}
