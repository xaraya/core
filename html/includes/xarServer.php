<?php
/**
 * File: $Id$
 *
 * HTTP Protocol Server/Request/Response utilities
 *
 * @package server
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.org
 * @author Marco Canini <m.canini@libero.it>
 */

/**
 * Initializes the HTTP Protocol Server/Request/Response utilities
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access protected
 * @global xarRequest_allowShortURLs bool
 * @global xarRequest_defaultModule array
 * @global xarRequest_shortURLVariables array
 * @param args['generateShortURLs'] bool
 * @param args['defaultModuleName'] string
 * @param args['defaultModuleName'] string
 * @param args['defaultModuleName'] string
 * @param whatElseIsGoingLoaded integer 
 * @return bool true
 */
function xarSerReqRes_init($args, $whatElseIsGoingLoaded)
{
    $GLOBALS['xarServer_generateXMLURLs'] = $args['generateXMLURLs'];

    $GLOBALS['xarRequest_allowShortURLs'] = $args['enableShortURLsSupport'];
    $GLOBALS['xarRequest_defaultRequestInfo'] = array($args['defaultModuleName'],
                                                      $args['defaultModuleType'],
                                                      $args['defaultModuleFunction']);
    $GLOBALS['xarRequest_shortURLVariables'] = array();

    $GLOBALS['xarResponse_closeSession'] = $whatElseIsGoingLoaded & XARCORE_SYSTEM_SESSION;
    $GLOBALS['xarResponse_redirectCalled'] = false;

    return true;
}

// SERVER FUNCTIONS

/**
 * Gets a server variable
 *
 * Returns the value of $name server variable.
 * Accepted values for $name are exactly the ones described by the
 * {@link http://www.php.net/manual/en/reserved.variables.html#reserved.variables.server PHP manual}.
 * If the server variable doesn't exist void is returned.
 *
 * @author Marco Canini <m.canini@libero.it>, Michel Dalle
 * @access public
 * @param name string the name of the variable
 * @return mixed value of the variable
 */
function xarServerGetVar($name)
{
    if (isset($_SERVER[$name])) {
        return $_SERVER[$name];
    }
    global $HTTP_SERVER_VARS;
    if (isset($HTTP_SERVER_VARS[$name])) {
        return $HTTP_SERVER_VARS[$name];
    }
    if (isset($_ENV[$name])) {
        return $_ENV[$name];
    }
    global $HTTP_ENV_VARS;
    if (isset($HTTP_ENV_VARS[$name])) {
        return $HTTP_ENV_VARS[$name];
    }
    if ($val = getenv($name)) {
        return $val;
    }
    return; // we found nothing here
}

/**
 * Get base URI for Xaraya
 *
 * @access public
 * @return string base URI for Xaraya
 * @todo remove whatever may come after the PHP script - TO BE CHECKED !
 * @todo See code comments.
 */
function xarServerGetBaseURI()
{
    // Get the name of this URI
    $path = xarServerGetVar('REQUEST_URI');

    //if ((empty($path)) ||
    //    (substr($path, -1, 1) == '/')) {
    //what's wrong with a path (cfr. Indexes index.php, mod_rewrite etc.) ?
    if (empty($path)) {
        // REQUEST_URI was empty or pointed to a path
        // Try looking at PATH_INFO
        $path = xarServerGetVar('PATH_INFO');
        if (empty($path)) {
            // No luck there either
            // Try SCRIPT_NAME
            $path = xarServerGetVar('SCRIPT_NAME');
        }
    }

    $path = preg_replace('/[#\?].*/', '', $path);

    $path = preg_replace('/\.php\/.*$/', '', $path);
    if (substr($path, -1, 1) == '/') {
        $path .= 'dummy';
    }
    $path = dirname($path);

    if (preg_match('!^[/\\\]*$!', $path)) {
        $path = '';
    }

    return $path;
}

/**
 * Gets the host name
 *
 * Returns the server host name fetched from HTTP headers when possible.
 * The host name is in the canonical form (host + : + port) when the port is different than 80.
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @return string HTTP host name
 */

function xarServerGetHost()
{
    $server = xarServerGetVar('HTTP_HOST');
    if (empty($server)) {
        // HTTP_HOST is reliable only for HTTP 1.1
        $server = xarServerGetVar('SERVER_NAME');
        $port = xarServerGetVar('SERVER_PORT');
        if ($port != '80') $server .= ":$port";
    }
    return $server;
}

/**
 * Gets the current protocol
 *
 * Returns the HTTP protocol used by current connection, it could be 'http' or 'https'.
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @return string current HTTP protocol
 */
function xarServerGetProtocol()
{
    $HTTPS = xarServerGetVar('HTTPS');
    // IIS seems to set HTTPS = off for some reason
    return (!empty($HTTPS) && $HTTPS != 'off') ? 'https' : 'http';
}

/**
 * get base URL for Xaraya
 *
 * @access public
 * @returns string
 * @return base URL for Xaraya
 */
function xarServerGetBaseURL()
{
    $server = xarServerGetHost();
    $protocol = xarServerGetProtocol();
    $path = xarServerGetBaseURI();

    return "$protocol://$server$path/";
}

/**
 * Get current URL
 *
 * @access public
 * @param args array additional parameters to be added to the URL (e.g. theme, ...)
 * @return string current URL
 * @todo cfr. BaseURI() for other possible ways, or try PHP_SELF
 */
function xarServerGetCurrentURL($args = array())
{
    $server = xarServerGetHost();
    $protocol = xarServerGetProtocol();
    $baseurl = "$protocol://$server";

    // get current URI
    $request = xarServerGetVar('REQUEST_URI');
    
    if (empty($request)) {
        $request = xarServerGetVar('SCRIPT_NAME');
        if (!empty($request)) {
            $qs = xarServerGetVar('QUERY_STRING');
            if (!empty($qs)) $request .= "?$qs";
        } else {
            $request = '/';
        }
    }

    // add optional parameters
    if (strpos($request,'?') === false) $request .= '?';
    else $request .= '&';

    foreach ($args as $k=>$v) {
        if (is_array($v)) {
            foreach($v as $l=>$w) {
                if (!empty($w)) $request .= $k . "[$l]=$w&";
            }
        } elseif (!empty($v)) {
            $request .= "$k=$v&";
        }
    }

    $request = substr($request, 0, -1);
    if ($GLOBALS['xarServer_generateXMLURLs']) $request = htmlspecialchars($request);

    return $baseurl . $request;
}

// REQUEST FUNCTIONS

/**
 * Get request variable
 *
 * @access public
 * @global xarRequest_shortURLVariables array
 * @global xarRequest_allowshortURLs bool
 * @param name string
 * @param allowOnlyMethod string
 * @return mixed
 * @todo change order (POST normally overrides GET)
 */
function xarRequestGetVar($name, $allowOnlyMethod = NULL)
{
    if ($allowOnlyMethod == 'GET') {
        // Short URLs variables override GET variables
        if ($GLOBALS['xarRequest_allowShortURLs'] && isset($GLOBALS['xarRequest_shortURLVariables'][$name])) {
            $value = $GLOBALS['xarRequest_shortURLVariables'][$name];
        // Then check in $_GET
        } elseif (isset($_GET[$name])) {
            $value = $_GET[$name];
        // Try to fallback to global $HTTP_GET_VARS for older php versions
        } elseif (isset($GLOBALS['HTTP_GET_VARS'][$name])) {
            $value = $GLOBALS['HTTP_GET_VARS'][$name];
        // Nothing found, return void
        } else {
            return;
        }
        $method = $allowOnlyMethod;
    } elseif ($allowOnlyMethod == 'POST') {
        // First check in $_POST
        if (isset($_POST[$name])) {
            $value = $_POST[$name];
        // Try to fallback to global $HTTP_POST_VARS for older php versions
        } elseif (isset($GLOBALS['HTTP_POST_VARS'][$name])) {
            $value = $GLOBALS['HTTP_POST_VARS'][$name];
        // Nothing found, return void
        } else {
            return;
        }
        $method = $allowOnlyMethod;
    } else {

        // Short URLs variables override GET and POST variables
        if ($GLOBALS['xarRequest_allowShortURLs'] && isset($GLOBALS['xarRequest_shortURLVariables'][$name])) {
            $value = $GLOBALS['xarRequest_shortURLVariables'][$name];
            $method = 'GET';
        // Then check in $_POST
        } elseif (isset($_POST[$name])) {
            $value = $_POST[$name];
            $method = 'POST';
        // Try to fallback to global $HTTP_POST_VARS for older php versions
        } elseif (isset($GLOBALS['HTTP_POST_VARS'][$name])) {
            $value = $GLOBALS['HTTP_POST_VARS'][$name];
            $method = 'POST';
        // Then check in $_GET
        } elseif (isset($_GET[$name])) {
            $value = $_GET[$name];
            $method = 'GET';
        // Try to fallback to global $HTTP_GET_VARS for older php versions
        } elseif (isset($GLOBALS['HTTP_GET_VARS'][$name])) {
            $value = $GLOBALS['HTTP_GET_VARS'][$name];
            $method = 'GET';
        // Nothing found, return void
        } else {
            return;
        }
    }

    $value = xarMLS_convertFromInput($value, $method);

    if (get_magic_quotes_gpc()) {
        xarVar_stripSlashes($value);
    }

    return $value;
}

/**
 * Gets request info for current page. 
 *
 * Example of short URL support :
 *
 * index.php/<module>/<something translated in xaruserapi.php of that module>, or
 * index.php/<module>/admin/<something translated in xaradminapi.php>
 *
 * We rely on function <module>_<type>_decode_shorturl() to translate PATH_INFO
 * into something the module can work with for the input variables.
 * On output, the short URLs are generated by <module>_<type>_encode_shorturl(),
 * that is called automatically by xarModURL().
 *
 * Short URLs are enabled/disabled globally based on a base configuration
 * setting, and can be disabled per module via its admin configuration
 *
 * TODO: evaluate and improve this, obviously :-)
 * + check security impact of people combining PATH_INFO with func/type param
 *
 * @author Marco Canini, Michel Dalle
 * @access public
 * @global xarRequest_allowShortURLs bool
 * @global xarRequest_defaultModule array
 * @return array requested module, type and func
 * @todo <marco> Do we want to use xarVarCleanUntrusted here?
 * @todo <mikespub> Allow user select start page
 * @todo <marco> Do we need to do a preg_match on $params[1] here? 
 * @todo <mikespub> you mean for upper-case Admin, or to support other funcs than user and admin someday ?
 * @todo <marco> Investigate this aliases thing before to integrate and promote it!
 */
function xarRequestGetInfo()
{
    static $requestInfo = NULL;
    if (is_array($requestInfo)) {
        return $requestInfo;
    }

    // Get variables
    xarVarFetch('module', 'str:1:', $modName, NULL, XARVAR_NOT_REQUIRED);
    xarVarFetch('type', 'str:1:', $modType, 'user');
    xarVarFetch('func', 'str:1:', $funcName, 'main');

    if ($GLOBALS['xarRequest_allowShortURLs'] && empty($modName) && ($path = xarServerGetVar('PATH_INFO')) != '') {
        // NOTE: <marco> The '-' character is not allowed in modules, types and function names,
        //               so it's not present in this regex
        preg_match_all('|/([a-z0-9_]+)|i', $path, $matches);
        $params = $matches[1];
        if (count($params) > 0) {
            $modName = $params[0];
            // if the second part is not admin, it's user by default
            if (isset($params[1]) && $params[1] == 'admin') $modType = 'admin';
            else $modType = 'user';
            // Check if this is an alias for some other module
            $modName = xarRequest__resolveModuleAlias($modName);
            // Call the appropriate decode_shorturl function
            if (xarModGetVar($modName, 'SupportShortURLs') && xarModAPILoad($modName, $modType)) {
                $res = xarModAPIFunc($modName, $modType, 'decode_shorturl', $params);
                if (is_array($res)) {
                    list($funcName, $args) = $res;
                    if (!empty($funcName)) { // bingo
                        // Forward decoded args to xarRequestGetVar
                        if (isset($args) && is_array($args)) {
                            $args['module'] = $modName;
                            $args['type'] = $modType;
                            $args['func'] = $funcName;
                            xarRequest__setShortURLVars($args);
                        } else {
                            xarRequest__setShortURLVars(array('module' => $modName,
                            'type' => $modType,
                            'func' => $funcName));
                        }
                    }
                }
            }
            if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
                // If exceptionId is MODULE_FUNCTION_NOT_EXIST there's no problem,
                // this exception means that the module does not support short urls
                // for this $modType.
                // If exceptionId is MODULE_FILE_NOT_EXIST there's no problem too,
                // this exception means that the module does not have the $modType API.
                if (xarExceptionId() != 'MODULE_FUNCTION_NOT_EXIST' && xarExceptionId() != 'MODULE_FILE_NOT_EXIST') {
                    // In all other cases we just log the exception since we must always
                    // return a valid request info.
                    xarLogException(XARDBG_LEVEL_ERROR);
                }
                xarExceptionFree();
            }
        }
    }

    if (!empty($modName)) {
        // Check if this is an alias for some other module
        $modName = xarRequest__resolveModuleAlias($modName);
        // Cache values into info static var
        $requestInfo = array($modName, $modType, $funcName);
    } else {
        // If $modName is still empty we use the default module/type/func to be loaded in that such case
        $requestInfo = $GLOBALS['xarRequest_defaultRequestInfo'];
    }

    return $requestInfo;
}

/**
 * Check to see if this is a local referral
 *
 * @access public
 * @return bool true if locally referred, false if not
 */
function xarRequestIsLocalReferer()
{
    $server = xarServerGetHost();
    $referer = xarServerGetVar('HTTP_REFERER');

    if (!empty($referer) && preg_match("!^https?://$server(:\d+|)/!", $referer)) {
        return true;
    } else {
        return false;
    }
}


/**
 * Set Short URL Variables
 *
 * @access public
 * @global xarRequest_shortURLVariables array
 * @param vars array
 */
function xarRequest__setShortURLVars($vars)
{
    $GLOBALS['xarRequest_shortURLVariables'] = $vars;
}

/**
 * Checks if a module name is an alias for some other module
 *
 * @access private
 * @param aliasModName name of the module
 * @returns mixed
 * @return string containing the module name
 * @raise BAD_PARAM
 */
function xarRequest__resolveModuleAlias($aliasModName)
{
    $aliasesMap = xarConfigGetVar('System.ModuleAliases');
    //$aliasesMap = $GLOBALS['xarRequest_aliasesMap'];

    if (!empty($aliasesMap[$aliasModName])) {
        return $aliasesMap[$aliasModName];
    } else {
        return $aliasModName;
    }
}

// RESPONSE FUNCTIONS

/**
 * Carry out a redirect
 *
 * @access public
 * @global xarResponse_redirectCalled bool 
 * @param redirectURL string the URL to redirect to
 * @returns bool
 */
function xarResponseRedirect($redirectURL)
{
    global $xarResponse_redirectCalled;

    // First checks if there's a pending exception, if so does not redirect browser
    if (xarExceptionMajor() != XAR_NO_EXCEPTION) return false;

    if ($xarResponse_redirectCalled == true) {
        if (headers_sent() == true) return false;
    }
    $xarResponse_redirectCalled = true;

    if (substr($redirectURL, 0, 4) == 'http') {
        // Absolute URL - simple redirect
        $header = "Location: $redirectURL";
    } else {
        // Removing leading slashes from redirect url
        $redirectURL = preg_replace('!^/*!', '', $redirectURL);

        // Get base URL
        $baseurl = xarServerGetBaseURL();

        $header = "Location: $baseurl$redirectURL";
    }
    if ($GLOBALS['xarResponse_closeSession']) {
        xarSession_close();
    }

    header($header, headers_sent());

    return true;
}

/**
 * Checks if a redirection header has already been sent.
 *
 * @author Marco Canini
 * @access public
 * @global xarResponse_redirectCalled bool
 * @returns bool
 */
function xarResponseIsRedirected()
{
    return $GLOBALS['xarResponse_redirectCalled'];
}

?>
