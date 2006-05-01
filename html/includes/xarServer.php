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
    // Use $args to configure the classes
    xarServer::init($args);
    xarRequest::init($args);
    // TODO: we will migrate the whatelseblah out later on, to keep the init interface clean, we trick it a bit for now.
    $args['whatelseisgoingloaded'] = $whatElseIsGoingLoaded;
    xarResponse::init($args);

    // Register the ServerRequest event
    xarEvents::register('ServerRequest');

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarServer__shutdown_handler');
    return true;
}

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
 * Wrapper functions to support Xaraya 1 API Server functions
 *
 */
function xarServerGetVar($name) { return xarServer::getVar($name); }
function xarServerGetBaseURI()  { return xarServer::getBaseURI();  }
function xarServerGetHost()     { return xarServer::getHost();     }
function xarServerGetProtocol() { return xarServer::getProtocol(); }
function xarServerGetBaseURL()  { return xarServer::getBaseURL();  }
function xarServerGetCurrentURL($args = array(), $generateXMLURL = NULL, $target = NULL)
{
    return xarServer::getCurrentUrl($args = array(), $generateXMLURL, $target);
}

/**
 * Wrapper function to support Xaraya 1 API Request functions
 *
 */
function xarRequestGetVar($name, $allowOnlyMethod = NULL) 
{ 
    return xarRequest::getVar($name, $allowOnlyMethod);
}
function xarRequestGetInfo()        { return xarRequest::getInfo();        }
function xarRequestIsLocalReferer() { return xarRequest::IsLocalReferer(); }

/**
 * Wrapper functions to support Xaraya 1 API Response functions
 *
 */
function xarResponseRedirect($redirectURL) { return xarResponse::Redirect($redirectURL); }

/**
 * Convenience classes
 *
 */
class xarServer
{
    public static $generateXMLURLs = true;

    /**
     * Initialize
     *
     */
    static function init($args)
    {
        self::$generateXMLURLs = $args['generateXMLURLs'];
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
    static function getVar($name) 
    {
        assert('version_compare("5.0",phpversion()) <= 0; /* The minimum PHP version supported by Xaraya is 5.0 */');
        if (isset($_SERVER[$name])) return $_SERVER[$name];
        if($name == 'PATH_INFO')    return;
        if (isset($_ENV[$name]))    return $_ENV[$name];
        if ($val = getenv($name))   return $val;
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
    static function getBaseURI()
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
        $path = self::getVar('REQUEST_URI');
        
        //if ((empty($path)) ||
        //    (substr($path, -1, 1) == '/')) {
        //what's wrong with a path (cfr. Indexes index.php, mod_rewrite etc.) ?
        if (empty($path)) {
            // REQUEST_URI was empty or pointed to a path
            // adapted patch from Chris van de Steeg for IIS
            // Try SCRIPT_NAME
            $path = self::getVar('SCRIPT_NAME');
            if (empty($path)) {
                // No luck there either
                // Try looking at PATH_INFO
                $path = self::getVar('PATH_INFO');
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
    static function getHost()
    {
        $server = self::getVar('HTTP_HOST');
        if (empty($server)) {
            // HTTP_HOST is reliable only for HTTP 1.1
            $server = self::getVar('SERVER_NAME');
            $port   = self::getVar('SERVER_PORT');
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
    static function getProtocol()
    {
        if (function_exists('xarConfigGetVar')) {
            if (xarConfigGetVar('Site.Core.EnableSecureServer') == true) {
                if (preg_match('/^http:/', self::getVar('REQUEST_URI'))) {
                    return 'http';
                }
                $HTTPS = self::getVar('HTTPS');
                // IIS seems to set HTTPS = off for some reason
                return (!empty($HTTPS) && $HTTPS != 'off') ? 'https' : 'http';
            }
        }
        return 'http';
    }
    
    /**
     * get base URL for Xaraya
     *
     * @access public
     * @return string base URL for Xaraya
     */
    static function getBaseURL()
    {
        static $baseurl = null;
        if (isset($baseurl))  return $baseurl;
        
        $server   = self::getHost();
        $protocol = self::getProtocol();
        $path     = self::getBaseURI();
        
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
    static function getCurrentUrl($args = array(), $generateXMLURL = NULL, $target = NULL)
    {
        $server   = self::getHost();
        $protocol = self::getProtocol();
        $baseurl  = "$protocol://$server";
        
        // get current URI
        $request = self::getVar('REQUEST_URI');
        
        if (empty($request)) {
            // adapted patch from Chris van de Steeg for IIS
            // TODO: please test this :)
            $scriptname = self::getVar('SCRIPT_NAME');
            $pathinfo   = self::getVar('PATH_INFO');
            if ($pathinfo == $scriptname) {
                $pathinfo = '';
            }
            if (!empty($scriptname)) {
                $request = $scriptname . $pathinfo;
                $querystring = self::getVar('QUERY_STRING');
                if (!empty($querystring)) $request .= '?'.$querystring;
            } else {
                $request = '/';
            }
        }
        
        // Note to Dracos: please don't replace & with &amp; here just yet - give me some time to test this first :-)
        // Mike can we change these now, so we can work on validation a bit?
        
        // add optional parameters
        if (count($args) > 0) {
            if (strpos($request,'?') === false) 
                $request .= '?';
            else 
                $request .= '&';
            
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
            // Strip off last &
            $request = substr($request, 0, -1);
        }
        
        // Finish up
        if (!isset($generateXMLURL)) $generateXMLURL = self::$generateXMLURLs;
        if (isset($target)) $request .= '#' . urlencode($target);
        if ($generateXMLURL) $request = htmlspecialchars($request);
        return $baseurl . $request;
    }
}

class xarRequest
{
    public static $allowShortURLs = true;
    public static $defaultRequestInfo = array();
    public static $shortURLVariables = array();

    /**
     * Initialize
     *
     */
    static function init($args)
    {
        self::$allowShortURLs = $args['enableShortURLsSupport'];
        self::$defaultRequestInfo = array($args['defaultModuleName'],
                                          $args['defaultModuleType'],
                                          $args['defaultModuleFunction']);
    }

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
    static function getVar($name, $allowOnlyMethod = NULL)
    {
        if ($allowOnlyMethod == 'GET') {
            // Short URLs variables override GET variables
            if (self::$allowShortURLs && isset(self::$shortURLVariables[$name])) {
                $value = self::$shortURLVariables[$name];
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
            if (self::$allowShortURLs && isset(self::$shortURLVariables[$name])) {
                // Short URLs variables override GET and POST variables
                $value = self::$shortURLVariables[$name];
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
     * @todo <mikespub> Allow user select start page
     * @todo <marco> Do we need to do a preg_match on $params[1] here?
     * @todo <mikespub> you mean for upper-case Admin, or to support other funcs than user and admin someday ?
     * @todo <marco> Investigate this aliases thing before to integrate and promote it!
     */
    static function getInfo()
    {
        static $requestInfo = NULL;
        static $loopHole = NULL;
        if (is_array($requestInfo)) {
            return $requestInfo;
        } elseif (is_array($loopHole)) {
            // FIXME: Security checks in functions used by decode_shorturl cause infinite loops,
            //        because they request the current module too at the moment - unnecessary ?
            xarLogMessage('Avoiding loop in xarRequest::getInfo()');
            return $loopHole;
        }

        // Get variables
        xarVarFetch('module', 'regexp:/^[a-z][a-z_0-9]*$/', $modName, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('type', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $modType, 'user');
        xarVarFetch('func', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $funcName, 'main');

        if (self::$allowShortURLs && empty($modName) && ($path = xarServer::getVar('PATH_INFO')) != ''
            // IIS fix
            && $path != xarServer::getVar('SCRIPT_NAME')) {
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
                $modType = 'user';
                if (isset($params[1]) && $params[1] == 'admin') $modType = 'admin';
                
                // Check if this is an alias for some other module
                $modName = self::resolveModuleAlias($modName);
                // Call the appropriate decode_shorturl function
                if (xarMod::isAvailable($modName) && xarModGetVar($modName, 'SupportShortURLs') && xarModAPILoad($modName, $modType)) {
                    $loopHole = array($modName,$modType,$funcName);
                    // don't throw exception on missing file or function anymore
                    $res = xarModAPIFunc($modName, $modType, 'decode_shorturl', $params, 0);
                    if (isset($res) && is_array($res)) {
                        list($funcName, $args) = $res;
                        if (!empty($funcName)) { // bingo
                            // Forward decoded args to xarRequest::getVar
                            if (isset($args) && is_array($args)) {
                                $args['module'] = $modName;
                                $args['type'] = $modType;
                                $args['func'] = $funcName;
                                self::$shortURLVariables = $args;
                            } else {
                                self::$shortURLVariables = array('module' => $modName,'type' => $modType,'func' => $funcName);
                            }
                        }
                    }
                    $loopHole = NULL;
                }
            }
        }
        
        if (!empty($modName)) {
            // Check if this is an alias for some other module
            $modName = self::resolveModuleAlias($modName);
            // Cache values into info static var
            $requestInfo = array($modName, $modType, $funcName);
        } else {
            // If $modName is still empty we use the default module/type/func to be loaded in that such case
            $requestInfo = self::$defaultRequestInfo;
        }
        return $requestInfo;
    }
    
    /**
     * Check to see if this is a local referral
     *
     * @access public
     * @return bool true if locally referred, false if not
     */
    static function IsLocalReferer()
    {
        $server  = xarServer::getHost();
        $referer = xarServer::getVar('HTTP_REFERER');
        
        if (!empty($referer) && preg_match("!^https?://$server(:\d+|)/!", $referer)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if a module name is an alias for some other module
     *
     * @access private
     * @param var string name of the module
     * @return string containing the module name
     * @raise BAD_PARAM
     */
    private static function resolveModuleAlias($var)
    {
        $aliasesMap = xarConfigGetVar('System.ModuleAliases');
        return (!empty($aliasesMap[$var])) ? $aliasesMap[$var] : $var;
    }
}

class xarResponse
{
    public static $closeSession = true;    // we usually will have sessions

    /**
     * initialize
     *
     */
    static function init($args)
    {
        // What does this do in the category useful?
        self::$closeSession = $args['whatelseisgoingloaded'] & XARCORE_SYSTEM_SESSION;
    }

    /**
     * Carry out a redirect
     *
     * @access public
     * @param redirectURL string the URL to redirect to
     */
    static function Redirect($url)
    {
        $redirectURL=urldecode($url); // this is safe if called multiple times.
        if (headers_sent() == true) return false;
        
        // Remove &amp; entities to prevent redirect breakage
        $redirectURL = str_replace('&amp;', '&', $redirectURL);
        
        if (substr($redirectURL, 0, 4) != 'http') {
            // Removing leading slashes from redirect url
            $redirectURL = preg_replace('!^/*!', '', $redirectURL);
            
            // Get base URL
            $baseurl = xarServer::getBaseURL();
            
            $redirectURL = $baseurl.$redirectURL;
        }
        
        if (preg_match('/IIS/', xarServer::getVar('SERVER_SOFTWARE')) && preg_match('/CGI/', xarServer::getVar('GATEWAY_INTERFACE')) ) {
            $header = "Refresh: 0; URL=$redirectURL";
        } else {
            $header = "Location: $redirectURL";
        }// if
        
        // Start all over again
        header($header);

        // NOTE: we *could* return for pure '1 exit point' but then we'd have to keep track of more,
        // so for now, we exit here explicitly. Besides the end of index.php this should be the only 
        // exit point.
        exit();
    }
}
?>
