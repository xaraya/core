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
 * use Xaraya\Bridge\Routing\FastRouteBridge;
 * use xarServer;
 *
 * // add route collection to your own dispatcher
 * // $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
 * //     // ...
 * //     // FastRouteBridge::addRouteCollection($r);
 * //     $r->addGroup('/mysite', function (FastRoute\RouteCollector $r) {
 * //         FastRouteBridge::addRouteCollection($r);
 * //     });
 * // });
 * // $routeInfo = $dispatcher->dispatch(xarServer::getVar('REQUEST_METHOD'), xarServer::getVar('PATH_INFO') ?? '/');
 * // if ($routeInfo[0] == FastRoute\Dispatcher::FOUND) {
 * //     $handler = $routeInfo[1];
 * //     $vars = $routeInfo[2];
 * //     // ... call $handler with $vars
 * // }
 *
 * // or get a route dispatcher to work with yourself, possibly in a group
 * // $dispatcher = FastRouteBridge::getSimpleDispatcher('/mysite');
 * // $routeInfo = $dispatcher->dispatch(xarServer::getVar('REQUEST_METHOD'), xarServer::getVar('PATH_INFO') ?? '/');
 *
 * // or let the route dispatcher handle the request itself and return the result
 * $bridge = new FastRouteBridge();
 * [$result, $context] = $bridge->dispatchRequest(xarServer::getVar('REQUEST_METHOD'), xarServer::getVar('PATH_INFO') ?? '/', '/mysite');
 * $bridge->output($result, $context);
 *
 * // or let it really do all the work here...
 * // $bridge->run('/mysite');
 */

namespace Xaraya\Bridge\Routing;

// use the FastRoute library here - see https://github.com/nikic/FastRoute
use FastRoute\ConfigureRoutes;
use FastRoute\Dispatcher;
use FastRoute\FastRoute;
use FastRoute\GenerateUri;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
// use some Xaraya classes
use Xaraya\Context\ContextFactory;
use Xaraya\Context\Context;
use xarServer;
use sys;
use Exception;
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

// @todo use FastRoute::recommendedSettings() in v2.x
use function FastRoute\simpleDispatcher;

/**
 * Keep track of collected routes - see https://github.com/nikic/FastRoute/blob/master/src/RouteCollector.php
 * @todo RouteCollector will become @final in v2.x - call processedRoutes() instead?
 * @deprecated 2.0.0 not available with new API
 */
class TrackRouteCollector extends RouteCollector
{
    /** @var array<mixed> */
    public static array $trackRoutes = [];
    public static string $groupStarted = 'GROUP STARTED';
    public static string $groupStopped = 'GROUP STOPPED';
    //protected string $currentGroupPrefix = '';

    public function addRoute($httpMethod, string $route, mixed $handler, array $extraParameters = []): void
    {
        static::$trackRoutes[] = [$this->currentGroupPrefix . $route, $httpMethod, $handler];
        //$route = $this->currentGroupPrefix . $route;
        //$routeDatas = $this->routeParser->parse($route);
        parent::addRoute($httpMethod, $route, $handler, $extraParameters);
    }

    public function addGroup(string $prefix, callable $callback): void
    {
        static::$trackRoutes[] = [$this->currentGroupPrefix . $prefix, static::$groupStarted, null];
        parent::addGroup($prefix, $callback);
        static::$trackRoutes[] = [$this->currentGroupPrefix . $prefix, static::$groupStopped, null];
    }

    /**
     * @return array<mixed>
     */
    public static function getRoutes(): array
    {
        return static::$trackRoutes;
    }
}

/**
 * FastRoute bridge to handle Xaraya object, module and block GUI calls + REST API and GraphQL API requests
 */
class FastRouteBridge extends BasicBridge
{
    /** @var Dispatcher */
    public static $dispatcher;
    public static string $baseUri = '';
    public static string $prefix = '';
    public bool $wrapPage = false;

    /**
     * Summary of addRouteCollection
     * @param RouteCollector $r
     * @return void
     */
    public static function addRouteCollection(RouteCollector $r)
    {
        // @todo move away from static methods for context
        $restHandler = DataObjectRESTHandler::class;
        $r->addGroup('/object', function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/{object}', [static::class, 'handleObjectRequest']);
            $r->addRoute(['GET', 'POST'], '/{object}/{itemid:\d+}[/{method}]', [static::class, 'handleObjectRequest']);
            $r->addRoute(['GET', 'POST'], '/{object}/{itemid:[0-9a-f]{24}}[/{method}]', [static::class, 'handleObjectRequest']);
            $r->addRoute(['GET', 'POST'], '/{object}/{method}', [static::class, 'handleObjectRequest']);
            //$r->addRoute('GET', '/', [static::class, 'handleObjectRequest']);
        });
        $r->addGroup('/block', function (RouteCollector $r) {
            $r->addRoute('GET', '/{instance}', [static::class, 'handleBlockRequest']);
        });
        $r->addGroup('/restapi', function (RouteCollector $r) use ($restHandler) {
            DataObjectRESTHandler::registerRoutes($r, $restHandler);
            $r->addRoute('GET', '/', [DataObjectRESTHandler::class, 'getOpenAPI']);
        });
        $r->addRoute(['GET', 'POST'], '/graphql', [xarGraphQL::class, 'handleRequest']);
        $r->addRoute('GET', '/routes', [static::class, 'handleRoutesRequest']);
        $r->addRoute(['GET', 'POST'], '/{module}', [static::class, 'handleModuleRequest']);
        $r->addRoute(['GET', 'POST'], '/{module}/{func}', [static::class, 'handleModuleRequest']);
        $r->addRoute(['GET', 'POST'], '/{module}/{type}/{func}', [static::class, 'handleModuleRequest']);
        $r->addRoute('GET', '/', [static::class, 'handleModuleRequest']);
        $r->addRoute('OPTIONS', '*', [DataObjectRESTHandler::class, 'sendCORSOptions']);
    }

    /**
     * Summary of getSimpleDispatcher
     * @todo use FastRoute::recommendedSettings() in v2.x
     * @param string $group
     * @return Dispatcher
     */
    public static function getSimpleDispatcher(string $group = '')
    {
        if (isset(static::$dispatcher) && static::$prefix == $group) {
            return static::$dispatcher;
        }
        static::$prefix = $group;
        // override standard routeCollector here
        if (empty($group)) {
            static::$dispatcher = simpleDispatcher(function (RouteCollector $r) {
                static::addRouteCollection($r);
            }, [
                'routeCollector' => TrackRouteCollector::class,
            ]);
            return static::$dispatcher;
        }
        static::$dispatcher = simpleDispatcher(function (RouteCollector $r) use ($group) {
            $r->addGroup($group, function (RouteCollector $r) {
                static::addRouteCollection($r);
            });
        }, [
            'routeCollector' => TrackRouteCollector::class,
        ]);
        return static::$dispatcher;
    }

    /**
     * Basic route builder for module requests e.g. in response output or templates - assuming short url format here
     * @param ?string $module
     * @param ?string $type
     * @param string|int|null $func
     * @param array<string, mixed> $extra
     * @return string
     */
    public static function buildUri(?string $module = null, ?string $type = null, string|int|null $func = null, array $extra = []): string
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
        $dispatcher = static::getSimpleDispatcher($group);
        $routeInfo = $dispatcher->dispatch($method, $path);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                http_response_code(404);
                if (!empty($group)) {
                    $result = 'Nothing to see here at ' . htmlspecialchars($path) . ' with prefix ' . htmlspecialchars($group);
                    return [$result, null];
                }
                $result = 'Nothing to see here at ' . htmlspecialchars($path);
                return [$result, null];

            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                header('Allow: ' . implode(', ', $allowedMethods));
                http_response_code(405);
                $result = 'Method ' . htmlspecialchars($method) . ' is not allowed for ' . htmlspecialchars($path);
                return [$result, null];

            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                $context = null;
                // ... call $handler with $vars
                if (str_starts_with($path, $group . '/restapi/')) {
                    // different processing for REST API - see rst.php
                    DataObjectRESTHandler::$endpoint = static::getBaseUri() . $group . '/restapi';
                    [$result, $context] = static::callRestApiHandler($handler, $vars, $request);
                } elseif (str_starts_with($path, $group . '/graphql')) {
                    // different processing for GraphQL API - see gql.php
                    [$result, $context] = $this->callHandler($handler, $vars, $request);
                } else {
                    // @todo keep dispatcher static but replace $handler[0] with $this if current class?
                    [$result, $context] = $this->callHandler($handler, $vars, $request);
                }
                return [$result, $context];
        }
        throw new Exception('Invalid routeInfo[0] after dispatch');
    }

    /**
     * Summary of run
     * @param string $group
     * @param mixed $request
     * @return void
     */
    public function run(string $group = '', &$request = null)
    {
        $method = static::getMethod($request);
        $path = static::getPathInfo($request);
        [$result, $context] = $this->dispatchRequest($method, $path, $group, $request);
        if (str_starts_with($path, $group . '/restapi/')) {
            // different processing for REST API - see rst.php
            DataObjectRESTHandler::output($result, 200, $context);
        } elseif (str_starts_with($path, $group . '/graphql')) {
            // different processing for GraphQL API - see gql.php
            xarGraphQL::output($result, $context);
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
                    echo $transform(static::wrapOutputInPage($result, $context));
                } else {
                    echo $transform($result);
                }
                return;
            }
            // wrap output in page
            if ($this->wrapPage) {
                echo static::wrapOutputInPage($result, $context);
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
                // @todo instantiate handler[0] for subclasses like FastRouteApiBridge?
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
     * Summary of callRestApiHandler
     * @param mixed $handler
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return mixed
     */
    public static function callRestApiHandler($handler, $vars, &$request = null)
    {
        if (empty($vars)) {
            $vars = [];
        }
        [$result, $context] = DataObjectRESTHandler::callHandler($handler, $vars, $request);
        if ($handler[1] === 'getOpenAPI') {
            header('Access-Control-Allow-Origin: *');
            // @checkme set server url to current path here
            //$result['servers'][0]['url'] = DataObjectRESTHandler::getBaseURL();
            $result['servers'][0]['url'] = xarServer::getProtocol() . '://' . xarServer::getHost() . DataObjectRESTHandler::$endpoint;
        }
        return [$result, $context];
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
        $query = static::getQueryParams($request);
        // add remaining query params to path vars
        $params = array_merge($vars, $query);
        // add body params to query params
        $input = static::getParsedBody($request);
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
        static::$baseUri = static::getBaseUri($request) . static::$prefix;
        $context['baseuri'] = static::$baseUri;
        // set current module to 'object' for Xaraya controller - used e.g. in xarMod::getName()
        static::prepareController('object', static::$baseUri . '/object');
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
        $query = static::getQueryParams($request);
        // filter out path vars from remaining query params here
        $params = array_diff_key($query, $vars);
        // add body params to query params (if any)
        $input = static::getParsedBody($request);
        if (!empty($input) && is_array($input)) {
            $params = array_merge($params, $input);
        }

        $context = ContextFactory::fromRequest($request, __METHOD__);
        $context['mediatype'] = '';
        static::$baseUri = static::getBaseUri($request) . static::$prefix;
        $context['baseuri'] = static::$baseUri;
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        static::prepareController($vars['module'], static::$baseUri);
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
        $query = static::getQueryParams($request);

        $context = ContextFactory::fromRequest($request, __METHOD__);
        $context['mediatype'] = '';
        static::$baseUri = static::getBaseUri($request) . static::$prefix;
        $context['baseuri'] = static::$baseUri;
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        static::prepareController($vars['module'] ?? 'base', static::$baseUri);
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
        foreach (TrackRouteCollector::getRoutes() as $info) {
            if (is_array($info[1])) {
                $result .= "<li>" . $info[0] . " [" . implode(', ', $info[1]) . "]</li>";
                continue;
            }
            match ($info[1]) {
                TrackRouteCollector::$groupStarted => $result .= "<li>" . $info[0] . "<ul>",
                TrackRouteCollector::$groupStopped => $result .= "</ul></li>",
                default => $result .= "<li>" . $info[0] . " [" . $info[1] . "]</li>",
            };
        }
        $result .= "</ul>";
        return [$result, null];
    }
}

/**
 * Same as FastRouteBridge but runs API calls instead of GUI calls
 *
 * Note: if you really want to use APIs for DataObject please have a look at the REST API or GraphQL API instead
 * They can be configured via the admin Back End > Dynamic Data > Utilities > Test APIs
 */
class FastRouteApiBridge extends FastRouteBridge
{
    /**
     * Summary of addRouteCollection
     * @param RouteCollector $r
     * @return void
     */
    public static function addRouteCollection(RouteCollector $r)
    {
        $r->addGroup('/object', function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/{object}', [static::class, 'handleObjectRequest']);
            $r->addRoute(['GET', 'POST'], '/{object}/{itemid:\d+}[/{method}]', [static::class, 'handleObjectRequest']);
            $r->addRoute(['GET', 'POST'], '/{object}/{itemid:[0-9a-f]{24}}[/{method}]', [static::class, 'handleObjectRequest']);
            $r->addRoute(['GET', 'POST'], '/{object}/{method}', [static::class, 'handleObjectRequest']);
            //$r->addRoute(['GET', 'POST'], '/', [static::class, 'handleObjectRequest']);
        });
        $r->addGroup('/block', function (RouteCollector $r) {
            $r->addRoute('GET', '/{instance}', [static::class, 'handleBlockRequest']);
        });
        $r->addRoute(['GET', 'POST'], '/{module}[/{type}[/{func}]]', [static::class, 'handleModuleRequest']);
        $r->addRoute(['GET', 'POST'], '/', [static::class, 'handleModuleRequest']);
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
 * Same as FastRouteBridge but handles static files too
 *
 * Note: static files should really be handled by a web server or reverse proxy in front of the application
 */
class FastRouteStaticBridge extends FastRouteBridge
{
    /**
     * Summary of addRouteCollection
     * @param RouteCollector $r
     * @param string $staticFiles
     * @return void
     */
    public static function addRouteCollection(RouteCollector $r, string $staticFiles = '')
    {
        // @checkme use this as group e.g. everything under /static
        if (!empty($staticFiles)) {
            $r->addGroup($staticFiles, function (RouteCollector $r) {
                static::addModuleFileRoutes($r);
                static::addThemeFileRoutes($r);
                static::addVarFileRoutes($r);
            });
        } else {
            static::addModuleFileRoutes($r);
            static::addThemeFileRoutes($r);
            static::addVarFileRoutes($r);
        }
        parent::addRouteCollection($r);
    }

    /**
     * Summary of addThemeFileRoutes
     * @param RouteCollector $r
     * @return void
     */
    public static function addThemeFileRoutes(RouteCollector $r)
    {
        $r->addGroup('/themes', function (RouteCollector $r) {
            $r->addRoute('GET', '/{source}/{folder}/{file:.+}', [static::class, 'handleThemeFileRequest']);
        });
    }

    /**
     * Summary of addModuleFileRoutes
     * @param RouteCollector $r
     * @return void
     */
    public static function addModuleFileRoutes(RouteCollector $r)
    {
        $r->addGroup('/code/modules', function (RouteCollector $r) {
            $r->addRoute('GET', '/{source}/{folder}/{file:.+}', [static::class, 'handleModuleFileRequest']);
        });
    }

    /**
     * Summary of addVarFileRoutes
     * @param RouteCollector $r
     * @return void
     */
    public static function addVarFileRoutes(RouteCollector $r)
    {
        $r->addGroup('/var', function (RouteCollector $r) {
            $r->addRoute('GET', '/{source}/{folder}/{file:.+}', [static::class, 'handleVarFileRequest']);
        });
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
     * @phpstan-import-type ParsedRoutes from RouteParser
     * @deprecated 2.0.0 not available with new API
     * @return array<mixed>
     */
    public static function getRoutes(?string $handlerMethod = null, ?string $handlerClass = null)
    {
        //if (empty($handlerMethod) && empty($handlerClass)) {
        //    return TrackRouteCollector::$trackRoutes;
        //}
        $parser = new \FastRoute\RouteParser\Std();
        $routes = [];
        foreach (TrackRouteCollector::getRoutes() as $info) {
            if (!is_array($info[2]) || count($info[2]) < 2) {
                continue;
            }
            [$class, $method] = $info[2];
            if (!empty($handlerMethod) && $method !== $handlerMethod) {
                continue;
            }
            if (!empty($handlerClass) && $class !== $handlerClass) {
                continue;
            }
            // @checkme re-using routeParser here - why not call it the first time?
            [$route, $method, $handler] = $info;
            $routeDatas = (array) $parser->parse($route);
            // from longest to shortest routes here for optional variables
            foreach (array_reverse($routeDatas) as $routeData) {
                $path = '';
                $variables = [];
                foreach ($routeData as $data) {
                    if (is_string($data)) {
                        $path .= $data;
                        continue;
                    }
                    $path .= '{' . $data[0] . '}';
                    $variables[] = $data[0];
                }
                $routes[] = [$path, $method, $handler, $variables];
            }
        }
        return $routes;
    }
}
