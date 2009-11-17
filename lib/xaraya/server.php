<?php
/**
 * HTTP Protocol URL/Server/Request/Response utilities
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage server
 * @author Marco Canini <marco@xaraya.com>
 * @author Michel Dalle <mikespub@xaraya.com>
**/

/**
 * Convenience classes
 *
**/
class xarURL extends Object
{
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
    static function encode($data, $type = 'getname')
    {
        // Different parts of a URL are encoded in different ways.
        // e.g. a '?' and '/' are allowed in GET parameters, but
        // '?' must be encoded when in a path, and '/' is not
        // allowed in a path at all except as the path-part
        // separators.
        // The aim is to encode as little as possible, so that URLs
        // remain as human-readable as we can allow.

        static $decode = array(
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

        // We will encode everything first, then restore a select few
        // characters.
        // TODO: tackle it the other way around, i.e. have rules for
        // what to encode, rather than undoing some ecoded characters.
        $data = rawurlencode($data);

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
    static function nested($args, $prefix)
    {
        $path = '';
        foreach ($args as $key => $arg) {
            if (is_array($arg)) {
                $path .= self::nested($arg, $prefix . '['.self::encode($key, 'getname').']');
            } else {
                $path .= $prefix . '['.self::encode($key, 'getname').']' . '=' . self::encode($arg, 'getvalue');
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
    static function addParametersToPath($args, $path, $pini, $psep)
    {
        if (count($args) > 0)
        {
            $params = '';

            foreach ($args as $k=>$v) {
                if (is_array($v)) {
                    // Recursively walk the array tree to as many levels as necessary
                    // e.g. ...&foo[bar][dee][doo]=value&...
                    $params .= self::nested($v, $psep . $k);
                } elseif (isset($v)) {
                    // TODO: rather than rawurlencode, use a xar function to encode
                    $params .= (!empty($params) ? $psep : '') . self::encode($k, 'getname') . '=' . self::encode($v, 'getvalue');
                }
            }

            // Join to the path with the appropriate character,
            // depending on whether there are already GET parameters.
            $path .= (strpos($path, $pini) === false ? $pini : $psep) . $params;
        }

        return $path;
    }
}

class xarServer extends Object
{
    public static $allowShortURLs = true;
    public static $generateXMLURLs = true;

    /**
     * Initialize
     *
     */
    static function init($args)
    {
        self::$allowShortURLs = $args['enableShortURLsSupport'];
        self::$generateXMLURLs = $args['generateXMLURLs'];
        xarEvents::register('ServerRequest');
    }
    /**
     * Gets a server variable
     *
     * Returns the value of $name server variable.
     * Accepted values for $name are exactly the ones described by the
     * {@link http://www.php.net/manual/en/reserved.variables.html PHP manual}.
     * If the server variable doesn't exist void is returned.
     *
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
            $BaseURI =  xarSystemVars::get(sys::LAYOUT, 'BaseURI');
            return $BaseURI;
        } catch(Exception $e) {
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
     * @access public
     * @return string current HTTP protocol
     */
    static function getProtocol()
    {
        if (method_exists('xarConfigVars','Get')) {
            if (xarConfigVars::get(null, 'Site.Core.EnableSecureServer') == true) {
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
     * get the elapsed time since this page started
     *
     * @access public
     * @return seconds and microseconds elapsed since the page started
     */
    static function getPageTime()
    {
        return microtime(true) - $GLOBALS["Xaraya_PageTime"];
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
    static function getCurrentURL($args = array(), $generateXMLURL = NULL, $target = NULL)
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

// TODO: re-use some common code (with in-line replacement here) or use parse_url + http_build_query ?

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

    /**
     * Generates an URL that reference to a module function.
     *
     * Cfr. xarModURL() in modules
     */
    static function getModuleURL($modName = NULL, $modType = 'user', $funcName = 'main', $args = array(), $generateXMLURL = NULL, $fragment = NULL, $entrypoint = array())
    {
        // CHECKME: move xarModURL() and xarMod__URL* stuff here, and leave stub in modules ?
        return xarModURL($modName, $modType, $funcName, $args, $generateXMLURL, $fragment, $entrypoint);
    }

    /**
     * Generates an URL that reference to an object user interface method.
     */
    static function getObjectURL($objectName = NULL, $methodName = 'view', $args = array(), $generateXMLURL = NULL, $fragment = NULL, $entrypoint = array())
    {
        // 1. override any existing 'method' in args, and place before the rest
        if (!empty($methodName)) {
            $args = array('method' => $methodName) + $args;
        }
        // 2. override any existing 'object' or 'name' in args, and place before the rest
        if (!empty($objectName)) {
            unset($args['name']);
            // use 'object' here to distinguish from module URLs
            $args = array('object' => $objectName) + $args;
        }
        // 3. remove default method 'view' from URLs
        if ($args['method'] == 'view') {
            unset($args['method']);
        // and remove default method 'display' from URLs with an itemid
        } elseif (!empty($args['itemid']) && $args['method'] == 'display') {
            unset($args['method']);
        }

// TODO: some common code for getCurrentURL, getModuleURL and getObjectURL ?

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
            $BaseModURL = xarSystemVars::get(sys::LAYOUT, 'BaseModURL');
        } catch(Exception $e) {
            $BaseModURL = 'index.php';
        }
/*
        // No object specified - just jump to the home page.
        if (empty($args['object'])) return xarServer::getBaseURL() . $BaseModURL;

        // If an entry point has been set, then modify the URL entry point and args['type'].
        if (!empty($entrypoint)) {
            if (is_array($entrypoint)) {
            // CHECKME: is this relevant here ?
                $args['type'] = $entrypoint['action'];
                $entrypoint = $entrypoint['entry'];
            }
            $BaseModURL = $entrypoint;
        }

        // Check the global short URL setting before trying to load the URL encoding function
        // for the module. This also applies to custom entry points.
        if (self::$allowShortURLs) {
// CHECKME: short URLs for objects = go via getModuleURL or here ?
        }
*/

        // Add GET parameters to the path, ensuring each value is encoded correctly.
        $path = xarURL::addParametersToPath($args, $BaseModURL, $pini, $psep);

        // Add the fragment if required.
        if (isset($fragment)) $path .= '#' . urlencode($fragment);

        // Encode the URL if an XML-compatible format is required.
        if (!isset($generateXMLURL)) $generateXMLURL = self::$generateXMLURLs;
        if ($generateXMLURL) $path = htmlspecialchars($path);

        // Return the URL.
        return xarServer::getBaseURL() . $path;
    }
}

class xarController extends Object
{
    public static $allowShortURLs = true;

    public static $request;
    public static $response;

    /**
     * Initialize
     *
     */
    static function init($args)
    {
        self::$allowShortURLs = $args['enableShortURLsSupport'];
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
            $value = self::__stripslashes($value);
        }
        return $value;
    }

    static function __stripslashes($value)
    {
        $value = is_array($value) ? array_map(array('self','__stripslashes'), $value) : stripslashes($value);
        return $value;
    }

    static function setRequest($url=null)
    {
        self::$request = new xarRequest($url);
    }

    static function getRequest($url=null)
    {
        if (empty(self::$request) || !empty($url)) self::setRequest($url);
        return self::$request;
    }

    static function dispatch()
    {
        self::$response = new xarResponse();
        if (self::$request->isObjectURL()) {
            sys::import('xaraya.objects');

            // Call the object handler and return the output (or exit with 404 Not Found)
            self::$response->output = xarObject::guiMethod(self::$request->getType(), self::$request->getFunction());

        } else {

            // Call the main module function and return the output (or exit with 404 Not Found)
            self::$response->output = xarMod::guiFunc(self::$request->getModule(), self::$request->getType(), self::$request->getFunction());
        }
    }

    /**
     * Check to see if this is a local referral
     *
     * @access public
     * @return bool true if locally referred, false if not
     */
    static function isLocalReferer()
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
     * Carry out a redirect
     *
     * @access public
     * @param redirectURL string the URL to redirect to
     */
    static function redirect($url)
    {
        xarCache::noCache();
        $redirectURL = urldecode($url); // this is safe if called multiple times.
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

class xarResponse extends Object
{
    public $output;
    
    /**
     * initialize
     *
     */
    static function init($args) { }

// CHECKME: Should we support this kind of high-level user response in module GUI functions ?
//          And should some of the existing exceptions (to be defined) call those methods too ?

    /**
     * Return a 404 Not Found header, and fill in the template message-notfound.xt from the base module
     *
     * Usage in GUI functions etc.:
     *
     *    if (something not found, e.g. item $id) {
     *        $msg = xarML("Sorry, item #(1) is not available right now", $id);
     *        return xarResponse::NotFound($msg);
     *    }
     *    ...
     *
     * @access public
     * @param msg string the message
     * @param ... string template overrides, cfr. xarTplModule (optional)
     * @return string the template message-notfound.xt from the base module filled in
     */
    function NotFound($msg = '', $modName = 'base', $modType = 'message', $funcName = 'notfound', $templateName = NULL)
    {
        xarCache::noCache();
        if (!headers_sent()) {
            header('HTTP/1.0 404 Not Found');
        }

        xarTplSetPageTitle('404 Not Found');

        return xarTplModule($modName, $modType, $funcName, array('msg' => $msg), $templateName);
    }

    /**
     * Return a 403 Forbidden header, and fill in the message-forbidden.xt template from the base module
     *
     * Usage in GUI functions etc.:
     *
     *    if (something not allowed, e.g. edit item $id) {
     *        $msg = xarML("Sorry, you are not allowed to edit item #(1)", $id);
     *        return xarResponse::Forbidden($msg);
     *    }
     *    ...
     *
     * @access public
     * @param msg string the message
     * @param ... string template overrides, cfr. xarTplModule (optional)
     * @return string the template message-forbidden.xt from the base module filled in
     */
    function Forbidden($msg = '', $modName = 'base', $modType = 'message', $funcName = 'forbidden', $templateName = NULL)
    {
        xarCache::noCache();
        if (!headers_sent()) {
            header('HTTP/1.0 403 Forbidden');
        }

        xarTplSetPageTitle('403 Forbidden');

        return xarTplModule($modName, $modType, $funcName, array('msg' => $msg), $templateName);
    }

    function getOutput() { return $this->output; }
}

class xarRequest extends Object
{
    public $defaultRequestInfo = array();
    public $shortURLVariables = array();
    public $isObjectURL = false;

    public $url;
    public $module;
    
    function __construct($url=null)
    {
        $this->url= $url;
    }

    /**
     * Gets request info for current page or a given url.
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
     * @return array requested module, type and func
     * @todo <marco> Do we need to do a preg_match on $params[1] here?
     * @todo <mikespub> you mean for upper-case Admin, or to support other funcs than user and admin someday ?
     * @todo <marco> Investigate this aliases thing before to integrate and promote it!
     */
    public function getInfo($url='')
    {
        static $currentRequestInfo = NULL;
        static $loopHole = NULL;
        if (is_array($currentRequestInfo) && empty($url)) {
            return $currentRequestInfo;
        } elseif (is_array($loopHole)) {
            // FIXME: Security checks in functions used by decode_shorturl cause infinite loops,
            //        because they request the current module too at the moment - unnecessary ?
            xarLogMessage('Avoiding loop in xarController::$request->getInfo()');
            return $loopHole;
        }
        // Get variables
        if (empty($url)) {
            xarVarFetch('module', 'regexp:/^[a-z][a-z_0-9]*$/', $modName, NULL, XARVAR_NOT_REQUIRED);
            xarVarFetch('type', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $modType, 'user');
            xarVarFetch('func', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $funcName, 'main');
        } else {
            $decomposed = parse_url($url);
            $params = array();
            if (isset($decomposed['query'])) {
                $pairs = explode('&', $decomposed['query']);
                try {
                    foreach($pairs as $pair) {
                        if (trim($pair) == '') continue;
                        list($key, $value) = explode('=', $pair);
                        $params[$key] = urldecode($value);
                    }
                } catch(Exception $e) {}
                sys::import('xaraya.validations');
                $regex = ValueValidations::get('regexp');
            }

            if (isset($params['module'])) {
                $isvalid =  $regex->validate($params['module'], array('/^[a-z][a-z_0-9]*$/'));
                $modName = $isvalid ? $params['module'] : null;
            } else {
                $modName = null;
            }
            if (isset($params['type'])) {
                $isvalid =  $regex->validate($params['type'], array('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/'));
                $modType = $isvalid ? $params['type'] : 'user';
            } else {
                $modType = 'user';
            }
            if (isset($params['func'])) {
                $isvalid =  $regex->validate($params['func'], array('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/'));
                $funcName = $isvalid ? $params['func'] : 'main';
            } else {
                $funcName = 'main';
            }
        }

        if (xarController::$allowShortURLs && empty($modName) && ($path = xarServer::getVar('PATH_INFO')) != ''
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
                $modName = xarModAlias::resolve($modName);
                // Call the appropriate decode_shorturl function
                if (xarMod::isAvailable($modName) && xarModVars::get($modName, 'enable_short_urls') && xarMod::apiLoad($modName, $modType)) {
                    $loopHole = array($modName,$modType,$funcName);
                    // don't throw exception on missing file or function anymore
                    try {
                        $res = xarMod::apiFunc($modName, $modType, 'decode_shorturl', $params);
                    } catch ( NotFoundExceptions $e) {
                        // No worry
                    }
                    if (isset($res) && is_array($res)) {
                        list($funcName, $args) = $res;
                        if (!empty($funcName)) { // bingo
                            // Forward decoded args to xarController::getVar
                            if (isset($args) && is_array($args)) {
                                $args['module'] = $modName;
                                $args['type'] = $modType;
                                $args['func'] = $funcName;
                                $this->shortURLVariables = $args;
                            } else {
                                $this->shortURLVariables = array('module' => $modName,'type' => $modType,'func' => $funcName);
                            }
                        }
                    }
                    $loopHole = NULL;
                }
            }
        }

        if (!empty($modName)) {
            // Check if this is an alias for some other module
            $modName = xarModAlias::resolve($modName);
            // Cache values into info static var
            $requestInfo = array($modName, $modType, $funcName);
        } else {
            // Check if we have an object to work with for object URLs
            xarVarFetch('object', 'regexp:/^[a-zA-Z0-9_-]+$/', $objectName, NULL, XARVAR_NOT_REQUIRED);
            if (!empty($objectName)) {
                // Check if we have a method to work with for object URLs
                xarVarFetch('method', 'regexp:/^[a-zA-Z0-9_-]+$/', $methodName, NULL, XARVAR_NOT_REQUIRED);
                // Specify 'dynamicdata' as module for xarTpl_* functions etc.
                $requestInfo = array('dynamicdata', $objectName, $methodName);
                if (empty($url)) {
                    $this->isObjectURL = true;
                }
            } else {
                // If $modName is still empty we use the default module/type/func to be loaded in that such case
                if (empty($this->defaultRequestInfo)) {
                    $this->defaultRequestInfo = array(xarModVars::get('modules', 'defaultmodule'),
                                                      xarModVars::get('modules', 'defaultmoduletype'),
                                                      xarModVars::get('modules', 'defaultmodulefunction'));
                }
                $requestInfo = $this->defaultRequestInfo;
            }
        }
        // Save the current info in case we call this function again
        if (empty($url)) $currentRequestInfo = $requestInfo;
        
        list($this->module,
             $this->type,
             $this->func) = $requestInfo;

        return $requestInfo;
    }
    
    /**
     * Check to see if this request is an object URL
     *
     * @access public
     * @return bool true if object URL, false if not
     */
    function isObjectURL() { return $this->isObjectURL; }

    function getModule()   { return $this->module; }
    function getType()     { return $this->type; }
    function getFunction() { return $this->func; }
}
?>
