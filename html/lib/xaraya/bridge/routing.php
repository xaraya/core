<?php
/**
 * Experiment with routing bridges for use with other dispatchers
 *
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * sys::init();
 * xarCache::init();
 * xarCore::xarInit(xarCore::SYSTEM_USER);
 *
 * // use some routing bridge
 * use Xaraya\Bridge\Routing\RoutingBridge;
 * use xarServer;
 *
 * $path = xarServer::getVar('PATH_INFO') ?? '/';
 * $method = xarServer::getVar('REQUEST_METHOD');
 *
 * // let the routing bridge handle the request itself and return the result
 * $bridge = new RoutingBridge();
 * [$result, $context] = $bridge->dispatchRequest($method, $path, '/mysite');
 * $bridge->output($result, $context);
 *
 * // or let it really do all the work here...
 * // $bridge->run('/mysite');
 */

namespace Xaraya\Bridge\Routing;

// use the FastRoute library here - see https://github.com/nikic/FastRoute
//use Xaraya\Routing\FastRouter;
// use the Symfony Routing component here - see https://github.com/symfony/routing
use Xaraya\Routing\Routing;
use Xaraya\Routing\RouterInterface;
// use some Xaraya classes
use xarServer;
use sys;
use JsonException;

sys::import('xaraya.bridge.requests.bridge');
sys::import('xaraya.bridge.requests.dataobject');
sys::import('xaraya.bridge.requests.module');
sys::import('xaraya.bridge.requests.block');
sys::import('xaraya.bridge.requests.staticfile');
sys::import('xaraya.bridge.requests.generic');
use Xaraya\Bridge\Requests\BasicBridge;
use Xaraya\Bridge\Requests\BasicRequest;
use Xaraya\Bridge\Requests\DataObjectGuiHandler;
use Xaraya\Bridge\Requests\DataObjectApiHandler;
use Xaraya\Bridge\Requests\ModuleGuiHandler;
use Xaraya\Bridge\Requests\ModuleApiHandler;
use Xaraya\Bridge\Requests\BlockGuiHandler;
use Xaraya\Bridge\Requests\BlockApiHandler;
use Xaraya\Bridge\Requests\GenericGuiHandler;
use Xaraya\Bridge\Requests\GenericApiHandler;
use Xaraya\Bridge\Requests\StaticFileHandler;
use Xaraya\Bridge\RestAPI\RestAPIHandler;
use Xaraya\Bridge\GraphQL\GraphQLHandler;

/**
 * Routing bridge to handle Xaraya object, module and block GUI calls + REST API and GraphQL API requests
 * @phpstan-import-type RouteDef from BasicBridge
 */
class RoutingBridge extends BasicBridge
{
    public const ROUTING_CACHE_FILE = 'routing_cache.php';

    //public static string $routerClass = FastRouter::class;
    protected static string $routerClass = Routing::class;
    public static string $baseUri = '';
    public static string $prefix = '';
    /** @var RouterInterface|null */
    public $router = null;
    public bool $wrapPage = false;
    protected ?RestAPIHandler $restAPIHandler = null;
    protected ?GraphQLHandler $graphQLHandler = null;
    protected string $handlerClass = 'generic';

    /**
     * Summary of getRouter
     * @param ?array<mixed> $routes with pre-defined routes (optional)
     * @param string $cacheFile for pre-defined routes (optional)
     * @return RouterInterface
     */
    public function getRouter($routes = null, $cacheFile = '')
    {
        if (!empty($routes)) {
            // create router with pre-defined routes - see combined RoutingHandler::getRouter()
            $this->router = new (static::$routerClass)(function () use ($routes) {
                return $routes;
            }, $cacheFile);
            return $this->router;
        }
        if (isset($this->router)) {
            return $this->router;
        }
        $cacheKey = $cacheFile ?: sys::varpath() . '/cache/' . static::ROUTING_CACHE_FILE;
        $this->router = new (static::$routerClass)(static::getRoutes(...), $cacheKey);
        return $this->router;
    }

    /**
     * Summary of setRouter
     * @param ?RouterInterface $router
     * @return ?RouterInterface
     */
    public function setRouter($router)
    {
        $this->router = $router;
        return $this->router;
    }

    /**
     * Summary of getRoutes
     * @param string $pathPrefix
     * @param string $namePrefix
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = ''): array
    {
        $routes = [];
        $extra = [];

        // @todo move away from static methods for context
        $path = $pathPrefix . '/restapi';
        $name = $namePrefix;
        $restHandler = null;
        $routes = array_replace($routes, RestAPIHandler::getRoutes($path, $name, $restHandler));

        $path = $pathPrefix . '/graphql';
        $name = $namePrefix . 'graphql';
        $routes[$name] = [['GET', 'POST'], $path, [GraphQLHandler::class, 'handleRequest'], $extra];

        //$handler = static::class;
        $handler = null;

        $routes = array_merge($routes, DataObjectGuiHandler::getRoutes($pathPrefix, $namePrefix, $handler, $extra));
        $routes = array_merge($routes, BlockGuiHandler::getRoutes($pathPrefix, $namePrefix, $handler, $extra));
        $routes = array_merge($routes, GenericGuiHandler::getRoutes($pathPrefix, $namePrefix, $handler, $extra));
        $routes = array_merge($routes, ModuleGuiHandler::getRoutes($pathPrefix, $namePrefix, $handler, $extra));

        // @todo do we want/need to add pathPrefix here too?
        $path = '*';
        $name = $namePrefix . 'cors';
        $routes[$name] = ['OPTIONS', $path, [RestAPIHandler::class, 'sendCORSOptions'], $extra];

        return $routes;
    }

    /**
     * Basic route builder for module requests e.g. in response output or templates - assuming short url format here
     * @param ?string $module
     * @param ?string $type
     * @param string|int|null $func
     * @param array<string, mixed> $extra
     * @see \Xaraya\Bridge\Requests\BasicBridgeTrait::prepareController()
     * @return string
     */
    public function buildUri(?string $module = null, ?string $type = null, string|int|null $func = null, array $extra = []): string
    {
        $prefix = static::$baseUri;
        // @todo do we want to keep this static?
        $handler = new ModuleGuiHandler();
        return $handler->buildModulePath($module, $type, $func, $extra, $prefix);
    }

    public function __construct(bool $wrapPage = false)
    {
        $this->wrapPage = $wrapPage;
    }

    /**
     * Summary of dispatchRequest
     * @param string $method
     * @param string $path
     * @param string $group
     * @param mixed $request
     * @return array<mixed>
     */
    public function dispatchRequest(string $method, string $path, string $group = '', &$request = null)
    {
        // @todo keep dispatcher static but replace $handler[0] with $this if current class?
        //$dispatcher = static::getSimpleDispatcher($group);
        $router = $this->getRouter();
        // @todo remove $group prefix from path here? - see /htmx
        if (!empty($group) && str_starts_with($path, $group . '/')) {
            $path = substr($path, strlen($group));
            self::$prefix .= $group;
        }
        [$handler, $vars] = $router->match($path, $method);
        if (empty($handler)) {
            switch ((string) $vars['status']) {
                case '404':
                    // ... 404 Not Found
                    http_response_code(404);
                    if (!empty($group)) {
                        $result = 'Nothing to see here at ' . htmlspecialchars($path) . ' with prefix ' . htmlspecialchars($group);
                        return [$result, null];
                    }
                    $result = 'Nothing to see here at ' . htmlspecialchars($path);
                    return [$result, null];

                case '405':
                    // ... 405 Method Not Allowed
                    if (!empty($vars['methods'])) {
                        header('Allow: ' . implode(', ', $vars['methods']));
                    }
                    http_response_code(405);
                    $result = 'Method ' . htmlspecialchars($method) . ' is not allowed for ' . htmlspecialchars($path);
                    return [$result, null];
            }
        }

        // ... call $handler with $vars
        [$result, $context] = $this->callHandler($handler, $vars, $request);
        return [$result, $context];
    }

    /**
     * Summary of run
     * @param string $group
     * @param mixed $request
     * @return void
     */
    public function run(string $group = '', &$request = null)
    {
        $method = $this->getMethod($request);
        $path = $this->getPathInfo($request);
        [$result, $context] = $this->dispatchRequest($method, $path, $group, $request);
        if ($this->handlerClass == RestAPIHandler::class) {
            // different processing for REST API - see rst.php
            $this->getRestApiHandler()->output($result);
        } elseif ($this->handlerClass == GraphQLHandler::class) {
            // different processing for GraphQL API - see gql.php
            $this->getGraphQLHandler()->output($result);
        } else {
            $this->output($result, $context);
        }
    }

    /**
     * Summary of output
     * @param mixed $result
     * @param mixed $context for wrapPage and mediaType - may not be set in instance here
     * @param mixed $transform
     * @return void
     */
    public function output($result, $context = null, $transform = null)
    {
        if (http_response_code() !== 200 && php_sapi_name() !== 'cli') {
            return;
        }
        if (is_string($result)) {
            if (!empty($context) && !empty($context['mediatype'])) {
                header('Content-Type: ' . $context['mediatype'] . '; charset=utf-8');
            } elseif (str_starts_with($result, '<?xml')) {
                header('Content-Type: application/xml; charset=utf-8');
            } else {
                header('Content-Type: text/html; charset=utf-8');
            }
            // transform output
            if ($transform) {
                // wrap output in page
                if ($this->wrapPage) {
                    echo $transform($this->wrapOutputInPage($result, $context));
                } else {
                    echo $transform($result);
                }
                return;
            }
            // wrap output in page
            if ($this->wrapPage) {
                echo $this->wrapOutputInPage($result, $context);
            } else {
                echo $result;
            }
        } else {
            if (!empty(xarServer::getVar('HTTP_ORIGIN'))) {
                header('Access-Control-Allow-Origin: *');
            }
            header('Content-Type: application/json; charset=utf-8');
            try {
                // @checkme GraphQL playground doesn't like JSON_NUMERIC_CHECK for introspection, e.g. default value for offset = 0 instead of "0"
                //$output = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR);
                $output = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $output = '{"JSON Exception": ' . json_encode($e->getMessage()) . '}';
            }
            echo $output;
        }
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
        // keep dispatcher static but replace $handler[0] with instance if known class
        if (is_string($handler[0])) {
            if ($handler[0] == static::class) {
                // replace with $this - see webhooks fastroute endpoint
                $handler[0] = $this;
            } elseif (is_subclass_of($handler[0], static::class)) {
                // @todo instantiate handler[0] for subclasses like RoutingApiBridge?
                $handler[0] = new $handler[0]();
            } elseif (is_subclass_of($handler[0], BasicBridge::class)) {
                // @todo instantiate handler[0] for subclasses of BasicRequest with router?
                $handler[0] = new $handler[0]($this->getRouter());
            } else {
                // leave it for someone else to take care of...
            }
            return $handler;
        }
        if (is_object($handler[0])) {
            if ($handler[0]::class == static::class) {
                // @todo replace with $this? - see DD rest handler
                $handler[0] = $this;
            } elseif (is_subclass_of($handler[0], static::class)) {
                // @todo clone handler[0] here? - see DD rest handler
                $handler[0] = clone $handler[0];
            } else {
                // leave it for someone else to take care of...
            }
            return $handler;
        }
        return $handler;
    }

    /**
     * Summary of callHandler
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
        if ($this->isRestApiHandler($handler)) {
            // different processing for REST API - see rst.php
            RestAPIHandler::$endpoint = $this->getBaseUri() . self::$prefix . '/restapi';
            return $this->callRestApiHandler($handler, $vars, $request);
        }
        if ($this->isGraphQLHandler($handler)) {
            // different processing for GraphQL API - see gql.php
            return $this->callGraphQLHandler($handler, $vars, $request);
        }
        $this->handlerClass = static::class;
        // fix handler if needed
        $handler = $this->resolveHandler($handler);
        // don't use call_user_func here anymore because $request is passed by reference
        return $handler($vars, $request);
    }

    // different processing for REST API - see rst.php
    /**
     * Summary of isRestApiHandler
     * @param mixed $handler
     * @return bool
     */
    public function isRestApiHandler($handler): bool
    {
        if (!is_array($handler)) {
            return false;
        }
        // handler is (sub-class of) RestAPIHandler
        if (is_a($handler[0], RestAPIHandler::class, true)) {
            return true;
        }
        return false;
    }

    /**
     * Summary of getRestHandler
     * @return RestAPIHandler
     */
    public function getRestApiHandler()
    {
        sys::import('xaraya.bridge.restapi.handler');
        $this->restAPIHandler ??= new RestAPIHandler();
        return $this->restAPIHandler;
    }

    /**
     * Summary of callRestApiHandler
     * @param mixed $handler
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return mixed
     */
    public function callRestApiHandler($handler, $vars, &$request = null)
    {
        if (empty($vars)) {
            $vars = [];
        }
        $this->handlerClass = RestAPIHandler::class;
        [$result, $context] = $this->getRestApiHandler()->callHandler($handler, $vars, $request);
        if ($handler[1] === 'getOpenAPI') {
            // @todo move to output?
            //header('Access-Control-Allow-Origin: *');
            // @checkme set server url to current path here
            //$result['servers'][0]['url'] = RestAPIHandler::getBaseURL();
            $result['servers'][0]['url'] = xarServer::getProtocol() . '://' . xarServer::getHost() . RestAPIHandler::$endpoint;
        }
        return [$result, $context];
    }

    // different processing for GraphQL API - see gql.php
    /**
     * Summary of isGraphQLHandler
     * @param mixed $handler
     * @return bool
     */
    public function isGraphQLHandler($handler): bool
    {
        if (!is_array($handler)) {
            return false;
        }
        // handler is (sub-class of) GraphQLHandler
        if (is_a($handler[0], GraphQLHandler::class, true)) {
            return true;
        }
        return false;
    }

    /**
     * Summary of getGraphQLHandler
     * @return GraphQLHandler
     */
    public function getGraphQLHandler()
    {
        sys::import('xaraya.bridge.graphql.handler');
        $this->graphQLHandler ??= new GraphQLHandler();
        return $this->graphQLHandler;
    }

    /**
     * Summary of callGraphQLHandler
     * @param mixed $handler
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return mixed
     */
    public function callGraphQLHandler($handler, $vars, &$request = null)
    {
        if (empty($vars)) {
            $vars = [];
        }
        $this->handlerClass = GraphQLHandler::class;
        [$result, $context] = $this->getGraphQLHandler()->handleRequest($vars, $request);
        return [$result, $context];
    }
}

/**
 * Same as RoutingBridge but runs API calls instead of GUI calls
 *
 * Note: if you really want to use APIs for DataObject please have a look at the REST API or GraphQL API instead
 * They can be configured via the admin Back End > Dynamic Data > Utilities > Test APIs
 * @phpstan-import-type RouteDef from BasicBridge
 */
class RoutingApiBridge extends RoutingBridge
{
    public const ROUTING_CACHE_FILE = 'routing_api_cache.php';

    /**
     * Summary of getRoutes
     * @param string $pathPrefix
     * @param string $namePrefix
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '/api', string $namePrefix = 'api-'): array
    {
        $routes = [];
        $extra = [];

        //$handler = static::class;
        $handler = null;

        $routes = array_merge($routes, DataObjectApiHandler::getRoutes($pathPrefix, $namePrefix, $handler, $extra));
        $routes = array_merge($routes, BlockApiHandler::getRoutes($pathPrefix, $namePrefix, $handler, $extra));
        $routes = array_merge($routes, GenericApiHandler::getRoutes($pathPrefix, $namePrefix, $handler, $extra));
        $routes = array_merge($routes, ModuleApiHandler::getRoutes($pathPrefix, $namePrefix, $handler, $extra));

        return $routes;
    }
}

/**
 * Same as RoutingBridge but handles static files too
 *
 * Note: static files should really be handled by a web server or reverse proxy in front of the application
 * @phpstan-import-type RouteDef from BasicBridge
 */
class RoutingStaticBridge extends RoutingBridge
{
    public const ROUTING_CACHE_FILE = 'routing_static_cache.php';

    /**
     * Summary of getRoutes
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param string $staticFiles use this as group e.g. everything under /static
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = 'static-', string $staticFiles = ''): array
    {
        $routes = [];
        $extra = [];

        //$handler = static::class;
        $handler = null;

        // @checkme use this as group e.g. everything under /static
        $path = $pathPrefix . $staticFiles;
        $routes = array_merge($routes, StaticFileHandler::getRoutes($path, $namePrefix, $handler, $extra));

        // add parent route collection = RoutingBridge::getRoutes()
        // @todo strip one level of prefix and pass along here?
        $routes = array_replace($routes, parent::getRoutes($pathPrefix));

        return $routes;
    }
}
