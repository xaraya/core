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

/*
 * Modules modes
 */
define('XARMOD_MODE_SHARED', 1);
define('XARMOD_MODE_PER_SITE', 2);

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
                    'site/module_uservars' => $sitePrefix . '_module_uservars');
    // Old tables
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
    if (empty($name)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }

    if (empty($prep)) {
        $prep = XARVAR_PREP_FOR_NOTHING;
    }

    if (xarVarIsCached('Mod.Variables.' . $modName, $name)) {
        $value = xarVarGetCached('Mod.Variables.' . $modName, $name);
        if ($value === '*!*MiSSiNG*!*') {
            return;
        } else {
            if ($prep == XARVAR_PREP_FOR_DISPLAY){
                $value = xarVarPrepForDisplay($value);
            } elseif ($prep == XARVAR_PREP_FOR_HTML){
                $value = xarVarPrepHTMLDisplay($value);
            }
            return $value;
        }
    } elseif (xarVarIsCached('Mod.GetVarsByModule', $modName)) {
        // we already got everything for this module, and didn't find it above
        return;
    } elseif (xarVarIsCached('Mod.GetVarsByName', $name)) {
        // we already got everything for this name, and didn't find it above
        return;
    }
    // TODO: add pre-loading of all module variables for $modName ?
    // xarMod_getVarsByModule($modName);

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    // Takes the right table basing on module mode
    if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
        $module_varstable = $tables['system/module_vars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_varstable = $tables['site/module_vars'];
    }

    $query = "SELECT xar_value
              FROM $module_varstable
              WHERE xar_modid = '" . xarVarPrepForStore($modBaseInfo['systemid']) . "'
              AND xar_name = '" . xarVarPrepForStore($name) . "'";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        xarVarSetCached('Mod.Variables.' . $modName, $name, '*!*MiSSiNG*!*');
        return;
    }
    list($value) = $result->fields;
    $result->Close();
    
    xarVarSetCached('Mod.Variables.' . $modName, $name, $value);

    if ($prep == XARVAR_PREP_FOR_DISPLAY){
        $value = xarVarPrepForDisplay($value);
    } elseif ($prep == XARVAR_PREP_FOR_HTML){
        $value = xarVarPrepHTMLDisplay($value);
    }

    return $value;
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
    if (empty($name)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    // Takes the right table basing on module mode
    if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
        $module_varstable = $tables['system/module_vars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_varstable = $tables['site/module_vars'];
    }

    $oldValue = xarModGetVar($modName, $name);
    if (!isset($oldValue)) {
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back

        $seqId = $dbconn->GenId($module_varstable);
        $query = "INSERT INTO $module_varstable
                     (xar_id,
                      xar_modid,
                      xar_name,
                      xar_value)
                  VALUES
                     ('$seqId',
                      '" . xarVarPrepForStore($modBaseInfo['systemid']) . "',
                      '" . xarVarPrepForStore($name) . "',
                      '" . xarVarPrepForStore($value) . "');";
    } else {
        $query = "UPDATE $module_varstable
                  SET xar_value = '" . xarVarPrepForStore($value) . "'
                  WHERE xar_modid = '" . xarVarPrepForStore($modBaseInfo['systemid']) . "'
                  AND xar_name = '" . xarVarPrepForStore($name) . "'";
    }

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    xarVarSetCached('Mod.Variables.' . $modName, $name, $value);

    return true;
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
    if (empty($name)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back

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

    // Delete the user variables first
    $modvarid = xarModGetVarId($modName, $name);
    if(!$modvarid) return;

    // MrB: we could use xarModDelUserVar in a loop here, but this is
    //      much faster.
    $query = "DELETE FROM $module_uservarstable
              WHERE xar_mvid = '" . xarVarPrepForStore($modvarid) . "'";
    $result =& $dbconn->Execute($query);
    if(!$result) return;

    // Now delete the module var
    $query = "DELETE FROM $module_varstable
              WHERE xar_modid = '" . xarVarPrepForStore($modBaseInfo['systemid']) . "'
              AND xar_name = '" . xarVarPrepForStore($name) . "'";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    xarVarDelCached('Mod.Variables.' . $modName, $name);

    return true;
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
function xarModGetUserVar($modName, $name, $uid=NULL)
{
    // Module name and variable name are necessary
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (empty($name)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }

    // If uid not specified take the current user
    if ($uid == NULL) $uid=xarUserGetVar('uid');

    // Anonymous user always uses the module default setting
    if ($uid==_XAR_ID_UNREGISTERED) return xarModGetVar($modName,$name);

    // Retrieve the info for the module to see where we need to retrieve the values
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    // Takes the right table basing on module mode
    if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
        $module_uservarstable = $tables['system/module_uservars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_uservarstable = $tables['site/module_uservars'];
    }

    // We need the id of the module variable
    unset($modvarid); // is this necessary?
    $modvarid = xarModGetVarId($modName, $name);
    if (!$modvarid) return;

    $query = "SELECT xar_value
              FROM $module_uservarstable
              WHERE xar_mvid = '" . xarVarPrepForStore($modvarid) . "'
              AND xar_uid ='" . xarVarPrepForStore($uid). "'";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // If there is no such thing, return the global setting.
    if ($result->EOF) {
        $result->Close();
        // return global setting
        return xarModGetVar($modName, $name);
    }

    list($value) = $result->fields;
    $result->Close();

    return $value;
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
    if (empty($name)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }

    // If no uid specified assume current user
    if ($uid == NULL) $uid = xarUserGetVar('uid');

    // For anonymous users no preference can be set
    // MrB: should we raise an exception here?
    if ($uid==_XAR_ID_UNREGISTERED) return false;

    // Get the info for the module so we can determine where we need to set
    // the variable
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    // Takes the right table basing on module mode
    if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
        $module_uservarstable = $tables['system/module_uservars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_uservarstable = $tables['site/module_uservars'];
    }

    // Get the default setting to compare the value against.
    $modsetting = xarModGetVar($modName, $name);

    // We need the variable id
    unset($modvarid);
    $modvarid = xarModGetVarId($modName, $name);
    if(!$modvarid) return;

    // First delete it.
    // We could first retrieve and then compare the new value to
    // both oldvalue and the module default, but this leads to
    // simpler code and assuming the two queries are equal performing
    // this looks better.
    // FIXME: check this for performance (compare with xarModSetVar
    //        performance which uses either update or insert statement.
    xarModDelUserVar($modName,$name,$uid);

    // Only store setting if different from global setting
    if ($value != $modsetting) {
        $query = "INSERT INTO $module_uservarstable
                 (xar_mvid, xar_uid, xar_value)
                VALUES
                 ('" . xarVarPrepForStore($modvarid) . "',
                  '" . xarVarPrepForStore($uid) . "',
                  '" . xarVarPrepForStore($value) . "');";

        if (! $dbconn->Execute($query)) return;
    }

    return true;
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
    if (empty($name)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }

    // If uid is not set assume current user
    if ($uid == NULL) $uid = xarUserGetVar('uid');

    // Deleting for anonymous user is useless return true
    // MrB: should we continue, can't harm either and we have
    //      a failsafe that records are deleted, bit dirty, but
    //      it would work.
    if ($uid == 0 ) return true;

    // Get the module info so we know where to delete the value
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back


    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    // Takes the right table basing on module mode
    if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
        $module_uservarstable = $tables['system/module_uservars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_uservarstable = $tables['site/module_uservars'];
    }

    // We need the variable id
    unset($modvarid); // is this necessary?
    $modvarid = xarModGetVarId($modName, $name);
    if(!$modvarid) return;

    $query = "DELETE FROM $module_uservarstable
              WHERE xar_mvid = '" . xarVarPrepForStore($modvarid) . "'
              AND xar_uid = '" . xarVarPrepForStore($uid) . "'";
    if(!$dbconn->Execute($query)) return;

    return true;
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

    return $modvarid;
}


/**
 * Get module registry ID by name
 *
 * @access public
 * @param modName string The name of the module
 * @return string The module registry ID.
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
 */
function xarModGetIDFromName($modName)
{
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back
    // MrB: this is a bit confusing as we also have the 'system' id.
    return $modBaseInfo['regid'];
}

/**
 * Get information on module
 *
 * @access public
 * @param modRegId string module id
 * @return array of module information
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function xarModGetInfo($modRegId)
{
    if ($modRegId < 1) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modRegId');
        return;
    }

    if (xarVarIsCached('Mod.Infos', $modRegId)) {
        return xarVarGetCached('Mod.Infos', $modRegId);
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
    $modulestable = $tables['modules'];

    $query = "SELECT xar_name,
                     xar_directory,
                     xar_mode,
                     xar_version
              FROM $modulestable
              WHERE xar_regid = " . xarVarPrepForStore($modRegId);
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

    $modState = xarMod__getState($modInfo['regid'], $modInfo['mode']);
    if (!isset($modState)) $modState = XARMOD_STATE_MISSING; //return; // throw back
    $modInfo['state'] = $modState;

    // MrB: why do we have Info, BaseInfo, DBInfo, FileInfo etc. that's bloat
    //xarVarSetCached('Mod.BaseInfos', $modInfo['name'], $modInfo);

    $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory']);
    if (!isset($modFileInfo)) {
        // We couldn't get file info, fill in unknowns.
        // The exception for this is logged in getFileInfo
        $modFileInfo['class'] = xarML('Unknown');
        $modFileInfo['description'] = xarML('This module isn\'t installed properly. Not all info could be retrieved');
        $modFileInfo['category'] = xarML('Unknown');
        $modFileInfo['author'] = xarML('Unknown');
        $modFileInfo['contact'] = xarML('Unknown');
        $modFileInfo['dependency'] = array();
    } 
    $modInfo = array_merge($modFileInfo, $modInfo);

    xarVarSetCached('Mod.Infos', $modRegId, $modInfo);

    return $modInfo;
}

/**
 * Get a list of modules that matches required criteria.
 *
 * Supported criteria are Mode, UserCapable, AdminCapable, Class, Category,
 * State.
 * Permitted values for Mode are XARMOD_MODE_SHARED and XARMOD_MODE_PER_SITE.
 * Permitted values for UserCapable are 0 or 1 or unset. If you specify the 1
 * value the result will contain all the installed modules that support the
 * user GUI.
 * Obviously you get the opposite result if you specify a 0 value for
 * UserCapable in filter.
 * If you don't care of UserCapable property, simply don't specify a value for
 * it.
 * The same thing is applied to the AdminCapable property.
 * Permitted values for Class and Category are the ones defined in the proper
 * RFC.
 * Permitted values for State are XARMOD_STATE_ANY, XARMOD_STATE_UNINITIALISED,
 * XARMOD_STATE_INACTIVE, XARMOD_STATE_ACTIVE, XARMOD_STATE_MISSING,
 * XARMOD_STATE_UPGRADED, XARMOD_STATE_INSTALLED
 * The XARMOD_STATE_ANY means that any state is valid.
 * The default value of State is XARMOD_STATE_ACTIVE.
 * For other criteria there's no default value.
 * The orderBy parameter specifies the order by which is sorted the result
 * array, can be one of name, regid, class, category or a combination of them,
 * the default is name.
 * You can combine those fields to obtain a good ordered list simply by
 * separating them with the '/' character, i.e. if you want to order the list
 * first by class, then by category and lastly by name you pass
 * 'class/category/name' as orderBy parameter
 *
 * @author Marco Canini <marco@xaraya.com>
 * @param filter array of criteria used to filter the entire list of installed
 *        modules.
 * @param startNum integer the start offset in the list
 * @param numItems integer the length of the list
 * @param orderBy string the order type of the list
 * @return array array of module information arrays
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModGetList($filter = array(), $startNum = NULL, $numItems = NULL, $orderBy = 'name')
{
    static $validOrderFields = array('name' => 'mods', 'regid' => 'mods',
                                     'class' => 'mods', 'category' => 'mods');
    if (!is_array($filter)) {
        $msg = xarML('Parameter filter must be an array.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Optional arguments.
    if (!isset($startNum)) $startNum = 1;
    if (!isset($numItems)) $numItems = -1;

    $extraSelectClause = '';
    $whereClauses = array();

    $orderFields = explode('/', $orderBy);
    $orderByClauses = array();
    foreach ($orderFields as $orderField) {
        if (!isset($validOrderFields[$orderField])) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'orderBy');
            return;
        }
        // Here $validOrderFields[$orderField] is the table alias
        $orderByClauses[] = $validOrderFields[$orderField] . '.xar_' . $orderField;
        if ($validOrderFields[$orderField] == 'mods') {
            $extraSelectClause .= ', ' . $validOrderFields[$orderField] . '.xar_' . $orderField;
        }
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
    $modulestable = $tables['modules'];

    $module_statesTables = array($tables['system/module_states'], $tables['site/module_states']);

    if (isset($filter['Mode'])) {
        $whereClauses[] = 'mods.xar_mode = '.xarVarPrepForStore($filter['Mode']);
    }
    if (isset($filter['UserCapable'])) {
        $whereClauses[] = 'mods.xar_user_capable = '.xarVarPrepForStore($filter['UserCapable']);
    }
    if (isset($filter['AdminCapable'])) {
        $whereClauses[] = 'mods.xar_admin_capable = '.xarVarPrepForStore($filter['AdminCapable']);
    }
    if (isset($filter['Class'])) {
        $whereClauses[] = 'mods.xar_class = '.xarVarPrepForStore($filter['Class']);
    }
    if (isset($filter['Category'])) {
        $whereClauses[] = 'mods.xar_category = '.xarVarPrepForStore($filter['Category']);
    }
    if (isset($filter['State'])) {
        if ($filter['State'] != XARMOD_STATE_ANY) {
            if ($filter['State'] != XARMOD_STATE_INSTALLED) {
                $whereClauses[] = 'states.xar_state = '.xarVarPrepForStore($filter['State']);
            } else {
                $whereClauses[] = 'states.xar_state != '.XARMOD_STATE_UNINITIALISED;
            }
        }
    } else {
        $whereClauses[] = 'states.xar_state = '.XARMOD_STATE_ACTIVE;
    }

    $orderByClause = join(', ', $orderByClauses);

    $mode = XARMOD_MODE_SHARED;

    $modList = array();

    // Here we do 2 SELECTs: one for SHARED moded modules and
    // one for PER_SITE moded modules
    // Maybe this could be done with a single query?
    for ($i = 0; $i < 2; $i++ ) {
        $module_statesTable = $module_statesTables[$i];

        $query = "SELECT mods.xar_regid,
                         mods.xar_name,
                         mods.xar_directory,
                         mods.xar_version,
                         mods.xar_id,
                         states.xar_state";


        $query .= " FROM $modulestable AS mods";
        array_unshift($whereClauses, 'mods.xar_mode = '.$mode);

        // Do join
        $query .= " LEFT JOIN $module_statesTable AS states ON mods.xar_regid = states.xar_regid";

        $whereClause = join(' AND ', $whereClauses);
        $query .= " WHERE $whereClause";

        $query .= " ORDER BY $orderByClause";
        $result = $dbconn->SelectLimit($query, $numItems, $startNum - 1);
        if (!$result) return;

        while(!$result->EOF) {
            list($modInfo['regid'],
                 $modInfo['name'],
                 $modInfo['directory'],
                 $modInfo['version'],
                 $modInfo['systemid'],
                 $modState) = $result->fields;

            if (xarVarIsCached('Mod.Infos', $modInfo['regid'])) {
                // Get infos from cache
                $modList[] = xarVarGetCached('Mod.Infos', $modInfo['regid']);
            } else {
                $modInfo['mode'] = (int) $mode;
                $modInfo['displayname'] = xarModGetDisplayableName($modInfo['name']);
                // Shortcut for os prepared directory
                $modInfo['osdirectory'] = xarVarPrepForOS($modInfo['directory']);

                $modInfo['state'] = (int) $modState;

                xarVarSetCached('Mod.BaseInfos', $modInfo['name'], $modInfo);

                $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory']);
                if (!isset($modFileInfo)) {
                    // The info from the DB doesn't match the filesystem
                    // This is already logged in the getfileinfo function, we don't have to do it again
                    // FIXME: Set the status of module to missing files or something?
                } else {
                    //     $modInfo = array_merge($modInfo, $modFileInfo);
                    $modInfo = array_merge($modFileInfo, $modInfo);
                    xarVarSetCached('Mod.Infos', $modInfo['regid'], $modInfo);
                    $modList[] = $modInfo;
                }
            }
            $modInfo = array();
            $result->MoveNext();
        }

        $result->Close();
        $mode = XARMOD_MODE_PER_SITE;
        array_shift($whereClauses);
    }

    return $modList;
}


/**
 * Load the modType of module identified by modName.
 *
 * @access private
 * @param modName string - name of module to load
 * @param modType string - type of functions to load
 * @return mixed
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_NOT_ACTIVE
 */
function xarModPrivateLoad($modName, $modType)
{
    static $loadedModuleCache = array();

    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    // Make sure we access the cache with lower case key
    if (isset($loadedModuleCache[strtolower("$modName$modType")])) {
        // Already loaded (or tried to) from somewhere else
        return true;
    }

    xarLogMessage("xarModLoad: loading $modName:$modType");

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back

    if ($modBaseInfo['state'] != XARMOD_STATE_ACTIVE) {
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
        // Load file
        include_once($fileName);

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

    // FIXME: <marco> Remove it when the old language packs are gone
    $fileName = "modules/$modDir/xarlang/eng/$modType.php";
    if (file_exists($fileName)) {
        include_once($fileName);
    }

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

    return xarModPrivateLoad($modName, $modType.'api');
}

/**
 * Load database definition for a module
 *
 * @param modName name of module to load database definition for
 * @param modDir directory that module is in (if known)
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
 */
function xarModDBInfoLoad($modName, $modDir = NULL)
{
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    // Get the directory if we don't already have it
    if (empty($modDir)) {
        $modBaseInfo = xarMod_getBaseInfo($modName);
        if (!isset($modBaseInfo)) return; // throw back

        $modDir = $modBaseInfo['directory'];
    } else {
        $modDir = xarVarPrepForOS($modDir);
    }

    xarMod__loadDbInfo($modName, $modDir);

    return true;
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
                require_once $funcFile;
                if (!function_exists($modFunc)) {
                    $found = false;
                }
            }
        }
    }
    if (!$found) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST', $modFunc);
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
                require_once $funcFile;
                if (!function_exists($modAPIFunc)) {
                    $found = false;
                }
            }
        }
    }
    if (!$found) {
        if ($throwException) {
            // MrB: When there is a parse error in the api file we sometimes end up
            // here, the error is never shown !!!! (xmlrpc for example)
            $msg = xarML('Module API function #(1) doesn\'t exist.', $modAPIFunc);
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

    return $modName;

    //return xarMLByKey($modName);
}

/**
 * Check if a module is installed and its state is XARMOD_STATE_ACTIVE
 *
 * @access public
 * @static modAvailableCache array
 * @param modName string registered name of module
 * @return mixed true if the module is available
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModIsAvailable($modName)
{
    static $modAvailableCache = array();

    $modName = strtolower($modName);

    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    if (!empty($GLOBALS['xarMod_noCacheState']) || !isset($modAvailableCache[$modName])) {

        $modBaseInfo = xarMod_getBaseInfo($modName);
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
        $extraInfo['module'] = $modName;
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
 * @return array an array of module file information
 * @raise MODULE_FILE_NOT_EXIST
 * @todo <marco> #1 FIXME: admin or admin capable?
 */
function xarMod_getFileInfo($modOsDir)
{
    if (empty($modOsDir)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modDir');
        return;
    }

    $resarray = array();
    // Spliffster, additional mod info from modules/$modDir/xarversion.php
    $fileName = 'modules/' . $modOsDir . '/xarversion.php';
    if (!file_exists($fileName)) {
        $fileName = 'modules/' . $modOsDir . '/pnversion.php';
    }

    if (!file_exists($fileName)) {
        // Don't raise an exception, it is too harsh, but log it tho (bug #295)
        xarLogMessage("xarMod_getFileInfo: Could not find xarversion.php or pnversion.php, skipping $modOsDir");
        //xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', $fileName);
        return;
    }

    include($fileName);

    $modFileInfo['name']           = $modversion['name'];
    $modFileInfo['id']             = $modversion['id'];
    $modFileInfo['version']        = $modversion['version'];
    $modFileInfo['description']    = isset($modversion['description']) ? $modversion['description'] : false;
    // TODO: 1
    $modFileInfo['admin']          = isset($modversion['admin']) ? $modversion['admin'] : false;
    $modFileInfo['admin_capable']  = isset($modversion['admin']) ? $modversion['admin'] : false;
    $modFileInfo['user']           = isset($modversion['user']) ? $modversion['user'] : false;
    $modFileInfo['user_capable']   = isset($modversion['user']) ? $modversion['user'] : false;
    $modFileInfo['securityschema'] = isset($modversion['securityschema']) ? $modversion['securityschema'] : false;
    $modFileInfo['class']          = isset($modversion['class']) ? $modversion['class'] : false;
    $modFileInfo['category']       = isset($modversion['category']) ? $modversion['category'] : false;
    $modFileInfo['locale']         = isset($modversion['locale']) ? $modversion['locale'] : 'en_US.iso-8859-1';
    // EXTRA INFO: required by components mod and possibly other core modules; added by <andyv>
    $modFileInfo['author']         = isset($modversion['author']) ? $modversion['author'] : false;
    $modFileInfo['contact']        = isset($modversion['contact']) ? $modversion['contact'] : false;
    $modFileInfo['dependency']     = isset($modversion['dependency']) ? $modversion['dependency'] : array();

    return $modFileInfo;
}

/**
 * Load a module's base information
 *
 * @access protected
 * @param modName stromg the module's name
 * @return mixed an array of base module info on success
 * @raise DATABASE_ERROR, MODULE_NOT_EXIST
 */
function xarMod_getBaseInfo($modName)
{
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
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
    if (empty($GLOBALS['xarMod_noCacheState']) && xarVarIsCached('Mod.BaseInfos', $modName)) {
        return xarVarGetCached('Mod.BaseInfos', $modName);
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
    $modulestable = $tables['modules'];

    $query = "SELECT xar_regid,
                     xar_directory,
                     xar_mode,
                     xar_id
              FROM $modulestable
              WHERE xar_name = '" . xarVarPrepForStore($modName) . "'";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST', $modName);
        return;
    }

    list($modBaseInfo['regid'],
         $modBaseInfo['directory'],
         $mode,
         $modBaseInfo['systemid']) = $result->fields;
    $result->Close();

    $modBaseInfo['name'] = $modName;
    $modBaseInfo['mode'] = (int) $mode;
    $modBaseInfo['displayname'] = xarModGetDisplayableName($modName);
    // Shortcut for os prepared directory
    // TODO: <marco> get rid of it since useless
    $modBaseInfo['osdirectory'] = $modBaseInfo['directory'];

    $modState = xarMod__getState($modBaseInfo['regid'], $modBaseInfo['mode']);
    if (!isset($modState)) return; // throw back
    $modBaseInfo['state'] = $modState;
    xarVarSetCached('Mod.BaseInfos', $modName, $modBaseInfo);
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
function xarMod_getVarsByModule($modName)
{
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

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
 */
function xarMod_getVarsByName($name)
{
    // MrB: This couldn't possibly have worked, what gives?
    //if (empty($modName)) {
    //    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
    //    return;
    //}

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $module_table = $tables['system/modules'];
    $module_varstable = $tables['system/module_vars'];

    // TODO: fetch from site table too ?
    //    $module_varstable = $tables['site/module_vars'];

    $query = "SELECT mods.xar_name, vars.xar_value
              FROM $module_table as mods , $module_varstable as vars
              WHERE mods.xar_id = vars.xar_modid AND
                    vars.xar_name = '" . xarVarPrepForStore($name) . "'";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    while (!$result->EOF) {
        list($modName,$value) = $result->fields;
        xarVarSetCached('Mod.Variables.' . $modName, $name, $value);
        $result->MoveNext();
    }
    $result->Close();

    xarVarSetCached('Mod.GetVarsByName', $name, true);
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
        return false;
    }
    include_once $osxartablefile;

    $tablefunc = $modName . '_' . 'xartables';
    if (function_exists($tablefunc)) {
        xarDB_importTables($tablefunc());
    }

    $loadedDbInfoCache[$modName] = true;

    return true;
}

/**
 * Get the module's current state
 *
 * @access private
 * @param modRegId integer the module's registered id
 * @param modMode integer the module's site mode
 * @return mixed the module's current state
 * @raise DATABASE_ERROR, MODULE_NOT_EXIST
 * @todo implement the xarMod__setState reciproke
 */
function xarMod__getState($modRegId, $modMode)
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

    if ($modMode == XARMOD_MODE_SHARED) {
        $module_statesTable = $tables['system/module_states'];
    } elseif ($modMode == XARMOD_MODE_PER_SITE) {
        $module_statesTable = $tables['site/module_states'];
    }

    $query = "SELECT xar_state
              FROM $module_statesTable
              WHERE xar_regid = '" . xarVarPrepForStore($modRegId) . "'";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    //assert(!$result->EOF);
    // the module is not in the table
    // set state to XARMOD_STATE_UNINITIALISED
    // FIXME: CHECK whether this has no side-effects,
    // it was only put in to get the installer running.
    if (!$result->EOF) {
        list($modState) = $result->fields;
        $result->Close();
        return (int) $modState;
    } else {
        $result->Close();
        return (int) XARMOD_STATE_UNINITIALISED;
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
