<?php
/**
 * Module handling subsystem
 *
 * @package core\modules\legacy
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo the double headed theme/module stuff needs to go, a theme is not a module
 */

/**
 * State of modules
 * @todo do we really need 13 module states?
 * @deprecated
 */
define('XARMOD_STATE_UNINITIALISED', 1);
define('XARMOD_STATE_INACTIVE', 2);
define('XARMOD_STATE_ACTIVE', 3);
define('XARMOD_STATE_MISSING_FROM_UNINITIALISED', 4);
define('XARMOD_STATE_UPGRADED', 5);
// This isn't a module state, but only a convenient definition to indicates,
// where it's used, that we don't care about state, any state is good
define('XARMOD_STATE_ANY', 0);
// <andyv> added an extra superficial state, need it for the list filter because
// some ppl requested not to see modules which are not initialised FR/BUG #252
// now we define 'Installed' state as all except 'uninitialised'
// in fact we dont even need a record as it's an exact reverse of state 1
// tell me if there is something wrong with my (twisted) logic ;-)
define('XARMOD_STATE_INSTALLED', 6);
define('XARMOD_STATE_MISSING_FROM_INACTIVE', 7);
define('XARMOD_STATE_MISSING_FROM_ACTIVE', 8);
define('XARMOD_STATE_MISSING_FROM_UPGRADED', 9);
// Bug 1664 - Add  module states for modules that have a db version
// that is greater than the file version
define('XARMOD_STATE_ERROR_UNINITIALISED', 10);
define('XARMOD_STATE_ERROR_INACTIVE', 11);
define('XARMOD_STATE_ERROR_ACTIVE', 12);
define('XARMOD_STATE_ERROR_UPGRADED', 13);

/**
 * Define the theme here for now as well
 * @deprecated
 */
define('XARTHEME_STATE_UNINITIALISED', 1);
define('XARTHEME_STATE_INACTIVE', 2);
define('XARTHEME_STATE_ACTIVE', 3);
define('XARTHEME_STATE_MISSING_FROM_UNINITIALISED', 4);
define('XARTHEME_STATE_UPGRADED', 5);
define('XARTHEME_STATE_ANY', 0);
define('XARTHEME_STATE_INSTALLED', 6);
define('XARTHEME_STATE_MISSING_FROM_INACTIVE', 7);
define('XARTHEME_STATE_MISSING_FROM_ACTIVE', 8);
define('XARTHEME_STATE_MISSING_FROM_UPGRADED', 9);

/*
    Bring in the module variables to maintain interface compatibility for now
*/
sys::import('xaraya.variables.module');
sys::import('xaraya.variables.moduser');
/**
 * Wrapper functions to support Xaraya 1 API for modvars and moduservars
 * @deprecated
**/
/**
 * Legacy call
 * @uses xarModVars::getID()
 * @deprecated
 */
function xarModGetVarId($modName, $name)             {   return xarModVars::getID($modName, $name);       }

/**
 * Legacy call
 * @uses xarModUserVars::delete()
 * @deprecated
 */
function xarModDelUserVar($modName, $name, $id=NULL) {   return xarModUserVars::delete($modName, $name, $id);      }

/**
 * Legacy call
 * @uses xarURL::encode()
 * @deprecated
 */
function xarMod__URLencode($data, $type = 'getname')
{
    return xarURL::encode($data, $type);
}

/**
 * Legacy call
 * @uses xarURL::nested()
 * @deprecated
 */
function xarMod__URLnested($args, $prefix)
{
    return xarURL::nested($args, $prefix);
}

/**
 * Legacy call
 * @uses xarURL::addParametersToPath()
 * @deprecated
 */
function xarMod__URLaddParametersToPath($args, $path, $pini, $psep)
{
    return xarURL::addParametersToPath($args, $path, $pini, $psep);
}

/**
 * Legacy call
 * @uses xarController::URL()
 * @deprecated
 */
function xarModURL($modName=NULL, $modType='user', $funcName='main', $args=array(), $generateXMLURL=NULL, $fragment=NULL, $entrypoint=array())
{   
    return xarController::URL($modName, $modType, $funcName, $args, $generateXMLURL, $fragment, $entrypoint); 
}

// (Module) Hooks handling subsystem - moved from modules to hooks for (future) clarity

/**
 * Wrapper functions to support Xaraya 1 API for module managment
 * @deprecated
 */
/**
 * Legacy call
 * @uses xarMod::getName()
 * @deprecated
 */
function xarModGetName()
{   return xarMod::getName(); }

/**
 * Legacy call
 * @uses xarMod::getName()
 * @deprecated
 */
function xarModGetNameFromID($regid)
{   return xarMod::getName($regid); }

/**
 * Legacy call
 * @uses xarMod::getDisplayName()
 * @deprecated
 */
function xarModGetDisplayableName($modName = NULL, $type = 'module')
{   return xarMod::getDisplayName($modName, $type); }

/**
 * Legacy call
 * @uses xarMod::getDisplayDescription()
 * @deprecated
 */
function xarModGetDisplayableDescription($modName = NULL, $type = 'module')
{   return xarMod::getDisplayDescription($modName,$type); }

/**
 * Legacy call
 * @uses xarMod::getRegID()
 * @deprecated
 */
function xarModGetIDFromName($modName, $type = 'module')
{   return xarMod::getRegID($modName, $type); }

/**
 * Legacy call
 * @uses xarMod::getInfo()
 * @deprecated
 */
function xarModGetInfo($modRegId, $type = 'module')
{   return xarMod::getInfo($modRegId, $type); }

/**
 * Legacy call
 * @uses xarMod::getBaseInfo()
 * @deprecated
 */
function xarMod_getBaseInfo($modName, $type = 'module')
{   return xarMod::getBaseInfo($modName, $type); }

/**
 * Legacy call
 * @uses xarMod::getFileInfo()
 * @deprecated
 */
function xarMod_getFileInfo($modOsDir, $type = 'module')
{   return xarMod::getFileInfo($modOsDir, $type); }

/**
 * Legacy call
 * @uses xarMod::loadDbInfo()
 * @deprecated
 */
function xarMod__loadDbInfo($modName, $modDir)
{   return xarMod::loadDbInfo($modName, $modDir); }

/**
 * Legacy call
 * @uses xarMod::loadDbInfo()
 * @deprecated
 */
function xarModDBInfoLoad($modName, $modDir = NULL, $type = 'module')
{   return xarMod::loadDbInfo($modName, $modDir, $type); }

/**
 * Legacy call
 * @uses xarMod::getState()
 * @deprecated
 */
function xarMod_getState($modRegId, $modMode = XARMOD_MODE_PER_SITE, $type = 'module')
{   return xarMod::getState($modRegId, $modMode, $type); }

/**
 * Legacy call
 * @uses xarMod::isAvailable()
 * @deprecated
 */
function xarModIsAvailable($modName, $type = 'module')
{   return xarMod::isAvailable($modName, $type); }

/**
 * Legacy call
 * @uses xarMod::guiFunc()
 * @deprecated
 */
function xarModFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{   return xarMod::guiFunc($modName, $modType, $funcName, $args); }

/**
 * Legacy call
 * @uses xarMod::apiFunc()
 * @deprecated
 */
function xarModAPIFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{   return xarMod::apiFunc($modName, $modType, $funcName, $args,'api'); }

/**
 * Legacy call
 * @uses xarMod::load()
 * @deprecated
 */
function xarModLoad($modName, $modType = 'user')
{   return xarMod::load($modName, $modType); }

/**
 * Legacy call
 * @uses xarMod::apiLoad()
 * @deprecated
 */
function xarModAPILoad($modName, $modType = 'user')
{   return xarMod::apiLoad($modName, $modType); }


/**
 * Wrapper functions to support Xaraya 1 API for module aliases
 * @deprecated
 */
/**
 * Legacy call
 * @uses xarModAlias::resolve()
 * @deprecated
 */
function xarModGetAlias($alias) { return xarModAlias::resolve($alias);}
/**
 * Legacy call
 * @uses xarModAlias::set()
 * @deprecated
 */
function xarModSetAlias($alias, $modName) { return xarModAlias::set($alias,$modName);}
/**
 * Legacy call
 * @uses xarModAlias::delete()
 * @deprecated
 */
function xarModDelAlias($alias, $modName) { return xarModAlias::delete($alias,$modName);}

