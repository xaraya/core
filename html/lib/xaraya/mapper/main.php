<?php
/**
 * Main Controller class
 *
 * @package core\controllers
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

use Xaraya\Requests\RequestInterface;

class xarController extends xarObject
{
    public static bool $allowShortURLs = true;
    /** @var array<string, mixed> */
    public static $shortURLVariables;
    public static string $delimiter = '?';    // This character divides the URL into action part and parameters
    public static string $separator = '&';    // This is the default separator between URL parameters in the default Xaraya route
    /** @var xarDispatcher */
    public static $dispatcher;
    /** @var xarRequest */
    public static $request;
    /** @var xarResponse */
    public static $response;
    /** @var xarRouter */
    public static $router;

    public static string $moduleKey = 'module';
    public static string $typeKey   = 'type';
    public static string $funcKey   = 'func';
    public static string $module    = 'base';
    public static string $type      = 'user';
    public static string $func      = 'main';
    public static string $object    = 'objects';
    public static string $method    = 'view';
    public static string $entryPoint = 'index.php';
    /** @var ?callable */
    public static $buildUri;     // callable for building URIs when using non-standard entrypoints
    /** @var ?callable */
    public static $redirectTo;   // callable for redirecting to when using non-standard entrypoints
    /** @var ?RequestInterface */
    private static $requestContext = null;

    /**
     * Initialize
     *
     * @param array<string, mixed> $args
     */
    public static function init(array $args = array()): void
    {
        if (empty($args)) {
            $args = self::getConfig();
        }
        if (isset($args['enableShortURLsSupport'])) {
            self::$allowShortURLs = $args['enableShortURLsSupport'];
        }

        // @todo update xarController::$endpoint based on actual SCRIPT_NAME?
        // The following allows you to modify the BaseModURL from the config file
        // it can be used to configure Xaraya for mod_rewrite by
        // setting BaseModURL = '' in config.system.php
        try {
            self::$entryPoint = xarSystemVars::get(sys::LAYOUT, 'BaseModURL');
        } catch(Exception $e) {
            self::$entryPoint = 'index.php';
        }
        // xarController::init() comes after xarServer::init()
        self::$requestContext = xarServer::getInstance();
    }

    /**
     * Summary of getConfig
     * @return array<string, mixed>
     */
    public static function getConfig()
    {
        $systemArgs = array('enableShortURLsSupport' => xarConfigVars::get(null, 'Site.Core.EnableShortURLsSupport'),
                            'generateXMLURLs' => true);
        return $systemArgs;
    }

    /**
     * Handle multi-dimensional array lookup name[key1][key2][...]
     * @param mixed $var
     * @param mixed $name
     * @return mixed
     */
    public static function getArrayVar($var, $name)
    {
        if (empty($var) || !is_array($var)) {
            return null;
        }
        // 1st: $key = 'name', $rest = [ 'key1]', 'key2]', '...]' ]
        // 2nd: $key = 'key1]', $rest = [ 'key2]', '...]' ]
        // 3rd: $key = 'key2]', $rest = [ '...]' ]
        // 4th: $key = '...]', $rest = []
        $rest = explode('[', $name . '[');
        $key = array_shift($rest);
        array_pop($rest);
        $key = rtrim($key, ']');
        $key = str_replace('"', '', $key);
        if (!isset($var[$key])) {
            return null;
        }
        if (empty($rest)) {
            // 4th: return $var[...]
            return $var[$key];
        }
        // 1st: pass along key1][key2][...]
        // 2nd: pass along key2][...]
        // 3rd: pass along ...]
        return self::getArrayVar($var[$key], implode('[', $rest));
    }

    /**
     * Get a request variable
     *
     * @param string $name
     * @param ?string $allowOnlyMethod
     * @return mixed
     * @todo change order (POST normally overrides GET)
     * @todo have a look at raw post data options (xmlhttp postings)
     */
    public static function getVar($name, $allowOnlyMethod = null)
    {
        // First check in $_POST
        if (strpos($name, '[') === false) {
            $value = self::$requestContext?->getBodyVar($name) ?? null;
            $isset = isset($value);
        } else {
            $value = self::getArrayVar(self::$requestContext?->getParsedBody(), $name);
            $isset = isset($value);
        }

        if ($allowOnlyMethod == 'GET') {
            // Short URLs variables override GET variables
            if (self::$allowShortURLs && isset(self::$shortURLVariables[$name])) {
                $value = self::$shortURLVariables[$name];
            } else {
                // Then check in $_GET
                $value = self::$requestContext?->getQueryVar($name);
                if (!isset($value)) {
                    // Nothing found, return null
                    return null;
                }
            }
            $method = $allowOnlyMethod;
        } elseif ($allowOnlyMethod == 'POST') {
            if ($isset) {
                // First check in $_POST
                // see $value above
            } else {
                // Nothing found, return null
                return null;
            }
            $method = $allowOnlyMethod;
        } else {
            if (self::$allowShortURLs && isset(self::$shortURLVariables[$name])) {
                // Short URLs variables override GET and POST variables
                $value = self::$shortURLVariables[$name];
                $method = 'GET';
            } elseif ($isset) {
                // Then check in $_POST
                // see $value above
                $method = 'POST';
            } else {
                // Then check in $_GET
                $value = self::$requestContext?->getQueryVar($name);
                if (!isset($value)) {
                    // Nothing found, return null
                    return null;
                }
                $method = 'GET';
            }
        }

        $value = xarMLS::convertFromInput($value, $method);

        //if (get_magic_quotes_gpc()) {
        //    $value = self::__stripslashes($value);
        //}
        return $value;
    }

    /**
     * Summary of __stripslashes
     * @param array<string, mixed>|string $value
     * @return array<string, mixed>|string
     */
    public static function __stripslashes($value)
    {
        $value = is_array($value) ? array_map(array('self','__stripslashes'), $value) : stripslashes($value);
        return $value;
    }

    /**
     * Summary of setRequest
     * @param mixed $url
     * @return void
     */
    public static function setRequest($url = null)
    {
        sys::import('xaraya.mapper.request');
        self::$request = new xarRequest($url);
    }

    /**
     * Summary of getRequest
     * @param mixed $url
     * @return xarRequest
     */
    public static function getRequest($url = null)
    {
        if (empty(self::$request) || !empty($url)) {
            self::setRequest($url);
        }
        return self::$request;
    }

    /**
     * Find the route for this request
     * @param xarRequest|null $request
     * @return void
     */
    public static function normalizeRequest($request = null)
    {
        if (!empty($request)) {
            self::$request = $request;
        }
        $router = self::getRouter();
        try {
            $router->route(self::$request);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Dispatch the request to the controller for that route
     * @param xarRequest|null $request
     * @return void
     */
    public static function dispatch($request = null)
    {
        sys::import('xaraya.mapper.response');
        self::$response = new xarResponse();
        try {
            do {
                self::$request->setDispatched(true);
                if (!self::$request->isDispatched()) {
                    continue;
                }
                self::$dispatcher->dispatch(self::$request, self::$response);
            } while (!self::$request->isDispatched());
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Check to see if this is a local referral
     *
     * @return boolean true if locally referred, false if not
     */
    public static function isLocalReferer()
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
     * Check if the referral comes from the same module for admin overview
     * @return bool
     */
    public static function isRefererSameModule()
    {
        $refererinfo = self::getRequest()->getInfo(xarServer::getVar('HTTP_REFERER'));
        $module = self::getRequest()->getModule();
        return $module == $refererinfo[0];
    }

    /**
     * Carry out a redirect
     *
     * @param string $url the URL to redirect to
     * @param mixed $httpResponse
     * @param mixed $context
     * @return bool|never
     */
    public static function redirect($url, $httpResponse = null, $context = null)
    {
        xarCache::noCache();
        $redirectURL = urldecode($url); // this is safe if called multiple times.

        // Remove &amp; entities to prevent redirect breakage
        $redirectURL = str_replace('&amp;', '&', $redirectURL);

        if (substr($redirectURL, 0, 4) != 'http') {
            // Removing leading slashes from redirect url
            $redirectURL = preg_replace('!^/*!', '', $redirectURL);

            // Get base URL
            $baseurl = xarServer::getBaseURL();

            $redirectURL = $baseurl.$redirectURL;
        }

        // default response is temp redirect
        if (!preg_match('/^301|302|303|307/', $httpResponse ?? '')) {
            $httpResponse = 302;
        }

        // Pass along redirectURL and bail out if we already sent headers
        if (headers_sent() == true) {
            if (!empty($context)) {
                $context['redirectURL'] = $redirectURL;
                $context['status'] = $httpResponse;
            }
            if (!empty(self::$redirectTo) && is_callable(self::$redirectTo)) {
                call_user_func(self::$redirectTo, $redirectURL, $httpResponse, $context);
            }
            return false;
        }

        if (preg_match('/IIS/', xarServer::getVar('SERVER_SOFTWARE') ?? '') && preg_match('/CGI/', xarServer::getVar('GATEWAY_INTERFACE') ?? '')) {
            $header = "Refresh: 0; URL=$redirectURL";
        } else {
            $header = "Location: $redirectURL";
        }// if

        // Start all over again
        header($header, true, $httpResponse);

        // NOTE: we *could* return for pure '1 exit point' but then we'd have to keep track of more,
        // so for now, we exit here explicitly. Besides the end of index.php this should be the only
        // exit point.
        exit();
    }

    /**
     * Summary of setRouter
     * @param xarRouter $router
     * @return void
     */
    public static function setRouter($router)
    {
        self::$router = $router;
    }

    /**
     * Summary of getRouter
     * @return xarRouter
     */
    public static function getRouter()
    {
        if (null == self::$router) {
            sys::import('xaraya.mapper.routers.router');
            self::setRouter(new xarRouter());
        }
        return self::$router;
    }

    /**
     * Summary of getDispatcher
     * @return xarDispatcher
     */
    public static function getDispatcher()
    {
        if (!self::$dispatcher instanceof xarDispatcher) {
            sys::import('xaraya.mapper.dispatcher');
            self::$dispatcher = new xarDispatcher();
        }
        return self::$dispatcher;
    }

    /**
     * Generates a URL that references a module function.
     *
     * @param string $modName registered name of module
     * @param string $modType type of function
     * @param string $funcName module function
     * @param string $fragment document fragment target (e.g. somesite.com/index.php?foo=bar#target)
     * @param array<string, mixed> $args array of arguments to put on the URL
     * @param ?bool $generateXMLURL
     * @param string|array<string, mixed> $entrypoint array of arguments for different entrypoint than index.php
     * @param ?string $route
     * @return mixed absolute URL for call, or false on failure
     * @todo allow for an alternative entry point (e.g. stream.php) without affecting the other parameters
     */
    public static function URL($modName = null, $modType = 'user', $funcName = 'main', $args = array(), $generateXMLURL = null, $fragment = null, $entrypoint = array(), $route = null)
    {
        // Allow overriding building URL if needed
        if (!empty(self::$buildUri) && is_callable(self::$buildUri)) {
            // @todo do we need to add baseUri as prefix here?
            return call_user_func(self::$buildUri, $modName, $modType, $funcName, $args);
        }
        // (Re)initialize the controller
        self::init();

        // No module specified - just jump to the home page.
        if (empty($modName)) {
            return xarServer::getBaseURL() . self::$entryPoint;
        }

        // If an entry point has been set, then modify the URL entry point and modType.
        if (!empty($entrypoint)) {
            if (is_array($entrypoint)) {
                $modType = $entrypoint['action'];
                $entrypoint = $entrypoint['entry'];
            }
            self::$entryPoint = $entrypoint;
        }

        // Create a new request and make its route the current route
        $args['module'] = $modName;
        $args['type'] = $modType;
        $args['func'] = $funcName;
        sys::import('xaraya.mapper.request');
        $request = new xarRequest($args);
        // <chris/> wrt to the problem of xaraya not obeying a particular route
        // when the main entry point, sans params, is accessed...
        // Here's an example using the shorturls setting in base module
        // It's hardly a leap to imagine storing the name of the route to use in a
        // similar config var and being able to set that in base module instead (IMO)
        // assuming multiple routes aren't in use, of course, although we could perhaps
        // deprecate the per module shorturl setting in favour of a dropdown of routes too :-?
        /*
        if (xarMod::$genShortUrls) {
            $request->setRoute('short');
        } else {
            $router = self::getRouter();
            $request->setRoute($router->getRoute());
        }
        */

        // If we are passed a route, then use it
        if (empty($route)) {
            // No route passed: use the default
            $route = xarConfigVars::get(null, 'Site.Core.EnableShortURLsSupport');
        }
        // Define the route
        if (!empty($route)) {
            $request->setRoute($route);
        } else {
            $router = self::getRouter();
            $request->setRoute($router->getRoute());
        }

        // Get the appropriate action controller for this request
        $dispatcher = self::getDispatcher();
        $controller = $dispatcher->findController($request);
        $path = $controller->encode($request);

        // Use Xaraya default (index.php) or BaseModURL if provided in config.system.php
        $path = self::$entryPoint . $path;

        // Remove the leading / from the path (if any).
        $path = preg_replace('/^\//', '', $path);

        // Add the fragment if required.
        if (isset($fragment)) {
            $path .= '#' . urlencode($fragment);
        }

        // Encode the URL if an XML-compatible format is required.
        // Take the global setting for XML format generation, if not specified.
        if (!isset($generateXMLURL)) {
            $generateXMLURL = xarMod::$genXmlUrls;
        }
        if ($generateXMLURL) {
            $path = htmlspecialchars($path);
        }

        // Return the URL.
        return xarServer::getBaseURL() . $path;
    }

    /**
     * Summary of parseQuery
     * @param string $url
     * @return array<string, mixed>
     */
    public static function parseQuery($url = '')
    {
        $params = array();
        if (empty($url)) {
            return $params;
        }
        $decomposed = parse_url($url);
        if (isset($decomposed['query'])) {
            $pairs = explode('&', $decomposed['query']);
            try {
                foreach($pairs as $pair) {
                    if (trim($pair) == '') {
                        continue;
                    }
                    list($key, $value) = explode('=', $pair);
                    $params[$key] = urldecode($value);
                }
            } catch(Exception $e) {
            }
        }
        return $params;
    }
}
