<?php
/**
 * (Module) Hooks handling subsystem - moved from modules to hooks for (future) clarity
 * @todo Hooks are currently linked with modules & itemtypes, not objects
 * @checkme Control actions further and e.g. automatically detect & call hook actions in various places ?
 * @checkme Replace 'module' with 'config' scope to indicate that we actually configure hooks for module itemtypes, objects, etc. there ?
 *
 * @package hooks
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 */

/**
 * Carry out hook operations for module
 *
 * @access public
 * @param hookScope string the scope the hook is called for - 'item', 'module', ...
 * @param hookAction string the action the hook is called for - 'transform', 'display', 'new', 'create', 'delete', ...
 * @param hookId integer the id of the object the hook is called for (module-specific)
 * @param extraInfo mixed extra information for the hook, dependent on hookAction
 * @param callerModName string for what module are we calling this (default = current main module)
 *        Note : better pass the caller module via $extraInfo['module'] if necessary, so that hook functions receive it too
 * @param callerItemType string optional item type for the calling module (default = none)
 *        Note : better pass the item type via $extraInfo['itemtype'] if necessary, so that hook functions receive it too
 * @return mixed output from hooks, or null if there are no hooks
 * @throws BadParameterException
 */
function xarModCallHooks($hookScope, $hookAction, $hookId, $extraInfo = NULL, $callerModName = NULL, $callerItemType = '')
{
    // internal class name - do not use outside of xaraya.hooks yet
    return xarModuleHooks::callHooks($hookScope, $hookAction, $hookId, $extraInfo, $callerModName, $callerItemType);
}

/**
 * Get list of available hooks for a particular module[, scope] and action
 *
 * @access private
 * @param callerModName string name of the calling module
 * @param hookScope string the hook scope
 * @param hookAction string the hook action
 * @param callerItemType string optional item type for the calling module (default = none)
 * @return array of hook information arrays, or null if database error
 * @throws DATABASE_ERROR
 */
function xarModGetHookList($callerModName, $hookScope, $hookAction, $callerItemType = '')
{
    // internal class name - do not use outside of xaraya.hooks yet
    return xarModuleHooks::getHookList($callerModName, $hookScope, $hookAction, $callerItemType);
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
    // internal class name - do not use outside of xaraya.hooks yet
    return xarModuleHooks::isHooked($hookModName, $callerModName, $callerItemType);
}

/**
 * register a hook function
 *
 * @access public
 * @param hookScope the hook scope
 * @param hookAction the hook action
 * @param hookArea the area of the hook (either 'GUI' or 'API')
 * @param hookModName name of the hook module
 * @param hookModType name of the hook type ('user' / 'admin' / ... for regular functions, or 'class' for hook call handlers)
 * @param hookModFunc name of the hook function or handler class
 * @return bool true on success
 * @throws BadParameterException
 */
function xarModRegisterHook($hookScope, $hookAction, $hookArea, $hookModName, $hookModType, $hookModFunc)
{
    return xarHooks::registerHookFunc($hookScope, $hookAction, $hookArea,$hookModName, $hookModType, $hookModFunc);
}

/**
 * unregister a hook function (deprecated - use unregisterHookModule or the standard deinstall for modules instead)
 *
 * @access public
 * @param hookScope the hook scope
 * @param hookAction the hook action
 * @param hookArea the area of the hook (either 'GUI' or 'API')
 * @param hookModName name of the hook module
 * @param hookModType name of the hook type ('user' / 'admin' / ... for regular functions, or 'class' for hook call handlers)
 * @param hookModFunc name of the hook function or handler class
 * @return bool true if the unregister call suceeded, false if it failed
 * @throws BadParameterException
 */
function xarModUnregisterHook($hookScope, $hookAction, $hookArea,$hookModName, $hookModType, $hookModFunc)
{
    return xarHooks::unregisterHookFunc($hookScope, $hookAction, $hookArea,$hookModName, $hookModType, $hookModFunc);
}

/**
 * Extensions to the hook system in Xaraya 2.x:
 *
 * 1. support hooked objects (e.g. dataobject) next to traditional hooked modules & itemtypes (e.g. articles)
 * 2. support hook call handlers in classes (e.g. hitcount) next to traditional hook module functions (e.g. categories)
 *
 * And of course any combination of old and new, i.e.
 *
 *   Subject                    =>> Observers
 *   xarModuleHooks
 *   - hooked module & itemtype =>> hook module functions (1.x way)
 *   - hooked module & itemtype =>> hook call handlers    (2.x way for hooks)
 *   xarObjectHooks
 *   - hooked object            =>> hook module functions (2.x way for object)
 *   - hooked object            =>> hook call handlers    (2.x way)
 *
 * Other changes to the hook system in Xaraya 2.x:
 *
 * The hook object ('item', 'module', ...) is now referred to as hook scope, to reflect actual usage.
 * Also, the hook action now uniquely identifies the scope (object), type and area of the hook as well,
 * based on how they were used in hook modules in the last few years - see standard list below. If you
 * added other hook actions in your module or distribution, please add them to the list below as well...
 *
 * @todo identify hooked objects by name someday, and adapt xar_hooks table too ?
 */

/**
 * Public class methods to register/unregister hook functions and hook call handlers, and verify hook actions
 */
class xarHooks
{
    /**
     * Standard hook actions with their corresponding scope, area and (typical) type
     *
     * This list should be fairly static and restricted to allow maximum interoperability between modules
     *
     * Note: the type is variable here, e.g. 'user', 'admin', 'class', ... depending on where
     * the hook module developer places the hook functions, and is mentioned for information only
     */
    private static $actions = array(
        /**
         * Default 'item' hook actions supported by many modules
         */

        // item API
        'create'    => array('scope' => 'item', 'area' => 'API', 'type' => 'admin'),
        'update'    => array('scope' => 'item', 'area' => 'API', 'type' => 'admin'),
        'delete'    => array('scope' => 'item', 'area' => 'API', 'type' => 'admin'),
        'transform' => array('scope' => 'item', 'area' => 'API', 'type' => 'user'),

        // item GUI
        'new'       => array('scope' => 'item', 'area' => 'GUI', 'type' => 'admin'),
        'modify'    => array('scope' => 'item', 'area' => 'GUI', 'type' => 'admin'),
        'display'   => array('scope' => 'item', 'area' => 'GUI', 'type' => 'user'),

        /**
         * Default 'module' hook actions supported by many modules
         */

        // module API
        'updateconfig' => array('scope' => 'module', 'area' => 'API', 'type' => 'admin'),
        'remove'       => array('scope' => 'module', 'area' => 'API', 'type' => 'module'),

        // module GUI
        'modifyconfig' => array('scope' => 'module', 'area' => 'GUI', 'type' => 'admin'),

        /**
         * Common 'reverse' hook actions - do not process those with hook call handlers !
         */

        'search'         => array('scope' => 'item', 'area' => 'GUI', 'type' => 'user'),
        'usermenu'       => array('scope' => 'item', 'area' => 'GUI', 'type' => 'user'),
        'waitingcontent' => array('scope' => 'item', 'area' => 'GUI', 'type' => 'admin'),

        /**
         * Other hook actions used in specific modules and/or distributions
         */

        // future standard item GUI action ?
        'view'            => array('scope' => 'item', 'area' => 'GUI', 'type' => 'user'),

        // used e.g. in bbcode
        'formheader'      => array('scope' => 'item', 'area' => 'GUI', 'type' => 'user'),
        'formaction'      => array('scope' => 'item', 'area' => 'GUI', 'type' => 'user'),
        'formdisplay'     => array('scope' => 'item', 'area' => 'GUI', 'type' => 'user'),
        'formarea'        => array('scope' => 'item', 'area' => 'GUI', 'type' => 'user'),
        // used e.g. in formantibot
        'submit'          => array('scope' => 'item', 'area' => 'API', 'type' => 'admin'),
        // used e.g. in html
        'transform-input' => array('scope' => 'item', 'area' => 'API', 'type' => 'user'),

        // (no longer) used in categories and query ?
        'getconfig'       => array('scope' => 'module', 'area' => 'API', 'type' => 'admin'),

        /**
         * Custom hook actions used only on your site (for example)
         */

        'api_example'     => array('scope' => 'item', 'area' => 'API', 'type' => 'user'),
        'gui_example'     => array('scope' => 'item', 'area' => 'GUI', 'type' => 'user'),

    );

    public static function getActionScope($hookAction)
    {
        if (!empty(self::$actions[$hookAction])) {
            return self::$actions[$hookAction]['scope'];
        }
    }

    public static function getActionArea($hookAction)
    {
        if (!empty(self::$actions[$hookAction])) {
            return self::$actions[$hookAction]['area'];
        }
    }

    public static function isActionDefined($hookAction)
    {
        if (!empty(self::$actions[$hookAction])) {
            return true;
        } else {
            return false;
        }
    }

// CHECKME: should we move those "administrative" functions back to the 'modules' module again ?

    /**
     * Register a hook function
     */
    public static function registerHookFunc($hookScope, $hookAction, $hookArea, $hookModName, $hookModType, $hookModFunc, $hookModClassPath = '')
    {
    // CHECKME: verify/reject registrations of unknown actions !? Yes for now, to check that we got most of them ;-)
        if (!xarHooks::isActionDefined($hookAction)) {
            throw new BadParameterException('hookAction');
            return false;
        }

        $checkScope = xarHooks::getActionScope($hookAction);
        if (!empty($checkScope) && $checkScope != $hookScope) {
            // reject registrations for the wrong scope ('item' or 'module' for now, perhaps 'config' later)
            throw new BadParameterException('hookScope');
            return false;
        }

        $checkArea = xarHooks::getActionArea($hookAction);
        if (!empty($checkArea) && $checkArea != $hookArea) {
            // reject registrations for the wrong area ('API' or 'GUI')
            throw new BadParameterException('hookArea');
            return false;
        }

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
                      (object, action, s_type, t_area, t_module_id, t_type, t_func, t_file)
                      VALUES (?,?,?,?,?,?,?,?)";
            $bindvars = array($hookScope,$hookAction,'',$hookArea,$tmodId,$hookModType,$hookModFunc,$hookModClassPath);
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
     * Register the actions supported by the hook call handler $hookModClassName in file $hookModClassFile (new in 2.x)
     *
     * @param hookModName name of the hook module
     * @param hookModClassName name of the hook handler class (e.g. 'HitcountItemHooks')
     * @param hookModClassPath import path for the class file (e.g. 'modules.hitcount.class.itemhooks')
     */
    public static function registerHookCallHandler($hookModName, $hookModClassName, $hookModClassPath)
    {
        try {
            // import the class file
            sys::import($hookModClassPath);
            if (!class_exists($hookModClassName)) {
                return false;
            }

            // get the list of supported actions for this hook call handler
            $handler = new $hookModClassName();
            // check if this handler supports any actions
            if (empty($handler->actions) || !method_exists($handler, 'handle')) {
                return false;
            }
            // get the scope and actions from the handler
            $scope = $handler->scope;
            $actions = $handler->actions;

            // register the different actions with type 'class' for this hook call handler
            foreach ($actions as $action => $info) {
                self::registerHookFunc($scope, $action, $info['area'], $hookModName, 'class', $hookModClassName, $hookModClassPath);
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Unregister a hook function (deprecated in 2.x - use unregisterHookModule or the standard deinstall for modules instead)
     */
    public static function unregisterHookFunc($hookScope, $hookAction, $hookArea, $hookModName, $hookModType, $hookModFunc, $hookModClassPath = '')
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
            // Note: we don't really care about $hookModClassPath here
            $bindvars = array($hookScope,$hookAction,$hookArea,$tmodId,$hookModType,$hookModFunc);
            $stmt->executeUpdate($bindvars);
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * Unregister all hooks for a hook module (new in 2.x, but recommend to use the standard deinstall for modules if possible)
     */
    public static function unregisterHookModule($hookModName)
    {
        // Get database info
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        $hookstable = $xartable['hooks'];

        // Remove hooks
        try {
            $dbconn->begin();
            // New query: same but test on tmodid instead of tmodname
            $tmodInfo = xarMod::getBaseInfo($hookModName);
            $tmodId = $tmodInfo['systemid'];
            $query = "DELETE FROM $hookstable
                      WHERE t_module_id = ?";
            $stmt = $dbconn->prepareStatement($query);
            $bindvars = array($tmodId);
            $stmt->executeUpdate($bindvars);
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }
        return true;
    }
}

/**
 * Public class methods for hooked modules & itemtypes (internal to xaraya.hooks for now - class name may change)
 */
class xarModuleHooks
{
    private static $hookListCache = array();
    private static $isHookedCache = array();

    public static function callHooks($hookScope, $hookAction, $hookId, $extraInfo = NULL, $callerModName = NULL, $callerItemType = '')
    {
        // initialize extraInfo where necessary
        $extraInfo = xarModuleHooks::initExtraInfo($hookId, $extraInfo, $callerModName, $callerItemType);

        // update arguments based on extraInfo
        $modName = $extraInfo['module'];
        $callerItemType = $extraInfo['itemtype'];
        $hookId = $extraInfo['itemid'];

        // get the hook list for this module, itemtype, scope and hook action
        xarLogMessage("xarModuleHooks: getting $hookScope $hookAction hooks for $modName.$callerItemType");
        $hookList = xarModuleHooks::getHookList($modName, $hookScope, $hookAction, $callerItemType);

        // try to determine 'API' or 'GUI' from the standard hook actions
        $hookArea = xarHooks::getActionArea($hookAction);
        if (empty($hookArea)) {
            if (!empty($hookList)) {
                // otherwise check the area of the first hook in the hooklist
                $hookArea = $hookList[0]['area'];
            } else {
                // otherwise default to API
                $hookArea = 'API';
            }
        }

        // run each hook in the hookList using xarModuleHooks:: class methods and process results here
        if ($hookArea == 'GUI') {
            $hookoutput = array();
            foreach ($hookList as $hookInfo) {
                if (!xarMod::isAvailable($hookInfo['module'])) continue;
                if ($hookInfo['type'] == 'class') {
                    // run hook call handler with extraInfo and hookAction
                    $result = xarModuleHooks::runHookCallHandler($hookInfo, $extraInfo, $hookAction);
                } else {
                    // run GUI hook function with extraInfo
                    $result = xarModuleHooks::runGuiHookFunc($hookInfo, $hookId, $extraInfo);
                }
                if (isset($result)) {
                    // add the output of the GUI function to the hookoutput array, using the hook modname as key
                    $hookoutput[$hookInfo['module']] = $result;
                }
            }
            // return the GUI output for each hook module
            return $hookoutput;

        } else {
            foreach ($hookList as $hookInfo) {
                if (!xarMod::isAvailable($hookInfo['module'])) continue;
                if ($hookInfo['type'] == 'class') {
                    // run hook call handler with extraInfo and hookAction
                    $result = xarModuleHooks::runHookCallHandler($hookInfo, $extraInfo, $hookAction);
                } else {
                    // run API hook function with extraInfo
                    $result = xarModuleHooks::runApiHookFunc($hookInfo, $hookId, $extraInfo);
                }
                if (isset($result)) {
                    // replace the current extraInfo with the output of the API function
                    $extraInfo = $result;
                }
            }
            // return the final updated extraInfo values
            return $extraInfo;
        }
    }

    /**
     * Initialize extraInfo
     */
    public static function initExtraInfo($hookId, $extraInfo = NULL, $callerModName = NULL, $callerItemType = '')
    {
        // make sure extraInfo is an array, e.g. modules_adminapi_remove() sets this to ''
        if (empty($extraInfo)) {
            $extraInfo = array();
        } elseif (!is_array($extraInfo)) {
            // CHECKME: what to do in this case ?
            throw new BadParameterException('extraInfo');
            //$extraInfo = array('extrainfo' => $extraInfo);
        }

        // allow override of current module if necessary (e.g. modules admin, blocks, API functions, ...)
        if (empty($callerModName)) {
            if (!empty($extraInfo['module'])) {
                $modName = $extraInfo['module'];
            } else {
                list($modName) = xarRequest::getInfo();
            }
        } else {
            $modName = $callerModName;
        }

        // retrieve the item type from $extraInfo if necessary (e.g. for articles, xarbb, ...)
        if (empty($callerItemType) && !empty($extraInfo['itemtype'])) {
            $callerItemType = $extraInfo['itemtype'];
        }

        // retrieve the itemid from $extraInfo if necessary
        if (empty($hookId) && !empty($extraInfo['itemid'])) {
            $hookId = $extraInfo['itemid'];
        }

        // make sure we have everything we need in $extraInfo for the hook modules
        $extraInfo['module']   = $modName;
        $extraInfo['itemtype'] = $callerItemType;
        $extraInfo['itemid']   = $hookId;

        return $extraInfo;
    }

    /**
     * Run API hook function with extraInfo
     *
     * @param hookInfo array information about the current hook action
     * @param hookId mixed itemid for 'item' hook actions, modname or other for 'module' hook action
     * @param extraInfo array the extraInfo array for module calls
     * @return mixed extraInfo array updated by the API hook function, or nothing
     */
    public static function runApiHookFunc($hookInfo, $hookId, $extraInfo)
    {
        if (!xarMod::apiLoad($hookInfo['module'], $hookInfo['type'])) return;
        return xarMod::apiFunc($hookInfo['module'], $hookInfo['type'], $hookInfo['func'],
                               array('objectid'  => $hookId,
                                     'extrainfo' => $extraInfo));
    }

    /**
     * Run GUI hook function with extraInfo
     *
     * @param hookInfo array information about the current hook action
     * @param hookId mixed itemid for 'item' hook actions, modname or other for 'module' hook action
     * @param extraInfo array the extraInfo array for module calls
     * @return mixed output string from the GUI hook function, or nothing
     */
    public static function runGuiHookFunc($hookInfo, $hookId, $extraInfo)
    {
        if (!xarMod::load($hookInfo['module'], $hookInfo['type'])) return;
        return xarMod::guiFunc($hookInfo['module'], $hookInfo['type'], $hookInfo['func'],
                               array('objectid'  => $hookId,
                                     'extrainfo' => $extraInfo));
    }

    /**
     * Run hook call handler with extraInfo and hookAction - we'll map extraInfo to a dummy object here (new in 2.x)
     *
     * @param hookInfo array information about the current hook action
     * @param extraInfo array the extraInfo array for module calls !
     * @param hookAction string the hook action
     * @return mixed updated extraInfo array (API) or output string (GUI) from dummy object for module calls with extraInfo
     */
    public static function runHookCallHandler($hookInfo, $extraInfo, $hookAction)
    {
        $handlerclazz = $hookInfo['func'];
        if (!empty($hookInfo['file'])) {
            sys::import($hookInfo['file']);
        }
        if (!class_exists($handlerclazz)) return;

        $handler = new $handlerclazz();

        // we're dealing with a traditional module calling hooks with extraInfo

        // create a dummy object with the extraInfo
        $hookSubject = new DummyHookedObject($extraInfo);

        // call the handler with dummy object and action
        $handler->handle($hookSubject, $hookAction);

        // get the hook area 'API' or 'GUI' from the handler
        $hookArea = $handler->actions[$hookAction]['area'];
        if ($hookArea == 'GUI') {
            // return the GUI output for this hook module from the dummy object
            if (!isset($hookSubject->hookoutput[$handler->modname])) return;
            // return the output string from the GUI method
            return $hookSubject->hookoutput[$handler->modname];

        } else {
            // return the extraInfo array updated by the API method
            return $hookSubject->hookinput;
        }
    }

    /**
     * Get list of available hooks for a particular module, itemtype[, scope] and action
     */
    public static function getHookList($callerModName, $hookScope, $hookAction, $callerItemType = '')
    {
        if (empty($callerModName)) throw new EmptyParameterException('callerModName');

        if (isset(self::$hookListCache["$callerModName$callerItemType$hookScope$hookAction"])) {
            return self::$hookListCache["$callerModName$callerItemType$hookScope$hookAction"];
        }

        // Get database info
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        $hookstable    = $xartable['hooks'];
        $modulestable  = $xartable['modules'];

        // Get applicable hooks
        // New query:
        $query ="SELECT DISTINCT hooks.t_area, tmods.name,
                                 hooks.t_type, hooks.t_func, hooks.t_file, hooks.priority
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
        $bindvars[] = $hookScope;
        $bindvars[] = $hookAction;
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_NUM);

        $resarray = array();
        while($result->next()) {
            list($hookArea, $hookModName, $hookModType, $hookModFunc, $hookModFile, $hookOrder) = $result->getRow();

            $tmparray = array('area' => $hookArea,
                              'module' => $hookModName,
                              'type' => $hookModType,
                              'func' => $hookModFunc,
                              'file' => $hookModFile);
    
            array_push($resarray, $tmparray);
        }
        $result->Close();
        self::$hookListCache["$callerModName$callerItemType$hookScope$hookAction"] = $resarray;
        return $resarray;
    }

    /**
     * Check if a particular hook module is hooked to the current module (+ itemtype)
     */
    public static function isHooked($hookModName, $callerModName = NULL, $callerItemType = '')
    {
        if (empty($hookModName)) throw new EmptyParameterException('hookModName');

        if (empty($callerModName)) {
            list($callerModName) = xarRequest::getInfo();
        }

        // Get all hook modules for the caller module once
        if (!isset(self::$isHookedCache[$callerModName])) {
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

            self::$isHookedCache[$callerModName] = array();
            while($result->next()) {
                list($modname,$itemtype) = $result->fields;
                if (!empty($itemtype)) {
                    $itemtype = trim($itemtype);
                }
                if (!isset(self::$isHookedCache[$callerModName][$itemtype])) {
                    self::$isHookedCache[$callerModName][$itemtype] = array();
                }
                self::$isHookedCache[$callerModName][$itemtype][$modname] = 1;
            }
            $result->close();
        }
        if (empty($callerItemType)) {
            if (isset(self::$isHookedCache[$callerModName][''][$hookModName])) {
                // generic hook is enabled
                return true;
            } else {
                return false;
            }
        } elseif (is_numeric($callerItemType)) {
            if (isset(self::$isHookedCache[$callerModName][''][$hookModName])) {
                // generic hook is enabled
                return true;
            } elseif (isset(self::$isHookedCache[$callerModName][$callerItemType][$hookModName])) {
                // or itemtype-specific hook is enabled
                return true;
            } else {
                return false;
            }
        } elseif (is_array($callerItemType) && count($callerItemType) > 0) {
            if (isset(self::$isHookedCache[$callerModName][''][$hookModName])) {
                // generic hook is enabled
                return true;
            } else {
                foreach ($callerItemType as $itemtype) {
                    if (!is_numeric($itemtype)) continue;
                    if (isset(self::$isHookedCache[$callerModName][$itemtype][$hookModName])) {
                        // or at least one of the itemtype-specific hooks is enabled
                        return true;
                    }
                }
            }
        }
        return false;
    }
}


/**
 * Public class methods for hooked objects (internal to xaraya.hooks for now - class name may change) (new in 2.x)
 *
 * @todo figure out what to do about 'module' (aka 'config') hooks with objects - do not use there atm.
 * @todo identify hooked objects by name someday, and adapt xar_hooks table too ?
 */
class xarObjectHooks
{
    public static function callHooks($hookSubject, $hookAction)
    {
        // verify that the hook action is defined
        if (!xarHooks::isActionDefined($hookAction)) {
            // we don't support unknown hook actions here
            throw new BadParameterException('hookAction');
            return;
        }

        // get the hook list for this dataobject and action
        $hookList = xarObjectHooks::getHookList($hookSubject, $hookAction);
        if (empty($hookList)) {
            // nothing to do here
            return;
        }

        // try to determine 'API' or 'GUI' area from the standard hook actions
        $hookArea = xarHooks::getActionArea($hookAction);
        if (empty($hookArea)) {
            // we don't support hook actions with unspecified areas here
            throw new BadParameterException('hookArea');
            return;
        }

        // initialize hookSubject where necessary
        xarObjectHooks::initHookSubject($hookSubject, $hookAction);

        // run each hook in the hookList using xarObjectHooks:: class methods
        if ($hookArea == 'GUI') {
            foreach ($hookList as $hookInfo) {
                if (!xarMod::isAvailable($hookInfo['module'])) continue;
                if ($hookInfo['type'] == 'class') {
                    // run hook call handler with hookSubject and hookAction
                    xarObjectHooks::runHookCallHandler($hookInfo, $hookSubject, $hookAction);
                } else {
                    // run GUI hook function with hookSubject
                    xarObjectHooks::runGuiHookFunc($hookInfo, $hookSubject->itemid, $hookSubject);
                }
            }
            // the GUI output for each hook module can be found in $hookSubject->hookoutput

        } else {
            foreach ($hookList as $hookInfo) {
                if (!xarMod::isAvailable($hookInfo['module'])) continue;
                if ($hookInfo['type'] == 'class') {
                    // run hook call handler with hookSubject and hookAction
                    xarObjectHooks::runHookCallHandler($hookInfo, $hookSubject, $hookAction);
                } else {
                    // run API hook function with hookSubject
                    xarObjectHooks::runApiHookFunc($hookInfo, $hookSubject->itemid, $hookSubject);
                }
            }
            // put the final updated values in $hookSubject->hookoutput
            $hookSubject->hookoutput = $hookSubject->hookinput;
        }
    }

    /**
     * Initialize the different hook* properties for hookSubject
     */
    public static function initHookSubject($hookSubject, $hookAction)
    {
        // initialize hookinput
        $hookSubject->hookinput = array();

        // Note: you can preset the list of properties to be transformed via $hookSubject->hooktransform

        // add property values to hookinput
        if ($hookAction == 'transform') {
            if (!empty($hookSubject->hooktransform)) {
                $fields = $hookSubject->hooktransform;
            } else {
                $fields = array_keys($hookSubject->properties);
            }
            $hookSubject->hookinput['transform'] = array();

            sys::import('modules.dynamicdata.class.properties.master');

            foreach($fields as $name) {
            // TODO: this is exactly the same as in the dataobject display function, consolidate it ?
                if(!isset($hookSubject->properties[$name])) continue;

                if(($hookSubject->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED)
                || ($hookSubject->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY)
                || ($hookSubject->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)) continue;

                // *never* transform an ID
                // TODO: there is probably lots more to skip here.
                if ($hookSubject->properties[$name]->type != 21) {
                    $hookSubject->hookinput['transform'][] = $name;
                }
                $hookSubject->hookinput[$name] = $hookSubject->properties[$name]->value;
            }
            $hookSubject->hooktransform = $hookSubject->hookinput['transform'];
        } else {
            foreach(array_keys($hookSubject->properties) as $name)
                $hookSubject->hookinput[$name] = $hookSubject->properties[$name]->value;
            $hookSubject->hooktransform = array();
        }

        // add extra info for traditional hook modules
        $hookSubject->hookinput['module'] = xarMod::getName($hookSubject->moduleid);
        $hookSubject->hookinput['itemtype'] = $hookSubject->itemtype;
        $hookSubject->hookinput['itemid'] = $hookSubject->itemid;
        // CHECKME: is this sufficient in most cases, or do we need an explicit xarModURL() ?
        $hookSubject->hookinput['returnurl'] = xarServer::getCurrentURL();

        // initialize hookoutput
        $hookSubject->hookoutput = array();
    }

    /**
     * Run API hook function with dataobject
     *
     * @param hookInfo array information about the current hook action
     * @param hookId integer itemid for 'item' hook actions (unused)
     * @param hookSubject object the dataobject for object calls !
     * @return nothing for object calls with dataobject - the object gets updated directly here
     */
    public static function runApiHookFunc($hookInfo, $hookId, $hookSubject)
    {
        if (!xarMod::apiLoad($hookInfo['module'], $hookInfo['type'])) return;
        $result = xarMod::apiFunc($hookInfo['module'], $hookInfo['type'], $hookInfo['func'],
                                  array('objectid'  => $hookSubject->itemid,
                                        'extrainfo' => $hookSubject->hookinput));
        if (isset($result)) {
            // replace the current hookSubject->hookinput with the output of the API function
            $hookSubject->hookinput = $result;
        }
    }

    /**
     * Run GUI hook function with dataobject
     *
     * @param hookInfo array information about the current hook action
     * @param hookId integer itemid for 'item' hook actions (unused)
     * @param hookSubject object the dataobject for object calls !
     * @return nothing for object calls with dataobject - the object gets updated directly here
     */
    public static function runGuiHookFunc($hookInfo, $hookId, $hookSubject)
    {
        if (!xarMod::load($hookInfo['module'], $hookInfo['type'])) return;
        $result = xarMod::guiFunc($hookInfo['module'], $hookInfo['type'], $hookInfo['func'],
                                  array('objectid'  => $hookSubject->itemid,
                                        'extrainfo' => $hookSubject->hookinput));
        if (isset($result)) {
            // add the output of the GUI function to the hookSubject->hookoutput array, using the hook modname as key
            $hookSubject->hookoutput[$hookInfo['module']] = $result;
        }
    }

    /**
     * Run hook call handler with dataobject and action
     *
     * @param hookInfo array information about the current hook action
     * @param hookSubject object the dataobject for object calls !
     * @param hookAction string the hook action
     * @return nothing for object calls with dataobject - the object gets updated directly by the hook call handler
     */
    public static function runHookCallHandler($hookInfo, $hookSubject, $hookAction)
    {
        $handlerclazz = $hookInfo['func'];
        if (!empty($hookInfo['file'])) {
            sys::import($hookInfo['file']);
        }
        if (!class_exists($handlerclazz)) return;

        $handler = new $handlerclazz();

        // we're dealing with a dataobject calling hooks with itself as subject

        // call the handler with subject and action and return
        return $handler->handle($hookSubject, $hookAction);
    }

    /**
     * Get list of available hooks for a particular dataobject and action
     */
    public static function getHookList($hookSubject, $hookAction)
    {
        // get module name for this object (for now)
        $modName  = xarMod::getName($hookSubject->moduleid);

        // try to determine 'item' or 'module' scope from the standard hook actions (for now)
        $hookScope = xarHooks::getActionScope($hookAction);
        if (empty($hookScope)) {
            // we don't support hook actions with unspecified scope here
            throw new BadParameterException('hookScope');
            return;
        }

        // get list of available hooks for a particular module, itemtype[, scope] and action (for now)
        return xarModuleHooks::getHookList($modName, $hookScope, $hookAction, $hookSubject->itemtype);
    }

    /**
     * Check if a particular hook module is hooked to the current dataobject
     */
    public static function isHooked($hookModName, $hookSubject)
    {
        // get module name for this object (for now)
        $modName  = xarMod::getName($hookSubject->moduleid);

        // check if a particular hook module is hooked to the current module + itemtype (for now)
        return xarModuleHooks::isHooked($hookModName, $modName, $hookSubject->itemtype);
    }
}

/**
 * Default hook call handler - extended below in ItemHookCallHandler and ConfigHookCallHandler (new in 2.x)
 */
class BasicHookCallHandler extends Object
{
    // specify the name of the hook module here
    public $modname = 'generic';
    public $scope   = 'specified in child classes';

    // specify the different actions this hook call handler will support here
    public $actions = array(
        // example of API and GUI action methods - replace with standard 'item' or 'module' hook actions above
        'api_example' => array('type' => 'user', 'area' => 'API'),
        'gui_example' => array('type' => 'user', 'area' => 'GUI'),
        // example of importing a specific action handler - replace with standard 'item' or 'module' hook actions above
        'import_example' => array('type' => 'admin', 'area' => 'API'),
    );

    // specify an optional method mapper e.g. if you have several actions to support, and you want to import them only on demand
    public $mapper  = array(
        'import_example' => array('classname'  => 'MyExampleUpdateConfigHookHandler',
                                  'classfunc'  => 'run',
                                  'importname' => 'modules.myexample.class.hooks.updateconfig'),
    );

    /**
     * Constructor for the hook call handler - this does not need to be overridden
     */
    public function __construct()
    {
        // nothing to do here ?
    }

    /**
     * Handle a hook call for a specific action - this does not need to be overridden
     *
     * @param subject mixed the dataobject for object calls, or the dummy object (= based on extraInfo) for module calls
     * @param action string the hook action
     */
    public function handle($subject, $action)
    {
        if (empty($this->actions[$action])) {
            return;
        }

        // the hook call handler will have its own methods for the different actions it supports

        // if we mapped the action to some external handler method (import_example)
        if (!empty($this->mapper[$action])) {
            // get the methodmap corresponding to this action
            $methodmap = $this->mapper[$action];

            // get the right handler class for this method
            $handlerclazz = $methodmap['classname'];

            // get the right function to call in this handler class
            $handlerfunc = $methodmap['classfunc'];

            // import something extra for the class definition if specified
            if (!empty($methodmap['importname'])) {
                sys::import($methodmap['importname']);
            }

            // create a new handler for this action
            $handler = new $handlerclazz();

            // run the handler method with the subject and return the output
            return $handler->$handlerfunc($subject);

        // if the action has a corresponding method in this class (api_example and gui_example)
        } elseif (method_exists($this, $action)) {
            // run the method with the subject and return the output
            return $this->$action($subject);

        } else {
            return;
        }
    }

    /**
     * Run the 'api_example' action - this needs to be replaced for each of your API actions in the hook call handler
     *
     * @param subject mixed the dataobject for object calls, or the dummy object (= based on extraInfo) for module calls
     */
    public function api_example($subject)
    {
        // list of fields that shouldn't be updated by API actions
        $fixedlist = array('module','itemtype','itemid','returnurl','transform');

    // TODO: validate this way of working in tricky situations
        // do some processing with $subject->hookinput or other properties in this API method
        // note: for transform hooks, you can use $subject->hooktransform to know which properties to transform
        // $hookoutput = xarMod::apiFunc(..., ..., etc.)

        // update the current $subject->hookinput values in the API method if needed
        if (!empty($hookoutput) && is_array($hookoutput)) {
            foreach (array_keys($hookoutput) as $name) {
                if (in_array($name, $fixedlist)) continue;
                $subject->hookinput[$name] = $hookoutput[$name];
                $subject->hookinput[$name] .= 'The "api_example" action in ' . $this->modname . ' changed property ' . $name;
            }
        }
        // no need to return anything here
    }

    /**
     * Run the 'gui_example' action - this needs to be replaced for each of your GUI actions in the hook call handler
     *
     * @param subject mixed the dataobject for object calls, or the dummy object (= based on extraInfo) for module calls
     */
    public function gui_example($subject)
    {
    // TODO: validate this way of working in tricky situations
        // generate some GUI output with $subject->hookinput or other properties in this method
        // $hookoutput = xarMod::guiFunc(..., ..., etc.), xarTplObject(..., etc.), xarTplProperty(..., etc.), ...

        // add the output of the GUI method to the $subject->hookoutput array, using the hook modname as key
        if (isset($hookoutput)) {
            $subject->hookoutput[$this->modname] = $hookoutput;
            $subject->hookoutput[$this->modname] .= '<br/>That was the hook output for the "gui_example" action in ' . $this->modname;
        }
        // no need to return anything here
    }
}

/**
 * Handle 'item' hook calls for different actions - to be extended by a custom child class in hook modules (new in 2.x)
 */
class ItemHookCallHandler extends BasicHookCallHandler
{
    // specify the name of the hook module here
    public $modname = 'generic';
    public $scope   = 'item';

    // specify the different actions this hook call handler will support here
    public $actions = array(
        // example of API and GUI action methods - replace with standard 'item' hook actions above
        'api_example' => array('type' => 'user', 'area' => 'API'),
        'gui_example' => array('type' => 'user', 'area' => 'GUI'),
        // example of importing a specific action handler - replace with standard 'item' hook actions above
        'import_example' => array('type' => 'admin', 'area' => 'API'),
    );

    // specify an optional method mapper e.g. if you have several actions to support, and you want to import them only on demand
    public $mapper  = array(
        'import_example' => array('classname'  => 'MyExampleDeleteHookHandler',
                                  'classfunc'  => 'run',
                                  'importname' => 'modules.myexample.class.hooks.delete'),
    );

    // we re-use the __construct() and handle() methods from the BasicHookHandler

    // CHECKME: for 'item' hook calls, we deal with a real dataobject or with the dummy object (= based on extraInfo)

/* add your own action methods here in your child class */
/*
    public function my_action($subject)
    {
    }
*/
}

/**
 * Handle 'module' hook calls for different actions - to be extended by a custom child class in hook modules (new in 2.x)
 */
class ConfigHookCallHandler extends BasicHookCallHandler
{
    // specify the name of the hook module here
    public $modname = 'generic';
    public $scope   = 'module';

    // specify the different actions this hook call handler will support here
    public $actions = array(
        // example of API and GUI action methods - replace with standard 'module' hook actions above
        'api_example' => array('type' => 'admin', 'area' => 'API'),
        'gui_example' => array('type' => 'admin', 'area' => 'GUI'),
        // example of importing a specific action handler - replace with standard 'module' hook actions above
        'import_example' => array('type' => 'admin', 'area' => 'API'),
    );

    // specify an optional method mapper e.g. if you have several actions to support, and you want to import them only on demand
    public $mapper  = array(
        'import_example' => array('classname'  => 'MyExampleUpdateConfigHookHandler',
                                  'classfunc'  => 'run',
                                  'importname' => 'modules.myexample.class.hooks.updateconfig'),
    );

    // we re-use the __construct() and handle() methods from the BasicHookHandler

    // CHECKME: for 'module' hook calls, we only deal with the dummy object (= based on extraInfo)

/* add your own action methods here in your child class */
/*
    public function my_action($subject)
    {
    }
*/
}

/**
 * Handle "reverse" hook calls for different actions - NOT to be extended by a custom class in content modules (new in 2.x)
 */
class ReverseHookCallHandler extends ItemHookCallHandler
{
    // reverse hooks should NOT be handled this way by content modules
}

/**
 * Dummy object to support traditional modules calling hooks with extraInfo in hook call handlers (new in 2.x)
 */
class DummyHookedObject extends Object
{
    public $moduleid      = null;
    public $itemtype      = 0;
    public $itemid        = 0;

    public $properties    = array();  // just in case a hook call handler tries to access properties directly ;-)

// TODO: validate this way of working in tricky situations
    public $hookinput     = array();  // extrainfo used by hook modules
    public $hookoutput    = array();  // updated hookinput for API actions, or output from each hook module for GUI actions
    public $hooktransform = array();  // list of names for the properties to be transformed by the transform hook

    private $hooklist     = null;     // list of hook modules (= observers) to call
    private $hookscope    = 'item';   // the hook scope for dataobject (for now)

    public function __construct(Array $extraInfo)
    {
        // assume $extraInfo was properly filled in by callHooks()
        $this->moduleid = xarMod::getRegId($extraInfo['module']);
        $this->itemtype = $extraInfo['itemtype'];
        $this->itemid   = $extraInfo['itemid'];

        $this->hookinput = $extraInfo;
        $this->hookoutput = array();
        if (!empty($extraInfo['transform'])) {
            $this->hooktransform = $extraInfo['transform'];
        }
    }
}

?>
