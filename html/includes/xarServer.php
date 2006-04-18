<?php

/**
 * HTTP Protocol Server/Request/Response utilities
 *
 * @package server
 * @copyright (C) 2002-2006 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 */

/**
 * Initializes the HTTP Protocol Server/Request/Response utilities
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @global xarRequest_defaultModule array
 * @param args['generateShortURLs'] bool
 * @param args['defaultModuleName'] string
 * @param args['defaultModuleName'] string
 * @param args['defaultModuleName'] string
 * @param whatElseIsGoingLoaded integer
 * @return bool true
 */

function xarSerReqRes_init($args, $whatElseIsGoingLoaded)
{
    xarServer::$generateXMLURLs = $args['generateXMLURLs'];
    xarRequest::$allowShortURLs = $args['enableShortURLsSupport'];
    xarRequest::$defaultRequestInfo  = array($args['defaultModuleName'],
                                             $args['defaultModuleType'],
                                             $args['defaultModuleFunction']);
    xarResponse::$closeSession   = $whatElseIsGoingLoaded & XARCORE_SYSTEM_SESSION;
    xarResponse::$redirectCalled = false;

    // Register the ServerRequest event
    xarEvt_registerEvent('ServerRequest');

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarServer__shutdown_handler');
    return true;
}

// SERVER FUNCTIONS

/**
 * Shutdown handler for the xarServer subsystem
 *
 * @access private
 */
function xarServer__shutdown_handler()
{
    //xarLogMessage("xarServer shutdown handler");
}

/**
 * Gets a server variable
 *
 * Returns the value of $name server variable.
 * Accepted values for $name are exactly the ones described by the
 * {@link http://www.php.net/manual/en/reserved.variables.html PHP manual}.
 * If the server variable doesn't exist void is returned.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @author Michel Dalle
 * @access public
 * @param name string the name of the variable
 * @return mixed value of the variable
 */
function xarServerGetVar($name)
{
    assert('version_compare("4.1.2",phpversion()) <= 0; /* The minimum PHP version supported by Xaraya is 4.1.2 */');
    if (isset($_SERVER[$name])) {
        return $_SERVER[$name];
    }
    if($name == 'PATH_INFO') return;
    
    if (isset($_ENV[$name])) {
        return $_ENV[$name];
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
  // Allows overriding the Base URI from config.php
  // it can be used to configure Xaraya for mod_rewrite by
  // setting BaseURI = '' in config.php
  try {
      $BaseURI =  xarCore_getSystemVar('BaseURI');
      return $BaseURI;
  } catch(VariableNotFoundException $e) {
      // We need to build it
  }

  // Get the name of this URI
  $path = xarServerGetVar('REQUEST_URI');

  //if ((empty($path)) ||
  //    (substr($path, -1, 1) == '/')) {
  //what's wrong with a path (cfr. Indexes index.php, mod_rewrite etc.) ?
    if (empty($path)) {
        // REQUEST_URI was empty or pointed to a path
        // adapted patch from Chris van de Steeg for IIS
        // Try SCRIPT_NAME
        $path = xarServerGetVar('SCRIPT_NAME');
        if (empty($path)) {
            // No luck there either
            // Try looking at PATH_INFO
            $path = xarServerGetVar('PATH_INFO');
        }
    }
    
    $path = preg_replace('/[#\?].*/', '', $path);
    
    $path = preg_replace('/\.php\/.*$/', '', $path);
    if (substr($path, -1, 1) == '/') {
        $path .= 'dummy';
    }
    $path = dirname($path);
    
    //FIXME: This is VERY slow!!
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
 * @author Marco Canini <marco@xaraya.com>
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
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string current HTTP protocol
 */
function xarServerGetProtocol()
{
    if (function_exists('xarConfigGetVar')){
        if (xarConfigGetVar('Site.Core.EnableSecureServer') == true){
            if (preg_match('/^http:/', $_SERVER['REQUEST_URI'])) {
                return 'http';
            }
            $HTTPS = xarServerGetVar('HTTPS');
            // IIS seems to set HTTPS = off for some reason
            return (!empty($HTTPS) && $HTTPS != 'off') ? 'https' : 'http';
        } else {
            return 'http';
        }
    } else {
        return 'http';
    }
}

/**
 * get base URL for Xaraya
 *
 * @access public
 * @return string base URL for Xaraya
 */
function xarServerGetBaseURL()
{
    static $baseurl = null;

    if (isset($baseurl))  return $baseurl;

    $server = xarServerGetHost();
    $protocol = xarServerGetProtocol();
    $path = xarServerGetBaseURI();

    $baseurl = "$protocol://$server$path/";
    return $baseurl;
}

/**
 * Get current URL (and optionally add/replace some parameters)
 *
 * @access public
 * @param args array additional parameters to be added to/replaced in the URL (e.g. theme, ...)
 * @param generateXMLURL boolean over-ride Server default setting for generating XML URLs (true/false/NULL)
 * @param target string add a 'target' component to the URL
 * @return string current URL
 * @todo cfr. BaseURI() for other possible ways, or try PHP_SELF
 */
function xarServerGetCurrentURL($args = array(), $generateXMLURL = NULL, $target = NULL)
{
    $server = xarServerGetHost();
    $protocol = xarServerGetProtocol();
    $baseurl = "$protocol://$server";

    // get current URI
    $request = xarServerGetVar('REQUEST_URI');

    if (empty($request)) {
        // adapted patch from Chris van de Steeg for IIS
    // TODO: please test this :)
        $scriptname = xarServerGetVar('SCRIPT_NAME');
        $pathinfo = xarServerGetVar('PATH_INFO');
        if ($pathinfo == $scriptname) {
            $pathinfo = '';
        }
        if (!empty($scriptname)) {
            $request = $scriptname . $pathinfo;
            $querystring = xarServerGetVar('QUERY_STRING');
            if (!empty($querystring)) $request .= '?'.$querystring;
        } else {
            $request = '/';
        }
    }

// Note to Dracos: please don't replace & with &amp; here just yet - give me some time to test this first :-)
// Mike can we change these now, so we can work on validation a bit?

    // add optional parameters
    if (count($args) > 0) {
        if (strpos($request,'?') === false) $request .= '?';
        else $request .= '&';

        foreach ($args as $k=>$v) {
            if (is_array($v)) {
                foreach($v as $l=>$w) {
                // TODO: replace in-line here too ?
                    if (!empty($w)) $request .= $k . "[$l]=$w&";
                }
            } else {
                // if this parameter is already in the query string...
                if (preg_match("/(&|\?)($k=[^&]*)/",$request,$matches)) {
                    $find = $matches[2];
                    // ... replace it in-line if it's not empty
                    if (!empty($v)) {
                        $request = preg_replace("/(&|\?)".preg_quote($find)."/","$1$k=$v",$request);

                    // ... or remove it otherwise
                    } elseif ($matches[1] == '?') {
                        $request = preg_replace("/\?".preg_quote($find)."(&|)/",'?',$request);
                    } else {
                        $request = str_replace("&$find",'',$request);
                    }
                } elseif (!empty($v)) {
                    $request .= "$k=$v&";
                }
            }
        }

        $request = substr($request, 0, -1);
    }

    if (!isset($generateXMLURL)) {
        $generateXMLURL = xarServer::$generateXMLURLs;
    }

    if (isset($target)) {
        $request .= '#' . urlencode($target);
    }

    if ($generateXMLURL) {
        $request = htmlspecialchars($request);
    }

    return $baseurl . $request;
}

// REQUEST FUNCTIONS

/**
 * Get request variable
 *
 * @access public
 * @param name string
 * @param allowOnlyMethod string
 * @return mixed
 * @todo change order (POST normally overrides GET)
 * @todo have a look at raw post data options (xmlhttp postings)
 */
function xarRequestGetVar($name, $allowOnlyMethod = NULL)
{
    if ($allowOnlyMethod == 'GET') {
        // Short URLs variables override GET variables
        if (xarRequest::$allowShortURLs && isset(xarRequest::$shortURLVariables[$name])) {
            $value = xarRequest::$shortURLVariables[$name];
        } elseif (isset($_GET[$name])) {
            // Then check in $_GET
            $value = $_GET[$name];
        } else {
            // Nothing found, return void
            return;
        }
        $method = $allowOnlyMethod;
    } elseif ($allowOnlyMethod == 'POST') {
        if (isset($_POST[$name])) {
            // First check in $_POST
            $value = $_POST[$name];
        } else {
            // Nothing found, return void
            return;
        }
        $method = $allowOnlyMethod;
    } else {
        if (xarRequest::$allowShortURLs && isset(xarRequest::$shortURLVariables[$name])) {
            // Short URLs variables override GET and POST variables
            $value = xarRequest::$shortURLVariables[$name];
            $method = 'GET';
        } elseif (isset($_POST[$name])) {
            // Then check in $_POST
            $value = $_POST[$name];
            $method = 'POST';
        } elseif (isset($_GET[$name])) {
            // Then check in $_GET
            $value = $_GET[$name];
            $method = 'GET';
        } else {
            // Nothing found, return void
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
    static $loopHole = NULL;
    if (is_array($requestInfo)) {
        return $requestInfo;
    } elseif (is_array($loopHole)) {
    // FIXME: Security checks in functions used by decode_shorturl cause infinite loops,
    //        because they request the current module too at the moment - unnecessary ?
        xarLogMessage('Avoiding loop in xarRequestGetInfo()');
        return $loopHole;
    }

    // Get variables
    xarVarFetch('module', 'regexp:/^[a-z][a-z_0-9]*$/', $modName, NULL, XARVAR_NOT_REQUIRED);
    xarVarFetch('type', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $modType, 'user');
    xarVarFetch('func', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $funcName, 'main');

    if (xarRequest::$allowShortURLs && empty($modName) && ($path = xarServerGetVar('PATH_INFO')) != '');
        // IIS fix
        && $path != xarServerGetVar('SCRIPT_NAME')) {
        /*
        Note: we need to match anything that might be used as module params here too ! (without compromising security)
        preg_match_all('|/([a-z0-9_ .+-]+)|i', $path, $matches);
        
        The original regular expression prevents the use of titles, even when properly encoded, 
        as parts of a short-url path -- because it wouldn't not permit many characters that would
        in titles, such as parens, commas, or apostrophes.  Since a similiar "security" check is not
        done to normal URL params, I've changed this to a more flexable regex at the other extreme.
        
        This also happens to address Bug 2927 
        
        TODO: The security of doing this should be examined by someone more familiar with why this works
        as a security check in the first place.
        */
        preg_match_all('|/([^/]+)|i', $path, $matches);
        
        $params = $matches[1];
        if (count($params) > 0) {
            $modName = $params[0];
            // if the second part is not admin, it's user by default
            if (isset($params[1]) && $params[1] == 'admin') $modType = 'admin';
            else $modType = 'user';
            // Check if this is an alias for some other module
            $modName = xarRequest__resolveModuleAlias($modName);
            // Call the appropriate decode_shorturl function
            if (xarModIsAvailable($modName) && xarModGetVar($modName, 'SupportShortURLs') && xarModAPILoad($modName, $modType)) {
                $loopHole = array($modName,$modType,$funcName);
            // don't throw exception on missing file or function anymore
                $res = xarModAPIFunc($modName, $modType, 'decode_shorturl', $params, 0);
                if (isset($res) && is_array($res)) {
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
                $loopHole = NULL;
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
        $requestInfo = xarRequest::$defaultRequestInfo;
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
 * @param vars array
 */
function xarRequest__setShortURLVars($vars)
{
    xarRequest::$shortURLVariables = $vars;
}

/**
 * Checks if a module name is an alias for some other module
 *
 * @access private
 * @param aliasModName name of the module
 * @return string containing the module name
 * @raise BAD_PARAM
 */
function xarRequest__resolveModuleAlias($aliasModName)
{
    $aliasesMap = xarConfigGetVar('System.ModuleAliases');

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
 * @param redirectURL string the URL to redirect to
 */
function xarResponseRedirect($redirectURL)
{
    if (headers_sent() == true) return false;

    // MrB: We only do this for pn Legacy, consider removing it
    xarResponse::$redirectCalled = true;


    // Remove &amp; entites to prevent redirect breakage
    // according to besfred's php.net research str_replace is faster
    // if it was preg_replace it should have been
    // $redirectURL = preg_replace('!&amp;!', '&', $redirectURL);
    // to be able to work properly
    // for now we use str_replace tho, end of discussion :-)
    $redirectURL = str_replace('&amp;', '&', $redirectURL);

    if (substr($redirectURL, 0, 4) != 'http') {
        // Removing leading slashes from redirect url
        $redirectURL = preg_replace('!^/*!', '', $redirectURL);

        // Get base URL
        $baseurl = xarServerGetBaseURL();

        $redirectURL = $baseurl.$redirectURL;
    }

    if (preg_match('/IIS/', xarServerGetVar('SERVER_SOFTWARE')) && preg_match('/CGI/', xarServerGetVar('GATEWAY_INTERFACE')) ) {
      $header = "Refresh: 0; URL=$redirectURL";
    } else {
      $header = "Location: $redirectURL";
    }// if


    // Start all over again
    header($header);
    exit();
}

/**
 * Checks if a redirection header has already been sent.
 *
 * @author Marco Canini
 * @access public
 * @return bool
 */
function xarResponseIsRedirected()
{
    return xarResponse::$redirectCalled;
}

/** 
 * Convenience classes
 *
 */
class xarServer
{
    public static $generateXMLURLs = true;
}

class xarRequest
{
    public static $allowShortURLs = true;
    public static $defaultRequestInfo = array();
    public static $shortURLVariables = array();
}

class xarResponse
{
    public static $closeSession = true;    // we usually will have sessions
    public static $redirectCalled = false; // do we still need this?
}
?>
