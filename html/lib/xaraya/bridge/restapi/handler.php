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
use Xaraya\Tools\TimerInterface;
use Xaraya\Tools\TimerTrait;
use Xaraya\Bridge\Requests\CommonRequestInterface;
use Xaraya\Bridge\Requests\CommonRequestTrait;
use Xaraya\Context\ContextFactory;
use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use Xaraya\Context\Context;
use Xaraya\Authentication\AuthToken;
use xarObject;
use xarCache;
use xarDatabase;
use xarServer;
use sys;
use ForbiddenOperationException;
use UnauthorizedOperationException;
use JsonException;

sys::import('modules.dynamicdata.class.objects.factory');
sys::import('xaraya.tools.timertrait');
sys::import('xaraya.caching.cachetrait');
sys::import('xaraya.bridge.requests.requesttrait');
sys::import('xaraya.context.contexttrait');
sys::import('modules.authsystem.class.authtoken');

/**
 * Class to handle REST API calls
 * @uses \sys::autoload()
 */
class RestAPIHandler extends xarObject implements CommonRequestInterface, ContextInterface, CacheInterface, TimerInterface
{
    use CommonRequestTrait;
    use ContextTrait;
    use TimerTrait;  // activate with self::enableTimer(true)
    use CacheTrait;  // activate with self::enableCache(true)

    public static string $endpoint = 'rst.php/v1';
    /** @var array<string, mixed> */
    public static $schemas = [];
    /** @var array<string, mixed> */
    public static $config = [];

    /**
     * Summary of getOpenAPI
     * @param array<string, mixed> $vars
     * @param mixed $context
     * @return mixed
     */
    public function getOpenAPI($vars = [], $context = null)
    {
        $openapi = sys::varpath() . '/cache/api/openapi.json';
        if (!file_exists($openapi)) {
            xarDatabase::init();
            sys::import('xaraya.bridge.restapi.builder');
            RestAPIBuilder::init();
            return ['TODO' => 'generate var/cache/api/openapi.json with builder'];
        }
        $content = file_get_contents($openapi);
        $doc = json_decode($content, true);
        return $doc;
    }

    /**
     * Summary of getBaseURL
     * @param string $base
     * @param ?string $path
     * @param array<string, mixed> $args
     * @return string
     */
    public function getBaseURL($base = '', $path = null, $args = [])
    {
        if (empty($path)) {
            return xarServer::getBaseURL() . self::$endpoint . $base;
        }
        return xarServer::getBaseURL() . self::$endpoint . $base . '/' . $path;
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
     * @param Context<string, mixed> $context
     * @throws \UnauthorizedOperationException
     * @return int
     */
    protected function checkUser($context)
    {
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
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes($pathPrefix = '/v1', $namePrefix = 'restapi-', $restHandler = null)
    {
        //$restHandler ??= static::class;
        return RestAPIRoutes::getRoutes($pathPrefix, $namePrefix, $restHandler);
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
            // @checkme set server url to current path here
            //$result['servers'][0]['url'] = $this->getBaseURL();
            $result['servers'][0]['url'] = xarServer::getProtocol() . '://' . xarServer::getHost() . self::$endpoint;
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
     * @uses xarCache::init()
     * @uses xarDatabase::init()
     * @throws \UnauthorizedOperationException
     * @throws \ForbiddenOperationException
     * @return mixed
     */
    public function getResult($handler, $params, &$request = null)
    {
        // initialize caching - delay until we need results
        xarCache::init();
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
        xarDatabase::init();
        // initialize modules
        //xarMod::init();
        // initialize users
        //xarUser::init();
        $this->setTimer('handle');
        // define context of the request - see GraphQL
        $context = ContextFactory::fromRequest($request, __METHOD__);
        $context['mediatype'] = '';
        // get handler instance with context
        $handler = $this->getHandler($handler, $context);
        try {
            $result = call_user_func($handler, $params, $context);
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
        return [$result, $context];
    }

    /**
     * Summary of getHandler
     * @param mixed $handler
     * @param mixed $context
     * @return mixed
     */
    public function getHandler($handler, &$context)
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
        $callInstance->setContext($context);
        $handler = [$callInstance, $routeMethod];
        return $handler;
    }

    /**
     * Send Content-Type and JSON result to the browser
     * @param mixed $result
     * @param mixed $status
     * @param mixed $context
     * @return void
     */
    public function output($result, $status = 200, $context = null)
    {
        if (!isset($result) && php_sapi_name() !== 'cli') {
            return;
        }
        if (is_array($result) && self::enableTimer()) {
            $result['x-times'] = $this->getTimers();
        }
        if (!headers_sent() && $status !== 200) {
            http_response_code($status);
        }
        if (!empty(xarServer::getVar('HTTP_ORIGIN'))) {
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
