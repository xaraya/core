<?php
/**
 * Interface declaration for module user vars
 *
 */
sys::import('xaraya.variables');
sys::import('xaraya.variables.moditem');

/**
 * Class to implement the interface to module user vars
 *
 * @todo decide on sessionvars for anonymous users
 * @todo when yes on the previous todo, remember promotion of the vars
 */
class xarModUserVars extends xarModItemVars implements IxarModItemVars
{
    /**
     * Get a user variable for a module
     *
     * This is basically the same as xarModVars::set(), but this
     * allows for getting variable values which are tied to
     * a specific item for a certain module. Typical usage
     * is storing user preferences.
     *
     * @access public
     * @param  string  $scope   The name of the module
     * @param  string  $name    The name of the variable to get
     * @param  integer $itemid  User id for which value is to be retrieved
     * @return mixed The value of the variable or void if variable doesn't exist.
     * @see  xarModVars::get()
     * @todo Mrb : Add caching?
     */
    static function get($scope, $name, $itemid = null)
    {
        // If uid not specified take the current user
        if ($itemid == NULL) $itemid = xarUserGetVar('uid');

        // Anonymous user always uses the module default setting
        if ($itemid == _XAR_ID_UNREGISTERED) return xarModVars::get($scope, $name);
        return parent::get($scope, $name, $itemid);
    }

    /**
     * Set a user variable for a module
     *
     * This is basically the same as xarModVars::set(), but this
     * allows for setting variable values which are tied to
     * a specific user for a certain module. Typical usage
     * is storing user preferences.
     * Only deviations from the module vars are stored.
     *
     * @access public
     * @param  string  $scope   The name of the module to set a user variable for
     * @param  string  $name    The name of the variable to set
     * @param  mixed   $value   Value to set the variable to.
     * @param  integer $itemid  User id for which value needs to be set
     * @return bool true on success false on failure
     * @throws EmptyParameterException
     * @see xarModVars::set()
     * @todo Add caching?
     */
    static function set($scope, $name, $value, $itemid = null)
    {
        // If no uid specified assume current user
        if ($itemid == null) $itemid = xarUserGetVar('uid');

        // For anonymous users no preference can be set
        // MrB: should we raise an exception here?
        if ($itemid == _XAR_ID_UNREGISTERED) return false;

        return parent::set($scope, $name, $value, $itemid);
    }

    /**
     * Delete a user variable for a module
     *
     * This is the same as xarModVars::delete() but this allows
     * for deleting a specific user variable, effectively
     * setting the value for that user to the default setting
     *
     * @access public
     * @param  string  $scope The name of the module to set a variable for
     * @param  string  $name  The name of the variable to set
     * @param  integer $itemid User id of the user to delete the variable for.
     * @return bool true on success
     * @see xarModVars::delete()
     * @todo Add caching?
     */
    static function delete($scope, $name, $itemid = null)
    {
        // If uid is not set assume current user
        if ($itemid == null) $itemid = xarUserGetVar('uid');

        // Deleting for anonymous user is useless return true
        // MrB: should we continue, can't harm either and we have
        //      a failsafe that records are deleted, bit dirty, but
        //      it would work.
        if ($itemid == _XAR_ID_UNREGISTERED ) return true;

        return parent::delete($scope, $name, $itemid);
    }
}
?>
