<?php
/**
 * File: $Id$
 *
 * Modules Support
 *
 * @package modules
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.org
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>
 * @todo Use serialize in module variables?
 */

/*
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
 * Initializes the Modules Support
 *
 * @author Marco Canini <m.canini@libero.it>
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
                    'site/module_vars' => $sitePrefix . '_module_vars');
    // Old tables
    $tables['module_vars']           = $systemPrefix . '_module_vars';
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
 * Gets a module variable
 *
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>, Michel Dalle
 * @access public
 * @param modName The name of the module
 * @param name The name of the variable
 * @returns bool
 * @return mixed The value of the variable or void if variable doesn't exist
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModGetVar($modName, $name)
{
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (empty($name)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
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

    return $value;
}

/**
 * Sets a module variable
 *
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>, Michel Dalle
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
        $module_varsTable = $tables['system/module_vars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_varsTable = $tables['site/module_vars'];
    }

    $oldValue = xarModGetVar($modName, $name);

    if (!isset($oldValue)) {
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back

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

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    xarVarSetCached('Mod.Variables.' . $modName, $name, $value);

    return true;
}


/**
 * Deletes a module variable
 *
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>, Michel Dalle
 * @access public
 * @param modName The name of the module
 * @param name The name of the variable
 * @returns bool
 * @return true on success
 * @raise DATABASE_ERROR, BAD_PARAM
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
        $module_varsTable = $tables['system/module_vars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_varsTable = $tables['site/module_vars'];
    }

    $query = "DELETE FROM $module_varsTable
              WHERE xar_modname = '" . xarVarPrepForStore($modName) . "'
              AND xar_name = '" . xarVarPrepForStore($name) . "'";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    xarVarDelCached('Mod.Variables.' . $modName, $name);

    return true;
}

/**
 * Gets module registry ID given its name
 *
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>
 * @access public
 * @param modName The name of the module
 * @returns string
 * @return The module registry ID.
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
    return $modBaseInfo['regid'];
}

/**
 * Gets information on module
 *
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>, Johnny Robeson
 * @access public
 * @param modRegId module id
 * @returns array
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
    // TODO: <marco> get rid of it since useless
    $modInfo['osdirectory'] = $modInfo['directory'];

    $modState = xarMod__getState($modInfo['regid'], $modInfo['mode']);
    if (!isset($modState)) return; // throw back
    $modInfo['state'] = $modState;

    xarVarSetCached('Mod.BaseInfos', $modInfo['name'], $modInfo);

    $modFileInfo = xarMod_getFileInfo($modInfo['directory']);
    if (!isset($modFileInfo)) return; // throw back
//    $modInfo = array_merge($modInfo, $modFileInfo);
    $modInfo = array_merge($modFileInfo, $modInfo);

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
 * @author Marco Canini <m.canini@libero.it>
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
                                     'class' => 'mods', 'category' => 'mods');

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
                         mods.xar_version,
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

        if (!$result->EOF) {
            while(!$result->EOF) {
                list($modInfo['regid'],
                    $modInfo['name'],
                    $modInfo['directory'],
                    $modInfo['version'],
                    $modState) = $result->fields;
                $result->MoveNext();

                if (xarVarIsCached('Mod.Infos', $modInfo['regid'])) {
                    // Get infos from cache
                    $modList[] = xarVarGetCached('Mod.Infos', $modInfo['regid']);
                } else {
                    $modInfo['mode'] = (int) $mode;
                    $modInfo['displayname'] = xarModGetDisplayableName($modInfo['name']);
                    // Shortcut for os prepared directory
                    // TODO: <marco> get rid of it since useless
                    $modInfo['osdirectory'] = $modInfo['directory'];

                    $modInfo['state'] = (int) $modState;

                    xarVarSetCached('Mod.BaseInfos', $modInfo['name'], $modInfo);

                    $modFileInfo = xarMod_getFileInfo($modInfo['directory']);
                    if (!isset($modFileInfo)) return; // throw back
               //     $modInfo = array_merge($modInfo, $modFileInfo);
                    $modInfo = array_merge($modFileInfo, $modInfo);

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
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>
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

    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if ($modType != 'user' && $modType != 'admin') {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modType');
        return;
    }

    xarLogMessage("xarModLoad: loading $modName:$modType");

    if (isset($loadedModuleCache["$modName$modType"])) {
        // Already loaded from somewhere else
        return true;
    }

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back

    if ($modBaseInfo['state'] != XARMOD_STATE_ACTIVE) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_ACTIVE', $modName);
        return;
    }

    // Load the module files
    $modDir = $modBaseInfo['directory'];

    $fileName = "modules/$modDir/xar$modType.php";

    if (!file_exists($fileName)) {
        // File does not exist
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', $fileName);
        return;
    }

    // Load file
    include $fileName;
    $loadedModuleCache["$modName$modType"] = true;

    // Load the module translations files
    if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, XARMLS_CTXTYPE_FILE, $modType) === NULL) return;

    // FIXME: <marco> Remove it when the old language packs are gone
    $fileName = "modules/$modDir/xarlang/eng/$modType.php";
    if (file_exists($fileName)) {
        include $fileName;
    }

    // Load database info
    xarMod__loadDbInfo($modName, $modDir);

    // Module loaded successfully, notify the proper event
    xarEvt_notify($modName, $modType, 'ModLoad', NULL);

    return true;
}

/**
 * Loads the modType API for module identified by modName.
 *
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>
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

    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if ($modType != 'user' && $modType != 'admin') {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modType');
        return;
    }

    xarLogMessage("xarModAPILoad: loading $modName:$modType");

    if (isset($loadedAPICache["$modName$modType"])) {
        // Already loaded from somewhere else
        return true;
    }

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back

    if ($modBaseInfo['state'] != XARMOD_STATE_ACTIVE) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_ACTIVE', $modName);
        return;
    }

    $modDir = $modBaseInfo['directory'];

    $fileName = "modules/$modDir/xar{$modType}api.php";
    if (!file_exists($fileName)) {
        // File does not exist
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', $fileName);
        return;
    }

    // Load the file
    include $fileName;
    $loadedAPICache["$modName$modType"] = true;

    // Load the API translations files
    if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, XARMLS_CTXTYPE_FILE, $modType.'api') === NULL) return;

    // FIXME: <marco> Remove it when the old language packs are gone
    $fileName = "modules/$modDir/xarlang/eng/{$modType}api.php";
    if (file_exists($fileName)) {
        include $fileName;
    }

    // Load database info
    xarMod__loadDbInfo($modName, $modDir);

    // Module API loaded successfully, notify the proper event
    xarEvt_notify($modName, $modType, 'ModAPILoad', NULL);

    return true;
}

/**
 * load database definition for a module
 *
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>
 * @param modName name of module to load database definition for
 * @param modDir directory that module is in (if known)
 * @returns bool
 * @return true on success
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
 * Calls a module function.
 *
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>, Paul Rosania
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
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if ($modType != 'user' && $modType != 'admin') {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modType');
        return;
    }
    if (empty($funcName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'funcName');
        return;
    }

    // Build function name and call function
    $modFunc = "{$modName}_{$modType}_{$funcName}";
    if (!function_exists($modFunc)) {
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
 * Calls a module API function.
 *
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>
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
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if ($modType != 'user' && $modType != 'admin') {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modType');
        return;
    }
    if (empty($funcName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'funcName');
        return;
    }

    // Build function name and call function
    $modAPIFunc = "{$modName}_{$modType}api_{$funcName}";
    if (!function_exists($modAPIFunc)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST', $modAPIFunc);
        return;
    }

    return $modAPIFunc($args);
}

/**
 * Generates an URL that reference to a module function.
 *
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>, Michel Dalle
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
    if (empty($modName)) {
        return xarServerGetBaseURL() . 'index.php';
    }

    if (!isset($generateXMLURL)) {
        $generateXMLURL = $GLOBALS['xarMod_generateXMLURLs'];
    }

    if ($GLOBALS['xarMod_generateShortURLs'] &&
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
 * @author Marco Canini <m.canini@libero.it>
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
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>
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
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    if (!isset($modAvailableCache[$modName])) {

        $modBaseInfo = xarMod_getBaseInfo($modName);
        if (!isset($modBaseInfo)) return; // throw back

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
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>
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
    if ($hookObject != 'item' && $hookObject != 'category') {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookObject');
        return;
    }
    if ($hookAction != 'create' && $hookAction != 'delete' && $hookAction != 'transform' && $hookAction != 'display') {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookAction');
        return;
    }

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
        // FIXME: <marco> Shouldn't this be a continue; instead of return; ?
        if (!xarModIsAvailable($hook['module'], $hook['type'])) return;
        if ($hook['area'] == 'GUI') {
            $isGUI = true;
            if (!xarModLoad($hook['module'], $hook['type'])) return;
            $res = xarModFunc($hook['module'],
                              $hook['type'],
                              $hook['func'],
                              array('objectid' => $hookId,
                              'extrainfo' => $extraInfo));
            if (!isset($res)) return;
            $output .= $res;
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

    if ($isGUI || $hookAction == 'display') {
        return $output;
    } else {
        return $extraInfo;
    }
}

/**
 * get list of available hooks for a particular module, object and action
 *
 * @author Jim McDonald, Marco Canini <m.canini@libero.it>
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

    if (empty($callerModName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'callerModName');
        return;
    }
    if ($hookObject != 'item' && $hookObject != 'category') {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookObject');
        return;
    }
    if ($hookAction != 'create' && $hookAction != 'delete' && $hookAction != 'transform' && $hookAction != 'display') {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookAction');
        return;
    }

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
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $resarray = array();
    if (!$result->EOF) {

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
    }
    $hookListCache["$callerModName$hookObject$hookAction"] = $resarray;
    return $resarray;
}

// PROTECTED FUNCTIONS

/**
 * Get info from xarversion.php
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access protected
 * @param modDir the module's directory
 * @returns array
 * @return an array of module file information
 * @raise MODULE_FILE_NOT_EXIST
 */
function xarMod_getFileInfo($modDir)
{
    if (empty($modDir)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modDir');
        return;
    }

    $resarray = array();
    // Spliffster, additional mod info from modules/$modDir/xarversion.php
    $fileName = 'modules/' . $modDir . '/xarversion.php';

    if (!file_exists($fileName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', $fileName);
        return;
    }

    include($fileName);

    $modFileInfo['id']             = $modversion['id'];
    $modFileInfo['version']        = $modversion['version'];
    $modFileInfo['description']    = isset($modversion['description']) ? $modversion['description'] : false;
    // FIXME: <marco> admin or admin capable?
    $modFileInfo['admin']          = isset($modversion['admin']) ? $modversion['admin'] : false;
    $modFileInfo['admin_capable']  = isset($modversion['admin']) ? $modversion['admin'] : false;
    $modFileInfo['user']           = isset($modversion['user']) ? $modversion['user'] : false;
    $modFileInfo['user_capable']   = isset($modversion['user']) ? $modversion['user'] : false;
    $modFileInfo['user_menu']      = isset($modversion['user_menu']) ? $modversion['user_menu'] : false;
    $modFileInfo['securityschema'] = isset($modversion['securityschema']) ? $modversion['securityschema'] : false;
    $modFileInfo['class']          = isset($modversion['class']) ? $modversion['class'] : false;
    $modFileInfo['category']       = isset($modversion['category']) ? $modversion['category'] : false;
    $modFileInfo['locale']         = isset($modversion['locale']) ? $modversion['locale'] : 'en_US.ISO-8859-1';

    return $modFileInfo;
}

/**
 * Load a module's base information
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access protected
 * @param modName the module's name
 * @returns array
 * @return an array of base module info
 * @raise DATABASE_ERROR, MODULE_NOT_EXIST
 */
function xarMod_getBaseInfo($modName)
{
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

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
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST', $modName);
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
 * @returns bool
 * @return true on success
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
        $module_varsTable = $tables['system/module_vars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_varsTable = $tables['site/module_vars'];
    }

    $query = "SELECT xar_name,
                     xar_value
              FROM $module_varsTable
              WHERE xar_modname = '" . xarVarPrepForStore($modName) . "'";
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
 * @returns bool
 * @return true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarMod_getVarsByName($name)
{
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
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

// PRIVATE FUNCTIONS

/**
 * Load database definition for a module
 *
 * @author Marco Canini <m.canini@libero.it>
 * @param modName name of module to load database definition for
 * @param modDir directory that module is in
 * @returns bool
 * @return true on success
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
 * @author Marco Canini <m.canini@libero.it>
 * @param modRegId the module's registered id
 * @param modMode the module's site mode
 * @returns int
 * @return the module's current state
 * @raise DATABASE_ERROR, MODULE_NOT_EXIST
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

    // Should never happen!
    assert(!$result->EOF);

    list($modState) = $result->fields;
    $result->Close();

    return (int) $modState;
}

?>
