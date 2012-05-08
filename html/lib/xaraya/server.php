<?php
/**
 * HTTP Protocol URL/Server utilities
 *
 * @package core
 * @subpackage server
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
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
     * 
     * @param data string the data to be encoded (see todo)
     * @param type string the type of string to be encoded ('getname', 'getvalue', 'path', 'url', 'domain')
     * @return string the encoded URL parts
     * @todo this could be made public
     * @todo support arrays and encode the complete array (keys and values)
    **/
    static private function encode($data, $type = 'getname')
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
     * 
     * @param args array the array to be expanded as a GET parameter
     * @param prefix string the prefix for the GET parameter
     * @return string the expanded GET parameter(s)
     **/
    static private function nested($args, $prefix)
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
     * 
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
    const PROTOCOL_HTTP  = 'http';
    const PROTOCOL_HTTPS = 'https';

    public static $allowShortURLs = true;
    public static $generateXMLURLs = true;

    /**
     * Initialize
     *
     */
    static function init(Array $args=array())
    {
        self::$allowShortURLs = $args['enableShortURLsSupport'];
        self::$generateXMLURLs = $args['generateXMLURLs'];
        // This event is now registered during base module init        
        //xarEvents::register('ServerRequest');
    }
    /**
     * Gets a server variable
     *
     * Returns the value of $name server variable.
     * Accepted values for $name are exactly the ones described by the
     * {@link http://www.php.net/manual/en/reserved.variables.server.php PHP manual}.
     * If the server variable doesn't exist void is returned.
     *
     * 
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
     * 
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
     * 
     * @return string HTTP host name
     */
    static function getHost()
    {
        $server = self::getVar('HTTP_HOST');
        if (empty($server)) {
            // HTTP_HOST is reliable only for HTTP 1.1
            $server = self::getVar('SERVER_NAME');
            $port   = self::getVar('SERVER_PORT');
            $protocol = self::getProtocol();
            if (!($protocol == self::PROTOCOL_HTTP && $port == 80) && !($protocol == self::PROTOCOL_HTTPS && $port == 443)) {
                $server .= ":$port";
            }
        }
        return $server;
    }

    /**
     * Gets the current protocol
     *
     * Returns the HTTP protocol used by current connection, it could be 'http' or 'https'.
     *
     * 
     * @return string current HTTP protocol
     */
    static function getProtocol()
    {
        if (method_exists('xarConfigVars','Get')) {
            try {
                if (xarConfigVars::get(null, 'Site.Core.EnableSecureServer') == true) {
                    if (preg_match('/^http:/', self::getVar('REQUEST_URI'))) {
                        return 'http';
                    }
                    $serverport = $_SERVER['SERVER_PORT'];
                    return ($serverport == xarConfigVars::get(null, 'Site.Core.SecureServerPort')) ? self::PROTOCOL_HTTPS : self::PROTOCOL_HTTP;
                }
            } catch (Exception $e) {
                return self::PROTOCOL_HTTP;
            }
        }
        return self::PROTOCOL_HTTP;
    }

    /**
     * get base URL for Xaraya
     *
     * 
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
     * 
     * @return seconds and microseconds elapsed since the page started
     */
    static function getPageTime()
    {
        return microtime(true) - $GLOBALS["Xaraya_PageTime"];
    }

    /**
     * Get current URL (and optionally add/replace some parameters)
     *
     * 
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

        return $baseurl . self::getCurrentRequestString($args, $generateXMLURL, $target);
    }

    static function getCurrentRequestString($args = array(), $generateXMLURL = NULL, $target = NULL)
    {
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
                            $request = preg_replace("§(&|\?)".preg_quote($find)."§","$k=$v",$request);

                            // ... or remove it otherwise
                        } elseif ($matches[1] == '?') {
                            $request = preg_replace("§\?".preg_quote($find)."(&|)§",'?',$request);
                        } else {
                            $request = str_replace("&$find",'',$request);
                        }
                    // <chris/> !empty is too greedy here, $v=0, $v='', et-al are valid 
                    } elseif (!is_null($v)) {
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
        return $request;
    }
    
    /**
     * Generates an URL that reference to a module function.
     *
     * Cfr. xarModURL() in modules
     */
    static function getModuleURL($modName = NULL, $modType = 'user', $funcName = 'main', $args = array(), $generateXMLURL = NULL, $fragment = NULL, $entrypoint = array())
    {
        // CHECKME: move xarModURL() and xarMod__URL* stuff here, and leave stub in modules ?
        return xarController::URL($modName, $modType, $funcName, $args, $generateXMLURL, $fragment, $entrypoint);
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

        // Create a new request and make its route the current route
        $args['module'] = 'object';
        $args['type'] = $objectName;
        $args['func'] = $methodName;
        sys::import('xaraya.mapper.request');
        $request = new xarRequest($args);
        $router = xarController::getRouter();
        $request->setRoute($router->getRoute());

        // Get the appropriate action controller for this request
        $dispatcher = xarController::getDispatcher();
        $controller = $dispatcher->findController($request);
        $path = $controller->encode($request);

         // Use Xaraya default (index.php) or BaseModURL if provided in config.system.php
        $path = xarController::$entryPoint . $path;

        // Remove the leading / from the path (if any).
        $path = preg_replace('/^\//', '', $path);

        // Add the fragment if required.
        if (isset($fragment)) $path .= '#' . urlencode($fragment);

        // Encode the URL if an XML-compatible format is required.
        if (!isset($generateXMLURL)) $generateXMLURL = self::$generateXMLURLs;
        if ($generateXMLURL) $path = htmlspecialchars($path);

        // Return the URL.
        return self::getBaseURL() . $path;
    }
}

?>
