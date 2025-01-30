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
 * // get a simple router to work with yourself, possibly in a group
 * // $router = RoutingBridge::getSimpleRouter('/mysite');
 * // [$handler, $params] = $router->match($path, $method);
 * // ... adapt handler and call with params ...
 *
 * // or let the routing bridge handle the request itself and return the result
 * $bridge = new RoutingBridge();
 * [$result, $context] = $bridge->dispatchRequest($method, $path, '/mysite');
 * $bridge->output($result, $context);
 *
 * // or let it really do all the work here...
 * // $bridge->run('/mysite');
 */

namespace Xaraya\Bridge\Routing;

// use the FastRoute library here - see https://github.com/nikic/FastRoute
use Xaraya\Routing\FastRouter;
// use the Symfony Routing component here - see https://github.com/symfony/routing
use Xaraya\Routing\Routing;
use Xaraya\Routing\RouterInterface;
// use some Xaraya classes
use Xaraya\Context\ContextFactory;
use Xaraya\Context\Context;
use xarServer;
use sys;
use JsonException;

sys::import('xaraya.bridge.requests.bridge');
sys::import('xaraya.bridge.requests.dataobject');
sys::import('xaraya.bridge.requests.module');
sys::import('xaraya.bridge.requests.block');
sys::import('xaraya.bridge.requests.staticfile');
use Xaraya\Bridge\Requests\BasicBridge;
use Xaraya\Bridge\Requests\DataObjectRequest;
use Xaraya\Bridge\Requests\ModuleRequest;
use Xaraya\Bridge\Requests\BlockRequest;
use Xaraya\Bridge\Requests\StaticFileRequest;
use DataObjectRESTHandler;
use xarGraphQL;

/**
 * Routing bridge to handle Xaraya object, module and block GUI calls + REST API and GraphQL API requests
 * @phpstan-type ExtraParameters array<string, string|int|bool|float>
 */
class RoutingBridge extends BasicBridge
{
    public const ROUTING_CACHE_FILE = 'fastroute_cache.php';

    public static string $routerClass = FastRouter::class;
    //protected static string $routerClass = Routing::class;
    /** @var RouterInterface|null */
    public static $router = null;
    public static string $baseUri = '';
    public static string $prefix = '';
    public bool $wrapPage = false;
    protected ?DataObjectRESTHandler $restAPIHandler = null;
    protected ?xarGraphQL $graphQLHandler = null;

    /**
     * Summary of getRouter
     * @param ?array<mixed> $routes with pre-defined routes (optional)
     * @return RouterInterface
     */
    public static function getRouter($routes = null)
    {
        if (!empty($routes)) {
            // create router with pre-defined routes - see combined FastRouteHandler::getRouter()
            static::$router = new (static::$routerClass)(function () use ($routes) {
                return $routes;
            });
            return static::$router;
        }
        if (isset(static::$router)) {
            return static::$router;
        }
        $cacheKey = sys::varpath() . '/cache/api/' . static::ROUTING_CACHE_FILE;
        static::$router = new (static::$routerClass)(static::getRoutes(...), $cacheKey);
        return static::$router;
    }

    /**
     * Summary of setRouter
     * @param RouterInterface $router
     * @return RouterInterface
     */
    public static function setRouter($router)
    {
        static::$router = $router;
        return static::$router;
    }

    /**
     * Summary of getRoutes
     * @param string $pathPrefix
     * @param string $namePrefix
     * @return array<mixed>
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = '')
    {
        $routes = [];
        $extra = [];

        $path = $pathPrefix . '/object/{object}';
        $name = $namePrefix . 'object';
        $routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleObjectRequest'], $extra];

        $path = $pathPrefix . '/object/{object}/{itemid:\d+}[/{method}]';
        $name = $namePrefix . 'object-item';
        $routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleObjectRequest'], $extra];

        $path = $pathPrefix . '/object/{object}/{itemid:[0-9a-f]{24}}[/{method}]';
        $name = $namePrefix . 'object-document';
        $routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleObjectRequest'], $extra];

        $path = $pathPrefix . '/object/{object}/{method}';
        $name = $namePrefix . 'object-method';
        $routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleObjectRequest'], $extra];

        //$path = $pathPrefix . '/object/';
        //$name = $namePrefix . 'object-root';
        //$routes[$name] = ['GET', $path, [static::class, 'handleObjectRequest']);

        $path = $pathPrefix . '/block/{instance}';
        $name = $namePrefix . 'block';
        $routes[$name] = ['GET', $path, [static::class, 'handleBlockRequest'], $extra];

        // @todo move away from static methods for context
        $path = $pathPrefix . '/restapi';
        $name = $namePrefix . 'restapi-';
        $restHandler = DataObjectRESTHandler::class;
        $routes = array_replace($routes, DataObjectRESTHandler::getRoutes($path, $name, $restHandler));

        $path = $pathPrefix . '/restapi/';
        $name = $namePrefix . 'restapi';
        $routes[$name] = ['GET', $path, [DataObjectRESTHandler::class, 'getOpenAPI'], $extra];

        $path = $pathPrefix . '/graphql';
        $name = $namePrefix . 'graphql';
        $routes[$name] = [['GET', 'POST'], $path, [xarGraphQL::class, 'handleRequest'], $extra];

        $path = $pathPrefix . '/routes';
        $name = $namePrefix . 'routes';
        $routes[$name] = ['GET', $path, [static::class, 'handleRoutesRequest'], $extra];

        $path = $pathPrefix . '/{module}';
        $name = $namePrefix . 'module';
        $routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleModuleRequest'], $extra];

        $path = $pathPrefix . '/{module}/{func}';
        $name = $namePrefix . 'module-func';
        $routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleModuleRequest'], $extra];

        $path = $pathPrefix . '/{module}/{type}/{func}';
        $name = $namePrefix . 'module-type-func';
        $routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleModuleRequest'], $extra];

        $path = $pathPrefix . '/';
        $name = $namePrefix . 'root';
        $routes[$name] = ['GET', $path, [static::class, 'handleModuleRequest'], $extra];

        // @todo do we want/need to add pathPrefix here too?
        $path = '*';
        $name = $namePrefix . 'cors';
        $routes[$name] = ['OPTIONS', $path, [DataObjectRESTHandler::class, 'sendCORSOptions'], $extra];

        return $routes;
    }

    /**
     * Summary of getSimpleRouter
     * @param string $group
     * @return RouterInterface
     */
    public static function getSimpleRouter(string $group = '')
    {
        if (isset(static::$router) && static::$prefix == $group) {
            return static::$router;
        }
        // @todo remove/add prefix in match/generate (see cache) or add to route (combo)?
        static::$prefix = $group;
        // override standard routeCollector here
        if (empty($group)) {
            return static::getRouter();
        }
        // @todo or reset router with new prefix here?
        static::$router = null;
        return static::getRouter();
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
        return ModuleRequest::buildModulePath($module, $type, $func, $extra, $prefix);
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
        $router = static::getRouter();
        // @todo remove $group prefix from path here? - see /htmx
        if (!empty($group) && str_starts_with($path, $group . '/')) {
            $path = substr($path, strlen($group));
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

        $context = null;
        // ... call $handler with $vars
        if (str_starts_with($path, $group . '/restapi/')) {
            // different processing for REST API - see rst.php
            DataObjectRESTHandler::$endpoint = $this->getBaseUri() . $group . '/restapi';
            [$result, $context] = $this->callRestApiHandler($handler, $vars, $request);
        } elseif (str_starts_with($path, $group . '/graphql')) {
            // different processing for GraphQL API - see gql.php
            [$result, $context] = $this->callHandler($handler, $vars, $request);
        } else {
            // @todo keep dispatcher static but replace $handler[0] with $this if current class?
            [$result, $context] = $this->callHandler($handler, $vars, $request);
        }
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
        if (str_starts_with($path, $group . '/restapi/')) {
            // different processing for REST API - see rst.php
            $this->getRestApiHandler()->output($result, 200, $context);
        } elseif (str_starts_with($path, $group . '/graphql')) {
            // different processing for GraphQL API - see gql.php
            $this->getGraphQLHandler()->output($result, $context);
        } else {
            $this->output($result, $context);
        }
    }

    /**
     * Summary of output
     * @param mixed $result
     * @param mixed $context
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
     * Summary of getHandler
     * @param mixed $handler
     * @return mixed
     */
    public function getHandler($handler)
    {
        if (!is_array($handler)) {
            // @todo handle first class callable syntax $this->method(...)
            return $handler;
        }
        if (is_string($handler[0])) {
            if ($handler[0] == static::class) {
                // replace with $this - see webhooks fastroute endpoint
                $handler[0] = $this;
            } elseif (is_subclass_of($handler[0], static::class)) {
                // @todo instantiate handler[0] for subclasses like RoutingApiBridge?
                $handler[0] = new $handler[0]();
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
        // fix handler if needed
        $handler = $this->getHandler($handler);
        // don't use call_user_func here anymore because $request is passed by reference
        $result = $handler($vars, $request);
        return $result;
    }

    // different processing for REST API - see rst.php
    /**
     * Summary of getRestHandler
     * @return DataObjectRESTHandler
     */
    public function getRestApiHandler()
    {
        $this->restAPIHandler ??= new DataObjectRESTHandler();
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
        [$result, $context] = $this->getRestApiHandler()->callHandler($handler, $vars, $request);
        if ($handler[1] === 'getOpenAPI') {
            header('Access-Control-Allow-Origin: *');
            // @checkme set server url to current path here
            //$result['servers'][0]['url'] = DataObjectRESTHandler::getBaseURL();
            $result['servers'][0]['url'] = xarServer::getProtocol() . '://' . xarServer::getHost() . DataObjectRESTHandler::$endpoint;
        }
        return [$result, $context];
    }

    /**
     * Summary of getGraphQLHandler
     * @return xarGraphQL
     */
    public function getGraphQLHandler()
    {
        $this->graphQLHandler ??= new xarGraphQL();
        return $this->graphQLHandler;
    }

    /**
     * Summary of handleObjectRequest
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     */
    public function handleObjectRequest($vars, &$request = null)
    {
        // if coming from module request handler, convert to object request
        if (empty($vars['object']) && $vars['module'] == 'object') {
            // path = /object/{object}
            $vars['object'] = $vars['type'] ?? '';
            if (!empty($vars['func'])) {
                if (is_numeric($vars['func'])) {
                    // path = /object/{object}/{itemid}
                    $vars['itemid'] = $vars['func'];
                } else {
                    // path = /object/{object}/{method}
                    $vars['method'] = $vars['func'];
                }
                unset($vars['func']);
            }
            unset($vars['module']);
            unset($vars['type']);
        }
        // path = /{object}[/{itemid}[/{method}]] or /{object}/{method}
        // dispatcher doesn't provide query params by default
        $query = $this->getQueryParams($request);
        // add remaining query params to path vars
        $params = array_merge($vars, $query);
        // add body params to query params
        $input = $this->getParsedBody($request);
        if (!empty($input) && is_array($input)) {
            $params = array_merge($params, $input);
        }

        // @checkme pass along buildUri() as link function to DD
        $params['linktype'] = 'other';
        $params['linkfunc'] = [static::class, 'buildDataObjectPath'];

        if ($params['object'] == 'roles_users') {
            $params['fieldlist'] = ['id', 'name', 'uname', 'state'];
        }

        $context = ContextFactory::fromRequest($request, __METHOD__);
        $context['mediatype'] = '';
        static::$baseUri = $this->getBaseUri($request) . static::$prefix;
        $context['baseuri'] = static::$baseUri;
        // set current module to 'object' for Xaraya controller - used e.g. in xarMod::getName()
        $this->prepareController('object', static::$baseUri . '/object');
        $context['module'] = 'object';

        $result = $this->runObjectRequest($params, $context);
        return [$result, $context];
    }

    /**
     * Summary of runObjectRequest
     * @param array<string, mixed> $params
     * @param ?Context<string, mixed> $context
     * @return string|null
     */
    public function runObjectRequest($params, $context = null)
    {
        return DataObjectRequest::runDataObjectGuiRequest($params, $context);
    }

    /**
     * Summary of handleModuleRequest
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     */
    public function handleModuleRequest($vars, &$request = null)
    {
        // path = /
        $vars['module'] ??= 'base';
        // path = /object[/...]
        if ($vars['module'] == 'object') {
            return $this->handleObjectRequest($vars, $request);
        }
        // path = /{module}/{func}
        if (empty($vars['type']) && !empty($vars['func'])) {
            $vars['type'] = 'user';
        } elseif (!empty($vars['type']) && empty($vars['func'])) {
            $vars['func'] = $vars['type'];
            $vars['type'] = 'user';
        }
        // path = /{module}/{type}/{func}
        // dispatcher doesn't provide query params by default
        $query = $this->getQueryParams($request);
        // filter out path vars from remaining query params here
        $params = array_diff_key($query, $vars);
        // add body params to query params (if any)
        $input = $this->getParsedBody($request);
        if (!empty($input) && is_array($input)) {
            $params = array_merge($params, $input);
        }

        $context = ContextFactory::fromRequest($request, __METHOD__);
        $context['mediatype'] = '';
        static::$baseUri = $this->getBaseUri($request) . static::$prefix;
        $context['baseuri'] = static::$baseUri;
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        $this->prepareController($vars['module'], static::$baseUri);
        $context['module'] = $vars['module'];

        $result = $this->runModuleRequest($vars, $params, $context);
        return [$result, $context];
    }

    /**
     * Summary of runModuleRequest
     * @param array<string, mixed> $vars
     * @param mixed $query
     * @param ?Context<string, mixed> $context
     * @return string|null
     */
    public function runModuleRequest($vars, $query, $context = null)
    {
        return ModuleRequest::runModuleGuiRequest($vars, $query, $context);
    }

    /**
     * Summary of handleBlockRequest
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     */
    public function handleBlockRequest($vars, &$request = null)
    {
        // @checkme limited to renderBlock() or getinfo() for now, so no query params or body params taken into account yet
        // dispatcher doesn't provide query params by default
        $query = $this->getQueryParams($request);

        $context = ContextFactory::fromRequest($request, __METHOD__);
        $context['mediatype'] = '';
        static::$baseUri = $this->getBaseUri($request) . static::$prefix;
        $context['baseuri'] = static::$baseUri;
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        $this->prepareController($vars['module'] ?? 'base', static::$baseUri);
        $context['module'] = $vars['module'] ?? 'base';

        $result = $this->runBlockRequest($vars, $query, $context);
        return [$result, $context];
    }

    /**
     * Summary of runBlockRequest
     * @param array<string, mixed> $vars
     * @param mixed $query
     * @param ?Context<string, mixed> $context
     * @return string
     */
    public function runBlockRequest($vars, $query = null, $context = null)
    {
        return BlockRequest::runBlockGuiRequest($vars, $query, $context);
    }

    /**
     * Show available routes
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     */
    public function handleRoutesRequest($vars, &$request = null)
    {
        $result = "<ul>";
        foreach (static::getRouter()->getRoutes() as $name => $route) {
            $result .= "<li>" . $name . " [" . json_encode($route, JSON_UNESCAPED_SLASHES) . "]</li>";
        }
        $result .= "</ul>";
        return [$result, null];
    }
}

/**
 * Same as RoutingBridge but runs API calls instead of GUI calls
 *
 * Note: if you really want to use APIs for DataObject please have a look at the REST API or GraphQL API instead
 * They can be configured via the admin Back End > Dynamic Data > Utilities > Test APIs
 * @phpstan-type ExtraParameters array<string, string|int|bool|float>
 */
class RoutingApiBridge extends RoutingBridge
{
    public const ROUTING_CACHE_FILE = 'fastroute_api_cache.php';

    /**
     * Summary of getRoutes
     * @param string $pathPrefix
     * @param string $namePrefix
     * @return array<mixed>
     */
    public static function getRoutes(string $pathPrefix = '/api', string $namePrefix = 'api-')
    {
        $routes = [];
        $extra = [];

        $path = $pathPrefix . '/object/{object}';
        $name = $namePrefix . 'object';
        $routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleObjectRequest'], $extra];

        $path = $pathPrefix . '/object/{object}/{itemid:\d+}[/{method}]';
        $name = $namePrefix . 'object-item';
        $routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleObjectRequest'], $extra];

        $path = $pathPrefix . '/object/{object}/{itemid:[0-9a-f]{24}}[/{method}]';
        $name = $namePrefix . 'object-document';
        $routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleObjectRequest'], $extra];

        $path = $pathPrefix . '/object/{object}/{method}';
        $name = $namePrefix . 'object-method';
        $routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleObjectRequest'], $extra];

        //$path = $pathPrefix . '/object/';
        //$name = $namePrefix . 'object-root';
        //$routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleObjectRequest'], $extra];

        $path = $pathPrefix . '/block/{instance}';
        $name = $namePrefix . 'block';
        $routes[$name] = ['GET', $path, [static::class, 'handleBlockRequest'], $extra];

        $path = $pathPrefix . '/{module}[/{type}[/{func}]]';
        $name = $namePrefix . 'module';
        $routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleModuleRequest'], $extra];

        $path = $pathPrefix . '/';
        $name = $namePrefix . 'root';
        $routes[$name] = [['GET', 'POST'], $path, [static::class, 'handleModuleRequest'], $extra];

        return $routes;
    }

    /**
     * Summary of runObjectRequest
     * @param array<string, mixed> $params
     * @param ?Context<string, mixed> $context
     * @return mixed
     */
    public function runObjectRequest($params, $context = null)
    {
        return DataObjectRequest::runDataObjectApiRequest($params, $context);
    }

    /**
     * Summary of runModuleRequest
     * @param array<string, mixed> $vars
     * @param mixed $query
     * @param ?Context<string, mixed> $context
     * @return mixed
     */
    public function runModuleRequest($vars, $query, $context = null)
    {
        return ModuleRequest::runModuleApiRequest($vars, $query, $context);
    }

    /**
     * Summary of runBlockRequest
     * @param array<string, mixed> $vars
     * @param mixed $query
     * @param ?Context<string, mixed> $context
     * @return mixed
     */
    public function runBlockRequest($vars, $query = null, $context = null)
    {
        return BlockRequest::runBlockApiRequest($vars, $query, $context);
    }
}

/**
 * Same as RoutingBridge but handles static files too
 *
 * Note: static files should really be handled by a web server or reverse proxy in front of the application
 * @phpstan-type ExtraParameters array<string, string|int|bool|float>
 */
class RoutingStaticBridge extends RoutingBridge
{
    public const ROUTING_CACHE_FILE = 'fastroute_static_cache.php';

    /**
     * Summary of getRoutes
     * @param string $pathPrefix
     * @param string $staticFiles
     * @param string $namePrefix
     * @return array<mixed>
     */
    public static function getRoutes(string $pathPrefix = '', string $staticFiles = '', string $namePrefix = 'static-')
    {
        $routes = [];

        // @checkme use this as group e.g. everything under /static
        $path = $pathPrefix . $staticFiles;
        $routes = array_replace($routes, static::addModuleFileRoutes($path, $namePrefix));
        $routes = array_replace($routes, static::addThemeFileRoutes($path, $namePrefix));
        $routes = array_replace($routes, static::addVarFileRoutes($path, $namePrefix));

        // add parent route collection = RoutingBridge::getRoutes()
        // @todo strip one level of prefix and pass along here?
        $routes = array_replace($routes, parent::getRoutes($pathPrefix));

        return $routes;
    }

    /**
     * Summary of addThemeFileRoutes
     * @param string $pathPrefix
     * @param string $namePrefix
     * @return array<mixed>
     */
    public static function addThemeFileRoutes(string $pathPrefix, string $namePrefix = '')
    {
        $routes = [];
        $extra = [];

        $path = $pathPrefix . '/themes/{source}/{folder}/{file:.+}';
        $name = $namePrefix . 'theme-file';
        $routes[$name] = ['GET', $path, [static::class, 'handleThemeFileRequest'], $extra];
        return $routes;
    }

    /**
     * Summary of addModuleFileRoutes
     * @param string $pathPrefix
     * @param string $namePrefix
     * @return array<mixed>
     */
    public static function addModuleFileRoutes(string $pathPrefix, string $namePrefix = '')
    {
        $routes = [];
        $extra = [];

        $path = $pathPrefix . '/code/modules/{source}/{folder}/{file:.+}';
        $name = $namePrefix . 'module-file';
        $routes[$name] = ['GET', $path, [static::class, 'handleModuleFileRequest'], $extra];
        return $routes;
    }

    /**
     * Summary of addVarFileRoutes
     * @param string $pathPrefix
     * @param string $namePrefix
     * @return array<mixed>
     */
    public static function addVarFileRoutes(string $pathPrefix, string $namePrefix = '')
    {
        $routes = [];
        $extra = [];

        $path = $pathPrefix . '/var/{source}/{folder}/{file:.+}';
        $name = $namePrefix . 'var-file';
        $routes[$name] = ['GET', $path, [static::class, 'handleVarFileRequest'], $extra];
        return $routes;
    }

    /**
     * Summary of handleThemeFileRequest
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     */
    public function handleThemeFileRequest($vars, &$request = null)
    {
        // path = /themes/{source}/{folder}/{file:.+}
        $path = StaticFileRequest::getThemeFileRequest($vars);
        $vars['path'] = $path;
        $vars['static'] = 'theme';
        if (file_exists($path)) {
            $vars['size'] = filesize($path);
            $vars['mtime'] = filemtime($path);
        }
        //if (!empty($request)) {
        //    $request = $request->withAttribute('mediaType', '...');
        //}
        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        return [var_export($vars, true), null];
    }

    /**
     * Summary of handleModuleFileRequest
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     */
    public function handleModuleFileRequest($vars, &$request = null)
    {
        // path = /code/modules/{source}/{folder}/{file:.+}
        $path = StaticFileRequest::getModuleFileRequest($vars);
        $vars['path'] = $path;
        $vars['static'] = 'module';
        if (file_exists($path)) {
            $vars['size'] = filesize($path);
            $vars['mtime'] = filemtime($path);
        }
        //if (!empty($request)) {
        //    $request = $request->withAttribute('mediaType', '...');
        //}
        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        return [var_export($vars, true), null];
    }

    /**
     * Summary of handleVarFileRequest
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     */
    public function handleVarFileRequest($vars, &$request = null)
    {
        // path = /var/{source}/{folder}/{file:.+}
        $path = StaticFileRequest::getVarFileRequest($vars);
        $vars['path'] = $path;
        $vars['static'] = 'var';
        if (file_exists($path)) {
            $vars['size'] = filesize($path);
            $vars['mtime'] = filemtime($path);
        }
        //if (!empty($request)) {
        //    $request = $request->withAttribute('mediaType', '...');
        //}
        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        return [var_export($vars, true), null];
    }
}

/**
 * Summary of FastRouteBuildTest
 */
class FastRouteBuildTest
{
    /**
     * Summary of getObjectRoute
     * @param array<string, mixed> $params
     * @return string
     */
    public static function getObjectRoute($params)
    {
        static $routes;
        if (empty($routes)) {
            $routes = static::getRoutes('handleObjectRequest');
        }
        $attributes = ['object', 'method', 'itemid'];
        $allowed = array_flip($attributes);
        $vars = array_intersect_key($params, $allowed);
        return static::matchRoutes($routes, $vars);
    }

    /**
     * Summary of getModuleRoute
     * @param array<string, mixed> $params
     * @return string
     */
    public static function getModuleRoute($params)
    {
        static $routes;
        if (empty($routes)) {
            $routes = static::getRoutes('handleModuleRequest');
        }
        $attributes = ['module', 'type', 'func'];
        $allowed = array_flip($attributes);
        $vars = array_intersect_key($params, $allowed);
        if (!empty($vars['func']) && empty($vars['type'])) {
            $vars['type'] = 'user';
        }
        return static::matchRoutes($routes, $vars);
    }

    /**
     * Summary of getBlockRoute
     * @param array<string, mixed> $params
     * @return string
     */
    public static function getBlockRoute($params)
    {
        static $routes;
        if (empty($routes)) {
            $routes = static::getRoutes('handleBlockRequest');
        }
        $attributes = ['instance'];
        $allowed = array_flip($attributes);
        $vars = array_intersect_key($params, $allowed);
        return static::matchRoutes($routes, $vars);
    }

    /**
     * Summary of matchRoutes
     * @param array<mixed> $routes
     * @param array<string, mixed> $vars
     * @return string
     */
    public static function matchRoutes($routes, $vars)
    {
        $vars = array_filter($vars);
        $variables = array_keys($vars);
        sort($variables);
        $replace = [];
        foreach ($vars as $key => $value) {
            $replace['{' . $key . '}'] = $value;
        }
        foreach ($routes as $info) {
            // [$path, $method, $handler, $variables] = $info;
            sort($info[3]);
            if ($variables === $info[3]) {
                return strtr($info[0], $replace);
            }
        }
        return '';
    }

    /**
     * Get available routes, optionally by handler method and/or handler class
     * @return array<mixed>
     */
    public static function getRoutes(?string $handlerMethod = null, ?string $handlerClass = null)
    {
        $router = RoutingBridge::getRouter();
        $routes = [];
        foreach ($router->getRoutes() as $name => $route) {
            // add extra options if needed
            $route[] = [];
            /** @var array<string, string|int|bool|float> $options */
            [$methods, $path, $handler, $options] = $route;
            if (!is_array($handler) || count($handler) < 2) {
                continue;
            }
            [$class, $method] = $handler;
            if (!empty($handlerMethod) && $method !== $handlerMethod) {
                continue;
            }
            if (!empty($handlerClass) && $class !== $handlerClass) {
                continue;
            }
            // @todo parse variables from path (again)?
            $variables = [];
            $routes[] = [$path, $methods, $handler, $variables];
            // @checkme re-using routeParser here - why not call it the first time?
            //$routeDatas = (array) $parser->parse($route);
        }
        return $routes;
    }
}
