<?php
class xarController extends Object
{
    public static $allowShortURLs = true;
    public static $shortURLVariables;
    public static $delimiter = '/';
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
    public static $entryPoint;
    
    /**
     * Initialize
     *
     */
    static function init($args)
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
        sys::import('xaraya.mapper.request');
        self::$request = new xarRequest($url);
    }

    static function getRequest($url=null)
    {
        if (empty(self::$request) || !empty($url)) self::setRequest($url);
        return self::$request;
    }

    static function dispatch($request=null)
    {
        // CHECKME: How to handle null request?
        if (!empty($request)) self::$request = $request;
        sys::import('xaraya.mapper.response');
        self::$response = new xarResponse();
        $router = self::getRouter();
        try {
            $router->route(self::$request);

            /**
             *  Attempt to dispatch the controller/action. If the self::$request
             *  indicates that it needs to be dispatched, move to the next
             *  action in the request.
             */
            do {
                self::$request->setDispatched(true);
                if (!self::$request->isDispatched()) continue;
                self::$dispatcher->dispatch(self::$request, self::$response);

            } while (!self::$request->isDispatched());
        } catch (Exception $e) {
                throw $e;
        }
        if (self::$request->isObjectURL()) {
            sys::import('xaraya.objects');

            // Call the object handler and return the output (or exit with 404 Not Found)
            self::$response->output = xarObject::guiMethod(self::$request->getType(), self::$request->getFunction());

        } else {
            if (!xarConfigVars::get(null, 'Site.Core.EnableShortURLsSupport')) {
                // Call the main module function and return the output (or exit with 404 Not Found)
                self::$response->output = xarMod::guiFunc(self::$request->getModule(), self::$request->getType(), self::$request->getFunction());
            } else {
                if (self::$request->isModuleURL()) {
                    // Call the main module function and return the output (or exit with 404 Not Found)
                    self::$response->output = xarMod::guiFunc(self::$request->getModule(), self::$request->getType(), self::$request->getFunction());
                } else {
                    self::$response->output = self::$response->notFound();
                }
            }
        }
    }
    
    static function encode($request=null)
    {
        // CHECKME: How to handle null request?
        if (!empty($request)) self::$request = $request;

        if (self::$request->isObjectURL()) {
            // Stuck for now
        } else {
            $dispatcher = new xarDispatcher($request);
            $controller = $dispatcher->getController();
            return $controller->encode($request);
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

    public static function setRouter($router)
    {
        self::$router = $router;
    }
    public static function getRouter()
    {
        if (null == self::$router) {
            sys::import('xaraya.mapper.routers.router');;
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
    static function URL($modName=NULL, $modType='user', $funcName='main', $args=array(), $generateXMLURL=NULL, $fragment=NULL, $entrypoint=array())
    {
        // Parameter separator and initiator.
        $psep = '&';
        $pini = '?';
        $pathsep = '/';

        // Initialise the path.
        $path = '';

        // No module specified - just jump to the home page.
        if (empty($modName)) return xarServer::getBaseURL() . self::$entryPoint;

        // Take the global setting for XML format generation, if not specified.
        if (!isset($generateXMLURL)) $generateXMLURL = xarMod::$genXmlUrls;

        // If an entry point has been set, then modify the URL entry point and modType.
        if (!empty($entrypoint)) {
            if (is_array($entrypoint)) {
                $modType = $entrypoint['action'];
                $entrypoint = $entrypoint['entry'];
            }
            self::$emtryPoint = $entrypoint;
        }

        // If we have an empty argument (ie null => null) then set a flag and
        // remove that element.
        // FIXME: this is way too hacky, NULL as a key for an array sooner or later will fail. (php 4.2.2 ?)
        if (is_array($args) && @array_key_exists(NULL, $args) && $args[NULL] === NULL) {
            // This flag means that the GET part of the URL must be opened.
            $open_get_flag = true;
            unset($args[NULL]);
        }

        // Check the short URL settings
//       if (xarMod::$genShortUrls && xarModVars::get($modName, 'enable_short_urls') && $modType == 'user') {
        
            // Create a new request and make its route the current route
            $encoderArgs = $args;
            $encoderArgs['module'] = $modName;
            $encoderArgs['func'] = $funcName;
            $request = new xarRequest($encoderArgs);
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
/*
            // CHECKME: what is this?
            // Workaround for bug 3603
            // why: template might add extra params we dont see here
            if (!empty($open_get_flag) && !strpos($path, $pini)) {$path .= $pini;}
        }
*/
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
            $path = xarURL::addParametersToPath($args, self::$entryPoint, $pini, $psep);

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
}

?>