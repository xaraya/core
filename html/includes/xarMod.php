<?php
/**
 * File: $Id: s.xarMod.php 1.123 03/01/21 13:54:43+00:00 johnny@falling.local.lan $
 * 
 * Module handling subsystem
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
 * Get a module variable
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
 * Set a module variable
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
 * Delete a module variable
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
    if (!isset($modState)) return; // throw back
    $modInfo['state'] = $modState;

    xarVarSetCached('Mod.BaseInfos', $modInfo['name'], $modInfo);

    $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory']);
    if (!isset($modFileInfo)) return; // throw back
//    $modInfo = array_merge($modInfo, $modFileInfo);
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
 * @author Marco Canini <marco@xaraya.com>
 * @param filter array of criteria used to filter the entire list of installed
 *        modules.
 * @param startNum integer the start offset in the list
 * @param numItems integer the length of the list
 * @param orderBy string the order type of the list
 * @return array of module information arrays
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
                    $modInfo['osdirectory'] = xarVarPrepForOS($modInfo['directory']);

                    $modInfo['state'] = (int) $modState;

                    xarVarSetCached('Mod.BaseInfos', $modInfo['name'], $modInfo);

                    $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory']);
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
 * Load the modType of module identified by modName.
 *
 * @access public
 * @param modName string - name of module to load
 * @param modType string - type of functions to load
 * @return mixed
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_NOT_ACTIVE
 */
function xarModLoad($modName, $modType = 'user')
{
    static $loadedModuleCache = array();

    xarLogMessage("xarModLoad: loading $modName:$modType");

    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (!xarCoreIsApiAllowed($modType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', "modType : $modType for $modName");
        return;
    }

    // Make sure we access the cache with lower case key
    if (isset($loadedModuleCache[strtolower("$modName$modType")])) {
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
    // MrB: this was a fix in main (the strtolower thing)
    // Make sure we access the case with lower case key
    $loadedModuleCache[strtolower("$modName$modType")] = true;

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
 * Load the modType API for module identified by modName.
 *
 * @access public
 * @param modName string registered name of the module
 * @param modType string type of functions to load
 * @return mixed true on success
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_NOT_ACTIVE
 */
function xarModAPILoad($modName, $modType = 'user')
{
    static $loadedAPICache = array();
    
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    
    if (!xarCoreIsAPIAllowed($modType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', "modType : $modType for $modName");
        return;
    }
    // MrB: strtolower fix in main, merged into review
    if (isset($loadedAPICache[strtolower("$modName$modType")])) {
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
    // MrB: strtolower fix from main
    include $fileName;
    $loadedAPICache[strtolower("$modName$modType")] = true;

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
 * Load database definition for a module
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
 * @return mixed The output of the function, or false on failure
 * @raise BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
 */
function xarModAPIFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{
    if (empty($modName)) {
        die("$modName, $modType, $funcName");
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
    $modAPIFunc = "{$modName}_{$modType}api_{$funcName}";
	if (!function_exists($modAPIFunc)) {
        // attempt to load the module's api
		xarModAPILoad($modName,$modType);
		// let's check for that function again to be sure
		if (!function_exists($modAPIFunc)) {
            //die("api load went fine");
			$msg = xarML('Module API function #(1) doesn\'t exist.', $modAPIFunc);
			xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
							new SystemException($msg));
			return;
		}
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
    
    if ($target != NULL) {
        $url = "$url#$target";
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
    return xarMLByKey($modName);
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
 * Carry out hook operations for module
 *
 * @access public
 * @param hookObject string the object the hook is called for - either 'item' or 'category'
 * @param hookAction string the action the hook is called for - one of 'create', 'delete', 'transform', or 'display'
 * @param hookId integer the id of the object the hook is called for (module-specific)
 * @param extraInfo mixed extra information for the hook, dependent on hookAction
 * @param callerModName string for what module are we calling this (used by modules admin)
 * @return mixed output from hooks, or null if there are no hooks
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST
 * @todo <marco> #1 add BAD_PARAM exception
 * @todo <marco> #2 check way of hanlding exception
 * @todo <marco> <mikespub> re-evaluate how GUI / API hooks are handled
 */
function xarModCallHooks($hookObject, $hookAction, $hookId, $extraInfo, $callerModName = NULL)
{
    //if ($hookObject != 'item' && $hookObject != 'category') {
    //    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookObject');
    //    return;
    //}
    //if ($hookAction != 'create' && $hookAction != 'delete' && $hookAction != 'transform' && $hookAction != 'display') {
    //    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookAction');
    //    return;
    //}

    // allow override of current module in special cases (e.g. modules admin)
    if (empty($callerModName)) {
        list($modName) = xarRequestGetInfo();
    } else {
        $modName = $callerModName;
    }

    $hooklist = xarModGetHookList($modName, $hookObject, $hookAction);

    // TODO: #2
    if (!isset($hooklist) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    $output = '';
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
 * Get list of available hooks for a particular module, object and action
 *
 * @access private
 * @param callerModName string name of the calling module
 * @param object string the hook object
 * @param action string the hook action
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
    //if ($hookObject != 'item' && $hookObject != 'category') {
    //    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookObject');
    //    return;
    //}
    //if ($hookAction != 'create' && $hookAction != 'delete' && $hookAction != 'transform' && $hookAction != 'display') {
    //    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookAction');
    //    return;
    //}

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
              AND xar_action = '" . xarVarPrepForStore($hookAction) . "'
              ORDER BY xar_order ASC";
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

/**
 * check if a module name is an alias for some other module
 *
 * (only used for short URL support at the moment)
 *
 * @access private
 * @param modName name of the module
 * @returns mixed
 * @return string containing the module name, or null if database error
 * @raise BAD_PARAM
 */
// function xarModGetAlias($modName)
// {
//     if (empty($modName)) {
//         $msg = xarML('Invalid module name');
//         xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
//                        new SystemException($msg));
//         return;
//     }

//     $aliases = xarConfigGetVar('System.ModuleAliases');

//     if (isset($aliases) && !empty($aliases[$modName])) {
//         return $aliases[$modName];
//     } else {
//         return $modName;
//     }
// }

/**
 * Define a module name as an alias for some other module
 *
 * (only used for short URL support at the moment)
 *
 * @access public
 * @param modName string name of the 'fake' module you want to define
 * @param alias string name of the 'real' module you want to assign it to
 * @return mixed true on success
 * @raise BAD_PARAM
 * @todo <marco> <mikespub> #1 what if 2 modules want to use the same aliases ?
 */
// function xarModSetAlias($modName, $alias)
// {
//     if (empty($modName) || empty($alias)) {
//         $msg = xarML('Invalid module name or alias');
//         xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
//                        new SystemException($msg));
//         return false;
//     }

//     // Check if the module name we want to define is already in use
//     $modid = xarModGetIDFromName($modName);
//     if (isset($modid)) {
//         $msg = xarML('Module name #(1) is already in use',
//                     xarVarPrepForDisplay($modName));
//         xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
//                        new SystemException($msg));
//         return false;
//     }
//     if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
//         // Ignore exceptions here!
//         xarExceptionFree();
//     }

//     // Check if the alias we want to set it to *does* exist
//     $modid = xarModGetIDFromName($alias);
//     if (!isset($modid)) {
//         $msg = xarML('Alias #(1) is unknown',
//                     xarVarPrepForDisplay($alias));
//         xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
//                        new SystemException($msg));
//         return false;
//     }

//     // Get the list of current aliases
//     $aliases = xarConfigGetVar('System.ModuleAliases');

//     if (!isset($aliases)) {
//         $aliases = array();
//     }

//     // TODO: #1
//     $aliases[$modName] = $alias;
//     xarConfigSetVar('System.ModuleAliases', $aliases);

//     return true;
//}

/**
 * Remove an alias for a module name
 *
 * (only used for short URL support at the moment)
 *
 * @access public
 * @param modName string name of the 'fake' module you want to remove
 * @param alias string name of the 'real' module it was assigned to (= verification)
 * @return mixed true on success
 * @raise BAD_PARAM
 */
// function xarModDelAlias($modName, $alias)
// {
//     if (empty($modName) || empty($alias)) {
//         $msg = xarML('Invalid module name or alias');
//         xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
//                        new SystemException($msg));
//         return false;
//     }

//     $aliases = xarConfigGetVar('System.ModuleAliases');

//     // Make sure we only delete it *if* it was assigned to the right alias
//     if (isset($aliases) && !empty($aliases[$modName]) &&
//         $aliases[$modName] == $alias) {
//         unset($aliases[$modName]);
//         xarConfigSetVar('System.ModuleAliases',$aliases);
//     }

//     return true;
// }

/**
 * Get info from xarversion.php for module specified by modOsDir
 *
 * @access protected
 * @param modOSdir the module's directory
 * @returns array
 * @return an array of module file information
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
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', $fileName);
        return;
    }

    include($fileName);

    $modFileInfo['id']             = $modversion['id'];
    $modFileInfo['version']        = $modversion['version'];
    $modFileInfo['description']    = isset($modversion['description']) ? $modversion['description'] : false;
    // TODO: 1
    $modFileInfo['admin']          = isset($modversion['admin']) ? $modversion['admin'] : false;
    $modFileInfo['admin_capable']  = isset($modversion['admin']) ? $modversion['admin'] : false;
    $modFileInfo['user']           = isset($modversion['user']) ? $modversion['user'] : false;
    $modFileInfo['user_capable']   = isset($modversion['user']) ? $modversion['user'] : false;
    $modFileInfo['user_menu']      = isset($modversion['user_menu']) ? $modversion['user_menu'] : false;
    $modFileInfo['securityschema'] = isset($modversion['securityschema']) ? $modversion['securityschema'] : false;
    $modFileInfo['class']          = isset($modversion['class']) ? $modversion['class'] : false;
    $modFileInfo['category']       = isset($modversion['category']) ? $modversion['category'] : false;
    $modFileInfo['locale']         = isset($modversion['locale']) ? $modversion['locale'] : 'en_US.iso-8859-1';
    // EXTRA INFO: required by components mod and possibly other core modules; added by <andyv>
    $modFileInfo['author']         = isset($modversion['author']) ? $modversion['author'] : false;
    $modFileInfo['contact']        = isset($modversion['contact']) ? $modversion['contact'] : false;
    $modFileInfo['dependency']     = isset($modversion['dependency']) ? $modversion['dependency'] : false;
    
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
    // with reality. I've take a couple ones out, but I haven't testen all
    // the way through.
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
 * @param name string
 * @return mixed true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 * @todo <marco> #1 fetch from site table too ?
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
        return (int) XARMOD_STATE_UNINITIALISED;
    }
}

?>
