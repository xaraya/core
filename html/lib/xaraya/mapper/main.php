<?php
/**
 * Main Controller class
 *
 * @package core
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

class xarController extends Object
{
    public static $allowShortURLs = true;
    public static $shortURLVariables;
    public static $delimiter = '?';    // This character divides the URL into action part and parameters
    public static $separator = '&';    // This is the default separator between URL parameters in the default Xaraya route
    public static $dispatcher;
    public static $request;
    public static $response;
    public static $router;

    public static $moduleKey = 'module';
    public static $typeKey   = 'type';
    public static $funcKey   = 'func';
    public static $module    = 'base';
    public static $type      = 'user';
    public static $func      = 'main';
    public static $object    = 'objects';
    public static $method    = 'view';
    public static $entryPoint;
    
    /**
     * Initialize
     *
     */
    static function init(Array $args=array())
    {
        self::$allowShortURLs = $args['enableShortURLsSupport'];

        // The following allows you to modify the BaseModURL from the config file
        // it can be used to configure Xaraya for mod_rewrite by
        // setting BaseModURL = '' in config.system.php
        try {
            self::$entryPoint = xarSystemVars::get(sys::LAYOUT, 'BaseModURL');
        } catch(Exception $e) {
            self::$entryPoint = 'index.php';
        }
    }

    /**
     * Get request variable
     *
     * 
     * @param name string
     * @param allowOnlyMethod string
     * @return mixed
     * @todo change order (POST normally overrides GET)
     * @todo have a look at raw post data options (xmlhttp postings)
     */
    static function getVar($name, $allowOnlyMethod = NULL)
    {
        if (strpos($name, '[') === false) {
            $poststring = '$_POST["' . $name . '"]';            
        } else {
            $position = strpos($name, '[');
            $poststring = '$_POST["' . substr($name,0,$position) . '"]' . substr($name,$position);            
        }
        eval("\$isset = isset($poststring);");

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
            if ($isset) {
                // First check in $_POST
                eval("\$value = $poststring;");
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
            } elseif ($isset) {
                // Then check in $_POST
                eval("\$value = $poststring;");
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
        sys::import('xaraya.mapper.request');
        self::$request = new xarRequest($url);
    }

    static function getRequest($url=null)
    {
        if (empty(self::$request) || !empty($url)) self::setRequest($url);
        return self::$request;
    }

    // Find the route for this request
    static function normalizeRequest($request=null)
    {
        if (!empty($request)) self::$request = $request;
        $router = self::getRouter();
        try {
            $router->route(self::$request);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    static function dispatch($request=null)
    {
        sys::import('xaraya.mapper.response');
        self::$response = new xarResponse();
        try {
            do {
                self::$request->setDispatched(true);
                if (!self::$request->isDispatched()) continue;
                self::$dispatcher->dispatch(self::$request, self::$response);
            } while (!self::$request->isDispatched());
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Check to see if this is a local referral
     *
     * 
     * @return boolean true if locally referred, false if not
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
     * 
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

    public static function setRouter($router)
    {
        self::$router = $router;
    }
    public static function getRouter()
    {
        if (null == self::$router) {
            sys::import('xaraya.mapper.routers.router');
            self::setRouter(new xarRouter());
        }
        return self::$router;
    }
    public static function getDispatcher()
    {
        if (!self::$dispatcher instanceof xarDispatcher) {
            sys::import('xaraya.mapper.dispatcher');
            self::$dispatcher = new xarDispatcher();
        }
        return self::$dispatcher;
    }

    /**
     * Generates an URL that references a module function.
     *
     * 
     * @param modName string registered name of module
     * @param modType string type of function
     * @param funcName string module function
     * @param string fragment document fragment target (e.g. somesite.com/index.php?foo=bar#target)
     * @param args array of arguments to put on the URL
     * @param entrypoint array of arguments for different entrypoint than index.php
     * @return mixed absolute URL for call, or false on failure
     * @todo allow for an alternative entry point (e.g. stream.php) without affecting the other parameters
     */
    static function URL($modName=NULL, $modType='user', $funcName='main', $args=array(), $generateXMLURL=NULL, $fragment=NULL, $entrypoint=array())
    {
        // No module specified - just jump to the home page.
        if (empty($modName)) return xarServer::getBaseURL() . self::$entryPoint;

        // If an entry point has been set, then modify the URL entry point and modType.
        if (!empty($entrypoint)) {
            if (is_array($entrypoint)) {
                $modType = $entrypoint['action'];
                $entrypoint = $entrypoint['entry'];
            }
            self::$emtryPoint = $entrypoint;
        }

        // Create a new request and make its route the current route
        $args['module'] = $modName;
        $args['type'] = $modType;
        $args['func'] = $funcName;
        sys::import('xaraya.mapper.request');
        $request = new xarRequest($args);
        $router = self::getRouter();
        $request->setRoute($router->getRoute());

        // Get the appropriate action controller for this request
        $dispatcher = self::getDispatcher();
        $controller = $dispatcher->findController($request);
        $path = $controller->encode($request);

         // Use Xaraya default (index.php) or BaseModURL if provided in config.system.php
        $path = self::$entryPoint . $path;

        // Remove the leading / from the path (if any).
        $path = preg_replace('/^\//', '', $path);

        // Add the fragment if required.
        if (isset($fragment)) $path .= '#' . urlencode($fragment);

        // Encode the URL if an XML-compatible format is required.
        // Take the global setting for XML format generation, if not specified.
        if (!isset($generateXMLURL)) $generateXMLURL = xarMod::$genXmlUrls;
        if ($generateXMLURL) $path = htmlspecialchars($path);

        // Return the URL.
        return xarServer::getBaseURL() . $path;
    }
    
    public static function parseQuery($url='') 
    {
        $params = array();
        if (empty($url)) return $params;
        $decomposed = parse_url($url);
        if (isset($decomposed['query'])) {
            $pairs = explode('&', $decomposed['query']);
            try {
                foreach($pairs as $pair) {
                    if (trim($pair) == '') continue;
                    list($key, $value) = explode('=', $pair);
                    $params[$key] = urldecode($value);
                }
            } catch(Exception $e) {}
        }
        return $params;
    }

}

?>
