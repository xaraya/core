<?php
/**
 * File: $Id$
 *
 * Module handling subsystem
 *
 * @package modules
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Jim McDonald
 * @author Marco Canini <m.canini@libero.it>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo Use serialize in module variables?
 */

/**
 * State of modules
 */
define('XARMOD_STATE_UNINITIALISED', 1);
define('XARMOD_STATE_INACTIVE', 2);
define('XARMOD_STATE_ACTIVE', 3);
define('XARMOD_STATE_MISSING', 4);
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

/**
 * Define these here for now
 *
 */
define('XARTHEME_STATE_UNINITIALISED', 1);
define('XARTHEME_STATE_INACTIVE', 2);
define('XARTHEME_STATE_ACTIVE', 3);
define('XARTHEME_STATE_MISSING', 4);
define('XARTHEME_STATE_UPGRADED', 5);
define('XARTHEME_STATE_ANY', 0);

/**
 * Flags for loading APIs
 */
define('XARMOD_LOAD_ONLYACTIVE', 1);
define('XARMOD_LOAD_ANYSTATE', 2);

/*
 * Modules modes
 */
define('XARMOD_MODE_SHARED', 1);
define('XARMOD_MODE_PER_SITE', 2);

define('XARTHEME_MODE_SHARED', 1);
define('XARTHEME_MODE_PER_SITE', 2);


/*
 * Used caches are:
 * Mod.Variables
 * Mod.Infos
 * Mod.BaseInfos
 */

/**
 * Start the module subsystem
 *
 * @access protected
 * @global xarMod_generateShortURLs bool
 * @global xarMod_generateXMLURLs bool
 * @param args['generateShortURLs'] bool
 * @param args['generateXMLURLs'] bool
 * @return bool true
 */
function xarMod_init($args, $whatElseIsGoingLoaded)
{
    // generateShortURLs
    $GLOBALS['xarMod_generateShortURLs'] = $args['enableShortURLsSupport'];
    $GLOBALS['xarMod_generateXMLURLs'] = $args['generateXMLURLs'];

    xarEvt_registerEvent('ModLoad');
    xarEvt_registerEvent('ModAPILoad');

    // Modules Support Tables
    $systemPrefix = xarDBGetSystemTablePrefix();
    $sitePrefix = xarDBGetSiteTablePrefix();

    // New tables
    $tables = array('modules' => $systemPrefix . '_modules',
                    'system/module_states' => $systemPrefix . '_module_states',
                    'system/module_vars' => $systemPrefix . '_module_vars',
                    'site/module_states' => $sitePrefix . '_module_states',
                    'site/module_vars' => $sitePrefix . '_module_vars',
                    'system/module_uservars' => $systemPrefix . '_module_uservars',
                    'site/module_uservars' => $sitePrefix . '_module_uservars',
                    'themes' => $systemPrefix . '_themes',
                    'system/theme_states' => $systemPrefix . '_theme_states',
                    'system/theme_vars' => $systemPrefix . '_theme_vars',
                    'site/theme_states' => $sitePrefix . '_theme_states',
                    'site/theme_vars' => $sitePrefix . '_theme_vars');

    // JC -- Question are these depreciated?
    // Old tables
    $tables['theme_vars']           = $systemPrefix . '_theme_vars';
    $tables['module_vars']           = $systemPrefix . '_module_vars';
    $tables['module_uservars']       = $systemPrefix . '_module_uservars';
    $tables['hooks']                 = $systemPrefix . '_hooks';

    xarDB_importTables($tables);

    // Not feasible here in this way!
    /*
    // Pre-fetch all 'SupportShortURLs' variables if needed
    if (!empty($xarMod_generateShortURLs)) {
        xarMod_getVarsByName('SupportShortURLs');
    }
    */
    return true;
}

/**
 * Get a module variable
 *
 * @access public
 * @param modName The name of the module
 * @param name The name of the variable
 * @return mixed The value of the variable or void if variable doesn't exist
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModGetVar($modName, $name, $prep = NULL)
{
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    return xarVar__GetVarByAlias($modName, $name, $uid = NULL, $prep, $type = 'modvar');
}

/**
 * Set a module variable
 *
 * @access public
 * @param modName The name of the module
 * @param name The name of the variable
 * @param value The value of the variable
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 * @todo  We could delete the user vars for the module with the new value to save space?
 */
function xarModSetVar($modName, $name, $value)
{
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    return xarVar__SetVarByAlias($modName, $name, $value, $prime = NULL, $description = NULL, $uid = NULL, $type = 'modvar');
}


/**
 * Delete a module variable
 *
 * @access public
 * @param modName The name of the module
 * @param name The name of the variable
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 * @todo Add caching for user variables?
 */
function xarModDelVar($modName, $name)
{
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    return xarVar__DelVarByAlias($modName, $name, $uid = NULL, $type = 'modvar');
}

/**
 * Delete all module variables
 *
 * @access public
 * @param modName The name of the module
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 * @todo Add caching for user variables?
 */
function xarModDelAllVars($modName)
{
    if(empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    $modBaseInfo = xarMod_getBaseInfo($modName);
    //if (!isset($modBaseInfo)) return; // throw back

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    // Takes the right table basing on module mode
    if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
        $module_varstable = $tables['system/module_vars'];
        $module_uservarstable = $tables['system/module_uservars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_varstable = $tables['site/module_vars'];
        $module_uservarstable = $tables['site/module_uservars'];
    }

    // PostGres (allows only one table in DELETE)
    // MySql: multiple table delete only from 4.0 up
    // Select the id's which need to be removed
    $sql="SELECT $module_varstable.xar_id FROM $module_varstable WHERE $module_varstable.xar_modid = '" . xarVarPrepForStore($modBaseInfo['systemid']) . "'";
    $result =& $dbconn->Execute($sql);
    if(!$result) return;

    // Seems that at least mysql and pgsql support the scalar IN operator
    $idlist = array();
    while (!$result->EOF) {
        list($id) = $result->fields;
        $result->MoveNext();
        $idlist[] = $id;
    }

    if(count($idlist) != 0 ) {
            $idlist = join(', ',$idlist);

            $sql = "DELETE FROM $module_uservarstable WHERE $module_uservarstable.xar_mvid IN (".$idlist.")";
            $result =& $dbconn->Execute($sql);
            if(!$result) return;
            $result->Close();
    }

    // Now delete the module vars
    $query = "DELETE FROM $module_varstable
              WHERE xar_modid = '" . xarVarPrepForStore($modBaseInfo['systemid']) . "'";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

/**
 * Get a user variable for a module
 *
 * This is basically the same as xarModSetVar, but this
 * allows for getting variable values which are tied to
 * a specific user for a certain module. Typical usage
 * is storing user preferences.
 *
 * @access public
 * @param modName The name of the module
 * @param name    The name of the variable to get
 * @param uid     User id for which value is to be retrieved
 * @return mixed Teh value of the variable or void if variable doesn't exist.
 * @raise  DATABASE_ERROR, BAD_PARAM (indirect)
 * @see  xarModGetVar
 * @todo Mrb : Add caching?
 */
function xarModGetUserVar($modName, $name, $uid = NULL, $prep = NULL)
{
    // Module name and variable name are necessary
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    // If uid not specified take the current user
    if ($uid == NULL) $uid=xarUserGetVar('uid');

    // Anonymous user always uses the module default setting
    if ($uid==_XAR_ID_UNREGISTERED) return xarModGetVar($modName,$name);

    return xarVar__GetVarByAlias($modName, $name, $uid, $prep, $type = 'moduservar');


}

/**
 * Set a user variable for a module
 *
 * This is basically the same as xarModSetVar, but this
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
 * @raise BAD_PARAM
 * @see xarModSetVar
 * @todo Add caching?
 */
function xarModSetUserVar($modName, $name, $value, $uid=NULL)
{
    // Module name and variable name are necessary
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    // If no uid specified assume current user
    if ($uid == NULL) $uid = xarUserGetVar('uid');

    // For anonymous users no preference can be set
    // MrB: should we raise an exception here?
    if ($uid==_XAR_ID_UNREGISTERED) return false;

    return xarVar__SetVarByAlias($modName, $name, $value, $prime = NULL, $description = NULL, $uid, $type = 'moduservar');
}

/**
 * Delete a user variable for a module
 *
 * This is the same as xarModDelVar but this allows
 * for deleting a specific user variable, effectively
 * setting the value for that user to the default setting
 *
 * @access public
 * @param modName The name of the module to set a variable for
 * @param name    The name of the variable to set
 * @param uid     User id of the user to delete the variable for.
 * @return bool true on success
 * @raise BAD_PARAM
 * @see xarModDelVar
 * @todo Add caching?
 */
function xarModDelUserVar($modName, $name, $uid=NULL)
{
    // ModName and name are required
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    // If uid is not set assume current user
    if ($uid == NULL) $uid = xarUserGetVar('uid');

    // Deleting for anonymous user is useless return true
    // MrB: should we continue, can't harm either and we have
    //      a failsafe that records are deleted, bit dirty, but
    //      it would work.
    if ($uid == 0 ) return true;

    return xarVar__DelVarByAlias($modName, $name, $uid, $type = 'moduservar');
}

/**
 * Support function for xarModUser*Var functions
 *
 * private function which delivers a module user variable
 * id based on the module name and the variable name
 *
 * @access private
 * @param modName The name of the module
 * @param name    The name of the variable
 * @return int id identifier for the variable
 * @raise BAD_PARAM
 * @see xarModUserSetVar, xarModUserGetVar, xarModUserDelVar
*/
function xarModGetVarId($modName, $name)
{
    // Module name and variable name are both necesary
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (empty($name)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }

    // Retrieve module info, so we can decide where to look
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back

    if (xarVarIsCached('Mod.GetVarID', $modName . $name)) {
        return xarVarGetCached('Mod.GetVarID', $modName . $name);
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    // Takes the right table basing on module mode
    if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
        $module_varstable = $tables['system/module_vars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_varstable = $tables['site/module_vars'];
    }

    $query = "SELECT xar_id
            FROM $module_varstable
            WHERE xar_modid = '" . xarVarPrepForStore($modBaseInfo['systemid']) . "'
            AND xar_name = '" . xarVarPrepForStore($name) . "'";
    $result =& $dbconn -> Execute($query);

    if(!$result) return;

    // If there was no such thing return
    /*
    if ($result->EOF) {
        $result->Close();
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ID_NOT_FOUND', xarML('modvarid for module #(1) variable #(2)',$modName,$name));
        return;
       }
    */
    list($modvarid) = $result->fields;
    $result->Close();

    xarVarSetCached('Mod.GetVarID', $modName . $name, $modvarid);

    return $modvarid;
}


/**
 * Get module registry ID by name
 *
 * @access public
 * @param modName string The name of the module
 * @param type determines theme or module
 * @return string The module registry ID.
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
 */
function xarModGetIDFromName($modName, $type = 'module')
{
    if (empty($modName)) {
        $msg = xarML('Module or Theme Name #(1) is empty.', $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', new SystemException($msg));
        return;
    }

    switch(strtolower($type)) {
        case 'module':
            default:
            $modBaseInfo = xarMod_getBaseInfo($modName);
            break;
        case 'theme':
            $modBaseInfo = xarMod_getBaseInfo($modName, $type = 'theme');
            break;
    }

    if (!isset($modBaseInfo)) return; // throw back
    // MrB: this is a bit confusing as we also have the 'system' id.
    return $modBaseInfo['regid'];
}

/**
 * Get information on module
 *
 * @access public
 * @param modRegId string module id
 * @param type determines theme or module
 * @return array of module information
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function xarModGetInfo($modRegId, $type = 'module')
{

    if (empty($modRegId) || $modRegId == 0) {
        $msg = xarML('Empty RegId (#(1)) or RegId is equal to 0.', $modRegId);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));return;
    }

    switch(strtolower($type)) {
        case 'module':
            default:
            if (xarVarIsCached('Mod.Infos', $modRegId)) {
                return xarVarGetCached('Mod.Infos', $modRegId);
            }
            break;
        case 'theme':
            if (xarVarIsCached('Theme.Infos', $modRegId)) {
                return xarVarGetCached('Theme.Infos', $modRegId);
            }
            break;
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    switch(strtolower($type)) {
        case 'module':
            default:

            $modulestable = $tables['modules'];
            $query = "SELECT xar_name,
                             xar_directory,
                             xar_mode,
                             xar_version
                      FROM $modulestable
                      WHERE xar_regid = " . xarVarPrepForStore($modRegId);

            break;
        case 'theme':

            $themestable = $tables['themes'];
            $query = "SELECT xar_name,
                             xar_directory,
                             xar_mode,
                             xar_version
                      FROM $themestable
                      WHERE xar_regid = " . xarVarPrepForStore($modRegId);

            break;
    }

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ID_NOT_EXIST', $modRegId);
        return;
    }

    list($modInfo['name'],
         $modInfo['directory'],
         $mode,
         $modInfo['version']) = $result->fields;
    $result->Close();

    $modInfo['regid'] = $modRegId;
    $modInfo['mode'] = (int) $mode;
    $modInfo['displayname'] = xarModGetDisplayableName($modInfo['name']);

    // Shortcut for os prepared directory
    $modInfo['osdirectory'] = xarVarPrepForOS($modInfo['directory']);

    switch(strtolower($type)) {
        case 'module':
            default:
            $modState = xarMod_getState($modInfo['regid'], $modInfo['mode']);
            if (!isset($modState)) $modState = XARMOD_STATE_MISSING; //return; // throw back
            $modInfo['state'] = $modState;

            $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory']);
            break;
        case 'theme':
            $modState = xarMod_getState($modInfo['regid'], $modInfo['mode'], $type = 'theme');
            if (!isset($modState)) $modState = XARTHEME_STATE_MISSING; //return; // throw back
            $modInfo['state'] = $modState;

            $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory'], $type = 'theme');
            break;
    }

    if (!isset($modFileInfo)) {
        // We couldn't get file info, fill in unknowns.
        // The exception for this is logged in getFileInfo
        $modFileInfo['class'] = xarML('Unknown');
        $modFileInfo['description'] = xarML('This module isn\'t installed properly. Not all info could be retrieved');
        $modFileInfo['category'] = xarML('Unknown');
        $modFileInfo['author'] = xarML('Unknown');
        $modFileInfo['contact'] = xarML('Unknown');
        $modFileInfo['dependency'] = array();
        $modFileInfo['xar_version'] = xarML('Unknown');
        $modFileInfo['bl_version'] = xarML('Unknown');
        $modFileInfo['class'] = xarML('Unknown');
        $modFileInfo['author'] = xarML('Unknown');
        $modFileInfo['homepage'] = xarML('Unknown');
        $modFileInfo['email'] = xarML('Unknown');
        $modFileInfo['description'] = xarML('Unknown');
        $modFileInfo['contactinfo'] = xarML('Unknown');
        $modFileInfo['publishdate'] = xarML('Unknown');
        $modFileInfo['license'] = xarML('Unknown');
    }

    $modInfo = array_merge($modFileInfo, $modInfo);

    switch(strtolower($type)) {
        case 'module':
            default:
            xarVarSetCached('Mod.Infos', $modRegId, $modInfo);
            break;
        case 'theme':
            xarVarSetCached('Theme.Infos', $modRegId, $modInfo);
            break;
    }

    return $modInfo;
}

/**
 * Load the modType of module identified by modName.
 *
 * @access private
 * @param modName string - name of module to load
 * @param modType string - type of functions to load
 * @param flags number - flags to modify function behaviour
 * @return mixed
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_NOT_ACTIVE
 */
function xarModPrivateLoad($modName, $modType, $flags = XARMOD_LOAD_ANYSTATE)
{
    static $loadedModuleCache = array();
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    // Make sure we access the cache with lower case key
    //Why to repeat a functionality already present in the PHP functions?
    //Better complain with the PHP team if include_once has any problem...
    if (isset($loadedModuleCache[strtolower("$modName$modType")])) {
        // Already loaded (or tried to) from somewhere else
        return true;
    }

    xarLogMessage("xarModLoad: loading $modName:$modType");

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_ACTIVE', xarML('Unable to find Base Info for Module: #(1)', $modName));
        return; // throw back
    }

    if ($modBaseInfo['state'] != XARMOD_STATE_ACTIVE && !($flags & XARMOD_LOAD_ANYSTATE) ) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_ACTIVE', $modName);
        return;
    }

// TODO: use the xarVarPrepForOS()'d version for $modDir and $modType

    // Load the module files
    $modDir = $modBaseInfo['directory'];

    $fileName = "modules/$modDir/xar$modType.php";

    if (!file_exists($fileName)){
        $fileName = "modules/$modDir/pn$modType.php";
    }

    // Removed the execption.  Causing some wierd results with modules without an api.
    // <nuncanada> But now we wont know if something was loaded or not!
    // <nuncanada> We need some way to find it out.
    if (file_exists($fileName)) {
        xarInclude($fileName);

        // Make sure we access the case with lower case key
        $loadedModuleCache[strtolower("$modName$modType")] = true;
    } elseif (is_dir("modules/$modDir/xar$modType")) {
        // this is OK too - do nothing

        // Make sure we access the case with lower case key
        $loadedModuleCache[strtolower("$modName$modType")] = true;
    } else {
        // this is not OK - we don't have this -> set cache to false

        // Make sure we access the case with lower case key
        $loadedModuleCache[strtolower("$modName$modType")] = false;
    }

    // Load the module translations files
    if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, XARMLS_CTXTYPE_FILE, $modType) === NULL) return;

    //Try to load PN style translations -- Bridge mechanism -- Should disappear later on
    //How to find out what language is being used and what is the correspondent in pn style?
    $fileName = "modules/$modDir/pnlang/eng/$modType.php";
    if (!xarInclude($fileName, XAR_INCLUDE_MAY_NOT_EXIST)) {return;}

    // FIXME: <marco> Remove it when the old language packs are gone
    $fileName = "modules/$modDir/xarlang/eng/$modType.php";
    if (!xarInclude($fileName, XAR_INCLUDE_MAY_NOT_EXIST)) {return;}

    // Load database info
    xarMod__loadDbInfo($modName, $modDir);

    // Module loaded successfully, notify the proper event
    xarEvt_notify($modName, $modType, 'ModLoad', NULL);

    return true;
}



/**
 * Load the modType of module identified by modName.
 *
 * @access public
 * @param modName string - name of module to load
 * @param modType string - type of functions to load
 * @return mixed
 * @raise XAR_SYSTEM_EXCEPTION
 */
function xarModLoad($modName, $modType = 'user')
{
    if (!xarCoreIsApiAllowed($modType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', "modType : $modType for $modName");
        return;
    }
    return xarModPrivateLoad($modName, $modType);
}

/**
 * Load the modType API for module identified by modName.
 *
 * @access public
 * @param modName string registered name of the module
 * @param modType string type of functions to load
 * @return mixed true on success
 * @raise XAR_SYSTEM_EXCEPTION
 */
function xarModAPILoad($modName, $modType = 'user')
{
    if (!xarCoreIsAPIAllowed($modType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', "modType : $modType for $modName");
        return;
    }

    return xarModPrivateLoad($modName, $modType.'api', XARMOD_LOAD_ANYSTATE);
}

/**
 * Load database definition for a module
 *
 * @param modName name of module to load database definition for
 * @param modDir directory that module is in (if known)
 * @param type determines theme or module
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
 */
function xarModDBInfoLoad($modName, $modDir = NULL, $type = 'module')
{
    if (empty($modName)) {
        $msg = xarML('Module Name #(1) is empty.', $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', new SystemException($msg));
        return;
    }
    // Get the directory if we don't already have it
    if (empty($modDir)) {
        switch(strtolower($type)) {
            case 'module':
                default:
                $modBaseInfo = xarMod_getBaseInfo($modName);
                break;
            case 'theme':
                $modBaseInfo = xarMod_getBaseInfo($modName, $type = 'theme');
                break;
        }
        if (!isset($modBaseInfo)) return; // throw back
        $modDir = xarVarPrepForOS($modBaseInfo['directory']);
    } else {
        $modDir = xarVarPrepForOS($modDir);
    }
    switch(strtolower($type)) {
        case 'module':
            default:
            xarMod__loadDbInfo($modName, $modDir);
            return true;
            break;
        case 'theme':
            return true;
            break;
    }
}

/**
 * Call a module GUI function.
 *
 * @access public
 * @param modName string registered name of module
 * @param modType string type of function to run
 * @param funcName string specific function to run
 * @param args array
 * @return mixed The output of the function, or raise an exception
 * @raise BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
 */
function xarModFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (!xarCoreIsApiAllowed($modType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modType');
        return;
    }
    if (empty($funcName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'funcName');
        return;
    }

    // Build function name and call function
    $modFunc = "{$modName}_{$modType}_{$funcName}";
    $found = true;
    $isLoaded = true;
    $msg='';
    if (!function_exists($modFunc)) {
        // attempt to load the module's api
        xarModLoad($modName,$modType);
        // let's check for that function again to be sure
        if (!function_exists($modFunc)) {
            // good thing this information is cached :)
            $modBaseInfo = xarMod_getBaseInfo($modName);
            if (!isset($modBaseInfo)) return; // throw back

            $funcFile = 'modules/'.$modBaseInfo['osdirectory'].'/xar'.$modType.'/'.$funcName.'.php';
            if (!file_exists($funcFile)) {
                $found = false;
            } else {

                ob_start();
                $r = require_once $funcFile;
                $error_msg = strip_tags(ob_get_contents());
                ob_end_clean();

                if (empty($r) || !$r) {
                    $msg = xarML("Could not load function file: [#(1)].\n\n Error Caught:\n #(2)", $funcFile, $error_msg);
                    $isLoaded = false;
                }

                if (!function_exists($modFunc)) {
                    $found = false;
                }
            }
        }
    }
    if (!$found) {
        // if it's loaded but not found, then set the error message to that
        if (!$isLoaded || empty($msg)) {
            $msg = xarML('Module function #(1) doesn\'t exist.', $modFunc);
        }
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST', new SystemException($msg));
        return;
    }

    $tplData = $modFunc($args);
    if (xarExceptionMajor() != XAR_NO_EXCEPTION) return;

    if (!is_array($tplData)) {
        return $tplData;
    }

    $templateName = NULL;
    if (isset($tplData['_bl_template'])) {
        $templateName = $tplData['_bl_template'];
    }

    return xarTplModule($modName, $modType, $funcName, $tplData, $templateName);
}

/**
 * Call a module API function.
 *
 * Using the modules name, type, func, and optional arguments
 * builds a function name by joining them together
 * and using the optional arguments as parameters
 * like so:
 * Ex: modName_modTypeapi_modFunc($args);
 *
 * @access public
 * @param modName string registered name of module
 * @param modType string type of function to run
 * @param funcName string specific function to run
 * @param args array arguments to pass to the function
 * @param throwException boolean optional flag to throw an exception if the function doesn't exist or not (default = 1)
 * @return mixed The output of the function, or false on failure
 * @raise BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
 */
function xarModAPIFunc($modName, $modType = 'user', $funcName = 'main', $args = array(), $throwException = 1)
{
    if (empty($modName)) {
        //die("$modName, $modType, $funcName");
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (!xarCoreIsApiAllowed($modType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modType');
        return;
    }
    if (empty($funcName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'funcName');
        return;
    }

    // Build function name and call function
    $funcName = strtolower($funcName);
    $modAPIFunc = "{$modName}_{$modType}api_{$funcName}";
    $found = true;
    $isLoaded = true;
    if (!function_exists($modAPIFunc)) {
        // attempt to load the module's api
        xarModAPILoad($modName,$modType);
        // let's check for that function again to be sure
        if (!function_exists($modAPIFunc)) {
            // good thing this information is cached :)
            $modBaseInfo = xarMod_getBaseInfo($modName);
            if (!isset($modBaseInfo)) return; // throw back

            $funcFile = 'modules/'.$modBaseInfo['osdirectory'].'/xar'.$modType.'api/'.$funcName.'.php';
            if (!file_exists($funcFile)) {
                $found = false;
            } else {
                ob_start();
                $r = require_once $funcFile;
                $error_msg = strip_tags(ob_get_contents());
                ob_end_clean();

                if (empty($r) || !$r) {
                    $msg = xarML("Could not load function file: [#(1)].\n\n Error Caught:\n #(2)", $funcFile, $error_msg);
                    $isLoaded = false;
                }

                if (!function_exists($modAPIFunc)) {
                    $found = false;
                }
            }
        }
    }
    if (!$found) {
        if ($throwException) {
            if (!$isLoaded || empty($msg)) {
                $msg = xarML('Module API function #(1) doesn\'t exist or couldn\'t be loaded.', $modAPIFunc);
            }

            // MrB: When there is a parse error in the api file we sometimes end up
            // here, the error is never shown !!!! (xmlrpc for example)
            // TODO: the isloaded stuff -should- fix the problem above
            //       someone needs to double check this to be sure
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                            new SystemException($msg));
        }
        return;
    }

    return $modAPIFunc($args);
}

/**
 * Generates an URL that reference to a module function.
 *
 * @access public
 * @global xarMod_generateShortURLs bool
 * @global xarMod_generateXMLURLs bool
 * @param modName string registered name of module
 * @param modType string type of function
 * @param funcName string module function
 * @param string target anchor tag target (ie., somesite.com/index.php?foo=bar#target)
 * @param args array of arguments to put on the URL
 * @return mixed absolute URL for call, or false on failure
 */
function xarModURL($modName = NULL, $modType = 'user', $funcName = 'main', $args = array(), $generateXMLURL = NULL, $target = NULL )
{
    if (empty($modName)) {
        return xarServerGetBaseURL() . 'index.php';
    }

    if (!isset($generateXMLURL)) {
        $generateXMLURL = $GLOBALS['xarMod_generateXMLURLs'];
    }

    if ($GLOBALS['xarMod_generateShortURLs'] &&
        // WATCH OUT! : the encode_shorturl should be in userapi, so don't pass $modType
        xarModGetVar($modName, 'SupportShortURLs') && ($modType == 'user') &&
        xarModAPILoad($modName, 'user')) {

        $encoderArgs = $args;
        $encoderArgs['func'] = $funcName;
        $path = xarModAPIFunc($modName, 'user', 'encode_shorturl', $encoderArgs);
        if (!empty($path)) {
            if ($generateXMLURL) {
                $path = htmlspecialchars($path);
            }

            // FIXME: check if this works with all modules supporting short urls
            if ($target != NULL) {
                $path = "$path#$target";
            }

            return xarServerGetBaseURL() . 'index.php' . $path;
        }
    }
    if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
        // If exceptionId is MODULE_FUNCTION_NOT_EXIST there's no problem,
        // this exception means that the module does not support short urls
        // for this $modType.
        // If exceptionId is MODULE_FILE_NOT_EXIST there's no problem too,
        // this exception means that the module does not have the $modType API.
        if (xarExceptionId() != 'MODULE_FUNCTION_NOT_EXIST' &&
            xarExceptionId() != 'MODULE_FILE_NOT_EXIST') {
            // In all other cases we just log the exception since we must always
            // return a valid url
            xarLogException(XARLOG_LEVEL_ERROR);
        }
        // IMPORTANT: This freeing cause lots of error to be hidden, due to the fact that multiple
        // exceptions may be pending. As xarModUrl is used very often, and we want the exceptions
        // I commented it out (MrB). Not sure how to solve this in a better way.
        //xarExceptionFree();
    }

    // The arguments
    $urlArgs[] = "module=$modName";
    if ((!empty($modType)) && ($modType != 'user')) {
        $urlArgs[] = "type=$modType";
    }
    if ((!empty($funcName)) && ($funcName != 'main')) {
        $urlArgs[] = "func=$funcName";
    }
    $urlArgs = join('&amp;', $urlArgs);

    $url = "index.php?$urlArgs";

    foreach ($args as $k=>$v) {
        if (is_array($v)) {
            foreach($v as $l=>$w) {
                if (isset($w)) {
                    $url .= "&amp;$k" . "[$l]=$w";
                }
            }
        } elseif (isset($v)) {
            $url .= "&amp;$k=$v";
        }
    }

    if ($generateXMLURL) {
        $url = htmlspecialchars($url);
    }

    if ($target != NULL) {
        $url = "$url#$target";
    }

    // The URL
    return xarServerGetBaseURL() . $url;
}

/**
 * Generates an URL that reference to a module function via Email.
 *
 * @access public
 * @param modName string registered name of module
 * @param modType string type of function
 * @param funcName string module function
 * @param args array of arguments to put on the URL
 * @return mixed absolute URL for call, or false on failure
 */
function xarModEmailURL($modName = NULL, $modType = 'user', $funcName = 'main', $args = array())
{
    if (empty($modName)) {
        return xarServerGetBaseURL() . 'index.php';
    }

    if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
        // If exceptionId is MODULE_FUNCTION_NOT_EXIST there's no problem,
        // this exception means that the module does not support short urls
        // for this $modType.
        // If exceptionId is MODULE_FILE_NOT_EXIST there's no problem too,
        // this exception means that the module does not have the $modType API.
        if (xarExceptionId() != 'MODULE_FUNCTION_NOT_EXIST' &&
            xarExceptionId() != 'MODULE_FILE_NOT_EXIST') {
            // In all other cases we just log the exception since we must always
            // return a valid url
            xarLogException(XARLOG_LEVEL_ERROR);
        }
        xarExceptionFree();
    }

    // The arguments
    $urlArgs[] = "module=$modName";
    if ((!empty($modType)) && ($modType != 'user')) {
        $urlArgs[] = "type=$modType";
    }
    if ((!empty($funcName)) && ($funcName != 'main')) {
        $urlArgs[] = "func=$funcName";
    }
    $urlArgs = join('&', $urlArgs);

    $url = "index.php?$urlArgs";

    foreach ($args as $k=>$v) {
        if (is_array($v)) {
            foreach($v as $l=>$w) {
                if (isset($w)) {
                    $url .= "&$k" . "[$l]=$w";
                }
            }
        } elseif (isset($v)) {
            $url .= "&$k=$v";
        }
    }

    // The URL
    return xarServerGetBaseURL() . $url;
}

/**
 * Get the displayable name for modName
 *
 * The displayable name is sensible to user language.
 *
 * @access public
 * @param modName string registered name of module
 * @return string the displayable name
 */
function xarModGetDisplayableName($modName)
{
    // The module display name is language sensitive, so it's fetched through xarMLByKey
    //$modInfo = xarMod_getFileInfo($modName);
    //return xarML($modInfo['name']);

    return xarML($modName);

    //return xarMLByKey($modName);
}

/**
 * Check if a module is installed and its state is XARMOD_STATE_ACTIVE
 *
 * @access public
 * @static modAvailableCache array
 * @param modName string registered name of module
 * @param type determines theme or module
 * @return mixed true if the module is available
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModIsAvailable($modName, $type = 'module')
{
    static $modAvailableCache = array();

    $modName = strtolower($modName);

    if (empty($modName)) {
        $msg = xarML('Empty Module or Theme Name (#(1)).', $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));return;
        return;
    }

    if (!empty($GLOBALS['xarMod_noCacheState']) || !isset($modAvailableCache[$modName])) {
        switch(strtolower($type)) {
            case 'module':
                default:
                $modBaseInfo = xarMod_getBaseInfo($modName);
                break;
            case 'theme':
                $modBaseInfo = xarMod_getBaseInfo($modName, $type = 'theme');
                break;
        }

        // Catch the MODULE_NOT_EXIST exception first,
        // because that is what we're testing
        // here, we don't want to except on that.
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
            if (xarExceptionId() != 'MODULE_NOT_EXIST') {
                // Other exceptions are still thrown however
                return;
            } else {
                xarExceptionFree();
                return false;
            }
        }
        // Also return null if the result wasn't set
        if (!isset($modBaseInfo)) return; // throw back

        // We should be ok now, return the state of the module
        $modState = $modBaseInfo['state'];
        $modAvailableCache[$modName] = false;

        if ($modState == XARMOD_STATE_ACTIVE) {
            $modAvailableCache[$modName] = true;
        }
    }
    return $modAvailableCache[$modName];
}

/**
 * Carry out hook operations for module
 * Some commonly used hooks are :
 *   item - display        (user GUI)
 *   item - transform      (user API)
 *   item - new            (admin GUI)
 *   item - create         (admin API)
 *   item - modify         (admin GUI)
 *   item - update         (admin API)
 *   item - delete         (admin API)
 *   item - search         (user GUI)
 *   item - usermenu       (user GUI)
 *   module - modifyconfig (admin GUI)
 *   module - updateconfig (admin API)
 *   module - remove       (module API)
 *
 * @access public
 * @param hookObject string the object the hook is called for - 'item', 'category', 'module', ...
 * @param hookAction string the action the hook is called for - 'transform', 'display', 'new', 'create', 'delete', ...
 * @param hookId integer the id of the object the hook is called for (module-specific)
 * @param extraInfo mixed extra information for the hook, dependent on hookAction
 * @param callerModName string for what module are we calling this (default = current main module)
 *        Note : better pass the caller module via $extrainfo['module'] if necessary, so that hook functions receive it too
 * @param callerItemType string optional item type for the calling module (default = none)
 *        Note : better pass the item type via $extrainfo['itemtype'] if necessary, so that hook functions receive it too
 * @return mixed output from hooks, or null if there are no hooks
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST
 * @todo <marco> #1 add BAD_PARAM exception
 * @todo <marco> #2 check way of hanlding exception
 * @todo <marco> <mikespub> re-evaluate how GUI / API hooks are handled
 * @todo add itemtype (in extrainfo or as additional parameter)
 */
function xarModCallHooks($hookObject, $hookAction, $hookId, $extraInfo, $callerModName = NULL, $callerItemType = '')
{
    //if ($hookObject != 'item' && $hookObject != 'category') {
    //    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookObject');
    //    return;
    //}
    //if ($hookAction != 'create' && $hookAction != 'delete' && $hookAction != 'transform' && $hookAction != 'display') {
    //    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookAction');
    //    return;
    //}

    // allow override of current module if necessary (e.g. modules admin, blocks, API functions, ...)
    if (empty($callerModName)) {
        if (isset($extraInfo) && is_array($extraInfo) && !empty($extraInfo['module'])) {
            $modName = $extraInfo['module'];
        } else {
            list($modName) = xarRequestGetInfo();
            $extraInfo['module'] = $modName;
        }
    } else {
        $modName = $callerModName;
    }
    // retrieve the item type from $extraInfo if necessary (e.g. for articles, xarbb, ...)
    if (empty($callerItemType) && isset($extraInfo) &&
        is_array($extraInfo) && !empty($extraInfo['itemtype'])) {
        $callerItemType = $extraInfo['itemtype'];
    }
    xarLogMessage("xarModCallHooks: getting $hookObject $hookAction hooks for $modName.$callerItemType");
    $hooklist = xarModGetHookList($modName, $hookObject, $hookAction, $callerItemType);

    // TODO: #2
    if (!isset($hooklist) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    $output = array();
    $isGUI = false;

    // TODO: #3

    // Call each hook
    foreach ($hooklist as $hook) {
        if (!xarModIsAvailable($hook['module'], $hook['type'])) continue;
        if ($hook['area'] == 'GUI') {
            $isGUI = true;
            if (!xarModLoad($hook['module'], $hook['type'])) return;
            $res = xarModFunc($hook['module'],
                              $hook['type'],
                              $hook['func'],
                              array('objectid' => $hookId,
                              'extrainfo' => $extraInfo));
            if (!isset($res)) return;
            // Note: hook modules can only register 1 hook per hookObject, hookAction and hookArea
            //       so using the module name as key here is OK (and easier for designers)
            $output[$hook['module']] = $res;
        } else {
            if (!xarModAPILoad($hook['module'], $hook['type'])) return;
            $res = xarModAPIFunc($hook['module'],
                                 $hook['type'],
                                 $hook['func'],
                                 array('objectid' => $hookId,
                                       'extrainfo' => $extraInfo));
            if (!isset($res)) return;
            $extraInfo = $res;
        }
    }

// FIXME: this still returns the wrong output for many of the hook calls, whenever there are no hooks enabled
// Reason : we don't "know" here if the hooks defined by hookObject + hookAction are GUI or API hooks,
//          if we don't get that information from at least one enabled hook. But this is silly, really,
//          because there are *no* cases where you can have the same hookObject + hookAction in 2 different
//          hookAreas (GUI or API).
    if ($isGUI || eregi('^(display|new|modify|search|usermenu|modifyconfig)$',$hookAction)) {
        return $output;
    } else {
        return $extraInfo;
    }
}

/**
 * Get list of available hooks for a particular module, object and action
 *
 * @access private
 * @param callerModName string name of the calling module
 * @param object string the hook object
 * @param action string the hook action
 * @param callerItemType string optional item type for the calling module (default = none)
 * @return array of hook information arrays, or null if database error
 * @raise DATABASE_ERROR
 */
function xarModGetHookList($callerModName, $hookObject, $hookAction, $callerItemType = '')
{
    static $hookListCache = array();

    if (empty($callerModName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'callerModName');
        return;
    }
    //if ($hookObject != 'item' && $hookObject != 'category') {
    //    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookObject');
    //    return;
    //}
    //if ($hookAction != 'create' && $hookAction != 'delete' && $hookAction != 'transform' && $hookAction != 'display') {
    //    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookAction');
    //    return;
    //}

    if (isset($hookListCache["$callerModName$callerItemType$hookObject$hookAction"])) {
        return $hookListCache["$callerModName$callerItemType$hookObject$hookAction"];
    }

    // Get database info
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $hookstable = $xartable['hooks'];

    // Get applicable hooks
    $query = "SELECT DISTINCT xar_tarea,
                   xar_tmodule,
                   xar_ttype,
                   xar_tfunc,
                   xar_order
              FROM $hookstable
              WHERE xar_smodule = '" . xarVarPrepForStore($callerModName) . "'";
    if (empty($callerItemType)) {
        // Itemtype is not specified, only get the generic hooks
        $query .= " AND xar_stype = ''";
    } else {
        // FIXME: if itemtype is specified, why get the generic hooks? To save a function call in the modules?
        // hooks can be enabled for all or for a particular item type
        $query .= " AND (xar_stype = '' OR xar_stype = '" . xarVarPrepForStore($callerItemType) . "')";
    }
    $query .= " AND xar_object = '" . xarVarPrepForStore($hookObject) . "'
                AND xar_action = '" . xarVarPrepForStore($hookAction) . "'
              ORDER BY xar_order ASC";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $resarray = array();
    while(!$result->EOF) {
        list($hookArea,
             $hookModName,
             $hookModType,
             $hookFuncName,
             $hookOrder) = $result->fields;

        $tmparray = array('area' => $hookArea,
                          'module' => $hookModName,
                          'type' => $hookModType,
                          'func' => $hookFuncName);

        array_push($resarray, $tmparray);
        $result->MoveNext();
    }
    $result->Close();
    $hookListCache["$callerModName$callerItemType$hookObject$hookAction"] = $resarray;
    return $resarray;
}

/**
 * Check if a particular hook module is hooked to the current module
 *
 * @access public
 * @static modHookedCache array
 * @param hookModName string name of the hook module we're looking for
 * @param callerModName string name of the calling module (default = current)
 * @param callerItemType string optional item type for the calling module (default = none)
 * @return mixed true if the module is hooked
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModIsHooked($hookModName, $callerModName = NULL, $callerItemType = '')
{
    static $modHookedCache = array();

    if (empty($hookModName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'hookModName');
        return;
    }
    if (empty($callerModName)) {
        list($callerModName) = xarRequestGetInfo();
    }

    if (!isset($modHookedCache[$callerModName.$callerItemType])) {
        // Get database info
        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();
        $hookstable = $xartable['hooks'];

        // Get applicable hooks
        $query = "SELECT DISTINCT xar_tmodule
                  FROM $hookstable
                  WHERE xar_smodule = '" . xarVarPrepForStore($callerModName) . "'";
        if (empty($callerItemType)) {
            // Itemtype is not specified, get only the generic hooks
            $query .= " AND xar_stype = ''";
        } else {
            // FIXME: if itemtype is specified, i think we should not return the generic hook
            // hooks can be enabled for all or for a particular item type <-- this logic is strange
            $query .= " AND (xar_stype = '' OR xar_stype = '" . xarVarPrepForStore($callerItemType) . "')";
        }

        $result =& $dbconn->Execute($query);
        if (!$result) return;

        $modHookedCache[$callerModName.$callerItemType] = array();
        while(!$result->EOF) {
            list($modname) = $result->fields;
            $modHookedCache[$callerModName.$callerItemType][$modname] = 1;
            $result->MoveNext();
        }
        $result->Close();
    }
    if (isset($modHookedCache[$callerModName.$callerItemType][$hookModName])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Get info from xarversion.php for module specified by modOsDir
 *
 * @access protected
 * @param modOSdir the module's directory
 * @param type determines theme or module
 * @return array an array of module file information
 * @raise MODULE_FILE_NOT_EXIST
 * @todo <marco> #1 FIXME: admin or admin capable?
 */
function xarMod_getFileInfo($modOsDir, $type = 'module')
{
    if (empty($modOsDir)) {
        $msg = xarML('Directory information #(1) is empty.', $modOsDir);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', new SystemException($msg));
        return;
    }

    if (empty($GLOBALS['xarMod_noCacheState']) && xarVarIsCached('Mod.getFileInfos', $modOsDir)) {
        return xarVarGetCached('Mod.getFileInfos', $modOsDir);
    }

    // TODO redo legacy support via type.
    switch(strtolower($type)) {
        case 'module':
            default:
            // Spliffster, additional mod info from modules/$modDir/xarversion.php
            $fileName = 'modules/' . $modOsDir . '/xarversion.php';
            if (!file_exists($fileName)) {
                $fileName = 'modules/' . $modOsDir . '/pnversion.php';
                $modversion['id'] = time();
            }
            break;
        case 'theme':
            $fileName = xarConfigGetVar('Site.BL.ThemesDirectory'). '/' . $modOsDir . '/xartheme.php';
            // pnAPI compatibility
            if (!file_exists($fileName)) {
                $fileName = 'themes/' . $modOsDir . '/xartheme.php';
            }

            break;
    }

    if (!file_exists($fileName)) {
        // Don't raise an exception, it is too harsh, but log it tho (bug #295)
        xarLogMessage("xarMod_getFileInfo: Could not find xarversion.php or pnversion.php, skipping $modOsDir");
        //xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', $fileName);
        return;
    }

    include($fileName);

    if (!isset($themeinfo)){
        $themeinfo = array();
    }
    if (!isset($modversion)){
        $modversion = array();
    }
    $version = array_merge($themeinfo, $modversion);

    $FileInfo['name']           = $version['name'];
    $FileInfo['id']             = $version['id'];
    $FileInfo['version']        = (($version['version']) || ($version['xar_version']));
    $FileInfo['description']    = isset($version['description'])    ? $version['description'] : false;
    $FileInfo['admin']          = isset($version['admin'])          ? $version['admin'] : false;
    $FileInfo['admin_capable']  = isset($version['admin'])          ? $version['admin'] : false;
    $FileInfo['user']           = isset($version['user'])           ? $version['user'] : false;
    $FileInfo['user_capable']   = isset($version['user'])           ? $version['user'] : false;
    $FileInfo['securityschema'] = isset($version['securityschema']) ? $version['securityschema'] : false;
    $FileInfo['class']          = isset($version['class'])          ? $version['class'] : false;
    $FileInfo['category']       = isset($version['category'])       ? $version['category'] : false;
    $FileInfo['locale']         = isset($version['locale'])         ? $version['locale'] : 'en_US.iso-8859-1';
    $FileInfo['author']         = isset($version['author'])         ? $version['author'] : false;
    $FileInfo['contact']        = isset($version['contact'])        ? $version['contact'] : false;
    $FileInfo['dependency']     = isset($version['dependency'])     ? $version['dependency'] : array();
    $FileInfo['directory']      = isset($version['directory'])      ? $version['directory'] : false;
    $FileInfo['homepage']       = isset($version['homepage'])       ? $version['homepage'] : false;
    $FileInfo['email']          = isset($version['email'])          ? $version['email'] : false;
    $FileInfo['contact_info']   = isset($version['contact_info'])   ? $version['contact_info'] : false;
    $FileInfo['publish_date']   = isset($version['publish_date'])   ? $version['publish_date'] : false;
    $FileInfo['license']        = isset($version['license'])        ? $version['license'] : false;
    $FileInfo['version']        = isset($version['version'])        ? $version['version'] : false;
    $FileInfo['bl_version']     = isset($version['bl_version'])     ? $version['bl_version'] : false;

    xarVarSetCached('Mod.getFileInfos', $modOsDir, $FileInfo);

    return $FileInfo;
}

/**
 * Load a module's base information
 *
 * @access protected
 * @param modName stromg the module's name
 * @param type determines theme or module
 * @return mixed an array of base module info on success
 * @raise DATABASE_ERROR, MODULE_NOT_EXIST
 */
function xarMod_getBaseInfo($modName, $type = 'module')
{
    if (empty($modName)) {
        $msg = xarML('Module or Theme Name #(1) is empty.', $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM',  new SystemException($msg));
        return;
    }

    // FIXME: <MrB> I've seen cases where the cache info is not in sync
    // with reality. I've take a couple ones out, but I haven't tested all
    // the way through.
    // <mikespub> There were some issues where you tried to initialize/activate
    // several modules one after the other during the same page request (e.g. at
    // installation), since the state changes of those modules weren't taken
    // into account. The GLOBALS['xarMod_noCacheState'] flag tells Xaraya *not*
    // to cache module (+state) information in that case...

    switch(strtolower($type)) {
        case 'module':
            default:
            if (empty($GLOBALS['xarMod_noCacheState']) && xarVarIsCached('Mod.BaseInfos', $modName)) {
                return xarVarGetCached('Mod.BaseInfos', $modName);
            }
            break;
        case 'theme':
            if (empty($GLOBALS['xarTheme_noCacheState']) && xarVarIsCached('Theme.BaseInfos', $modName)) {
                return xarVarGetCached('Theme.BaseInfos', $modName);
            }
            break;
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    switch(strtolower($type)) {
        case 'module':
            default:

            $modulestable = $tables['modules'];
            $query = "SELECT xar_regid,
                             xar_directory,
                             xar_mode,
                             xar_id
                      FROM $modulestable
                      WHERE xar_name = '" . xarVarPrepForStore($modName) . "'";

            break;
        case 'theme':

            $themestable = $tables['themes'];
            $query = "SELECT xar_regid,
                             xar_directory,
                             xar_mode
                      FROM $themestable
                      WHERE xar_name = '" . xarVarPrepForStore($modName) . "'
                      OR xar_directory = '" . xarVarPrepForStore($modName) . "'";
            break;
    }

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        switch(strtolower($type)) {
            case 'module':
                default:

                $msg = xarML('Module #(1) doesn\'t exist.', $modName);
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST', new SystemException($msg));
                return;

                break;
            case 'theme':

                $msg = xarML('Theme #(1) doesn\'t exist.', $themeName);
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'THEME_NOT_EXIST', new SystemException($msg));
                return;

                break;
        }
    }

    switch(strtolower($type)) {
        case 'module':
            default:
            list($modBaseInfo['regid'],
                 $modBaseInfo['directory'],
                 $mode,
                 $modBaseInfo['systemid']) = $result->fields;
            $result->Close();
            break;
        case 'theme':
            list($modBaseInfo['regid'],
                 $modBaseInfo['directory'],
                 $mode) = $result->fields;
            $result->Close();
            break;
    }



    $modBaseInfo['name'] = $modName;
    $modBaseInfo['mode'] = (int) $mode;
    $modBaseInfo['displayname'] = xarModGetDisplayableName($modName);
    // Shortcut for os prepared directory
    // TODO: <marco> get rid of it since useless
    $modBaseInfo['osdirectory'] = xarVarPrepForOS($modBaseInfo['directory']);

    switch(strtolower($type)) {
        case 'module':
            default:
            $modState = xarMod_getState($modBaseInfo['regid'], $modBaseInfo['mode']);
            if (!isset($modState)) return; // throw back
            $modBaseInfo['state'] = $modState;
            xarVarSetCached('Mod.BaseInfos', $modName, $modBaseInfo);
            break;
        case 'theme':
            $modState = xarMod_getState($modBaseInfo['regid'], $modBaseInfo['mode'], $type = 'theme');
            if (!isset($modState)) return; // throw back
            $modBaseInfo['state'] = $modState;
            xarVarSetCached('Theme.BaseInfos', $modName, $modBaseInfo);
            break;
    }
    return $modBaseInfo;
}

/**
 * Get all module variables for a particular module
 *
 * @author Michel Dalle
 * @access protected
 * @param modName string
 * @return mixed true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarMod_getVarsByModule($modName, $type = 'module')
{
    if (empty($modName)) {
        $msg = xarML('Empty theme or module name (#(1)).', $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    switch(strtolower($type)) {
        case 'module':
            default:
            $modBaseInfo = xarMod_getBaseInfo($modName);
            if (!isset($modBaseInfo)) {
                return; // throw back
            }
            break;
        case 'theme':
            $modBaseInfo = xarMod_getBaseInfo($modName, $type = 'theme');
            if (!isset($modBaseInfo)) {
                return; // throw back
            }
            break;
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    switch(strtolower($type)) {
        case 'module':
            default:
            // Takes the right table basing on module mode
            if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
                $module_varstable = $tables['system/module_vars'];
            } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
                $module_varstable = $tables['site/module_vars'];
            }

            $query = "SELECT xar_name,
                             xar_value
                      FROM $module_varstable
                      WHERE xar_modid = '" . xarVarPrepForStore($modBaseInfo['systemid']) . "'";
            $result =& $dbconn->Execute($query);
            if (!$result) return;

            while (!$result->EOF) {
                list($name,$value) = $result->fields;
                xarVarSetCached('Mod.Variables.' . $modName, $name, $value);
                $result->MoveNext();
            }
            $result->Close();

            xarVarSetCached('Mod.GetVarsByModule', $modName, true);
            break;
        case 'theme':
            // Takes the right table basing on theme mode
            if ($themeBaseInfo['mode'] == XARTHEME_MODE_SHARED) {
                $theme_varsTable = $tables['theme_vars'];
            } elseif ($themeBaseInfo['mode'] == XARTHEME_MODE_PER_SITE) {
                $theme_varsTable = $tables['site/theme_vars'];
            }

            $query = "SELECT xar_name,
                             xar_prime,
                             xar_value,
                             xar_description
                      FROM $theme_varsTable
                      WHERE xar_themeName = '" . xarVarPrepForStore($themeName) . "'";
            $result =& $dbconn->Execute($query);
            if (!$result) return;

            $themevars = array();
            while (!$result->EOF) {
                list($name,$prime,$value,$description) = $result->fields;
                $themevars[] = array('name' => $name, 'prime' => $prime, 'value' => $value, 'description' => $description);
                xarVarSetCached('Theme.Variables.' . $themeName, $name, $value);
                $result->MoveNext();
            }
            $result->Close();

            xarVarSetCached('Theme.GetVarsByTheme', $themeName, true);
            break;
    }

    return true;
}

/**
 * Get all module variables with a particular name
 *
 * @author Michel Dalle
 * @access protected
 * @param name string
 * @return mixed true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 * @todo <marco> #1 fetch from site table too ?
 * @todo <mrb> #2 fetch from site table too? (yes i know it's the same, just making you thing twice before changing this)
 */
function xarMod_getVarsByName($varName, $type = 'module')
{
    if (empty($varName)) {
        $msg = xarML('Empty Theme or Module variable name (#(1)).', 'varName');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    switch(strtolower($type)) {
    case 'module':
    default:

        // NOTE: Not trivial to determine whether we should fetch from system
        //       or site table because this spans all modules / themes
        // <mrb> the prefix thing should be rethought, it's not scalable (at least for sites)

        // Takes the right table basing on module mode
        $module_varstable = $tables['system/module_vars'];
        $module_table = $tables['system/modules'];

        $query = "SELECT mods.xar_name, vars.xar_value
                      FROM $module_table as mods , $module_varstable as vars
                      WHERE mods.xar_id = vars.xar_modid AND
                            vars.xar_name = '" . xarVarPrepForStore($varName) . "'";
        break;
    case 'theme':
        $theme_varsTable = $tables['system/theme_vars'];
        $query = "SELECT xar_themeName,
                             xar_value
                      FROM $theme_varsTable
                      WHERE xar_name = '" . xarVarPrepForStore($varName) . "'";
        break;
    }

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $result->Close();
    switch(strtolower($type)) {
        case 'module':
            default:
            xarVarSetCached('Mod.GetVarsByName', $varName, true);
            break;
        case 'theme':
            xarVarSetCached('Theme.GetVarsByName', $varName, true);
            break;
    }

    return true;
}

/**
 * Load database definition for a module
 *
 * @access private
 * @param modName string name of module to load database definition for
 * @param modOsDir string directory that module is in
 * @return mixed true on success
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
 */
function xarMod__loadDbInfo($modName, $modDir)
{
    static $loadedDbInfoCache = array();

    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (empty($modDir)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modDir');
        return;
    }

    // Check to ensure we aren't doing this twice
    if (isset($loadedDbInfoCache[$modName])) {
        return true;
    }

    // Load the database definition if required
    $osxartablefile = "modules/$modDir/xartables.php";
    if (!file_exists($osxartablefile)) {
        $osxartablefile = 'modules/' . $modDir . '/pntables.php';
    }

    if (!file_exists($osxartablefile)) {
        return false;
    }
    include_once $osxartablefile;

    $tablefunc = $modName . '_' . 'xartables';
    $pntablefunc = $modName . '_' . 'pntables';

    if (function_exists($tablefunc)) {
        xarDB_importTables($tablefunc());
    } elseif (function_exists($pntablefunc)) {
        xarDB_importTables($pntablefunc());
    }

    $loadedDbInfoCache[$modName] = true;

    return true;
}

/**
 * Get the module's current state
 *
 * @access public
 * @param modRegId integer the module's registered id
 * @param modMode integer the module's site mode
 * @param type determines theme or module
 * @return mixed the module's current state
 * @raise DATABASE_ERROR, MODULE_NOT_EXIST
 * @todo implement the xarMod__setState reciproke
 */
function xarMod_getState($modRegId, $modMode = XARMOD_MODE_PER_SITE, $type = 'module')
{
    if ($modRegId < 1) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modRegId');
        return;
    }
    if ($modMode != XARMOD_MODE_SHARED && $modMode != XARMOD_MODE_PER_SITE) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modMode');
        return;
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    switch(strtolower($type)) {
        case 'module':
            default:
            if ($modMode == XARMOD_MODE_SHARED) {
                $module_statesTable = $tables['system/module_states'];
            } elseif ($modMode == XARMOD_MODE_PER_SITE) {
                $module_statesTable = $tables['site/module_states'];
            }

            $query = "SELECT xar_state
                      FROM $module_statesTable
                      WHERE xar_regid = '" . xarVarPrepForStore($modRegId) . "'";
            break;
        case 'theme':
            if ($modMode == XARTHEME_MODE_SHARED) {
                $theme_statesTable = $tables['system/theme_states'];
            } else {
                $theme_statesTable = $tables['site/theme_states'];
            }

            $query = "SELECT xar_state
                      FROM $theme_statesTable
                      WHERE xar_regid = '" . xarVarPrepForStore($modRegId) . "'";

            break;
    }

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // the module is not in the table
    // set state to XARMOD_STATE_MISSING
    if (!$result->EOF) {
        list($modState) = $result->fields;
        $result->Close();
        return (int) $modState;
    } else {
        $result->Close();
        return (int) XARMOD_STATE_MISSING;
    }
}

/**
 * register a hook function
 *
 * @access public
 * @param hookObject the hook object
 * @param hookAction the hook action
 * @param hookArea the area of the hook (either 'GUI' or 'API')
 * @param hookModName name of the hook module
 * @param hookModType name of the hook type
 * @param hookFuncName name of the hook function
 * @return bool true on success
 * @raise DATABASE_ERROR
 */
function xarModRegisterHook($hookObject,
                           $hookAction,
                           $hookArea,
                           $hookModName,
                           $hookModType,
                           $hookFuncName)
{
    // FIXME: <marco> BAD_PARAM?

    // Get database info
    list($dbconn) = xarDBGetConn();
    $pntable = xarDBGetTables();
    $hookstable = $pntable['hooks'];

    // Insert hook
    $query = "INSERT INTO $hookstable (
              xar_id,
              xar_object,
              xar_action,
              xar_tarea,
              xar_tmodule,
              xar_ttype,
              xar_tfunc)
              VALUES (
              " . xarVarPrepForStore($dbconn->GenId($hookstable)) . ",
              '" . xarVarPrepForStore($hookObject) . "',
              '" . xarVarPrepForStore($hookAction) . "',
              '" . xarVarPrepForStore($hookArea) . "',
              '" . xarVarPrepForStore($hookModName) . "',
              '" . xarVarPrepForStore($hookModType) . "',
              '" . xarVarPrepForStore($hookFuncName) . "')";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

/**
 * unregister a hook function
 *
 * @access public
 * @param hookObject the hook object
 * @param hookAction the hook action
 * @param hookArea the area of the hook (either 'GUI' or 'API')
 * @param hookModName name of the hook module
 * @param hookModType name of the hook type
 * @param hookFuncName name of the hook function
 * @return bool true if the unregister call suceeded, false if it failed
 */
function xarModUnregisterHook($hookObject,
                             $hookAction,
                             $hookArea,
                             $hookModName,
                             $hookModType,
                             $hookFuncName)
{
    // FIXME: <marco> BAD_PARAM?

    // Get database info
    list($dbconn) = xarDBGetConn();
    $pntable = xarDBGetTables();
    $hookstable = $pntable['hooks'];

    // Remove hook
    $query = "DELETE FROM $hookstable
              WHERE xar_object = '" . xarVarPrepForStore($hookObject) . "'
              AND xar_action = '" . xarVarPrepForStore($hookAction) . "'
              AND xar_tarea = '" . xarVarPrepForStore($hookArea) . "'
              AND xar_tmodule = '" . xarVarPrepForStore($hookModName) . "'
              AND xar_ttype = '" . xarVarPrepForStore($hookModType) . "'
              AND xar_tfunc = '" . xarVarPrepForStore($hookFuncName) . "'";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}


/**
 * Resolve a module alias
 *
 * This is only a convenience wrapper fot xarRequest function
 *
 * @todo evalutate dependency consequences
*/
function xarModGetAlias($var)
{
    return xarRequest__resolveModuleAlias($var);
}

/**
 * Set an alias for a module
 *
 * @todo evalutate dependency consequences
 *
*/
function xarModSetAlias($modName, $alias)
{
    if (!xarModAPILoad('modules', 'admin')) return;
    $args = array('modName'=>$alias, 'aliasModName'=>$modName);
    return xarModAPIFunc('modules', 'admin', 'add_module_alias', $args);
}

/**
 * Delete an alias for a module
 * @todo evalutate dependency consequences
 *
*/
function xarModDelAlias($modName, $alias)
{
    if (!xarModAPILoad('modules', 'admin')) return;
    $args = array('aliasModName'=>$modName);
    return xarModAPIFunc('modules', 'admin', 'delete_module_alias', $args);
}

/**
 * get name of current top-level module
 *
 * @deprec
 * @access public
 * @return string the name of the current top-level module, false if not in a module
 */
function xarModGetName()
{
    //TODO Work around for the prefix.
    list($modName) = xarRequestGetInfo();

    return $modName;
}

?>
