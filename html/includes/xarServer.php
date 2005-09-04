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

function xarSerReqRes_init($args)
{
    global $xarRequest_allowShortURLs, $xarRequest_defaultModule,
           $xarRequest_shortURLVariables;

    $xarRequest_allowShortURLs = $args['enableShortURLsSupport'];

    $xarRequest_defaultModule = array('module' => $args['defaultModuleName'],
                                     'type' => $args['defaultModuleType'],
                                     'func' => $args['defaultModuleFunction']);

    $xarRequest_shortURLVariables = array();

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
    $val = getenv($name);
    if (!empty($val)) {
        return $val;
    }
    return; // we found nothing here
}

/**
 * get base URI for Xaraya
 *
 * @access public
 * @returns string
 * @return base URI for Xaraya
 */
function xarServerGetBaseURI()
{
    // Get the name of this URI
    $path = xarServerGetVar('REQUEST_URI');

//    if ((empty($path)) ||
//        (substr($path, -1, 1) == '/')) {
// what's wrong with a path (cfr. Indexes index.php, mod_rewrite etc.) ?
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
 * get base URL for Xaraya
 *
 * @access public
 * @returns string
 * @return base URL for Xaraya
 */
function xarServerGetBaseURL()
{
    $server = xarServerGetVar('HTTP_HOST');
    $isHTTPS = xarServerGetVar('HTTPS');

    // IIS seems to set HTTPS = off for some reason
    if (!empty($isHTTPS) && $isHTTPS != 'off') {
        $proto = 'https://';
    } else {
        $proto = 'http://';
    }

    $path = xarServerGetBaseURI();

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
function xarServerGetCurrentURL($args = array())
{
    // get base URL
    $baseurl = xarServerGetBaseURL();
    // strip off everything except protocol, server (and port)
    $baseurl = preg_replace('#^(https?://[^/]+)/.*$#','\\1',$baseurl);

    // get current URI
    $request = xarServerGetVar('REQUEST_URI');
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

function xarRequestGetVar($name, $allowOnlyMethod = NULL)
{
    global $xarRequest_shortURLVariables, $xarRequest_allowShortURLs;

    if ($allowOnlyMethod == 'GET') {
        // Short URLs variables override GET variables
        if ($xarRequest_allowShortURLs && isset($xarRequest_shortURLVariables[$name])) {
            $value = $xarRequest_shortURLVariables[$name];
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
// TODO: change order (POST normally overrides GET)
        // Short URLs variables override GET and POST variables
        if ($xarRequest_allowShortURLs && isset($xarRequest_shortURLVariables[$name])) {
            $value = $xarRequest_shortURLVariables[$name];
            $method = 'GET';
        // Then check in $_GET
        } elseif (isset($_GET[$name])) {
            $value = $_GET[$name];
            $method = 'GET';
        // Then check in $_POST
        } elseif (isset($_POST[$name])) {
            $value = $_POST[$name];
            $method = 'POST';
        // Try to fallback to global $HTTP_GET_VARS for older php versions
        } elseif (isset($GLOBALS['HTTP_GET_VARS'][$name])) {
            $value = $GLOBALS['HTTP_GET_VARS'][$name];
            $method = 'GET';
        // Try to fallback to global $HTTP_POST_VARS for older php versions
        } elseif (isset($GLOBALS['HTTP_POST_VARS'][$name])) {
            $value = $GLOBALS['HTTP_POST_VARS'][$name];
            $method = 'POST';
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
 * get request info for current page 
 *
 * @author Marco Canini, Michel Dalle
 * @access public
 * @returns array
 * @return requested module, type and func
 */
function xarRequestGetInfo()
{
    global $xarRequest_allowShortURLs, $xarRequest_defaultModule;

    static $requestInfo = NULL;
    if (is_array($requestInfo)) {
        return $requestInfo;
    }

    // Get variables
    // FIXME: <marco> Do we want to use xarVarCleanUntrusted here?
    $modName =  xarVarCleanUntrusted(xarRequestGetVar('module'));
    $modType =  xarVarCleanUntrusted(xarRequestGetVar('type'));
    $funcName = xarVarCleanUntrusted(xarRequestGetVar('func'));
    // Defaults for variables
    if (empty($modType)) {
        $modType = 'user';
    }
    if (empty($funcName)) {
        $funcName = 'main';
    }

    // Example of short URL support :
    //
    // index.php/<module>/<something translated in xaruserapi.php of that module>, or
    // index.php/<module>/admin/<something translated in xaradminapi.php>
    //
    // We rely on function <module>_<type>_decode_shorturl() to translate PATH_INFO
    // into something the module can work with for the input variables.
    // On output, the short URLs are generated by <module>_<type>_encode_shorturl(),
    // that is called automatically by xarModURL().
    //
    // Short URLs are enabled/disabled globally based on a base configuration
    // setting, and can be disabled per module via its admin configuration
    //
    // TODO: evaluate and improve this, obviously :-)
    // + check security impact of people combining PATH_INFO with func/type param

    if ($xarRequest_allowShortURLs && empty($modName)) {
        $path = xarServerGetVar('PATH_INFO');
        if (!empty($path)) {
            // FIXME: <marco> Do we want to use xarVarCleanUntrusted here?
            $path = xarVarCleanUntrusted($path);
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
                $modName = xarModGetAlias($modName);
                // Call the appropriate decode_shorturl function
                if (xarModGetVar($modName, 'SupportShortURLs') &&
                    xarModAPILoad($modName, $modType)) {

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
                    if (xarExceptionId() != 'MODULE_FUNCTION_NOT_EXIST' &&
                        xarExceptionId() != 'MODULE_FILE_NOT_EXIST') {
                        // In all other cases we just log the exception since we must always
                        // return a valid request info.
                        xarLogException(XARDBG_LEVEL_ERROR);
                    }
                    xarExceptionFree();
                }
            }
        }
    }

    // If $modName is still empty we use the default module/type/func to be loaded in that such case
    if (empty($modName)) {
        // Get Default Module -- Defined in Base Config or from config.site.xml
        // TODO -- Allow user select start page
        if (function_exists('xarConfigGetVar')){
            $modName = xarConfigGetVar('Site.Core.DefaultModuleName');
        } else {
            if (empty($modName)){
                $modName = $xarRequest_defaultModule['module'];
            }
        }
    // Get Default Module Type -- Defined in Base Config or from config.site.xml
        if (function_exists('xarConfigGetVar')){
            $modType = xarConfigGetVar('Site.Core.DefaultModuleType');
            if (!isset($modType)){
            if (isset($xarRequest_defaultModule['type'])) $modType = $xarRequest_defaultModule['type'];
            }
        } else {
            if (isset($xarRequest_defaultModule['type'])) $modType = $xarRequest_defaultModule['type'];
        }
        // Get Default Module Type -- Defined in Base Config or from config.site.xml
        if (function_exists('xarConfigGetVar')){
            $funcName = xarConfigGetVar('Site.Core.DefaultModuleFunction');
            if (empty($funcName)){
            if (isset($xarRequest_defaultModule['func'])) $funcName = $xarRequest_defaultModule['func'];
            }
        } else {
            if (isset($xarRequest_defaultModule['func'])) $funcName = $xarRequest_defaultModule['func'];
        }
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
function xarRequestIsLocalReferer()
{
    $server = xarServerGetVar('HTTP_HOST');
    $referer = xarServerGetVar('HTTP_REFERER');

    if (!empty($referer) && preg_match("!^https?://$server(:\d+|)/!", $referer)) {
        return true;
    } else {
        return false;
    }
}

// REQUEST PRIVATE FUNCTIONS

function xarRequest__setShortURLVars($vars)
{
    global $xarRequest_shortURLVariables;

    $xarRequest_shortURLVariables = $vars;
}

// RESPONSE FUNCTIONS

/**
 * Carry out a redirect
 *
 * @access public
 * @param the URL to redirect to
 * @returns bool
 */
function xarResponseRedirect($redirectURL)
{
    global $xarResponse_redirectCalled;
    if (isset($xarResponse_redirectCalled) && $xarResponse_redirectCalled == true) {
        if (headers_sent() == true) return false;
    }
    $xarResponse_redirectCalled = true;

    if (preg_match('!^http!', $redirectURL)) {
        // Absolute URL - simple redirect
        $header = "Location: $redirectURL";

    } else {
        // Removing leading slashes from redirect url
        $redirectURL = preg_replace('!^/*!', '', $redirectURL);

        // Get base URL
        $baseurl = xarServerGetBaseURL();

        $header = "Location: $baseurl$redirectURL";
    }
    xarSession_close();
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
function xarResponseIsRedirected()
{
    global $xarResponse_redirectCalled;
    if (isset($xarResponse_redirectCalled) && $xarResponse_redirectCalled == true) {
        return true;
    }
    return false;
}

?>