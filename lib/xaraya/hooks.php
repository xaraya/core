<?php
/**
 * (Module) Hooks handling subsystem - moved from modules to hooks for (future) clarity
 * @todo Hooks are currently linked with modules - see also xaraya.structures.hooks.* ?
 *
 * @package hooks
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 */

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
 * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST
 * @todo <marco> add BAD_PARAM exception
 * @todo <marco> <mikespub> re-evaluate how GUI / API hooks are handled
 * @todo add itemtype (in extrainfo or as additional parameter)
 */
function xarModCallHooks($hookObject, $hookAction, $hookId, $extraInfo = NULL, $callerModName = NULL, $callerItemType = '')
{
    //if ($hookObject != 'item' && $hookObject != 'category') {
    //    throw new BadParameterException('hookObject');
    //if ($hookAction != 'create' && $hookAction != 'delete' && $hookAction != 'transform' && $hookAction != 'display') {
    //    throw new BadParameterException('hookAction');


    // allow override of current module if necessary (e.g. modules admin, blocks, API functions, ...)
    if (empty($callerModName)) {
        if (isset($extraInfo) && is_array($extraInfo) && !empty($extraInfo['module'])) {
            $modName = $extraInfo['module'];
        } else {
            list($modName) = xarRequest::getInfo();
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

    $output = array();
    $isGUI = false;

    // TODO: #3
    // Call each hook
    foreach ($hooklist as $hook) {
        //THIS IS BROKEN
        //$hook['type'] and $type in the xarMod::isAvailable ARE NOT THE SAME THING
//        if (!xarMod::isAvailable($hook['module'], $hook['type'])) continue;
        if (!xarMod::isAvailable($hook['module'])) continue;
        if ($hook['area'] == 'GUI') {
            $isGUI = true;
            if (!xarMod::load($hook['module'], $hook['type'])) return;
            $res = xarMod::guiFunc($hook['module'], $hook['type'], $hook['func'],
                              array('objectid' => $hookId, 'extrainfo' => $extraInfo));
            if (!isset($res)) return;
            // Note: hook modules can only register 1 hook per hookObject, hookAction and hookArea
            //       so using the module name as key here is OK (and easier for designers)
            $output[$hook['module']] = $res;
        } else {
            if (!xarMod::apiLoad($hook['module'], $hook['type'])) return;
            $res = xarMod::apiFunc($hook['module'], $hook['type'], $hook['func'],
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
    if ($isGUI || mb_eregi('^(display|new|modify|search|usermenu|modifyconfig)$',$hookAction)) {
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
 * @throws DATABASE_ERROR
 */
function xarModGetHookList($callerModName, $hookObject, $hookAction, $callerItemType = '')
{
    static $hookListCache = array();

    if (empty($callerModName)) throw new EmptyParameterException('callerModName');

    //if ($hookObject != 'item' && $hookObject != 'category') {
    //    throw new BadParameterException('hookObject');
    //}
    //if ($hookAction != 'create' && $hookAction != 'delete' && $hookAction != 'transform' && $hookAction != 'display') {
    //    throw new BadParameterException('hookAction');
    //}

    if (isset($hookListCache["$callerModName$callerItemType$hookObject$hookAction"])) {
        return $hookListCache["$callerModName$callerItemType$hookObject$hookAction"];
    }

    // Get database info
    $dbconn   = xarDB::getConn();
    $xartable = xarDB::getTables();
    $hookstable    = $xartable['hooks'];
    $modulestable  = $xartable['modules'];

    // Get applicable hooks
    // New query:
    $query ="SELECT DISTINCT hooks.t_area, tmods.name,
                             hooks.t_type, hooks.t_func, hooks.priority
             FROM $hookstable hooks, $modulestable tmods, $modulestable smods
             WHERE hooks.t_module_id = tmods.id AND
                   hooks.s_module_id = smods.id AND
                   smods.name = ?";
    $bindvars = array($callerModName);

    if (empty($callerItemType)) {
        // Itemtype is not specified, only get the generic hooks
        $query .= " AND hooks.s_type = ?";
        $bindvars[] = '';
    } else {
        // hooks can be enabled for all or for a particular item type
        $query .= " AND (hooks.s_type = ? OR hooks.s_type = ?)";
        $bindvars[] = '';
        $bindvars[] = (string)$callerItemType;
        // Q     : if itemtype is specified, why get the generic hooks? To save a function call in the modules?
        // Answer: generic hooks apply for *all* itemtypes, so if a caller specifies an itemtype, you
        //         need to check whether hooks are enabled for this particular itemtype or for all
        //         itemtypes here...
    }
    $query .= " AND hooks.object = ? AND hooks.action = ? ORDER BY hooks.priority ASC";
    $bindvars[] = $hookObject;
    $bindvars[] = $hookAction;
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_NUM);

    $resarray = array();
    while($result->next()) {
        list($hookArea, $hookModName, $hookModType, $hookFuncName, $hookOrder) = $result->getRow();

        $tmparray = array('area' => $hookArea,
                          'module' => $hookModName,
                          'type' => $hookModType,
                          'func' => $hookFuncName);

        array_push($resarray, $tmparray);
    }
    $result->Close();
    $hookListCache["$callerModName$callerItemType$hookObject$hookAction"] = $resarray;
    return $resarray;
}

/**
 * Check if a particular hook module is hooked to the current module (+ itemtype)
 *
 * @access public
 * @static modHookedCache array
 * @param hookModName string name of the hook module we're looking for
 * @param callerModName string name of the calling module (default = current)
 * @param callerItemType string optional item type for the calling module (default = none)
 * @return mixed true if the module is hooked
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarModIsHooked($hookModName, $callerModName = NULL, $callerItemType = '')
{
    static $modHookedCache = array();

    if (empty($hookModName)) throw new EmptyParameterException('hookModName');

    if (empty($callerModName)) {
        list($callerModName) = xarRequest::getInfo();
    }

    // Get all hook modules for the caller module once
    if (!isset($modHookedCache[$callerModName])) {
        // Get database info
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        $hookstable   = $xartable['hooks'];
        $modulestable = $xartable['modules'];

        // Get applicable hooks
        // New query:
        $query = "SELECT DISTINCT tmods.name, hooks.s_type
                  FROM  $hookstable hooks, $modulestable tmods, $modulestable smods
                  WHERE hooks.s_module_id = smods.id AND
                        hooks.t_module_id = tmods.id AND
                        smods.name = ?";
        $bindvars = array($callerModName);
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);

        $modHookedCache[$callerModName] = array();
        while($result->next()) {
            list($modname,$itemtype) = $result->fields;
            if (!empty($itemtype)) {
                $itemtype = trim($itemtype);
            }
            if (!isset($modHookedCache[$callerModName][$itemtype])) {
                $modHookedCache[$callerModName][$itemtype] = array();
            }
            $modHookedCache[$callerModName][$itemtype][$modname] = 1;
        }
        $result->close();
    }
    if (empty($callerItemType)) {
        if (isset($modHookedCache[$callerModName][''][$hookModName])) {
            // generic hook is enabled
            return true;
        } else {
            return false;
        }
    } elseif (is_numeric($callerItemType)) {
        if (isset($modHookedCache[$callerModName][''][$hookModName])) {
            // generic hook is enabled
            return true;
        } elseif (isset($modHookedCache[$callerModName][$callerItemType][$hookModName])) {
            // or itemtype-specific hook is enabled
            return true;
        } else {
            return false;
        }
    } elseif (is_array($callerItemType) && count($callerItemType) > 0) {
        if (isset($modHookedCache[$callerModName][''][$hookModName])) {
            // generic hook is enabled
            return true;
        } else {
            foreach ($callerItemType as $itemtype) {
                if (!is_numeric($itemtype)) continue;
                if (isset($modHookedCache[$callerModName][$itemtype][$hookModName])) {
                    // or at least one of the itemtype-specific hooks is enabled
                    return true;
                }
            }
        }
    }
    return false;
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
 * @throws DATABASE_ERROR
 * @todo check for params?
 */
function xarModRegisterHook($hookObject, $hookAction, $hookArea, $hookModName, $hookModType, $hookFuncName)
{
    // Get database info
    $dbconn   = xarDB::getConn();
    $xartable = xarDB::getTables();
    $hookstable = $xartable['hooks'];

    // Insert hook
    try {
        $dbconn->begin();
        // New query: the same but insert the modid's instead of the modnames into tmodule
        $tmodInfo = xarMod::getBaseInfo($hookModName);
        $tmodId = $tmodInfo['systemid'];
        $query = "INSERT INTO $hookstable
                  (object, action, s_type, t_area, t_module_id, t_type, t_func)
                  VALUES (?,?,?,?,?,?,?)";
        $bindvars = array($hookObject,$hookAction,'',$hookArea,$tmodId,$hookModType,$hookFuncName);
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeUpdate($bindvars);
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
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
function xarModUnregisterHook($hookObject, $hookAction, $hookArea,$hookModName, $hookModType, $hookFuncName)
{
    // Get database info
    $dbconn   = xarDB::getConn();
    $xartable = xarDB::getTables();
    $hookstable = $xartable['hooks'];

    // Remove hook
    try {
        $dbconn->begin();
        // New query: same but test on tmodid instead of tmodname
        $tmodInfo = xarMod::getBaseInfo($hookModName);
        $tmodId = $tmodInfo['systemid'];
        $query = "DELETE FROM $hookstable
                  WHERE object = ?
                  AND action = ? AND t_area = ? AND t_module_id = ?
                  AND t_type = ?  AND t_func = ?";
        $stmt = $dbconn->prepareStatement($query);
        $bindvars = array($hookObject,$hookAction,$hookArea,$tmodId,$hookModType,$hookFuncName);
        $stmt->executeUpdate($bindvars);
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
    return true;
}

?>
