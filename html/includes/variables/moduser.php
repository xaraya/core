<?php
/**
 * Interface declaration for module user vars
 *
 */
sys::import('variables');
interface IxarModUserVars extends IxarVars
{}

/**
 * Class to implement the interface to module user vars
 *
 * @todo decide on sessionvars for anonymous users
 * @todo when yes on the previous todo, remember promotion of the vars
 */
class xarModUserVars implements IxarModUserVars
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
     * @param modName The name of the module
     * @param name    The name of the variable to get
     * @param uid     User id for which value is to be retrieved
     * @return mixed Teh value of the variable or void if variable doesn't exist.
     * @throws EmptyParameterException
     * @see  xarModVars::get()
     * @todo Mrb : Add caching?
     */
    static function get($modName, $name, $uid = NULL, $prep = NULL)
    {
        // Module name and variable name are necessary
        if (empty($modName)) throw new EmptyParameterException('modName');

        // If uid not specified take the current user
        if ($uid == NULL) $uid = xarUserGetVar('uid');

        // Anonymous user always uses the module default setting
        if ($uid== _XAR_ID_UNREGISTERED) return xarModVars::get($modName,$name);

        return xarVar__GetVarByAlias($modName, $name, $uid, $prep, $type = 'moditemvar');
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
     * @param modName The name of the module to set a user variable for
     * @param name    The name of the variable to set
     * @param value   Value to set the variable to.
     * @param uid     User id for which value needs to be set
     * @return bool true on success false on failure
     * @throws EmptyParameterException
     * @see xarModVars::set()
     * @todo Add caching?
     */
    static function set($modName, $name, $value, $uid=NULL)
    {
        // Module name and variable name are necessary
        if (empty($modName)) throw new EmptyParameterException('modName');

        // If no uid specified assume current user
        if ($uid == NULL) $uid = xarUserGetVar('uid');

        // For anonymous users no preference can be set
        // MrB: should we raise an exception here?
        if ($uid == _XAR_ID_UNREGISTERED) return false;

        return xarVar__SetVarByAlias($modName, $name, $value, $prime = NULL, $description = NULL, $uid, $type = 'moditemvar');
    }

    /**
     * Delete a user variable for a module
     *
     * This is the same as xarModVars::delete() but this allows
     * for deleting a specific user variable, effectively
     * setting the value for that user to the default setting
     *
     * @access public
     * @param modName The name of the module to set a variable for
     * @param name    The name of the variable to set
     * @param uid     User id of the user to delete the variable for.
     * @return bool true on success
     * @throws EmptyParameterException
     * @see xarModVars::delete()
     * @todo Add caching?
     */
    static function delete($modName, $name, $uid=NULL)
    {
        // ModName and name are required
        if (empty($modName)) throw new EmptyParameterException('modName');

        // If uid is not set assume current user
        if ($uid == NULL) $uid = xarUserGetVar('uid');

        // Deleting for anonymous user is useless return true
        // MrB: should we continue, can't harm either and we have
        //      a failsafe that records are deleted, bit dirty, but
        //      it would work.
        if ($uid == _XAR_ID_UNREGISTERED ) return true;

        return xarVar__DelVarByAlias($modName, $name, $uid, $type = 'moditemvar');
    }
}
?>