<?php

/**
 * @package core\variables
 * @subpackage variables
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
/**
 * Interface declaration for module user vars
 *
 */
sys::import('xaraya.variables');
sys::import('xaraya.variables.moditem');
sys::import('xaraya.services.xar');
use Xaraya\Services\xar;
use Xaraya\Services\Modules\UserVarsHelper;

/**
 * Class to implement the interface to module user vars
 *
 * @todo decide on sessionvars for anonymous users
 * @todo when yes on the previous todo, remember promotion of the vars
 * @deprecated 2.8.4 use xar::mod()->*UserVar() instead
 */
class xarModUserVars extends xarModItemVars implements IxarModItemVars
{
    protected static ?UserVarsHelper $uservars = null;

    protected static function uservars()
    {
        if (!isset(self::$uservars)) {
            $uservars = xar::getServicesClass()->service('modules.user');
            assert($uservars instanceof UserVarsHelper);
            self::$uservars = $uservars;
        }
        return self::$uservars;
    }

    /**
     * Get a user variable for a module
     *
     * This is basically the same as xar::mod()->getVar(), but this
     * allows for getting variable values which are tied to
     * a specific item for a certain module. Typical usage
     * is storing user preferences.
     *
     *
     * @param  string  $scope   The name of the module
     * @param  string  $name    The name of the variable to get
     * @param  integer $itemid  User id for which value is to be retrieved
     * @return mixed The value of the variable or void if variable doesn't exist.
     * @see  xar::mod()->getVar()
     * @todo Mrb : Add caching?
     */
    public static function get($scope, $name, $itemid = null)
    {
        return self::uservars()->get($scope, $name, $itemid);
    }

    /**
     * Set a user variable for a module
     *
     * This is basically the same as xar::mod()->setVar(), but this
     * allows for setting variable values which are tied to
     * a specific user for a certain module. Typical usage
     * is storing user preferences.
     * Only deviations from the module vars are stored.
     *
     *
     * @param  string  $scope   The name of the module to set a user variable for
     * @param  string  $name    The name of the variable to set
     * @param  mixed   $value   Value to set the variable to.
     * @param  integer $itemid  User id for which value needs to be set
     * @return boolean true on success false on failure
     * @throws EmptyParameterException
     * @see xar::mod()->setVar()
     * @todo Add caching?
     */
    public static function set($scope, $name, $value, $itemid = null)
    {
        return self::uservars()->set($scope, $name, $value, $itemid);
    }

    /**
     * Delete a user variable for a module
     *
     * This is the same as xar::mod()->delVar() but this allows
     * for deleting a specific user variable, effectively
     * setting the value for that user to the default setting
     *
     *
     * @param  string  $scope The name of the module to set a variable for
     * @param  string  $name  The name of the variable to set
     * @param  integer $itemid User id of the user to delete the variable for.
     * @return boolean true on success
     * @see xar::mod()->delVar()
     * @todo Add caching?
     */
    public static function delete($scope, $name, $itemid = null)
    {
        return self::uservars()->delete($scope, $name, $itemid);
    }
}
