<?php

/**
 * @package core\bridge
 * @subpackage restapi
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Bridge\RestAPI;

use Xaraya\Caching\CacheInterface;
use Xaraya\Caching\CacheTrait;
use Xaraya\Context\RequestContext;
use Xaraya\Services\WithServicesClass;
use Xaraya\Tools\TimerInterface;
use Xaraya\Tools\TimerTrait;
use Xaraya\Bridge\Requests\CommonRequestInterface;
use Xaraya\Bridge\Requests\CommonRequestTrait;
use Xaraya\Context\ContextFactory;
use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use Xaraya\Context\Context;
use Xaraya\Authentication\AuthToken;
use Xaraya\Services\xar;
use xarObject;
use sys;
use ForbiddenOperationException;
use UnauthorizedOperationException;
use JsonException;

/**
 * Class to handle REST API calls
 */
class RestAPIHandler extends xarObject implements CommonRequestInterface, ContextInterface, CacheInterface, TimerInterface
{
    use CommonRequestTrait;
    use ContextTrait;
    use TimerTrait;  // activate with self::enableTimer(true)
    use CacheTrait;  // activate with self::enableCache(true)
    use WithServicesClass;

    public static string $endpoint = 'rst.php/v1';
    /** @var array<string, mixed> */
    public static $schemas = [];
    /** @var array<string, mixed> */
    public static $config = [];

    public function __construct()
    {
        // @todo use request context for query params etc.
        //xar::req()->setRequestClass(RequestContext::class);
    }

    /**
     * Summary of getOpenAPI
     * @param array<string, mixed> $vars
     * @return mixed
     */
    public function getOpenAPI($vars = [])
    {
        $openapi = sys::varpath() . '/cache/api/openapi.json';
        if (!file_exists($openapi)) {
            xar::db()->init();
            RestAPIBuilder::init();
            return ['TODO' => 'generate var/cache/api/openapi.json with builder'];
        }
        $content = file_get_contents($openapi);
        $doc = json_decode($content, true);
        // @checkme set server url to current path here
        //$doc['servers'][0]['url'] = $this->getBaseURL();
        return $doc;
    }

    /**
     * Summary of getBaseURL
     * @param string $base
     * @param ?string $path
     * @param array<string, mixed> $args not used here
     * @return string
     */
    public function getBaseURL($base = '', $path = null, $args = [])
    {
        $ctl = $this->getServicesClass()->ctl();
        if (empty($path)) {
            return $ctl->getBaseURL() . self::$endpoint . $base;
        }
        return $ctl->getBaseURL() . self::$endpoint . $base . '/' . $path;
    }

    /**
     * Summary of loadConfig
     * @return void
     */
    public function loadConfig()
    {
        if (!empty(self::$config)) {
            return;
        }
        self::$config = [];
        $configFile = sys::varpath() . '/cache/api/restapi_config.json';
        if (file_exists($configFile)) {
            $contents = file_get_contents($configFile);
            self::$config = json_decode($contents, true);
        }
        /**
        if (!empty(self::$config['storage'])) {
            AuthToken::$storageType = self::$config['storage'];
        }
        if (!empty(self::$config['expires'])) {
            AuthToken::$tokenExpires = intval(self::$config['expires']);
        }
         */
        // use xarTimerTrait
        if (isset(self::$config['timer'])) {
            self::enableTimer(!empty(self::$config['timer']) ? true : false);
        }
        // use xarCacheTrait
        if (isset(self::$config['cache'])) {
            self::enableCache(!empty(self::$config['cache']) ? true : false);
        }
        if (self::enableCache()) {
            $cacheScope = 'RestAPI.Operation';
            $this->setCacheScope($cacheScope);
        }
        $this->setTimer('config');
    }

    /**
     * Summary of loadSchemas
     * @return mixed
     */
    public function loadSchemas()
    {
        if (empty(self::$schemas)) {
            $doc = $this->getOpenAPI();
            if (empty($doc['components']) || empty($doc['components']['schemas'])) {
                return $doc;
            }
            self::$schemas = $doc['components']['schemas'];
        }
    }

    /**
     * Verify that the token or cookie corresponds to an authorized user (with minimal core load) or exit with 401 status code
     * @throws \UnauthorizedOperationException
     * @return int
     */
    protected function checkUser()
    {
        $context = $this->getContext();
        $userId = $context->getUserId();
        // return the userId if we have one
        if (!empty($userId)) {
            return $userId;
        }
        // check if we can still send headers
        if (headers_sent()) {
            throw new UnauthorizedOperationException();
        }
        // check if we had an auth token before
        $token = AuthToken::getAuthToken($context);
        if (!empty($token)) {
            //header('WWW-Authenticate: Bearer realm="Xaraya Site Login"');
            header('WWW-Authenticate: Token realm="Xaraya Site Login", created=');
        } else {
            header('WWW-Authenticate: Cookie realm="Xaraya Site Login", cookie-name=XARAYASID');
        }
        throw new UnauthorizedOperationException();
    }

    /**
     * Get REST API routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param mixed $restHandler
     * @return array<string, array<mixed>> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes($pathPrefix = '/v1', $namePrefix = 'restapi-', $restHandler = null)
    {
        //$restHandler ??= static::class;
        return RestAPIRoutes::getRoutes($pathPrefix, $namePrefix, $restHandler);
    }

    /**
     * Summary of setRequestContext
     * @param mixed $request
     * @return Context<string, mixed>
     */
    public function setRequestContext(&$request = null)
    {
        // $request from RoutingBridge overrides any existing context here
        if (isset($request)) {
            $context = ContextFactory::fromRequest($request, __METHOD__);
            // Set context for core services here first
            xar::setServicesContext($context);
        } elseif (empty($this->getContext())) {
            $context = ContextFactory::fromGlobals(__METHOD__);
            // Set context for core services here first
            xar::setServicesContext($context);
        } else {
            $context = $this->getContext();
            // Assume context for core services is already set here
        }
        // Initialize server - not really needed since xar::req()->getInstance() is on demand
        //xar::req()->init([], $context);
        return $context;
    }

    /**
     * Summary of callHandler - different processing for REST API - see rst.php
     * @param mixed $handler
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return mixed
     */
    public function callHandler($handler, $vars, &$request = null)
    {
        if (empty($vars)) {
            $vars = [];
        }
        // set context for this request first - see GraphQL
        $context = $this->setRequestContext($request);
        $context['mediatype'] = '';
        // @todo check if we already have a context? (via request or from elsewhere)
        $this->setContext($context);
        $params = [];
        $params['path'] = $vars;
        $params['query'] = $this->getQueryParams($request);
        // handle php://input for POST etc.
        try {
            $params['input'] = $this->getJsonBody($request);
        } catch (JsonException $e) {
            $result = ["JSON Input Exception" => $e->getMessage()];
            return [$result, null];
        }
        // $this->setTimer('parse');
        [$result, $context] = $this->getResult($handler, $params, $request);
        /**
        if ($handler[1] === 'getOpenAPI') {
            header('Access-Control-Allow-Origin: *');
        }
         */
        return [$result, $context];
    }

    /**
     * Summary of getQueryId
     * @param string $method
     * @param array<string, mixed> $vars
     * @return string
     */
    public function getQueryId($method, $vars)
    {
        $queryId = $method;
        if (!empty($vars['path'])) {
            if (!empty($vars['path']['object'])) {
                $queryId .= '-' . $vars['path']['object'];
                if (!empty($vars['path']['itemid'])) {
                    $queryId .= '-' . $vars['path']['itemid'];
                }
            }
            if (!empty($vars['path']['module'])) {
                $queryId .= '-' . $vars['path']['module'];
                if (!empty($vars['path']['path'])) {
                    $queryId .= '-' . $vars['path']['path'];
                }
            }
        }
        // @checkme do we want to make this user-dependent?
        $queryId .= '-' . md5(json_encode($vars));
        return $queryId;
    }

    /**
     * Handle request and get result
     * @param mixed $handler
     * @param array<string, mixed> $params
     * @param mixed $request
     * @uses xar::cache()->init()
     * @uses xar::db()->init()
     * @throws \UnauthorizedOperationException
     * @throws \ForbiddenOperationException
     * @return mixed
     */
    public function getResult($handler, $params, &$request = null)
    {
        // initialize caching - delay until we need results
        xar::cache()->init();
        $this->loadConfig();
        $tryCachedResult = false;
        // this expects a class name or instance in $handler[0]
        if (is_array($handler) && is_string($handler[0]) && is_a($handler[0], RestAPIHandler::class, true) && str_starts_with($handler[1], "get")) {
            $tryCachedResult = true;
        }
        if ($tryCachedResult && self::enableCache()) {
            $queryId = $this->getQueryId($handler[1], $params);
            $cacheKey = $this->getCacheKey($queryId);
            // @checkme we need to initialize the database here too if variable caching uses database instead of apcu
            if (!empty($cacheKey) && $this->isCached($cacheKey)) {
                $result = $this->getCached($cacheKey);
                if (is_array($result)) {
                    // $result['x-cached'] = true;
                    $result['x-cached'] = $this->keyCached($cacheKey);
                } else {
                    $keyInfo = $this->keyCached($cacheKey);
                    if (!empty($keyInfo) && is_array($keyInfo) && !headers_sent()) {
                        header('X-Cache-Key: ' . $keyInfo['key']);
                        header('X-Cache-Code: ' . $keyInfo['code']);
                        header('X-Cache-Time: ' . $keyInfo['time']);
                        if (isset($keyInfo['hits'])) {
                            header('X-Cache-Hits: ' . $keyInfo['hits']);
                        }
                    }
                    // header('X-Cache-Hit: true');
                }
                $this->setTimer('cached');
                return $result;
            }
        }
        // initialize database - delay until caching fails
        xar::db()->init();
        // initialize modules
        //xar::mod()->init();
        // initialize users
        //xar::user()->init();
        $this->setTimer('handle');
        // get handler instance with context
        $handler = $this->resolveHandler($handler);
        try {
            // no longer pass $context to method call here, since we use instance now
            $result = call_user_func($handler, $params);
        } catch (UnauthorizedOperationException) {
            $this->setTimer('unauthorized');
            throw new UnauthorizedOperationException();
        } catch (ForbiddenOperationException) {
            $this->setTimer('forbidden');
            throw new ForbiddenOperationException();
            //} catch (Throwable $e) {
            //    $this->setTimer('exception');
            //    $result = "Exception: " . $e->getMessage();
            //    if ($e->getPrevious() !== null) {
            //        $result .= "\nPrevious: " . $e->getPrevious()->getMessage();
            //    }
            //    $result .= "\nTrace:\n" . $e->getTraceAsString();
            //    return $result;
        }
        // if (is_array($result)) {
        //     $result['x-debug'] = ['handler' => $handler, 'params' => $params];
        // }
        if ($tryCachedResult && $this->hasCacheKey()) {
            $cacheKey = $this->getCacheKey();
            $this->setCached($cacheKey, $result);
        }
        $this->setTimer('result');
        return [$result, $this->getContext()];
    }

    /**
     * Summary of resolveHandler
     * @param mixed $handler
     * @return mixed
     */
    public function resolveHandler($handler)
    {
        if (!is_array($handler)) {
            // @todo handle first class callable syntax $this->method(...)
            return $handler;
        }
        if (is_object($handler[0])) {
            // clone handler instance here - @todo do we need this?
            $routeInstance = $handler[0];
            $routeMethod = $handler[1];
            $callInstance = clone $routeInstance;
        } else {
            // create handler instance for this class name
            $routeClassName = $handler[0];
            $routeMethod = $handler[1];
            $callInstance = new $routeClassName();
        }
        // set the context in the handler instance
        $callInstance->setContext($this->getContext());
        $handler = [$callInstance, $routeMethod];
        return $handler;
    }

    /**
     * Send Content-Type and JSON result to the browser
     * @param mixed $result
     * @param mixed $status
     * @return void
     */
    public function output($result, $status = 200)
    {
        if (!isset($result) && php_sapi_name() !== 'cli') {
            return;
        }
        $context = $this->getContext();
        if (is_array($result) && self::enableTimer()) {
            $result['x-times'] = $this->getTimers();
        }
        if (!headers_sent() && $status !== 200) {
            http_response_code($status);
        }
        $req = $this->getServicesClass()->req();
        if (!empty($req->getServerVar('HTTP_ORIGIN'))) {
            header('Access-Control-Allow-Origin: *');
        }
        if (is_string($result)) {
            if (!empty($context) && !empty($context['mediatype'])) {
                header('Content-Type: ' . $context['mediatype'] . '; charset=utf-8');
            } elseif (str_starts_with($result, '<?xml')) {
                header('Content-Type: application/xml; charset=utf-8');
            } else {
                header('Content-Type: text/html; charset=utf-8');
            }
            echo $result;
            return;
        }
        header('Content-Type: application/json; charset=utf-8');
        //echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PARTIAL_OUTPUT_ON_ERROR);
        try {
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo '{"JSON Exception": ' . json_encode($e->getMessage()) . '}';
        }
    }

    /**
     * Send CORS options to the browser in preflight checks
     * @param mixed $vars
     * @param mixed $context
     * @return void
     */
    public static function sendCORSOptions($vars = [], $context = null)
    {
        // See https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
        http_response_code(204);
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        // @checkme X-Apollo-Tracing is used in the GraphQL Playground
        header('Access-Control-Allow-Headers: X-Auth-Token, Content-Type, X-Apollo-Tracing');
        // header('Access-Control-Allow-Credentials: true');
    }
}
