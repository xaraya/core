<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: HTTP Protocol Server/Request/Response utilities
// ----------------------------------------------------------------------

function pnSerReqRes_init($args)
{
    global $pnRequest_allowShortURLs, $pnRequest_defaultModule,
           $pnRequest_shortURLVariables;

    $pnRequest_allowShortURLs = $args['enableShortURLsSupport'];

    $pnRequest_defaultModule = array('module' => $args['defaultModuleName'],
                                     'type' => $args['defaultModuleType'],
                                     'func' => $args['defaultModuleFunction']);

    $pnRequest_shortURLVariables = array();

    return true;
}

// SERVER FUNCTIONS

/**
 * get a server (or environment) variable
 *
 * Gets a SERVER variable, or an ENVironment variable if that fails. Tries the
 * new superglobals first.
 * @access public
 * @param name the name of the variable
 * @return mixed value of the variable, or void if variable doesn't exist
 */
function pnServerGetVar($name)
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
    $val = getenv($name);
    if (!empty($val)) {
        return $val;
    }
    return; // we found nothing here
}

/**
 * get base URI for PostNuke
 *
 * @access public
 * @returns string
 * @return base URI for PostNuke
 */
function pnServerGetBaseURI()
{
    // Get the name of this URI
    $path = pnServerGetVar('REQUEST_URI');

//    if ((empty($path)) ||
//        (substr($path, -1, 1) == '/')) {
// what's wrong with a path (cfr. Indexes index.php, mod_rewrite etc.) ?
    if (empty($path)) {
        // REQUEST_URI was empty or pointed to a path
        // Try looking at PATH_INFO
        $path = pnServerGetVar('PATH_INFO');
        if (empty($path)) {
            // No luck there either
            // Try SCRIPT_NAME
            $path = pnServerGetVar('SCRIPT_NAME');
        }
    }

    $path = preg_replace('/[#\?].*/', '', $path);
// TODO: remove whatever may come after the PHP script - TO BE CHECKED !
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
 * get base URL for PostNuke
 *
 * @access public
 * @returns string
 * @return base URL for PostNuke
 */
function pnServerGetBaseURL()
{
    $server = pnServerGetVar('HTTP_HOST');
    $isHTTPS = pnServerGetVar('HTTPS');

    // IIS seems to set HTTPS = off for some reason
    if (!empty($isHTTPS) && $isHTTPS != 'off') {
        $proto = 'https://';
    } else {
        $proto = 'http://';
    }

    $path = pnServerGetBaseURI();

    // TODO : this still doesn't work for non-standard ports !
    return "$proto$server$path/";
}

/**
 * get current URL
 *
 * @param $args additional parameters to be added to the URL (e.g. theme, ...)
 * @access public
 * @returns string
 * @return current URL
 */
function pnServerGetCurrentURL($args = array())
{
    // get base URL
    $baseurl = pnServerGetBaseURL();
    // strip off everything except protocol, server (and port)
    $baseurl = preg_replace('#^(https?://[^/]+)/.*$#','\\1',$baseurl);

    // get current URI
    $request = pnServerGetVar('REQUEST_URI');
// TODO: cfr. BaseURI() for other possible ways, or try PHP_SELF
    if (empty($request)) {
        $request = '/';
    }

    // add optional parameters
    if (strpos($request,'?') === false) {
        $join = '?';
    } else {
        $join = '&';
    }
    foreach ($args as $k=>$v) {
        if (is_array($v)) {
            foreach($v as $l=>$w) {
                if (isset($w)) {
                    $request .= $join . $k . "[$l]=$w";
                    $join = '&';
                }
            }
        } elseif (isset($v)) {
            $request .= $join . "$k=$v";
            $join = '&';
        }
    }

    return $baseurl . $request;
}

// REQUEST FUNCTIONS

function pnRequestGetVar($name, $allowOnlyMethod = NULL)
{
    global $pnRequest_shortURLVariables, $pnRequest_allowShortURLs;

    if ($allowOnlyMethod == 'GET') {
        // Short URLs variables override GET variables
        if ($pnRequest_allowShortURLs && isset($pnRequest_shortURLVariables[$name])) {
            $value = $pnRequest_shortURLVariables[$name];
        // Then check in $_GET
        } elseif (isset($_GET[$name])) {
            $value = $_GET[$name];
        // Try to fallback to global $HTTP_GET_VARIABLES for older php versions
        } elseif (isset($GLOBALS['HTTP_GET_VARIABLES'][$name])) {
            $value = $GLOBALS['HTTP_GET_VARIABLES'][$name];
        // Nothing found, return void
        } else {
            return;
        }
        $method = $allowOnlyMethod;
    } elseif ($allowOnlyMethod == 'POST') {
        // First check in $_POST
        if (isset($_POST[$name])) {
            $value = $_POST[$name];
        // Try to fallback to global $HTTP_POST_VARIABLES for older php versions
        } elseif (isset($GLOBALS['HTTP_POST_VARIABLES'][$name])) {
            $value = $GLOBALS['HTTP_POST_VARIABLES'][$name];
        // Nothing found, return void
        } else {
            return;
        }
        $method = $allowOnlyMethod;
    } else {
        // Short URLs variables override GET and POST variables
        if ($pnRequest_allowShortURLs && isset($pnRequest_shortURLVariables[$name])) {
            $value = $pnRequest_shortURLVariables[$name];
            $method = 'GET';
        // Then check in $_GET
        } elseif (isset($_GET[$name])) {
            $value = $_GET[$name];
            $method = 'GET';
        // Then check in $_POST
        } elseif (isset($_POST[$name])) {
            $value = $_POST[$name];
            $method = 'POST';
        // Try to fallback to global $HTTP_GET_VARIABLES for older php versions
        } elseif (isset($GLOBALS['HTTP_GET_VARIABLES'][$name])) {
            $value = $GLOBALS['HTTP_GET_VARIABLES'][$name];
            $method = 'GET';
        // Try to fallback to global $HTTP_POST_VARIABLES for older php versions
        } elseif (isset($GLOBALS['HTTP_POST_VARIABLES'][$name])) {
            $value = $GLOBALS['HTTP_POST_VARIABLES'][$name];
            $method = 'POST';
        // Nothing found, return void
        } else {
            return;
        }
    }

    $value = pnMLS_convertFromInput($value, $method);

    if (get_magic_quotes_gpc()) {
        pnVar_stripSlashes($value);
    }

    return $value;
}

/**
 * get request info for current page 
 *
 * @author Marco Canini, Michel Dalle
 * @access private
 * @returns array
 * @return requested module, type and func
 */
function pnRequestGetInfo()
{
    global $pnRequest_allowShortURLs, $pnRequest_defaultModule;

    static $requestInfo = NULL;
    if (is_array($requestInfo)) {
        return $requestInfo;
    }

    // Get variables
    // FIXME: <marco> Do we want to use pnVarCleanUntrusted here?
    $modName =  pnVarCleanUntrusted(pnRequestGetVar('module'));
    $modType =  pnVarCleanUntrusted(pnRequestGetVar('type'));
    $funcName = pnVarCleanUntrusted(pnRequestGetVar('func'));
    // Defaults for variables
    if (empty($modType)) {
        $modType = 'user';
    }
    if (empty($funcName)) {
        $funcName = 'main';
    }

    // Example of short URL support :
    //
    // index.php/<module>/<something translated in pnuserapi.php of that module>, or
    // index.php/<module>/admin/<something translated in pnadminapi.php>
    //
    // We rely on function <module>_<type>_decode_shorturl() to translate PATH_INFO
    // into something the module can work with for the input variables.
    // On output, the short URLs are generated by <module>_<type>_encode_shorturl(),
    // that is called automatically by pnModURL().
    //
    // Short URLs are enabled/disabled globally based on a base configuration
    // setting, and can be disabled per module via its admin configuration
    //
    // TODO: evaluate and improve this, obviously :-)
    // + check security impact of people combining PATH_INFO with func/type param

    if ($pnRequest_allowShortURLs && empty($modName)) {
        $path = pnServerGetVar('PATH_INFO');
        if (!empty($path)) {
            // FIXME: <marco> Do we want to use pnVarCleanUntrusted here?
            $path = pnVarCleanUntrusted($path);
            $path = trim($path, '/');
            $params = explode('/', $path);
            if (count($params) > 0 &&
                preg_match('/^[a-z0-9_-]+$/i', $params[0])) {
                $modName = $params[0];
                // if the second part is not admin, it's user by default
                if (!empty($params[1]) && $params[1] == 'admin') {
                    $modType = 'admin';
                    // FIXME: <marco> Do we need to do a preg_match on $params[1] here?
                    // <mikespub> you mean for upper-case Admin, or to support
                    // other funcs than user and admin someday ?
                } else {
                    $modType = 'user';
                }
                // FIXME: <marco> Investigate this aliases thing before to integrate and promote it!
                // Check if this is an alias for some other module
                $modName = pnModGetAlias($modName);
                // Call the appropriate decode_shorturl function
                if (pnModGetVar($modName, 'SupportShortURLs') &&
                    pnModAPILoad($modName, $modType)) {

                    $res = pnModAPIFunc($modName, $modType, 'decode_shorturl', $params);
                    if (is_array($res)) {
                        list($funcName, $args) = $res;
                        if (!empty($funcName)) { // bingo
                            // Forward decoded args to pnRequestGetVar
                            if (isset($args) && is_array($args)) {
                                $args['module'] = $modName;
                                $args['type'] = $modType;
                                $args['func'] = $funcName;
                                pnRequest__setShortURLVars($args);
                            } else {
                                pnRequest__setShortURLVars(array('module' => $modName,
                                                                 'type' => $modType,
                                                                 'func' => $funcName));
                            }
                        }
                    }
                }
                if (pnExceptionMajor() != PN_NO_EXCEPTION) {
                    // If exceptionId is MODULE_FUNCTION_NOT_EXIST there's no problem,
                    // this exception means that the module does not support short urls
                    // for this $modType.
                    // If exceptionId is MODULE_FILE_NOT_EXIST there's no problem too,
                    // this exception means that the module does not have the $modType API.
                    if (pnExceptionId() != 'MODULE_FUNCTION_NOT_EXIST' &&
                        pnExceptionId() != 'MODULE_FILE_NOT_EXIST') {
                        // In all other cases we just log the exception since we must always
                        // return a valid request info.
                        pnLogException(PNDBG_LEVEL_ERROR);
                    }
                    pnExceptionFree();
                }
            }
        }
    }

    // If $modName is still empty we use the default module/type/func to be loaded in that such case
    if (empty($modName)) {
        $modName = $pnRequest_defaultModule['module'];
        if (isset($pnRequest_defaultModule['type'])) $modType = $pnRequest_defaultModule['type'];
        if (isset($pnRequest_defaultModule['func'])) $funcName = $pnRequest_defaultModule['func'];
    }

    // Cache values into info static var
    $requestInfo = array($modName, $modType, $funcName);
    return $requestInfo;
}

/**
 * check to see if this is a local referral
 *
 * @access public
 * @returns bool
 * @return true if locally referred, false if not
 */
function pnRequestIsLocalReferer()
{
    $server = pnServerGetVar('HTTP_HOST');
    $referer = pnServerGetVar('HTTP_REFERER');

    if (!empty($referer) && preg_match("!^https?://$server(:\d+|)/!", $referer)) {
        return true;
    } else {
        return false;
    }
}

// REQUEST PRIVATE FUNCTIONS

function pnRequest__setShortURLVars($vars)
{
    global $pnRequest_shortURLVariables;

    $pnRequest_shortURLVariables = $vars;
}

// RESPONSE FUNCTIONS

/**
 * Carry out a redirect
 *
 * @access public
 * @param the URL to redirect to
 * @returns bool
 */
function pnResponseRedirect($redirectURL)
{
    global $pnResponse_redirectCalled;
    if (isset($pnResponse_redirectCalled) && $pnResponse_redirectCalled == true) {
        if (headers_sent() == true) return false;
    }
    $pnResponse_redirectCalled = true;

    if (preg_match('!^http!', $redirectURL)) {
        // Absolute URL - simple redirect
        $header = "Location: $redirectURL";

    } else {
        // Removing leading slashes from redirect url
        $redirectURL = preg_replace('!^/*!', '', $redirectURL);

        // Get base URL
        $baseurl = pnServerGetBaseURL();

        $header = "Location: $baseurl$redirectURL";
    }

    header($header, headers_sent());

    return true;
}

/**
 * Check if a redirection header was yet sent
 *
 * @access public
 * @author Marco Canini
 * @returns bool
 */
function pnResponseIsRedirected()
{
    global $pnResponse_redirectCalled;
    if (isset($pnResponse_redirectCalled) && $pnResponse_redirectCalled == true) {
        return true;
    }
    return false;
}

?>
