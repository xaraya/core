<?php
/**
 * Module handling subsystem
 *
 * Eventually we want this to split up in multiple files, for reference:
 * current classes in here (disregarding exceptions):
 *      xarModVars
 *      xarModUserVars
 *      xarMod
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo Use serialize in module variables?
 * @todo the double headed theme/module stuff needs to go, a theme is not a module
 */

/**
 * Exceptions defined by this subsystem
 *
 * @package modules
 */
class ModuleBaseInfoNotFoundException extends NotFoundExceptions
{
    protected $message = 'The base info for module "#(1)" could not be found';
}
/**
 * @package modules
**/
class ModuleNotFoundException extends NotFoundExceptions
{
    protected $message = 'A module is missing, the module name could not be determined in the current context';
}
/**
 * @package modules
**/
class ModuleNotActiveException extends xarExceptions
{
    protected $message = 'The module "#(1)" was called, but it is not active.';
}


/**
 * State of modules
 * @todo do we really need 13 module states?
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
 *
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

/**
 * Flags for loading APIs
 */
define('XARMOD_LOAD_ONLYACTIVE', 1);
define('XARMOD_LOAD_ANYSTATE', 2);

/*
    Bring in the module variables to maintain interface compatibility for now
*/
sys::import('xaraya.variables.module');
sys::import('xaraya.variables.moduser');
/**
 * Wrapper functions to support Xaraya 1 API for modvars and moduservars
**/
function xarModGetVarId($modName, $name)             {   return xarModVars::getID($modName, $name);       }

function xarModDelUserVar($modName, $name, $id=NULL)                {   return xarModUserVars::delete($modName, $name, $id);      }


/**
 * Encode parts of a URL.
 * This will encode the path parts, the and GET parameter names
 * and data. It cannot encode a complete URL yet.
 *
 * @access private
 * @param data string the data to be encoded (see todo)
 * @param type string the type of string to be encoded ('getname', 'getvalue', 'path', 'url', 'domain')
 * @return string the encoded URL parts
 * @todo this could be made public
 * @todo support arrays and encode the complete array (keys and values)
**/
function xarMod__URLencode($data, $type = 'getname')
{
    // Different parts of a URL are encoded in different ways.
    // e.g. a '?' and '/' are allowed in GET parameters, but
    // '?' must be encoded when in a path, and '/' is not
    // allowed in a path at all except as the path-part
    // separators.
    // The aim is to encode as little as possible, so that URLs
    // remain as human-readable as we can allow.

    // We will encode everything first, then restore a select few
    // characters.
    // TODO: tackle it the other way around, i.e. have rules for
    // what to encode, rather than undoing some ecoded characters.
    $data = rawurlencode($data);

    $decode = array(
        'path' => array(
            array('%2C', '%24', '%21', '%2A', '%28', '%29', '%3D'),
            array(',', '$', '!', '*', '(', ')', '=')
        ),
        'getname' => array(
            array('%2C', '%24', '%21', '%2A', '%28', '%29', '%3D', '%27', '%5B', '%5D'),
            array(',', '$', '!', '*', '(', ')', '=', '\'', '[', ']')
        ),
        'getvalue' => array(
            array('%2C', '%24', '%21', '%2A', '%28', '%29', '%3D', '%27', '%5B', '%5D', '%3A', '%2F', '%3F', '%3D'),
            array(',', '$', '!', '*', '(', ')', '=', '\'', '[', ']', ':', '/', '?', '=')
        )
    );

    // TODO: check what automatic ML settings have on this.
    // I suspect none, as all multi-byte characters have ASCII values
    // of their parts > 127.
    if (isset($decode[$type])) {
        $data = str_replace($decode[$type][0], $decode[$type][1], $data);
    }
    return $data;
}

/**
 * Format GET parameters formed by nested arrays, to support xarModURL().
 * This function will recurse for each level to the arrays.
 *
 * @access private
 * @param args array the array to be expanded as a GET parameter
 * @param prefix string the prefix for the GET parameter
 * @return string the expanded GET parameter(s)
 **/
function xarMod__URLnested($args, $prefix)
{
    $path = '';
    foreach ($args as $key => $arg) {
        if (is_array($arg)) {
            $path .= xarMod__URLnested($arg, $prefix . '['.xarMod__URLencode($key, 'getname').']');
        } else {
            $path .= $prefix . '['.xarMod__URLencode($key, 'getname').']' . '=' . xarMod__URLencode($arg, 'getvalue');
        }
    }

    return $path;
}

/**
 * Add further parameters to the path, ensuring each value is encoded correctly.
 *
 * @access private
 * @param args array the array to be encoded
 * @param path string the current path to append parameters to
 * @param psep string the path seperator to use
 * @return string the path with encoded parameters
 */
function xarMod__URLaddParametersToPath($args, $path, $pini, $psep)
{
    if (count($args) > 0)
    {
        $params = '';

        foreach ($args as $k=>$v) {
            if (is_array($v)) {
                // Recursively walk the array tree to as many levels as necessary
                // e.g. ...&foo[bar][dee][doo]=value&...
                $params .= xarMod__URLnested($v, $psep . $k);
            } elseif (isset($v)) {
                // TODO: rather than rawurlencode, use a xar function to encode
                $params .= (!empty($params) ? $psep : '') . xarMod__URLencode($k, 'getname') . '=' . xarMod__URLencode($v, 'getvalue');
            }
        }

        // Join to the path with the appropriate character,
        // depending on whether there are already GET parameters.
        $path .= (strpos($path, $pini) === false ? $pini : $psep) . $params;
    }

    return $path;
}


/**
 * Generates an URL that reference to a module function.
 *
 * @access public
 * @param modName string registered name of module
 * @param modType string type of function
 * @param funcName string module function
 * @param string fragment document fragment target (e.g. somesite.com/index.php?foo=bar#target)
 * @param args array of arguments to put on the URL
 * @param entrypoint array of arguments for different entrypoint than index.php
 * @return mixed absolute URL for call, or false on failure
 * @todo allow for an alternative entry point (e.g. stream.php) without affecting the other parameters
 */
function xarModURL($modName = NULL, $modType = 'user', $funcName = 'main', $args = array(), $generateXMLURL = NULL, $fragment = NULL, $entrypoint = array())
{
    // Parameter separator and initiator.
    $psep = '&';
    $pini = '?';
    $pathsep = '/';

    // Initialise the path.
    $path = '';

    // The following allows you to modify the BaseModURL from the config file
    // it can be used to configure Xaraya for mod_rewrite by
    // setting BaseModURL = '' in config.system.php
    try {
        $BaseModURL = xarSystemVars::get(sys::CONFIG, 'BaseModURL');
    } catch(Exception $e) {
        $BaseModURL = 'index.php';
    }

    // No module specified - just jump to the home page.
    if (empty($modName)) return xarServer::getBaseURL() . $BaseModURL;

    // Take the global setting for XML format generation, if not specified.
    if (!isset($generateXMLURL)) $generateXMLURL = xarMod::$genXmlUrls;

    // If an entry point has been set, then modify the URL entry point and modType.
    if (!empty($entrypoint)) {
        if (is_array($entrypoint)) {
            $modType = $entrypoint['action'];
            $entrypoint = $entrypoint['entry'];
        }
        $BaseModURL = $entrypoint;
    }

    // If we have an empty argument (ie null => null) then set a flag and
    // remove that element.
    // FIXME: this is way too hacky, NULL as a key for an array sooner or later will fail. (php 4.2.2 ?)
    if (is_array($args) && @array_key_exists(NULL, $args) && $args[NULL] === NULL) {
        // This flag means that the GET part of the URL must be opened.
        $open_get_flag = true;
        unset($args[NULL]);
    }

    // Check the global short URL setting before trying to load the URL encoding function
    // for the module. This also applies to custom entry points.
    if (xarMod::$genShortUrls) {
        // The encode_shorturl will be in userapi.
        // Note: if a module declares itself as supporting short URLs, then the encoding
        // API subsequently fails to load, then we want those errors to be raised.
        if ($modType == 'user' && xarModVars::get($modName, 'SupportShortURLs') && xarMod::apiLoad($modName, $modType)) {
            $encoderArgs = $args;
            $encoderArgs['func'] = $funcName;

            // Execute the short URL function.
            // It must exist if the SupportShortURLs variable is set for the module.
            // FIXME: if the function does not exist, then errors are not handled well, often hidden.
            // Ensure a missing short URL encoding function gets written to the log file.
            $short = xarMod::apiFunc($modName, $modType, 'encode_shorturl', $encoderArgs);
            if (!empty($short)) {
                if (is_array($short)) {
                    // An array of path and args has been returned (both optional) - new style.
                    if (!empty($short['path'])) {
                        foreach($short['path'] as $pathpart) {
                            // Use path encoding method, which can differ from
                            // the GET parameter encoding method.
                            if ($pathpart != '') {
                                $path .= $pathsep . xarMod__URLencode($pathpart, 'path');
                            }
                        }
                    }
                    // Unconsumed arguments, to be treated as additional GET parameters.
                    // These may actually be additional GET parameters injected by the
                    // short URL function - it makes no difference either way.
                    if (!empty($short['get']) && is_array($short['get'])) {
                        $path = xarMod__URLaddParametersToPath($short['get'], $path, $pini, $psep);
                    } else {
                        $args = array();
                    }
                } else {
                    // A string URL has been returned - old style - deprecated.
                    $path = $short;
                    $args = array();
                }

                // Use xaraya default (index.php) or BaseModURL if provided in config.system.php
                $path = $BaseModURL . $path;

                // Remove the leading / from the path (if any).
                $path = preg_replace('/^\//', '', $path);

                // Workaround for bug 3603
                // why: template might add extra params we dont see here
                if (!empty($open_get_flag) && !strpos($path, $pini)) {$path .= $pini;}

                // We now have the short form of the URL.
                // Further custom manipulation of the URL can be added here.
                // It may be worthwhile allowing for some kind of hook?
            }
        }
    }

    // If the path is still empty, then there is either no short URL support
    // at all, or no short URL encoding was available for these arguments.
    if (empty($path)) {
        if (!empty($entrypoint)) {
            // Custom entry-point.
            // TODO: allow the alt entry point to work without assuming it is calling
            // ws.php, so retaining the module and type params, and short url.
            // Entry Point comes as an array since ws.php sets a type var.
            // Entry array should be $entrypoint['entry'], $entrypoint['action']
            // e.g. ws.php?type=xmlrpc&args=foo
            // * Can also pass in the 'action' to $modType, and the entry point as
            // a string. It makes sense using existing parameters that way.
            $args = array('type' => $modType) + $args;
        }  else {
            $baseargs = array('module' => $modName);
            if ($modType !== 'user')  $baseargs['type'] = $modType;
            if ($funcName !== 'main') $baseargs['func'] = $funcName;

            // Standard entry point - index.php or BaseModURL if provided in config.system.php
            $args = $baseargs + $args;
        }

        // Add GET parameters to the path, ensuring each value is encoded correctly.
        $path = xarMod__URLaddParametersToPath($args, $BaseModURL, $pini, $psep);

        // We have the long form of the URL here.
        // Again, some form of hook may be useful.
    }

    // Add the fragment if required.
    if (isset($fragment)) $path .= '#' . urlencode($fragment);

    // Encode the URL if an XML-compatible format is required.
    if ($generateXMLURL) $path = htmlspecialchars($path);

    // Return the URL.
    return xarServer::getBaseURL() . $path;
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

/**
 * Wrapper functions to support Xaraya 1 API for module managment
 *
 */
function xarModGetName()
{   return xarMod::getName(); }

function xarModGetNameFromID($regid)
{   return xarMod::getName($regid); }

function xarModGetDisplayableName($modName = NULL, $type = 'module')
{   return xarMod::getDisplayName($modName, $type); }

function xarModGetDisplayableDescription($modName = NULL, $type = 'module')
{   return xarMod::getDisplayDescription($modName,$type); }

function xarModGetIDFromName($modName, $type = 'module')
{   return xarMod::getRegID($modName, $type); }

function xarModgetInfo($modRegId, $type = 'module')
{   return xarMod::getInfo($modRegId, $type); }

function xarMod_getBaseInfo($modName, $type = 'module')
{   return xarMod::getBaseInfo($modName, $type); }

function xarMod_getFileInfo($modOsDir, $type = 'module')
{   return xarMod::getFileInfo($modOsDir, $type); }

function xarMod__loadDbInfo($modName, $modDir)
{   return xarMod::loadDbInfo($modName, $modDir); }

function xarModDBInfoLoad($modName, $modDir = NULL, $type = 'module')
{   return xarMod::loadDbInfo($modName, $modDir, $type); }

function xarMod_getState($modRegId, $modMode = XARMOD_MODE_PER_SITE, $type = 'module')
{   return xarMod::getState($modRegId, $modMode, $type); }

function xarModIsAvailable($modName, $type = 'module')
{   return xarMod::isAvailable($modName, $type); }

function xarModFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{   return xarMod::guiFunc($modName, $modType, $funcName, $args); }

function xarModAPIFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{   return xarMod::apiFunc($modName, $modType, $funcName, $args,'api'); }

function xarModLoad($modName, $modType = 'user')
{   return xarMod::load($modName, $modType); }

function xarModAPILoad($modName, $modType = 'user')
{   return xarMod::apiLoad($modName, $modType); }

/**
 * Interface declaration for xarMod
 *
 * @todo this is very likely to change, it was created as baseline for refactoring
 */
interface IxarMod
{

}

/**
 * Preliminary class to model xarMod interface
 *
 */
class xarMod extends Object implements IxarMod
{
    static $genShortUrls = false;
    static $genXmlUrls   = true;

    /**
     * Initialize
     *
     */
    static function init($args)
    {
        self::$genShortUrls = $args['enableShortURLsSupport'];
        self::$genXmlUrls   = $args['generateXMLURLs'];

        // Register the events for this subsystem
        xarEvents::register('ModLoad');
        xarEvents::register('ModAPILoad');

        // Modules Support Tables
        $prefix = xarDB::getPrefix();

        // How we want it
        $tables['modules']         = $prefix . '_modules';
        $tables['module_vars']     = $prefix . '_module_vars';
        $tables['module_itemvars'] = $prefix . '_module_itemvars';
        $tables['hooks']           = $prefix . '_hooks';
        $tables['themes']          = $prefix . '_themes';

        xarDB::importTables($tables);
        return true;
    }

    /**
     * Get name of a module
     *
     * If regID is passed in, return the name of that module, otherwise use
     * current toplevel module.
     *
     * @access public
     * @param  $regID integer optional regID for module
     * @return string the name of the current top-level module
     */
    static function getName($regID = NULL)
    {
        if(!isset($regID)) {
            list($modName) = xarRequest::getInfo();
        } else {
            $modinfo = self::getInfo($regID);
            $modName = $modinfo['name'];
        }
        return $modName;
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
    static function getDisplayName($modName = NULL, $type = 'module')
    {
        if (empty($modName)) $modName = self::getName();
        $modInfo = self::getFileInfo($modName, $type);
        return xarML($modInfo['displayname']);
    }

    /**
     * Get the displayable description for modName
     *
     * The displayable description is sensible to user language.
     *
     * @access public
     * @param modName string registered name of module
     * @return string the displayable description
     */
    static function getDisplayDescription($modName = NULL, $type = 'module')
    {
        //xarLogMessage("xarMod::getDisplayDescription ". $modName ." / " . $type);
        if (empty($modName)) $modName = self::getName();

        $modInfo = self::getFileInfo($modName, $type);
        return xarML($modInfo['displaydescription']);
    }

    /**
     * Temporary helper function during regid->systemid migration
     *
     * @todo once the migration is done, migrate this out.
     */
    private static function getIds($modName, $type = 'module')
    {
        if (empty($modName)) throw new EmptyParameterException('modName');

        // For themes, kinda weird
        $modBaseInfo = self::getBaseInfo($modName,$type);
        if (!isset($modBaseInfo)) return; // throw back
        return array('systemid' => $modBaseInfo['systemid'], 'regid' => $modBaseInfo['regid']);
    }

    /**
     * Get module registry ID by name
     *
     * @access public
     * @param modName string The name of the module
     * @param type determines theme or module
     * @return string The module registry ID.
     */
    static function getRegId($modName, $type = 'module')
    {
        $ids = self::getIds($modName);
        return $ids['regid'];
    }

    /**
     * Get module system ID by name
     *
     * @access public
     * @param modName string The name of the module
     * @param type determines theme or module
     * @return string The module registry ID.
     */
    static function getId($modName)
    {
        $ids = self::getIds($modName);
        return $ids['systemid'];
    }

    /**
     * Get the module's current state
     *
     * @access public
     * @param  integer the module's registered id
     * @param type determines theme or module
     * @return mixed the module's current state
     * @throws DATABASE_ERROR, MODULE_NOT_EXIST
     * @todo implement the xarMod__setState reciproke
     * @todo We dont need this, used nowhere
     */
    static function getState($modRegId, $type = 'module')
    {
        $tmp = self::getInfo($modRegid, $type);
        return $tmp['state'];
    }

    /**
     * Check if a module is installed and its state is XARMOD_STATE_ACTIVE
     *
     * @access public
     * @static modAvailableCache array
     * @param modName string registered name of module
     * @param type determines theme or module
     * @return mixed true if the module is available
     * @throws DATABASE_ERROR, BAD_PARAM
     */
    static function isAvailable($modName, $type = 'module')
    {
        //xarLogMessage("xarMod::isAvailable: begin $type:$modName");

        // FIXME: there is no point to the cache here, since
        // xarMod::getBaseInfo() caches module details anyway.
        static $modAvailableCache = array();

        if (empty($modName)) throw new EmptyParameterException('modName');

        // Get the real module details.
        // The module details will be cached anyway.
        $modBaseInfo = self::getBaseInfo($modName, $type);

        // Return null if the result wasn't set
        if (!isset($modBaseInfo)) return false; // throw back

        if (!empty($GLOBALS['xarMod_noCacheState']) || !isset($modAvailableCache[$modBaseInfo['name']])) {
            // We should be ok now, return the state of the module
            $modState = $modBaseInfo['state'];
            $modAvailableCache[$modBaseInfo['name']] = false;

            if ($modState == XARMOD_STATE_ACTIVE) {
                $modAvailableCache[$modBaseInfo['name']] = true;
            }
        }
        //xarLogMessage("xarMod::isAvailable: end $type:$modName");
        return $modAvailableCache[$modBaseInfo['name']];
    }

    /**
     * Get information on module
     *
     * @access public
     * @param modRegId string module id
     * @param type determines theme or module
     * @return array of module information
     * @throws DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
     */
    static function getInfo($modRegId, $type = 'module')
    {
        if (empty($modRegId)) throw new EmptyParameterException('modRegid');

        switch($type) {
        case 'module':
        default:
            if (xarCore::isCached('Mod.Infos', $modRegId)) {
                return xarCore::getCached('Mod.Infos', $modRegId);
            }
            break;
        case 'theme':
            if (xarCore::isCached('Theme.Infos', $modRegId)) {
                return xarCore::getCached('Theme.Infos', $modRegId);
            }
            break;
        }
        // Log it when it doesnt come from the cache
        xarLogMessage("xarMod::getInfo ". $modRegId ." / " . $type);

        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();

        switch($type) {
        case 'module':
        default:
            $the_table = $tables['modules'];
            $query = "SELECT id,
                             name,
                             directory,
                             version,
                             admin_capable,
                             user_capable,
                             state
                       FROM  $the_table WHERE regid = ?";
            break;
        case 'theme':
            $the_table = $tables['themes'];
            $query = "SELECT id,
                             name,
                             directory,
                             version,
                             state
                       FROM  $the_table WHERE regid = ?";
            break;
        }
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($modRegId),ResultSet::FETCHMODE_NUM);

        if (!$result->next()) {
            $result->close();
            throw new IDNotFoundException($modRegId);
        }

        switch($type) {
        case 'module':
        default:
            list($modInfo['systemid'],
                 $modInfo['name'],
                 $modInfo['directory'],
                 $modInfo['version'],
                 $modInfo['admincapable'],
                 $modInfo['usercapable'],
                 $modInfo['state']) = $result->getRow();
            break;
        case 'theme':
            list($modInfo['systemid'],
                 $modInfo['name'],
                 $modInfo['directory'],
                 $modInfo['version'],
                 $modInfo['state']) = $result->getRow();
            break;
        }
        $result->Close();
        unset($result);

        $modInfo['regid'] = (int) $modRegId;
        $modInfo['displayname'] = self::getDisplayName($modInfo['name'], $type);
        $modInfo['displaydescription'] = self::getDisplayDescription($modInfo['name'], $type);

        // Shortcut for os prepared directory
        $modInfo['osdirectory'] = xarVarPrepForOS($modInfo['directory']);

        switch($type) {
        case 'module':
        default:
            if (!isset($modInfo['state'])) $modInfo['state'] = XARMOD_STATE_MISSING_FROM_UNINITIALISED; //return; // throw back
            $modFileInfo = self::getFileInfo($modInfo['osdirectory']);
            break;
        case 'theme':
            if (!isset($modInfo['state'])) {
                $modInfo['state']= XARTHEME_STATE_MISSING_FROM_UNINITIALISED;
            }
            $modFileInfo = self::getFileInfo($modInfo['osdirectory'], $type = 'theme');
            break;
        }

        if (!isset($modFileInfo)) {
            // We couldn't get file info, fill in unknowns.
            // The exception for this is logged in getFileInfo
            $modFileInfo['class'] = xarML('Unknown');
            $modFileInfo['description'] = xarML('This module is not installed properly. Not all info could be retrieved');
            $modFileInfo['category'] = xarML('Unknown');
            $modFileInfo['displayname'] = xarML('Unknown');
            $modFileInfo['displaydescription'] = xarML('Unknown');
            $modFileInfo['author'] = xarML('Unknown');
            $modFileInfo['contact'] = xarML('Unknown');
            $modFileInfo['admin'] = xarML('Unknown');
            $modFileInfo['user'] = xarML('Unknown');
            $modFileInfo['dependency'] = array();
            $modFileInfo['extensions'] = array();

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

        switch($type) {
        case 'module':
        default:
            xarCore::setCached('Mod.Infos', $modRegId, $modInfo);
            break;
        case 'theme':
            xarCore::setCached('Theme.Infos', $modRegId, $modInfo);
            break;
        }
        return $modInfo;
    }

    /**
     * Load a module's base information
     *
     * @access public
     * @param modName string the module's name
     * @param type determines theme or module
     * @return mixed an array of base module info on success
     * @throws EmptyParameterException, BadParameterException
     */
    static function getBaseInfo($modName, $type = 'module')
    {
        if (empty($modName)) throw new EmptyParameterException('modName');

        if ($type != 'module' && $type != 'theme') {
            throw new BadParameterException($type,'The value of the "type" parameter must be "module" or "theme", it was "#(1)"');
        }

        // The GLOBALS['xarMod_noCacheState'] flag tells Xaraya *not*
        // to cache module (+state) where this would lead to problems
        // like in the installer for example.
        if ($type == 'module') {
            $cacheCollection = 'Mod.BaseInfos';
            $checkNoState = 'xarMod_noCacheState';
        } else {
            $cacheCollection = 'Theme.BaseInfos';
            $checkNoState = 'xarTheme_noCacheState';
        }

        if (empty($GLOBALS[$checkNoState]) && xarCore::isCached($cacheCollection, $modName)) {
            return xarCore::getCached($cacheCollection, $modName);
        }
        // Log it when it doesnt come from the cache
        xarLogMessage("xarMod::getBaseInfo ". $modName ." / ". $type);

        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();

        // theme+s or module+s
        $table = $tables[$type.'s'];

        $query = "SELECT items.regid, items.directory,
                     items.id, items.state, items.name
              FROM   $table items
              WHERE  items.name = ? OR items.directory = ?";
        $bindvars = array($modName, $modName);
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars,ResultSet::FETCHMODE_NUM);

        if (!$result->next()) {
            $result->Close();
            return;
        }

        $modBaseInfo = array();
        list($regid,  $directory, $systemid, $state, $name) = $result->getRow();
        $result->Close();

        $modBaseInfo['regid'] = (int) $regid;
        $modBaseInfo['systemid'] = (int) $systemid;
        $modBaseInfo['state'] = (int) $state;
        $modBaseInfo['name'] = $name;
        $modBaseInfo['directory'] = $directory;
        $modBaseInfo['displayname'] = xarMod::getDisplayName($directory, $type);
        $modBaseInfo['displaydescription'] = xarMod::getDisplayDescription($directory, $type);
        // Shortcut for os prepared directory
        // TODO: <marco> get rid of it since useless
        $modBaseInfo['osdirectory'] = xarVarPrepForOS($directory);

        // This needed?
        if (empty($modBaseInfo['state'])) {
            $modBaseInfo['state'] = XARMOD_STATE_UNINITIALISED;
        }
        xarCore::setCached($cacheCollection, $name, $modBaseInfo);

        return $modBaseInfo;
    }

    /**
     * Get info from xarversion.php for module specified by modOsDir
     *
     * @access protected
     * @param modOSdir the module's directory
     * @param type determines theme or module
     * @return array an array of module file information
     * @throws MODULE_FILE_NOT_EXIST
     * @todo <marco> #1 FIXME: admin or admin capable?
     */
    static function getFileInfo($modOsDir, $type = 'module')
    {
        if (empty($modOsDir)) throw new EmptyParameterException('modOsDir');

        if (empty($GLOBALS['xarMod_noCacheState']) && xarCore::isCached('Mod.getFileInfos', $modOsDir)) {
            return xarCore::getCached('Mod.getFileInfos', $modOsDir ." / " . $type);
        }
        // Log it when it didnt came from cache
        xarLogMessage("xarMod::getFileInfo ". $modOsDir ." / " . $type);


        // TODO redo legacy support via type.
        switch($type) {
        case 'module':
            // Spliffster, additional mod info from modules/$modDir/xarversion.php
            $fileName = 'modules/' . $modOsDir . '/xarversion.php';
            $part = 'xarversion';
            // If the locale is already present, it means we can make the translations available
            if(!empty($GLOBALS['xarMLS_currentLocale']))
                xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modOsDir, 'modules:', 'version');
            break;
        case 'theme':
            $fileName = xarModVars::get('themes', 'themesdirectory'). '/' . $modOsDir . '/xartheme.php';
            $part = 'xartheme';
            break;
        default:
            throw new BadParameterException('module/theme type');
        }

        if (!file_exists($fileName)) {
            // Don't raise an exception, it is too harsh, but log it tho (bug 295)
            xarLogMessage("xarMod::getFileInfo: Could not find xarversion.php, skipping $modOsDir");
            // throw new FileNotFoundException($fileName);
            return;
        }
        // We can NOT use sys::import here, since the xarversion/xartheme files contain variables only
        // If they were loaded earlier, sys::import does nothing (as it should)
        // since inclusion of variables can be done multiple times (they just get overwritten)
        // the include is safe. Ergo: leave this in place.
        include $fileName;

        if (!isset($themeinfo))  $themeinfo = array();
        if (!isset($modversion)) $modversion = array();

        $version = array_merge($themeinfo, $modversion);

        // name and id are required, assert them, otherwise the module is invalid
        assert('isset($version["name"]) && isset($version["id"]); /* Both name and id need to be present in xarversion.php */');
        $FileInfo['name']           = $version['name'];
        $FileInfo['id']             = (int) $version['id'];
        $FileInfo['displayname']    = isset($version['displayname'])    ? $version['displayname'] : $version['name'];
        $FileInfo['description']    = isset($version['description'])    ? $version['description'] : false;
        $FileInfo['displaydescription'] = isset($version['displaydescription']) ? $version['displaydescription'] : $FileInfo['description'];
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
        $FileInfo['dependencyinfo'] = isset($version['dependencyinfo']) ? $version['dependencyinfo'] : array();
        $FileInfo['extensions']     = isset($version['extensions'])     ? $version['extensions'] : array();
        $FileInfo['directory']      = isset($version['directory'])      ? $version['directory'] : false;
        $FileInfo['homepage']       = isset($version['homepage'])       ? $version['homepage'] : false;
        $FileInfo['email']          = isset($version['email'])          ? $version['email'] : false;
        $FileInfo['contact_info']   = isset($version['contact_info'])   ? $version['contact_info'] : false;
        $FileInfo['publish_date']   = isset($version['publish_date'])   ? $version['publish_date'] : false;
        $FileInfo['license']        = isset($version['license'])        ? $version['license'] : false;
        $FileInfo['version']        = isset($version['version'])        ? $version['version'] : false;
        // Check that 'xar_version' key exists before assigning
        if (!$FileInfo['version'] && isset($version['xar_version'])) {
            $FileInfo['version'] = $version['xar_version'];
        }
        $FileInfo['bl_version']     = isset($version['bl_version'])     ? $version['bl_version'] : false;

        xarCore::setCached('Mod.getFileInfos', $modOsDir ." / " . $type, $FileInfo);
        return $FileInfo;
    }

    /**
     * Load database definition for a module
     *
     * @access private
     * @param modName string name of module to load database definition for
     * @param modOsDir string directory that module is in
     * @return mixed true on success
     * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
     *
     * @todo make this private again
     */
    static function loadDbInfo($modName, $modDir = NULL, $type = 'module')
    {
        if($type == 'theme') return true; // sigh.
        static $loadedDbInfoCache = array();

        if (empty($modName)) throw new EmptyParameterException('modName');

        // Get the directory if we don't already have it
        if (empty($modDir)) {
            $modBaseInfo = self::getBaseInfo($modName,$type);
            if (!isset($modBaseInfo)) return; // throw back
            $modDir = xarVarPrepForOS($modBaseInfo['directory']);
        } else {
            $modDir = xarVarPrepForOS($modDir);
        }

        // Check to ensure we aren't doing this twice
        if (isset($loadedDbInfoCache[$modName])) return true;

        // Load the database definition if required
        $osxartablefile = "modules/$modDir/xartables.php";
        if (!file_exists($osxartablefile)) return false;
        // CHECKME: do we need to track this one?
        sys::import('modules.'.$modDir.'.xartables');

        $tablefunc = $modName . '_' . 'xartables';
        if (function_exists($tablefunc)) xarDB::importTables($tablefunc());

        $loadedDbInfoCache[$modName] = true;
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
     * @throws BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
     */
    static function guiFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
    {
        if (empty($modName)) throw new EmptyParameterException('modName');
        $tplData = self::callFunc($modName,$modType,$funcName,$args);
        // If we have a string of data, we assume someone else did xarTpl* for us
        if (!is_array($tplData)) return $tplData;

        // See if we have a special template to apply
        $templateName = NULL;
        if (isset($tplData['_bl_template'])) $templateName = $tplData['_bl_template'];

        // Create the output.
        $tplOutput = xarTplModule($modName, $modType, $funcName, $tplData, $templateName);
        return $tplOutput;
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
     * @throws BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
     */
    static function apiFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
    {
        if (empty($modName)) throw new EmptyParameterException('modName');
        return self::callfunc($modName, $modType, $funcName, $args,'api');
    }

    /**
     * Work horse method for the lazy calling of module functions
     *
     * @access private
     */
    private static function callFunc($modName,$modType,$funcName,$args,$funcType = '')
    {
        assert('($funcType == "api" or $funcType==""); /* Wrong funcType argument in private callFunc method */');
        if (empty($modName)) throw new EmptyParameterException('modName');
        if (empty($funcName)) throw new EmptyParameterException('funcName');

        // good thing this information is cached :)
        $modBaseInfo = self::getBaseInfo($modName);
        if (!isset($modBaseInfo)) {return;} // throw back

        // Build function name and call function
        $modFunc = "{$modName}_{$modType}{$funcType}_{$funcName}";
        $found = true;
        $isLoaded = true;
        $msg = '';
        if (!function_exists($modFunc)) {
            // attempt to load the module's api
            if ($funcType == 'api') {
                xarMod::apiLoad($modName, $modType);
            } else {
                xarMod::load($modName,$modType);
            }
            // let's check for that function again to be sure
            if (!function_exists($modFunc)) {
                // Q: who are we kidding with this? osdirectory == modName always, no?
                $funcFile = 'modules/'.$modBaseInfo['osdirectory'].'/xar'.$modType.$funcType.'/'.strtolower($funcName).'.php';
                if (!file_exists($funcFile)) {
                    $found = false;
                } else {
                    ob_start();
                    sys::import('modules.'.$modName.'.xar'.$modType.$funcType.'.'.strtolower($funcName));
                    $error_msg = strip_tags(ob_get_contents());
                    ob_end_clean();

                    if (empty($r) || !$r) {
                        $msg = "Could not load function file: [#(1)].\n\n Error Caught:\n #(2)";
                        $params = array($funcFile, $error_msg);
                        $isLoaded = false;
                    }
                    if (!function_exists($modFunc)) $found = false;
                }
            }

            if ($found) {
                // Load the translations file, only if we have loaded the API function for the first time here.
                if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:'.$modType.$funcType, $funcName) === NULL) {return;}
            }
        }

        if (!$found) {
            if (!$isLoaded || empty($msg)) {
                // if it's loaded but not found, then set the error message to that
                $msg = 'Module '. strtoupper($funcType) .' function #(1) does not exist or could not be loaded.';
                $params = array($modFunc);
            }
            throw new FunctionNotFoundException($params, $msg);
        }

        $funcResult = $modFunc($args);
        return $funcResult;
    }

    /**
     * Load the modType of module identified by modName.
     *
     * @access public
     * @param modName string - name of module to load
     * @param modType string - type of functions to load
     * @return mixed
     * @throws XAR_SYSTEM_EXCEPTION
     */
    static function load($modName, $modType = 'user')
    {
        return self::privateLoad($modName, $modType);
    }

    /**
     * Load the modType API for module identified by modName.
     *
     * @access public
     * @param modName string registered name of the module
     * @param modType string type of functions to load
     * @return mixed true on success
     * @throws XAR_SYSTEM_EXCEPTION
     */
    static function apiLoad($modName, $modType = 'user')
    {
        return self::privateLoad($modName, $modType.'api', XARMOD_LOAD_ANYSTATE);
    }

    /**
     * Load the modType of module identified by modName.
     *
     * @access private
     * @param modName string - name of module to load
     * @param modType string - type of functions to load
     * @param flags number - flags to modify function behaviour
     * @return mixed
     * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_NOT_ACTIVE
     */
    static private function privateLoad($modName, $modType, $flags = 0)
    {
        static $loadedModuleCache = array();
        if (empty($modName)) throw new EmptyParameterException('modName');

        // Make sure we access the cache with lower case key, return true when we already loaded
        $cacheKey = strtolower($modName.$modType);
        if (isset($loadedModuleCache[$cacheKey])) return true;

        // Log it when it doesnt come from the cache
        xarLogMessage("xarMod::load: loading $modName:$modType");

        $modBaseInfo = self::getBaseInfo($modName);
        if (!isset($modBaseInfo)) throw new ModuleNotFoundException($modName);

        if ($modBaseInfo['state'] != XARMOD_STATE_ACTIVE && !($flags & XARMOD_LOAD_ANYSTATE) ) {
            throw new ModuleNotActiveException($modName);
        }

        // Load the module files
        $modDir = $modBaseInfo['directory'];
        $fileName = 'modules/'.$modDir.'/xar'.$modType.'.php';

        // Removed the exception.  Causing some wierd results with modules without an api.
        // <nuncanada> But now we wont know if something was loaded or not!
        // <nuncanada> We need some way to find it out.
        // Assume failure
        if (file_exists($fileName)) {
            sys::import('modules.'.$modDir.'.xar'.$modType);
            $loadedModuleCache[$cacheKey] = true;
        } elseif (is_dir('modules/'.$modDir.'/xar'.$modType)) {
            // this is OK too - do nothing
            $loadedModuleCache[$cacheKey] = true;
        }

        // Load the module translations files (common functions, uncut functions etc.)
        if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:', $modType) === NULL) return;

        // Load database info
        self::loadDbInfo($modName, $modDir);

        // Module loaded successfully, trigger the proper event
        xarEvents::trigger('ModLoad', $modName);
        return true;
    }
}


/**
 * Wrapper functions to support Xaraya 1 API for module aliases
 *
 */
function xarModGetAlias($alias)
{ return xarModAlias::resolve($alias);}
function xarModSetAlias($alias, $modName)
{ return xarModAlias::set($alias,$modName);}
function xarModDelAlias($alias, $modName)
{ return xarModAlias::delete($alias,$modName);}

/**
 * Interface declaration for module aliases
 *
 */
interface IxarModAlias
{
    static function resolve($alias);
    static function set    ($alias,$modName);
    static function delete ($alias,$modName);
}

/**
 * Class to model interface to module aliases
 *
 * @todo evaluate dependency consequences
 * @todo evaluate usage in modules, it's not very common, as in, perhaps worth to scrap and bolt onto a request mapper
 */
class xarModAlias extends Object implements IxarModAlias
{
    /**
     * Resolve an alias for a module
     */
    static function resolve($alias)
    {
        $aliasesMap = xarConfigVars::get(null,'System.ModuleAliases');
        return (!empty($aliasesMap[$alias])) ? $aliasesMap[$alias] : $alias;
    }

    /**
     * Set an alias for a module
     */
    static function set($alias,$modName)
    {
        if (!xarMod::apiLoad('modules', 'admin')) return;
        $args = array('modName' => $modName, 'aliasModName' => $alias);
        return xarMod::apiFunc('modules', 'admin', 'add_module_alias', $args);
    }

    /**
     * Delete an alias for a module
     */
    static function delete($alias, $modName)
    {
        if (!xarMod::apiLoad('modules', 'admin')) return;
        $args = array('modName' => $modName, 'aliasModName' => $alias);
        return xarMod::apiFunc('modules', 'admin', 'delete_module_alias', $args);
    }
}
?>
