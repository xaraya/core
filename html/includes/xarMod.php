<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file: Module variable handling
// ----------------------------------------------------------------------

/* TODO:
 * Use serialize in module variables
 */

/*
 * State of modules
 */
define('XARMOD_STATE_UNINITIALISED', 1);
define('XARMOD_STATE_INACTIVE', 2);
define('XARMOD_STATE_ACTIVE', 3);
// FIXME: <marco> What're these two for?
define('XARMOD_STATE_MISSING', 4);
define('XARMOD_STATE_UPGRADED', 5);

// This isn't a module state, but only a convenient definition to indicates,
// where it's used, that we don't care about state, any state is good
define('XARMOD_STATE_ANY', 0);

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

function xarMod_init($args, $whatElseIsGoingLoaded)
{
    global $xarMod_generateShortURLs, $xarMod_generateXMLURLs;

    $xarMod_generateShortURLs = $args['enableShortURLsSupport'];

    $xarMod_generateXMLURLs = $args['generateXMLURLs'];

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
                    'site/module_vars' => $sitePrefix . '_module_vars');
    // Old tables
    $tables['module_vars']           = $systemPrefix . '_module_vars';
    $tables['hooks']                 = $systemPrefix . '_hooks';

    xarDB_importTables($tables);

    // Pre-fetch all 'SupportShortURLs' variables if needed
    if (!empty($xarMod_generateShortURLs)) {
        xarMod_getVarsByName('SupportShortURLs');
    }

    return true;
}

/**
 * get a module variable
 *
 * @access public
 * @param modName The name of the module
 * @param name The name of the variable
 * @returns bool
 * @return mixed The value of the variable or void if variable doesn't exist
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModGetVar($modName, $name)
{
    if (empty($modName) || empty($name)) {
        $msg = xarML('Empty modName (#(1)) or name (#(2)).', $modName, $name);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (xarVarIsCached('Mod.Variables.' . $modName, $name)) {
        $value = xarVarGetCached('Mod.Variables.' . $modName, $name);
        if ($value == '*!*MiSSiNG*!*') {
            return;
        } else {
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
        $module_varsTable = $tables['system/module_vars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_varsTable = $tables['site/module_vars'];
    }

    $query = "SELECT xar_value
              FROM $module_varsTable
              WHERE xar_modname = '" . xarVarPrepForStore($modName) . "'
              AND xar_name = '" . xarVarPrepForStore($name) . "'";
    $result = $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }
    if ($result->EOF) {
        $result->Close();
        xarVarSetCached('Mod.Variables.' . $modName, $name, '*!*MiSSiNG*!*');
        return;
    }
    list($value) = $result->fields;
    $result->Close();

    xarVarSetCached('Mod.Variables.' . $modName, $name, $value);

    return $value;
}

/**
 * set a module variable
 *
 * @access public
 * @param modName The name of the module
 * @param name The name of the variable
 * @param value The value of the variable
 * @returns bool
 * @return true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModSetVar($modName, $name, $value)
{
    if (empty($modName) || empty($name)) {
        $msg = xarML('Empty modName (#(1)) or name (#(2)).', $modName, $name);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));return;
    }

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    // Takes the right table basing on module mode
    if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
        $module_varsTable = $tables['system/module_vars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_varsTable = $tables['site/module_vars'];
    }

    $oldValue = xarModGetVar($modName, $name);

    if (!isset($oldValue)) {
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
            return; // throw back exception
        }

        $seqId = $dbconn->GenId($module_varsTable);
        $query = "INSERT INTO $module_varsTable
                     (xar_id,
                      xar_modname,
                      xar_name,
                      xar_value)
                  VALUES
                     ('$seqId',
                      '" . xarVarPrepForStore($modName) . "',
                      '" . xarVarPrepForStore($name) . "',
                      '" . xarVarPrepForStore($value) . "');";
    } else {
        $query = "UPDATE $module_varsTable
                  SET xar_value = '" . xarVarPrepForStore($value) . "'
                  WHERE xar_modname = '" . xarVarPrepForStore($modName) . "'
                  AND xar_name = '" . xarVarPrepForStore($name) . "'";
    }

    $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    xarVarSetCached('Mod.Variables.' . $modName, $name, $value);

    return true;
}


/**
 * delete a module variable
 *
 * @access public
 * @param modName The name of the module
 * @param name The name of the variable
 * @returns bool
 * @return true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModDelVar($modName, $name)
{
    if (empty($modName) || empty($name)) {
        $msg = xarML('Empty modName (#(1)) or name (#(2)).', $modName, $name);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));return;
    }

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    // Takes the right table basing on module mode
    if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
        $module_varsTable = $tables['system/module_vars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_varsTable = $tables['site/module_vars'];
    }

    $query = "DELETE FROM $module_varsTable
              WHERE xar_modname = '" . xarVarPrepForStore($modName) . "'
              AND xar_name = '" . xarVarPrepForStore($name) . "'";
    $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    xarVarDelCached('Mod.Variables.' . $modName, $name);

    return true;
}

/**
 * Gets module registry ID given its name
 *
 * @access public
 * @param modName The name of the module
 * @returns string
 * @return The module registry ID.
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
 */
function xarModGetIDFromName($modName)
{
    if (empty($modName)) {
        $msg = xarML('Empty modname.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back
    }
    return $modBaseInfo['regid'];
}

/**
 * get information on module
 *
 * @access public
 * @param modRegId module id
 * @returns array
 * @return array of module information
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function xarModGetInfo($modRegId)
{
    // a $modRegId of 0 is associated with core ( xar_blocks.mid, ... ).
    if (empty($modRegId) || $modRegId == 0) {
        $msg = xarML('Empty modRegId (#(1)) or modRegId is equal to 0.', $modRegId);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg ));return;
    }

    if (xarVarIsCached('Mod.Infos', $modRegId)) {
        return xarVarGetCached('Mod.Infos', $modRegId);
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
    $modulestable = $tables['modules'];

    $query = "SELECT xar_name,
                     xar_directory,
                     xar_mode
              FROM $modulestable
              WHERE xar_regid = " . xarVarPrepForStore($modRegId);
    $result = $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }
    if ($result->EOF) {
        $result->Close();
        $msg = xarML('Module identified by #(1) doesn\'t exist.', $modRegId);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ID_NOT_EXIST',
                       new SystemException($msg));
        return;
    }
    list($modInfo['name'],
         $modInfo['directory'],
         $mode) = $result->fields;
    $result->Close();

    $modInfo['regid'] = $modRegId;
    $modInfo['mode'] = (int) $mode;
    $modInfo['displayname'] = xarModGetDisplayableName($modInfo['name']);

    // Shortcut for os prepared directory
    $modInfo['osdirectory'] = xarVarPrepForOS($modInfo['directory']);

    $modState = xarMod__getState($modInfo['regid'], $modInfo['mode']);
    if (!isset($modState)) return; // throw back
    $modInfo['state'] = $modState;

    xarVarSetCached('Mod.BaseInfos', $modInfo['name'], $modInfo);

    $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory']);
    if (!isset($modFileInfo)) return; // throw back
    $modInfo = array_merge($modInfo, $modFileInfo);

    xarVarSetCached('Mod.Infos', $modRegId, $modInfo);

    return $modInfo;
}

/**
 * Gets a list of modules that matches required criteria.
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
 * XARMOD_STATE_UPGRADED.
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
 * @author Marco Canini <marco.canini@postnuke.com>
 * @param filter array of criteria used to filter the entire list of installed
 *        modules.
 * @param startNum the start offset in the list
 * @param numItems the length of the list
 * @param orderBy the order type of the list
 * @returns array
 * @return array of module information arrays
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModGetList($filter = array(), $startNum = NULL, $numItems = NULL, $orderBy = 'name')
{
    static $validOrderFields = array('name' => 'mods', 'regid' => 'mods',
                                     'class' => 'infos', 'category' => 'infos');
    if (!is_array($filter)) {
        $msg = xarML('Parameter filter must be an array.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Optional arguments.
    if (!isset($startNum)) {
        $startNum = 1;
    }
    if (!isset($numItems)) {
        $numItems = -1;
    }

    $extraSelectClause = '';
    $whereClauses = array();

    $orderFields = explode('/', $orderBy);
    $orderByClauses = array();
    foreach ($orderFields as $orderField) {
        if (!isset($validOrderFields[$orderField])) {
            $msg = xarML('Parameter orderBy can contain only \'name\' or \'regid\' or \'class\' or \'category\' as items.');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg));
            return;
        }
        // Here $validOrderFields[$orderField] is the table alias
        $orderByClauses[] = $validOrderFields[$orderField] . '.xar_' . $orderField;
        if ($validOrderFields[$orderField] == 'infos') {
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
            $whereClauses[] = 'states.xar_state = '.xarVarPrepForStore($filter['State']);
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
                         states.xar_state";


        $query .= " FROM $modulestable AS mods";
        array_unshift($whereClauses, 'mods.xar_mode = '.$mode);

        // Do join
        $query .= " LEFT JOIN $module_statesTable AS states ON mods.xar_regid = states.xar_regid";

        $whereClause = join(' AND ', $whereClauses);
        $query .= " WHERE $whereClause";

        $query .= " ORDER BY $orderByClause";
        $result = $dbconn->SelectLimit($query, $numItems, $startNum - 1);

        if ($dbconn->ErrorNo() != 0) {
            $msg = xarMLByKey('DATABASE_ERROR', $query);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                           new SystemException($msg));
            return;
        }

        if (!$result->EOF) {
            while(!$result->EOF) {
                list($modInfo['regid'],
                    $modInfo['name'],
                    $modInfo['directory'],
                    $modState) = $result->fields;
                $result->MoveNext();

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
                    if (!isset($modFileInfo)) return; // throw back
                    $modInfo = array_merge($modInfo, $modFileInfo);

                    xarVarSetCached('Mod.Infos', $modInfo['regid'], $modInfo);

                    $modList[] = $modInfo;
                }
                $modInfo = array();
            }
        }
        $result->Close();

        $mode = XARMOD_MODE_PER_SITE;
        array_shift($whereClauses);
    }

    return $modList;
}

/**
 * Loads the modType of module identified by modName.
 *
 * @access public
 * @param modName - name of module to load
 * @param modType - type of functions to load
 * @returns string
 * @return true
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_NOT_ACTIVE
 */
function xarModLoad($modName, $modType = 'user')
{
    static $loadedModuleCache = array();

    xarLogMessage("xarModLoad: loading $modName:$modType");

    if (empty($modName)) {
        $msg = xarML('Empty modname.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($loadedModuleCache["$modName$modType"])) {
        // Already loaded from somewhere else
        return true;
    }

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back
    }

    if ($modBaseInfo['state'] != XARMOD_STATE_ACTIVE) {
        $msg = xarML('Module #(1) is not active.', $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_ACTIVE',
                       new SystemException($msg));
        return;
    }

    // Load the module files
    $modOsType = xarVarPrepForOS($modType);
    $modOsDir = $modBaseInfo['osdirectory'];

    $osfile = "modules/$modOsDir/xar$modOsType.php";

    // pnAPI compatibility
    if (!file_exists($osfile)) {
        $osfile = "modules/$modOsDir/pn$modOsType.php";
        if (!file_exists($osfile)) {

            // File does not exist
            $msg = xarML('Module file #(1) doesn\'t exist.', $osfile);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException($msg));
            return;
        }
    }

    // Load file
    include $osfile;
    $loadedModuleCache["$modName$modType"] = true;

    // Load the module translations files
    if (xarMLS_loadTranslations('module', $modName, 'modules/'.$modOsDir, 'file', $modType) === NULL) return;

    // pnAPI compatibility
    $defaultlang = pnConfigGetVar('language');
    if (empty($defaultlang)) {
        $defaultlang = 'eng';
    }
    $currentlang = pnUserGetLang();
    if (file_exists("modules/$modOsDir/pnlang/$currentlang/$modType.php")) {
        include "modules/$modOsDir/pnlang/" . xarVarPrepForOS($currentlang) . "/$modType.php";
    } elseif (file_exists("modules/$modOsDir/pnlang/$defaultlang/$modType.php")) {
        include "modules/$modOsDir/pnlang/" . xarVarPrepForOS($defaultlang) . "/$modType.php";
    }

    // Load database info
    xarMod__loadDbInfo($modName, $modOsDir);

    // Module loaded successfully, notify the proper event
    xarEvt_notify($modName, $modType, 'ModLoad', NULL);

    return true;
}

/**
 * Loads the modType API for module identified by modName.
 *
 * @access public
 * @param modName registered name of the module
 * @param modType type of functions to load
 * @returns bool
 * @return true on success
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_NOT_ACTIVE
 */
function xarModAPILoad($modName, $modType = 'user')
{
    static $loadedAPICache = array();

    xarLogMessage("xarModAPILoad: loading $modName:$modType");

    if (empty($modName)) {
        $msg = xarML('Empty modname.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($loadedAPICache["$modName$modType"])) {
        // Already loaded from somewhere else
        return true;
    }

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back
    }

    if ($modBaseInfo['state'] != XARMOD_STATE_ACTIVE) {
        $msg = xarML('Module #(1) is not active.', $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_ACTIVE',
                       new SystemException($msg));
        return;
    }

    $modOsType = xarVarPrepForOS($modType);
    $modOsDir = $modBaseInfo['osdirectory'];

    $osfile = "modules/$modOsDir/xar{$modOsType}api.php";
    if (!file_exists($osfile)) {
        // File does not exist
        // pnAPI compatibility
        $osfile = "modules/$modOsDir/pn{$modOsType}api.php";
        if (!file_exists($osfile)) {
            // File does not exist
            $msg = xarML('Module file #(1) doesn\'t exist.', $osfile);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException($msg));
            return;
        }
    }

    // Load the file
    include $osfile;
    $loadedAPICache["$modName$modType"] = true;

    // Load the API translations files
    if (xarMLS_loadTranslations('module', $modName, 'modules/'.$modOsDir, 'file', $modType.'api') === NULL) return;

    // pnAPI compatibility
    // Load the module language files
    $currentlang = pnUserGetLang();
    $defaultlang = pnConfigGetVar('language');
    if (empty($defaultlang)) {
        $defaultlang = 'eng';
    }
    $oscurrentlang = xarVarPrepForOS($currentlang);
    $osdefaultlang = xarVarPrepForOS($defaultlang);
    if (file_exists("modules/$modOsDir/pnlang/$oscurrentlang/{$modType}api.php")) {
        include "modules/$modOsDir/pnlang/$oscurrentlang/{$modType}api.php";
    } elseif (file_exists("modules/$modOsDir/pnlang/$osdefaultlang/{$modType}api.php")) {
        include "modules/$modOsDir/pnlang/$osdefaultlang/{$modType}api.php";
    }

    // Load database info
    xarMod__loadDbInfo($modName, $modOsDir);

    // Module API loaded successfully, notify the proper event
    xarEvt_notify($modName, $modType, 'ModAPILoad', NULL);

    return true;
}

/**
 * load database definition for a module
 *
 * @param modName name of module to load database definition for
 * @param modDir directory that module is in (if known)
 * @returns bool
 * @return true on success
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
 */
function xarModDBInfoLoad($modName, $modDir = NULL)
{
    if (empty($modName)) {
        $msg = xarML('Empty modname.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Get the directory if we don't already have it
    if (empty($modDir)) {
        $modBaseInfo = xarMod_getBaseInfo($modName);
        if (!isset($modBaseInfo)) {
            return; // throw back
        }

        $modDir = $modBaseInfo['directory'];
    }

    xarMod__loadDbInfo($modName, xarVarPrepForOS($modDir));

    return true;
}

/**
 * Calls a module function.
 *
 * @access public
 * @param modName registered name of module
 * @param modType type of function to run
 * @param funcName specific function to run
 * @param args argument array
 * @returns mixed
 * @return The output of the function, or false on failure
 * @raise BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
 */
function xarModFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{
    if (empty($modName)) {
        $msg = xarML('Empty modname.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Build function name and call function
    $modFunc = "{$modName}_{$modType}_{$funcName}";
    if (!function_exists($modFunc)) {
        $msg = xarML('Module function #(1) doesn\'t exist.', $modFunc);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException($msg));
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
 * Calls a module API function.
 *
 * Using the modules name, type, func, and optional arguments
 * builds a function name by joining them together
 * and using the optional arguments as parameters
 * like so:
 * Ex: modName_modTypeapi_modFunc($args);
 *
 * @access public
 * @param modName registered name of module
 * @param modType type of function to run
 * @param funcName specific function to run
 * @param args arguments to pass to the function
 * @returns mixed
 * @return The output of the function, or false on failure
 * @raise BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
 */
function xarModAPIFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{
    if (empty($modName)) {
        $msg = xarML('Empty modname.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Build function name and call function
    $modAPIFunc = "{$modName}_{$modType}api_{$funcName}";
    if (!function_exists($modAPIFunc)) {
        $msg = xarML('Module API function #(1) doesn\'t exist.', $modAPIFunc);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    return $modAPIFunc($args);
}

/**
 * Generates an URL that reference to a module function.
 *
 * @access public
 * @param modName registered name of module
 * @param modType type of function
 * @param funcName module function
 * @param args array of arguments to put on the URL
 * @returns string
 * @return absolute URL for call, or false on failure
 */
function xarModURL($modName = NULL, $modType = 'user', $funcName = 'main', $args = array(), $generateXMLURL = NULL)
{
    global $xarMod_generateShortURLs, $xarMod_generateXMLURLs;

    if (empty($modName)) {
        return xarServerGetBaseURL() . 'index.php';
    }

    if (!isset($generateXMLURL)) {
        $generateXMLURL = $xarMod_generateXMLURLs;
    }

    if ($xarMod_generateShortURLs &&
        xarModGetVar($modName, 'SupportShortURLs') &&
        xarModAPILoad($modName, $modType)) {

        $encoderArgs = $args;
        $encoderArgs['func'] = $funcName;
        $path = xarModAPIFunc($modName, $modType, 'encode_shorturl', $encoderArgs);
        if (!empty($path)) {
            if ($generateXMLURL) {
                $path = htmlspecialchars($path);
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

    if ($generateXMLURL) {
        $url = htmlspecialchars($url);
    }
    // The URL
    return xarServerGetBaseURL() . $url;
}

/**
 * Gets the displayable name for the passed modName.
 * The displayble name is sensible to user language.
 *
 * @access public
 * @param modName registered name of module
 * @returns string
 * @return the displayable name
 */
function xarModGetDisplayableName($modName)
{
    // The module display name is language sensitive, so it's fetched through xarMLByKey
    return xarMLByKey($modName);
}

/**
 * checks if a module is installed and its state is XARMOD_STATE_ACTIVE
 *
 * @access public
 * @param modName registered name of module
 * @returns bool
 * @return true if the module is available, false if not
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModIsAvailable($modName)
{
    static $modAvailableCache = array();

    if (empty($modName)) {
        $msg = xarML('Empty modname.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (!isset($modAvailableCache[$modName])) {

        $modBaseInfo = xarMod_getBaseInfo($modName);
        if (!isset($modBaseInfo)) {
            return; // throw back
        }

        $modState = $modBaseInfo['state'];
        $modAvailableCache[$modName] = false;

        if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
            if (xarExceptionId() != 'MODULE_NOT_EXIST') {
                return; // throw back
            }
            xarExceptionFree();
        } else {
            if ($modState == XARMOD_STATE_ACTIVE) {
                $modAvailableCache[$modName] = true;
            }
        }
    }
    return $modAvailableCache[$modName];
}

/**
 * carry out hook operations for module
 *
 * @access public
 * @param hookObject the object the hook is called for - either 'item' or 'category'
 * @param hookAction the action the hook is called for - one of 'create', 'delete', 'transform', or 'display'
 * @param hookId the id of the object the hook is called for (module-specific)
 * @param extraInfo extra information for the hook, dependent on hookAction
 * @param callerModName for what module are we calling this (used by modules admin)
 * @returns mixed
 * @return output from hooks, or null if there are no hooks
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST
 */
function xarModCallHooks($hookObject, $hookAction, $hookId, $extraInfo, $callerModName = NULL)
{
    // FIXME: <marco> BAD_PARAM?

    // allow override of current module in special cases (e.g. modules admin)
    if (empty($callerModName)) {
        list($modName) = xarRequestGetInfo();
    } else {
        $modName = $callerModName;
    }

    $hooklist = xarModGetHookList($modName, $hookObject, $hookAction);

    // TODO : check that this is the right way !
    if (!isset($hooklist) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    $output = '';
    $isGUI = false;

// TODO: re-evaluate how GUI / API hooks are handled

    // Call each hook
    foreach ($hooklist as $hook) {
        if ($hook['area'] == 'GUI') {
            $isGUI = true;
            $res = xarModIsAvailable($hook['module'], $hook['type']);
            if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
                return;
            }
            if ($res) {
                $res = xarModLoad($hook['module'], $hook['type']);
                if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
                     return;
                 }
                 if ($res) {
                    $output .= xarModFunc($hook['module'],
                                         $hook['type'],
                                         $hook['func'],
                                         array('objectid' => $hookId,
                                               'extrainfo' => $extraInfo));
                }
            }
        } else {
            $res = xarModIsAvailable($hook['module'], $hook['type']);
            if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
                return;
            }
            if ($res) {
                $res = xarModAPILoad($hook['module'], $hook['type']);
                if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
                    return;
                }
                if ($res) {
                    $extraInfo = xarModAPIFunc($hook['module'],
                                              $hook['type'],
                                              $hook['func'],
                                              array('objectid' => $hookId,
                                                    'extrainfo' => $extraInfo));
                }
            }
        }
    }

    if ($isGUI || $hookAction == 'display') {
        return $output;
    } else {
        return $extraInfo;
    }
}

/**
 * get list of available hooks for a particular module, object and action
 * @access private
 * @param callerModName name of the calling module
 * @param object the hook object
 * @param action the hook action
 * @returns array
 * @return array of hook information arrays, or null if database error
 * @raise DATABASE_ERROR
 */
function xarModGetHookList($callerModName, $hookObject, $hookAction)
{
    static $hookListCache = array();

    if (isset($hookListCache["$callerModName$hookObject$hookAction"])) {
        return $hookListCache["$callerModName$hookObject$hookAction"];
    }

    // Get database info
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $hookstable = $xartable['hooks'];

    // Get applicable hooks
    $query = "SELECT xar_tarea,
                   xar_tmodule,
                   xar_ttype,
                   xar_tfunc
              FROM $hookstable
              WHERE xar_smodule = '" . xarVarPrepForStore($callerModName) . "'
              AND xar_object = '" . xarVarPrepForStore($hookObject) . "'
              AND xar_action = '" . xarVarPrepForStore($hookAction) . "'";
    $result = $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    $resarray = array();
    if ($result->EOF) {
        $hookListCache["$callerModName$hookObject$hookAction"] = $resarray;
        return $resarray;
    }

    while(list($hookArea,
               $hookModName,
               $hookModType,
               $hookFuncName) = $result->fields) {
        $result->MoveNext();

        $tmparray = array('area' => $hookArea,
                          'module' => $hookModName,
                          'type' => $hookModType,
                          'func' => $hookFuncName);

        array_push($resarray, $tmparray);
    }
    $result->Close();

    $hookListCache["$callerModName$hookObject$hookAction"] = $resarray;
    return $resarray;
}

/**
 * check if a module name is an alias for some other module
 * (only used for short URL support at the moment)
 *
 * @access private
 * @param modName name of the module
 * @returns mixed
 * @return string containing the module name, or null if database error
 * @raise BAD_PARAM
 */
function xarModGetAlias($modName)
{
    if (empty($modName)) {
        $msg = xarML('Invalid module name');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    $aliases = xarConfigGetVar('System.ModuleAliases');

    if (isset($aliases) && !empty($aliases[$modName])) {
        return $aliases[$modName];
    } else {
        return $modName;
    }
}

/**
 * define a module name as an alias for some other module
 * (only used for short URL support at the moment)
 *
 * @access public
 * @param modName name of the 'fake' module you want to define
 * @param alias name of the 'real' module you want to assign it to
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM
 */
function xarModSetAlias($modName,$alias)
{
    if (empty($modName) || empty($alias)) {
        $msg = xarML('Invalid module name or alias');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return false;
    }

    // Check if the module name we want to define is already in use
    $modid = xarModGetIDFromName($modName);
    if (isset($modid)) {
        $msg = xarML('Module name #(1) is already in use',
                    xarVarPrepForDisplay($modName));
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return false;
    }
    if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
        // Ignore exceptions here!
        xarExceptionFree();
    }

    // Check if the alias we want to set it to *does* exist
    $modid = xarModGetIDFromName($alias);
    if (!isset($modid)) {
        $msg = xarML('Alias #(1) is unknown',
                    xarVarPrepForDisplay($alias));
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return false;
    }

    // Get the list of current aliases
    $aliases = xarConfigGetVar('System.ModuleAliases');

    if (!isset($aliases)) {
        $aliases = array();
    }

// TODO: what if 2 modules want to use the same aliases ?
    $aliases[$modName] = $alias;
    xarConfigSetVar('System.ModuleAliases', $aliases);

    return true;
}

/**
 * remove an alias for a module name
 * (only used for short URL support at the moment)
 *
 * @access public
 * @param modName name of the 'fake' module you want to remove
 * @param alias name of the 'real' module it was assigned to (= verification)
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM
 */
function xarModDelAlias($modName, $alias)
{
    if (empty($modName) || empty($alias)) {
        $msg = xarML('Invalid module name or alias');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return false;
    }

    $aliases = xarConfigGetVar('System.ModuleAliases');

    // Make sure we only delete it *if* it was assigned to the right alias
    if (isset($aliases) && !empty($aliases[$modName]) &&
        $aliases[$modName] == $alias) {
        unset($aliases[$modName]);
        xarConfigSetVar('System.ModuleAliases',$aliases);
    }

    return true;
}

// PROTECTED FUNCTIONS

/**
 * Get info from xarversion.php
 *
 * @access protected
 * @param modOSdir the module's directory
 * @returns array
 * @return an array of module file information
 * @raise MODULE_FILE_NOT_EXIST
 */
function xarMod_getFileInfo($modOsDir)
{
    $resarray = array();
    // Spliffster, additional mod info from modules/$modOsDir/xarversion.php
    $fileName = 'modules/' . $modOsDir . '/xarversion.php';

    // pnAPI compatibility
    if (!file_exists($fileName)) {
        $fileName = 'modules/' . $modOsDir . '/pnversion.php';
        if (!file_exists($fileName)) {
            $msg = xarML('Module file #(1) doesn\'t exist.', 'modules/' . $modOsDir . '/xar(pn)version.php');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException($msg));
            return;
        }
    }

    include($fileName);

    $modFileInfo['id']             = $modversion['id'];
    $modFileInfo['version']        = $modversion['version'];
    // FIXME: <marco> admin or admin capable?
    $modFileInfo['admin']          = isset($modversion['admin']) ? $modversion['admin'] : false;
    $modFileInfo['admin_capable']  = isset($modversion['admin']) ? $modversion['admin'] : false;
    $modFileInfo['user']           = isset($modversion['user']) ? $modversion['user'] : false;
    $modFileInfo['user_capable']   = isset($modversion['user']) ? $modversion['user'] : false;
    $modFileInfo['securityschema'] = isset($modversion['securityschema']) ? $modversion['securityschema'] : false;
    $modFileInfo['class']          = isset($modversion['class']) ? $modversion['class'] : false;
    $modFileInfo['category']       = isset($modversion['category']) ? $modversion['category'] : false;
    $modFileInfo['locale']         = isset($modversion['locale']) ? $modversion['locale'] : 'en_US.iso-8859-1';

    return $modFileInfo;
}

/**
 * Load a module's base information
 *
 * @access protected
 * @param modName the module's name
 * @returns array
 * @return an array of base module info
 * @raise DATABASE_ERROR, MODULE_NOT_EXIST
 */
function xarMod_getBaseInfo($modName)
{
    if (xarVarIsCached('Mod.BaseInfos', $modName)) {
        return xarVarGetCached('Mod.BaseInfos', $modName);
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
    $modulestable = $tables['modules'];

    $query = "SELECT xar_regid,
                     xar_directory,
                     xar_mode
              FROM $modulestable
              WHERE xar_name = '" . xarVarPrepForStore($modName) . "'";
    $result = $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    if ($result->EOF) {
        $result->Close();
        $msg = xarML('Module #(1) doesn\'t exist.', $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    list($modBaseInfo['regid'],
         $modBaseInfo['directory'],
         $mode) = $result->fields;
    $result->Close();

    $modBaseInfo['name'] = $modName;
    $modBaseInfo['mode'] = (int) $mode;
    $modBaseInfo['displayname'] = xarModGetDisplayableName($modName);
    // Shortcut for os prepared directory
    $modBaseInfo['osdirectory'] = xarVarPrepForOS($modBaseInfo['directory']);

    $modState = xarMod__getState($modBaseInfo['regid'], $modBaseInfo['mode']);
    if (!isset($modState)) return; // throw back
    $modBaseInfo['state'] = $modState;

    xarVarSetCached('Mod.BaseInfos', $modName, $modBaseInfo);

    return $modBaseInfo;
}

/**
 * Get all module variables for a particular module
 *
 * @access protected
 * @returns bool
 * @return true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarMod_getVarsByModule($modName)
{
    if (empty($modName)) {
        $msg = xarML('Empty module name (#(1)).', $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
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
        $module_varsTable = $tables['system/module_vars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_varsTable = $tables['site/module_vars'];
    }

    $query = "SELECT xar_name,
                     xar_value
              FROM $module_varsTable
              WHERE xar_modname = '" . xarVarPrepForStore($modName) . "'";
    $result = $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }
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
 * @access protected
 * @returns bool
 * @return true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarMod_getVarsByName($name)
{
    if (empty($name)) {
        $msg = xarML('Empty variable name (#(1)).', $name);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $module_varsTable = $tables['system/module_vars'];
// TODO: fetch from site table too ?
//    $module_varsTable = $tables['site/module_vars'];

    $query = "SELECT xar_modname,
                     xar_value
              FROM $module_varsTable
              WHERE xar_name = '" . xarVarPrepForStore($name) . "'";
    $result = $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }
    while (!$result->EOF) {
        list($modName,$value) = $result->fields;
        xarVarSetCached('Mod.Variables.' . $modName, $name, $value);
        $result->MoveNext();
    }
    $result->Close();

    xarVarSetCached('Mod.GetVarsByName', $name, true);
    return true;
}

// PRIVATE FUNCTIONS

/**
 * Load database definition for a module
 *
 * @param modName name of module to load database definition for
 * @param modOsDir directory that module is in
 * @returns bool
 * @return true on success
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
 */
function xarMod__loadDbInfo($modName, $modOsDir)
{
    static $loadedDbInfoCache = array();

    // Check to ensure we aren't doing this twice
    if (isset($loadedDbInfoCache[$modName])) {
        return true;
    }

    // Load the database definition if required
    $osxartablefile = "modules/$modOsDir/xartables.php";
    if (!file_exists($osxartablefile)) {
       // pnAPI compatibility
       $osxartablefile = "modules/$modOsDir/pntables.php";
       if (!file_exists($osxartablefile)) {
           return false;
       }
    }
    include_once $osxartablefile;

    $tablefunc = $modName . '_' . 'xartables';
    if (function_exists($tablefunc)) {
        xarDB_importTables($tablefunc());
    } else {
        // pnAPI compatibility
        $tablefunc = $modName . '_' . 'pntables';
        if (function_exists($tablefunc)) {
            xarDB_importTables($tablefunc());
        }
    }

    $loadedDbInfoCache[$modName] = true;

    return true;
}

/**
 * Get the module's current state
 *
 * @param modRegId the module's registered id
 * @param modMode the module's site mode
 * @returns int
 * @return the module's current state
 * @raise DATABASE_ERROR, MODULE_NOT_EXIST
 */
function xarMod__getState($modRegId, $modMode)
{
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
    $result = $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }
/*
    Should never happen!
    if ($result->EOF) {
        $result->Close();
        $msg = xarML('The state of module #(1) is not present.', $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }
*/
    list($modState) = $result->fields;
    $result->Close();

    return (int) $modState;
}
