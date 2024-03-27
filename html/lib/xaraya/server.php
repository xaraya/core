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
 * @link http://www.xaraya.info
 *
 * @author Marco Canini <marco@xaraya.com>
 * @author Michel Dalle <mikespub@xaraya.com>
**/

sys::import('xaraya.requests.interface');
sys::import('xaraya.requests.handler');
use Xaraya\Requests\RequestInterface;
use Xaraya\Requests\RequestHandler;

class xarServer extends xarObject
{
    public const PROTOCOL_HTTP  = 'http';
    public const PROTOCOL_HTTPS = 'https';

    /** @var ?string */
    public static $baseurl;
    /** @var bool */
    public static $allowShortURLs = true;
    /** @var bool */
    public static $generateXMLURLs = true;
    /** @var ?RequestInterface */
    private static $instance;
    /** @var class-string */
    private static $requestClass = RequestHandler::class;

    /**
     * Initialize
     * @param array<string, mixed> $args
     * @return void
     */
    public static function init(array $args = [])
    {
        if (empty($args)) {
            $args = self::getConfig();
        }
        self::$allowShortURLs = $args['enableShortURLsSupport'];
        self::$generateXMLURLs = $args['generateXMLURLs'];
        self::$baseurl = null;

        // Set up the request object
        $request = new self::$requestClass($args);
        // Initialize the request
        $request->initialize();
        // This event is now registered during base module init
        //xarEvents::register('ServerRequest');
    }

    /**
     * Get server configuration
     * @return array<string, mixed>
     */
    public static function getConfig()
    {
        $systemArgs = ['enableShortURLsSupport' => xarConfigVars::get(null, 'Site.Core.EnableShortURLsSupport'),
                            'generateXMLURLs' => true];
        return $systemArgs;
    }

    /**
     * Set the request class to use (instead of RequestHandler)
     * @param class-string $className
     * @return void
     */
    public static function setRequestClass($className)
    {
        self::$requestClass = $className;
    }

    /**
     * Get the request class instance
     * @return RequestInterface
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            // Set up the request object
            $request = new self::$requestClass([]);
            // Initialize the request
            $request->initialize();
        }
        return self::$instance;
    }

    /**
     * Set the request class instance
     * @param RequestInterface $instance
     * @return void
     */
    public static function setInstance($instance)
    {
        self::$instance = $instance;
    }

    /**
     * Gets a server variable
     *
     * Returns the value of $name server variable.
     * Accepted values for $name are exactly the ones described by the
     * {@link http://www.php.net/manual/en/reserved.variables.server.php PHP manual}.
     * If the server variable doesn't exist null is returned.
     *
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public static function getVar($name)
    {
        assert(version_compare("7.2", phpversion()) <= 0);
        return self::getInstance()->getServerVar($name);
    }

    /**
     * Allow setting server variable if needed
     * @param string $name the name of the variable
     * @param mixed $value value of the variable
     * @return void
     */
    public static function setVar($name, $value)
    {
        self::getInstance()->setServerVar($name, $value);
    }

    /**
     * Get base URI for Xaraya
     *
     * @return string base URI for Xaraya
     * @todo remove whatever may come after the PHP script - TO BE CHECKED !
     * @todo See code comments.
     */
    public static function getBaseURI()
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
        /** @var string $path */

        $path = preg_replace('/[#\?].*/', '', (string) $path);

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
     * @return string HTTP host name
     */
    public static function getHost()
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
     * @return string current HTTP protocol
     */
    public static function getProtocol()
    {
        if (method_exists('xarConfigVars', 'Get')) {
            try {
                if (xarConfigVars::get(null, 'Site.Core.EnableSecureServer') == true) {
                    if (preg_match('/^http:/', self::getVar('REQUEST_URI') ?? '')) {
                        return self::PROTOCOL_HTTP;
                    }
                    $serverport = self::getVar('SERVER_PORT');
                    $protocol = ($serverport == xarConfigVars::get(null, 'Site.Core.SecureServerPort')) ? self::PROTOCOL_HTTPS : self::PROTOCOL_HTTP;
                    return $protocol;
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
     * @return string base URL for Xaraya
     */
    public static function getBaseURL()
    {
        if (self::$baseurl != null) {
            return self::$baseurl;
        }

        $server   = self::getHost();
        $protocol = self::getProtocol();
        $path     = self::getBaseURI();

        self::$baseurl = "$protocol://$server$path/";
        return self::$baseurl;
    }

    /**
     * Allow setting baseurl if needed
     * @param string $baseurl
     * @return void
     */
    public static function setBaseURL($baseurl)
    {
        self::$baseurl = $baseurl;

        $info = parse_url($baseurl);
        self::setVar('SERVER_NAME', $info['host']);
        if ($info['scheme'] === 'https') {
            self::setVar('SERVER_PORT', $info['port'] ?? 443);
        } else {
            self::setVar('SERVER_PORT', $info['port'] ?? 80);
        }
        // strip trailing slash for BaseURI here - added again in getBaseURL()
        xarSystemVars::set(sys::LAYOUT, 'BaseURI', rtrim($info['path'], '/'));
    }

    /**
     * get the elapsed time since this page started
     *
     * @return float seconds and microseconds elapsed since the page started
     */
    public static function getPageTime()
    {
        return microtime(true) - $GLOBALS["Xaraya_PageTime"];
    }

    /**
     * Get current URL (and optionally add/replace some parameters)
     *
     * @param array<string, mixed> $args additional parameters to be added to/replaced in the URL (e.g. theme, ...)
     * @param ?bool $generateXMLURL over-ride Server default setting for generating XML URLs (true/false/NULL)
     * @param ?string $target add a 'target' component to the URL
     * @return string current URL
     * @todo cfr. BaseURI() for other possible ways, or try PHP_SELF
     */
    public static function getCurrentURL($args = [], $generateXMLURL = null, $target = null)
    {
        $server   = self::getHost();
        $protocol = self::getProtocol();
        $baseurl  = "$protocol://$server";

        // @checkme what you see is (not always) what you get - BaseURI may be missing here
        $request  = self::getCurrentRequestString($args, $generateXMLURL, $target);
        $path     = self::getBaseURI();
        if (!empty($path) && strpos($request, $path) !== 0) {
            $baseurl .= $path;
        }
        return $baseurl . $request;
    }

    /**
     * Get current query string (and optionally add/replace some parameters)
     *
     * @param array<string, mixed> $args additional parameters to be added to/replaced in the URL (e.g. theme, ...)
     * @param ?bool $generateXMLURL over-ride Server default setting for generating XML URLs (true/false/NULL)
     * @param ?string $target add a 'target' component to the URL
     * @return string current query string
     */
    public static function getCurrentRequestString($args = [], $generateXMLURL = null, $target = null)
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
                if (!empty($querystring)) {
                    $request .= '?' . $querystring;
                }
            } else {
                $request = '/';
            }
        }

        // TODO: re-use some common code (with in-line replacement here) or use parse_url + http_build_query ?

        //$url_variables = parse_str($querystring);
        //var_dump($url_variables);

        // add optional parameters
        if (count($args) > 0) {
            if (strpos($request, '?') === false) {
                $request .= '?';
            } else {
                $request .= '&';
            }

            foreach ($args as $k => $v) {
                if (is_array($v)) {
                    foreach($v as $l => $w) {
                        // TODO: replace in-line here too ?
                        if (!empty($w)) {
                            $request .= $k . "[$l]=$w&";
                        }
                    }
                } else {
                    // if this parameter is already in the query string...
                    if (preg_match("/(&|\?)($k=[^&]*)/", $request, $matches)) {
                        $find = $matches[2];
                        // ... replace it in-line if it's not empty
                        if (!empty($v)) {
                            $request = preg_replace("#(&|\?)" . preg_quote($find) . "#", "$1$k=$v", $request);

                        // ... or remove it otherwise
                        } elseif ($matches[1] == '?') {
                            $request = preg_replace("#\?" . preg_quote($find) . "(&|)#", '?', $request);
                        } else {
                            $request = str_replace("&$find", '', $request);
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
        if (!isset($generateXMLURL)) {
            $generateXMLURL = self::$generateXMLURLs;
        }
        if (isset($target)) {
            $request .= '#' . urlencode($target);
        }
        if ($generateXMLURL) {
            $request = htmlspecialchars($request);
        }
        return $request;
    }

    /**
     * Generates an URL that reference to a module function.
     *
     * Cfr. xarMod URL() in modules
     * @param ?string $modName registered name of module
     * @param string $modType type of function
     * @param string $funcName module function
     * @param array<string, mixed> $args additional parameters to be added to/replaced in the URL (e.g. theme, ...)
     * @param ?bool $generateXMLURL over-ride Server default setting for generating XML URLs (true/false/NULL)
     * @param ?string $fragment document fragment target (e.g. somesite.com/index.php?foo=bar#target)
     * @param string|array<string, mixed> $entrypoint array of arguments for different entrypoint than index.php
     * @return mixed absolute URL for call, or false on failure
     */
    public static function getModuleURL($modName = null, $modType = 'user', $funcName = 'main', $args = [], $generateXMLURL = null, $fragment = null, $entrypoint = [])
    {
        // CHECKME: move xarMod URL() and xarMod__URL* stuff here, and leave stub in modules ?
        return xarController::URL($modName, $modType, $funcName, $args, $generateXMLURL, $fragment, $entrypoint);
    }

    /**
     * Generates a URL that reference to an object user interface method.
     * @param ?string $objectName
     * @param string $methodName
     * @param array<string, mixed> $args additional parameters to be added to/replaced in the URL (e.g. theme, ...)
     * @param ?bool $generateXMLURL over-ride Server default setting for generating XML URLs (true/false/NULL)
     * @param ?string $fragment document fragment target (e.g. somesite.com/index.php?foo=bar#target)
     * @param string|array<string, mixed> $entrypoint array of arguments for different entrypoint than index.php
     * @return mixed absolute URL for call, or false on failure
     */
    public static function getObjectURL($objectName = null, $methodName = 'view', $args = [], $generateXMLURL = null, $fragment = null, $entrypoint = [])
    {
        // Allow overriding building URL if needed
        if (!empty(xarController::$buildUri) && is_callable(xarController::$buildUri)) {
            return call_user_func(xarController::$buildUri, 'object', $objectName, $methodName, $args);
        }
        // 1. override any existing 'method' in args, and place before the rest
        if (!empty($methodName)) {
            $args = ['method' => $methodName] + $args;
        }
        // 2. override any existing 'object' or 'name' in args, and place before the rest
        if (!empty($objectName)) {
            unset($args['name']);
            // use 'object' here to distinguish from module URLs
            $args = ['object' => $objectName] + $args;
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
        if (isset($fragment)) {
            $path .= '#' . urlencode($fragment);
        }

        // Encode the URL if an XML-compatible format is required.
        if (!isset($generateXMLURL)) {
            $generateXMLURL = self::$generateXMLURLs;
        }
        if ($generateXMLURL) {
            $path = htmlspecialchars($path);
        }

        // Return the URL.
        return self::getBaseURL() . $path;
    }
}
