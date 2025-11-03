<?php

/**
 * Request available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use Xaraya\Requests\RequestInterface as RequestFacade;
use Xaraya\Requests\RequestHandler;
use xarRequest;
use xarSystemVars;
use sys;
use Exception;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via RequestTrait
 */
interface RequestInterface extends ServiceInterface
{
    public const SLICE = 'request2';

    public static function setRequestClass(string $className): void;
    /** @param array<string, mixed> $config */
    public function setConfig(array $config = []): void;
    public function getInstance(): RequestFacade;
    public function setInstance(RequestFacade $instance): void;
    public function newInstance(): RequestFacade;
    public function getRequest(mixed $url = null): xarRequest;
    public function setRequest(mixed $url = null): void;
    public function getModule(): string;
    public function getType(): string;
    public function getFunction(): string;
    /** @param array<string, mixed> $params */
    public function getURL(array $params = []): string;
    public function getRequestString(array $params = []): string;
    public function getBaseURI(): string;
    public function getServerVar(string $varName): mixed;
    public function setServerVar(string $varName, mixed $value): void;
    public function getVar(string $varName, ?string $allowOnlyMethod = null): mixed;
    public function getHost(): string;
    public function getProtocol(): string;
    public function getMethod(): string;
    public function isLocalReferer(): bool;
    public function isSameReferer(): bool;
    /** @param array<mixed>|object $var */
    public function getArrayVar(mixed $var, string $varName): mixed;
}

/**
 * Request available via methods
 */
trait RequestTrait
{
    use ServiceTrait;

    public const PROTOCOL_HTTP  = 'http';
    public const PROTOCOL_HTTPS = 'https';

    /** @var class-string<RequestFacade> */
    private static $requestClass = RequestHandler::class;
    /** @var bool */
    public $allowShortURLs = false;
    /** @var array<string, mixed> */
    public $shortURLVariables;
    /** @var array<string, mixed> */
    private array $args = [];
    protected bool $initialized = false;
    /** @var xarRequest */
    public $request;

    public static function setRequestClass(string $className): void
    {
        // --- LEGACY METHOD BODY ---
        self::$requestClass = $className;
        // --- END LEGACY METHOD BODY ---
    }

    /** @param array<string, mixed> $config */
    public function init(array $config = []): bool
    {
        // --- LEGACY METHOD BODY ---
        if (empty($config)) {
            if (!empty($this->initialized)) {
                return true;
            }
            $config = $this->getConfig();
        }
        $this->setConfig($config);

        // Set up the request instance with current context
        $request = $this->newInstance();
        $this->setInstance($request);

        // Initialize the request
        $request->initialize();
        $this->initialized = true;
        return true;
        // --- END LEGACY METHOD BODY ---
        // this will be relying on RequestService in the future
        //return xarServer::init($config, $context);
    }

    /**
     * Get server configuration
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $xar = $this->getParent();
        // --- LEGACY METHOD BODY ---
        $systemArgs = [
            'enableShortURLsSupport' => $xar->config()->getVar('Site.Core.EnableShortURLsSupport'),
            //'generateXMLURLs'        => true,
        ];
        return $systemArgs;
        // --- END LEGACY METHOD BODY ---
        //return xarServer::getConfig();
    }

    /** @param array<string, mixed> $config */
    public function setConfig(array $config = []): void
    {
        if (isset($config['enableShortURLsSupport'])) {
            $this->allowShortURLs = $config['enableShortURLsSupport'];
        }
        $this->args = $config;
    }

    /**
     * Get the request class instance (on demand)
     */
    public function getInstance(): RequestFacade
    {
        // --- LEGACY METHOD BODY ---
        // moved to static services class
        $instance = $this->getParent()->getRequestInstance();
        if (!isset($instance)) {
            // Set up the request instance with current context
            $instance = $this->newInstance();
            $this->setInstance($instance);
            // Initialize the request
            $instance->initialize();
        }
        return $instance;
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Set the request class instance
     */
    public function setInstance(RequestFacade $instance): void
    {
        // --- LEGACY METHOD BODY ---
        // moved to static services class
        $this->getParent()->setRequestInstance($instance);
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Create new request class instance
     */
    public function newInstance(): RequestFacade
    {
        // Set up the request instance with current context
        // --- LEGACY METHOD BODY ---
        return new self::$requestClass($this->args, $this->getContext());
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Get the module that was resolved by the router for the current request.
     */
    public function getModule(): string
    {
        return $this->getRequest()->getModule();
    }

    /**
     * Get the request type (e.g., 'user', 'admin').
     */
    public function getType(): string
    {
        return $this->getRequest()->getType();
    }

    /**
     * Get the function name for the current request.
     */
    public function getFunction(): string
    {
        return $this->getRequest()->getFunction();
    }

    /**
     * Get current url
     * @param array<string, mixed> $params
     */
    public function getURL(array $params = []): string
    {
        // --- LEGACY METHOD BODY ---
        $server   = $this->getHost();
        $protocol = $this->getProtocol();
        $baseurl  = "$protocol://$server";

        // @checkme what you see is (not always) what you get - BaseURI may be missing here
        $request  = $this->getRequestString($params);
        $path     = $this->getBaseURI();
        if (!empty($path) && strpos($request, $path) !== 0) {
            $baseurl .= $path;
        }
        return $baseurl . $request;
        // --- END LEGACY METHOD BODY ---
        //return xarServer::getCurrentURL($params, $generateXMLURL);
    }

    public function getRequestString(array $params = []): string
    {
        // --- LEGACY METHOD BODY ---
        // get current URI
        $request = $this->getServerVar('REQUEST_URI');

        if (empty($request)) {
            // adapted patch from Chris van de Steeg for IIS
            // TODO: please test this :)
            $scriptname = $this->getServerVar('SCRIPT_NAME');
            $pathinfo   = $this->getServerVar('PATH_INFO');
            if ($pathinfo == $scriptname) {
                $pathinfo = '';
            }
            if (!empty($scriptname)) {
                $request = $scriptname . $pathinfo;
                $querystring = $this->getServerVar('QUERY_STRING');
                if (!empty($querystring)) {
                    $request .= '?' . $querystring;
                }
            } else {
                $request = '/';
            }
        }

        if (empty($params)) {
            return $request;
        }

        // TODO: re-use some common code (with in-line replacement here) or use parse_url + http_build_query ?

        //$url_variables = parse_str($querystring);
        //var_dump($url_variables);

        // add optional parameters
        if (strpos($request, '?') === false) {
            $request .= '?';
        } else {
            $request .= '&';
        }

        // @todo this assumes all params are in the query string (no short urls or other routes)
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $l => $w) {
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

        return $request;
        // --- END LEGACY METHOD BODY ---
        //return xarServer::getCurrentRequestString($params, $generateXMLURL, $target);
    }

    /**
     * Get base uri
     */
    public function getBaseURI(): string
    {
        // --- LEGACY METHOD BODY ---
        // Allows overriding the Base URI from config.php
        // it can be used to configure Xaraya for mod_rewrite by
        // setting BaseURI = '' in config.php
        try {
            $BaseURI =  xarSystemVars::get(sys::LAYOUT, 'BaseURI');
            return $BaseURI;
        } catch (Exception $e) {
            // We need to build it
        }

        // Get the name of this URI
        $path = $this->getServerVar('REQUEST_URI');

        //if ((empty($path)) ||
        //    (substr($path, -1, 1) == '/')) {
        //what's wrong with a path (cfr. Indexes index.php, mod_rewrite etc.) ?
        if (empty($path)) {
            // REQUEST_URI was empty or pointed to a path
            // adapted patch from Chris van de Steeg for IIS
            // Try SCRIPT_NAME
            $path = $this->getServerVar('SCRIPT_NAME');
            if (empty($path)) {
                // No luck there either
                // Try looking at PATH_INFO
                $path = $this->getServerVar('PATH_INFO');
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
        // --- END LEGACY METHOD BODY ---
        //return xarServer::getBaseURI();
    }

    /**
     * Get a server variable
     * @return mixed
     */
    public function getServerVar(string $varName): mixed
    {
        // --- LEGACY METHOD BODY ---
        return $this->getInstance()->getServerVar($varName);
        // --- END LEGACY METHOD BODY ---
    }

    public function setServerVar(string $varName, mixed $value): void
    {
        // --- LEGACY METHOD BODY ---
        $this->getInstance()->setServerVar($varName, $value);
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Get a request variable
     * @return mixed
     */
    public function getVar(string $varName, ?string $allowOnlyMethod = null): mixed
    {
        // --- LEGACY METHOD BODY ---
        // First check in $_POST
        if (strpos($varName, '[') === false) {
            $value = $this->getInstance()?->getBodyVar($varName) ?? null;
            $isset = isset($value);
        } else {
            $value = $this->getArrayVar($this->getInstance()?->getParsedBody(), $varName);
            $isset = isset($value);
        }

        if ($allowOnlyMethod == 'GET') {
            // Short URLs variables override GET variables
            if ($this->allowShortURLs && isset($this->shortURLVariables[$varName])) {
                $value = $this->shortURLVariables[$varName];
            } else {
                // Then check in $_GET
                $value = $this->getInstance()?->getQueryVar($varName);
                if (!isset($value)) {
                    // Nothing found, return null
                    return null;
                }
            }
            //$method = $allowOnlyMethod;
        } elseif ($allowOnlyMethod == 'POST') {
            if ($isset) {
                // First check in $_POST
                // see $value above
            } else {
                // Nothing found, return null
                return null;
            }
            //$method = $allowOnlyMethod;
        } else {
            if ($this->allowShortURLs && isset($this->shortURLVariables[$varName])) {
                // Short URLs variables override GET and POST variables
                $value = $this->shortURLVariables[$varName];
                //$method = 'GET';
            } elseif ($isset) {
                // Then check in $_POST
                // see $value above
                //$method = 'POST';
            } else {
                // Then check in $_GET
                $value = $this->getInstance()?->getQueryVar($varName);
                if (!isset($value)) {
                    // Nothing found, return null
                    return null;
                }
                //$method = 'GET';
            }
        }

        //$value = xarMLS::convertFromInput($value, $method);

        //if (get_magic_quotes_gpc()) {
        //    $value = $this->stripVarSlashes($value);
        //}
        return $value;
        // --- END LEGACY METHOD BODY ---
    }

    protected function stripVarSlashes($value)
    {
        // --- LEGACY METHOD BODY ---
        $value = is_array($value) ? array_map(['self','stripVarSlashes'], $value) : stripslashes($value);
        return $value;
        // --- END LEGACY METHOD BODY ---
    }

    public function getHost(): string
    {
        // --- LEGACY METHOD BODY ---
        $server = (string) $this->getServerVar('HTTP_HOST');
        if (empty($server)) {
            // @todo default to empty string here?
            // HTTP_HOST is reliable only for HTTP 1.1
            $server = (string) $this->getServerVar('SERVER_NAME');
            $port   = (int) $this->getServerVar('SERVER_PORT');
            $protocol = $this->getProtocol();
            if (!empty($port) && !($protocol == self::PROTOCOL_HTTP && $port == 80) && !($protocol == self::PROTOCOL_HTTPS && $port == 443)) {
                $server .= ":$port";
            }
        }
        return $server;
        // --- END LEGACY METHOD BODY ---
    }

    public function getProtocol(): string
    {
        $xar = $this->getParent();
        // --- LEGACY METHOD BODY ---
        try {
            if ($xar->config()->getVar('Site.Core.EnableSecureServer')) {
                if (preg_match('/^http:/', $this->getServerVar('REQUEST_URI') ?? '')) {
                    return self::PROTOCOL_HTTP;
                }
                $serverport = $this->getServerVar('SERVER_PORT');
                $protocol = ($serverport == $xar->config()->getVar('Site.Core.SecureServerPort')) ? self::PROTOCOL_HTTPS : self::PROTOCOL_HTTP;
                return $protocol;
            }
        } catch (Exception $e) {
            return self::PROTOCOL_HTTP;
        }
        return self::PROTOCOL_HTTP;
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Get the request method (GET, POST, etc.)
     */
    public function getMethod(): string
    {
        return $this->getServerVar('REQUEST_METHOD') ?? 'GET';
    }

    /**
     * Check to see if this is a local referral
     */
    public function isLocalReferer(): bool
    {
        // --- LEGACY METHOD BODY ---
        $server  = $this->getHost();
        $referer = $this->getServerVar('HTTP_REFERER');

        if (!empty($referer) && preg_match("!^https?://$server(:\d+|)/!", $referer)) {
            return true;
        } else {
            return false;
        }
        // --- END LEGACY METHOD BODY ---
        //return xarController::isLocalReferer();
    }

    /**
     * Check if the referral comes from the same module
     */
    public function isSameReferer(): bool
    {
        // @todo this should parse referrer url according to routes etc. too - see xarRequest::getInfo()
        // --- LEGACY METHOD BODY ---
        //$referer = new xarRequest($this->getServerVar('HTTP_REFERER'));
        //$refererinfo = $referer->getInfo();
        $refererinfo = $this->getRequest()->getInfo($this->getServerVar('HTTP_REFERER'));
        $module = $this->getRequest()->getModule();
        return $module == $refererinfo[0];
        // --- END LEGACY METHOD BODY ---
        //return xarController::isRefererSameModule();
    }

    /**
     * Get current request object
     * @return xarRequest
     */
    public function getRequest(mixed $url = null): xarRequest
    {
        // --- LEGACY METHOD BODY ---
        if (empty($this->request)) {
            $this->setRequest($url);
        }
        return $this->request;
        // --- END LEGACY METHOD BODY ---
    }

    public function setRequest(mixed $url = null): void
    {
        // --- LEGACY METHOD BODY ---
        $this->request = new xarRequest($url);
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Handle multi-dimensional array lookup name[key1][key2][...]
     * @param array<mixed>|object $var
     * @return mixed
     */
    public function getArrayVar(mixed $var, string $varName): mixed
    {
        // --- LEGACY METHOD BODY ---
        if (empty($var) || !is_array($var)) {
            return null;
        }
        // 1st: $key = 'name', $rest = [ 'key1]', 'key2]', '...]' ]
        // 2nd: $key = 'key1]', $rest = [ 'key2]', '...]' ]
        // 3rd: $key = 'key2]', $rest = [ '...]' ]
        // 4th: $key = '...]', $rest = []
        $rest = explode('[', $varName . '[');
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
        return $this->getArrayVar($var[$key], implode('[', $rest));
        // --- END LEGACY METHOD BODY ---
    }
}

/**
 * Access xarServer, xarController and xarRequest methods related to the incoming request
 */
class RequestService implements RequestInterface
{
    use RequestTrait;
}
