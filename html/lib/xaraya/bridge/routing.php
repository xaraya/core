<?php
/**
 * Experiment with routing bridges for use with other dispatchers
 *
 * require dirname(__DIR__).'/vendor/autoload.php';
 * sys::init();
 * xarCache::init();
 * xarCore::xarInit(xarCore::SYSTEM_USER);
 *
 * // use some routing bridge
 * use Xaraya\Bridge\Routing\FastRouteBridge;
 *
 * // add route collection to your own dispatcher
 * // $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
 * //     // ...
 * //     // FastRouteBridge::addRouteCollection($r);
 * //     $r->addGroup('/mysite', function (FastRoute\RouteCollector $r) {
 * //         FastRouteBridge::addRouteCollection($r);
 * //     });
 * // });
 * // $routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'] ?? '/');
 * // if ($routeInfo[0] == FastRoute\Dispatcher::FOUND) {
 * //     $handler = $routeInfo[1];
 * //     $vars = $routeInfo[2];
 * //     // ... call $handler with $vars
 * // }
 *
 * // or get a route dispatcher to work with yourself, possibly in a group
 * // $dispatcher = FastRouteBridge::getSimpleDispatcher('/mysite');
 * // $routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'] ?? '/');
 *
 * // or let the route dispatcher handle the request itself and return the result
 * $result = FastRouteBridge::dispatchRequest($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'] ?? '/', '/mysite');
 * FastRouteBridge::output($result);
 *
 * // or let it really do all the work here...
 * // FastRouteBridge::run('/mysite');
 */

namespace Xaraya\Bridge\Routing;

// use the FastRoute library here - see https://github.com/nikic/FastRoute
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
// use some Xaraya classes
use xarMod;
use xarServer;
use sys;
use JsonException;

sys::import('xaraya.bridge.requests.commontrait');
use Xaraya\Bridge\Requests\CommonBridgeInterface;
use Xaraya\Bridge\Requests\CommonBridgeTrait;
use Xaraya\Bridge\Requests\StaticFileBridgeTrait;
use DataObjectRESTHandler;
use xarGraphQL;

use function FastRoute\simpleDispatcher;

/**
 * Keep track of collected routes - see https://github.com/nikic/FastRoute/blob/master/src/RouteCollector.php
 */
class TrackRouteCollector extends RouteCollector
{
    public static array $trackRoutes = [];
    public static string $groupStarted = 'GROUP STARTED';
    public static string $groupStopped = 'GROUP STOPPED';
    //protected string $currentGroupPrefix = '';

    public function addRoute($httpMethod, string $route, $handler): void
    {
        static::$trackRoutes[] = [$this->currentGroupPrefix . $route, $httpMethod, $handler];
        //$route = $this->currentGroupPrefix . $route;
        //$routeDatas = $this->routeParser->parse($route);
        parent::addRoute($httpMethod, $route, $handler);
    }

    public function addGroup(string $prefix, callable $callback): void
    {
        static::$trackRoutes[] = [$this->currentGroupPrefix . $prefix, static::$groupStarted, null];
        parent::addGroup($prefix, $callback);
        static::$trackRoutes[] = [$this->currentGroupPrefix . $prefix, static::$groupStopped, null];
    }
}

class FastRouteBridge implements CommonBridgeInterface
{
    use CommonBridgeTrait;

    public static string $baseUri = '';
    public static string $mediaType = '';

    public static function addRouteCollection(RouteCollector $r)
    {
        $r->addGroup('/object', function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/{object}', [static::class, 'handleObjectRequest']);
            $r->addRoute(['GET', 'POST'], '/{object}/{itemid:\d+}[/{method}]', [static::class, 'handleObjectRequest']);
            $r->addRoute(['GET', 'POST'], '/{object}/{method}', [static::class, 'handleObjectRequest']);
            //$r->addRoute('GET', '/', [static::class, 'handleObjectRequest']);
        });
        $r->addGroup('/block', function (RouteCollector $r) {
            $r->addRoute('GET', '/{instance}', [static::class, 'handleBlockRequest']);
        });
        $r->addGroup('/restapi', function (RouteCollector $r) {
            DataObjectRESTHandler::registerRoutes($r);
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

    public static function getSimpleDispatcher(string $group = '')
    {
        // override standard routeCollector here
        if (empty($group)) {
            $dispatcher = simpleDispatcher(function (RouteCollector $r) {
                static::addRouteCollection($r);
            }, [
                'routeCollector' => TrackRouteCollector::class,
            ]);
            return $dispatcher;
        }
        $dispatcher = simpleDispatcher(function (RouteCollector $r) use ($group) {
            $r->addGroup($group, function (RouteCollector $r) {
                static::addRouteCollection($r);
            });
        }, [
            'routeCollector' => TrackRouteCollector::class,
        ]);
        return $dispatcher;
    }

    public static function dispatchRequest(string $method, string $path, string $group = '')
    {
        $dispatcher = static::getSimpleDispatcher($group);
        $routeInfo = $dispatcher->dispatch($method, $path);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                http_response_code(404);
                if (!empty($group)) {
                    return 'Nothing to see here at ' . htmlspecialchars($path) . ' with prefix ' . htmlspecialchars($group);
                }
                return 'Nothing to see here at ' . htmlspecialchars($path);

            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                header('Allow: ' . implode(', ', $allowedMethods));
                http_response_code(405);
                return 'Method ' . htmlspecialchars($method) . ' is not allowed for ' . htmlspecialchars($path);

            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                // ... call $handler with $vars
                if (strpos($path, $group . '/restapi/') === 0) {
                    // different processing for REST API - see rst.php
                    DataObjectRESTHandler::$endpoint = static::getBaseUri() . $group . '/restapi';
                    $result = static::callRestApiHandler($handler, $vars);
                } elseif (strpos($path, $group . '/graphql') === 0) {
                    // different processing for GraphQL API - see gql.php
                    $result = static::callHandler($handler, $vars);
                    if (is_string($result)) {
                        static::$mediaType = 'text/plain';
                    }
                } else {
                    $result = static::callHandler($handler, $vars);
                }
                return $result;
        }
    }

    public static function run(string $group = '')
    {
        $method = static::getMethod();
        $path = static::getPathInfo();
        $result = static::dispatchRequest($method, $path, $group);
        if (strpos($path, $group . '/restapi/') === 0) {
            // different processing for REST API - see rst.php
            DataObjectRESTHandler::output($result);
        } elseif (strpos($path, $group . '/graphql') === 0) {
            // different processing for GraphQL API - see gql.php
            xarGraphQL::output($result);
        } else {
            static::output($result);
        }
    }

    public static function output($result)
    {
        if (http_response_code() !== 200 && php_sapi_name() !== 'cli') {
            return;
        }
        if (is_string($result)) {
            if (!empty(self::$mediaType)) {
                header('Content-Type: ' . self::$mediaType . '; charset=utf-8');
            } elseif (substr($result, 0, 5) === '<?xml') {
                header('Content-Type: application/xml; charset=utf-8');
            } else {
                header('Content-Type: text/html; charset=utf-8');
            }
            echo $result;
        } else {
            if (!empty($_SERVER['HTTP_ORIGIN'])) {
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

    public static function callHandler($handler, $vars, &$request = null)
    {
        if (empty($vars)) {
            $vars = [];
        }
        // don't use call_user_func here anymore because $request is passed by reference
        $result = $handler($vars, $request);
        return $result;
    }

    // different processing for REST API - see rst.php
    public static function callRestApiHandler($handler, $vars, &$request = null)
    {
        if (empty($vars)) {
            $vars = [];
        }
        $result = DataObjectRESTHandler::callHandler($handler, $vars, $request);
        if ($handler[1] === 'getOpenAPI') {
            header('Access-Control-Allow-Origin: *');
            // @checkme set server url to current path here
            //$result['servers'][0]['url'] = DataObjectRESTHandler::getBaseURL();
            $result['servers'][0]['url'] = xarServer::getProtocol() . '://' . xarServer::getHost() . DataObjectRESTHandler::$endpoint;
        }
        return $result;
    }

    public static function handleObjectRequest($vars, &$request = null)
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

        static::$baseUri = static::getBaseUri();
        // set current module to 'object' for Xaraya controller - used e.g. in xarMod::getName()
        static::prepareController('object', static::$baseUri . '/object');

        return static::runObjectRequest($params);
    }

    public static function runObjectRequest($params)
    {
        return static::runDataObjectGuiRequest($params);
    }

    public static function handleModuleRequest($vars, &$request = null)
    {
        // path = /
        $vars['module'] ??= 'base';
        // path = /object[/...]
        if ($vars['module'] == 'object') {
            return static::handleObjectRequest($vars, $request);
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

        static::$baseUri = static::getBaseUri();
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        static::prepareController($vars['module'], static::$baseUri);

        return static::runModuleRequest($vars, $params);
    }

    public static function runModuleRequest($vars, $query)
    {
        return static::runModuleGuiRequest($vars, $query);
    }

    public static function handleBlockRequest($vars, &$request = null)
    {
        // @checkme limited to renderBlock() or getinfo() for now, so no query params or body params taken into account yet
        // dispatcher doesn't provide query params by default
        $query = static::getQueryParams($request);

        static::$baseUri = static::getBaseUri();
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        static::prepareController($vars['module'] ?? 'base', static::$baseUri);

        return static::runBlockRequest($vars, $query);
    }

    public static function runBlockRequest($vars, $query = null)
    {
        return static::runBlockGuiRequest($vars, $query);
    }

    /**
     * Show available routes
     */
    public static function handleRoutesRequest($vars, &$request = null)
    {
        $result = "<ul>";
        foreach (TrackRouteCollector::$trackRoutes as $info) {
            if (is_array($info[1])) {
                $result .= "<li>" . $info[0] . " [" . implode(', ', $info[1]) . "]</li>";
                continue;
            }
            switch ($info[1]) {
                case TrackRouteCollector::$groupStarted:
                    $result .= "<li>" . $info[0] . "<ul>";
                    break;
                case TrackRouteCollector::$groupStopped:
                    $result .= "</ul></li>";
                    break;
                default:
                    $result .= "<li>" . $info[0] . " [" . $info[1] . "]</li>";
            }
        }
        $result .= "</ul>";
        return $result;
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
    public static function addRouteCollection(RouteCollector $r)
    {
        $r->addGroup('/object', function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/{object}', [static::class, 'handleObjectRequest']);
            $r->addRoute(['GET', 'POST'], '/{object}/{itemid:\d+}[/{method}]', [static::class, 'handleObjectRequest']);
            $r->addRoute(['GET', 'POST'], '/{object}/{method}', [static::class, 'handleObjectRequest']);
            //$r->addRoute(['GET', 'POST'], '/', [static::class, 'handleObjectRequest']);
        });
        $r->addGroup('/block', function (RouteCollector $r) {
            $r->addRoute('GET', '/{instance}', [static::class, 'handleBlockRequest']);
        });
        $r->addRoute(['GET', 'POST'], '/{module}[/{type}[/{func}]]', [static::class, 'handleModuleRequest']);
        $r->addRoute(['GET', 'POST'], '/', [static::class, 'handleModuleRequest']);
    }

    public static function runObjectRequest($params)
    {
        return static::runDataObjectApiRequest($params);
    }

    public static function runModuleRequest($vars, $query)
    {
        return static::runModuleApiRequest($vars, $query);
    }

    public static function runBlockRequest($vars, $query = null)
    {
        return static::runBlockApiRequest($vars, $query);
    }
}

/**
 * Same as FastRouteBridge but handles static files too
 *
 * Note: static files should really be handled by a web server or reverse proxy in front of the application
 */
class FastRouteStaticBridge extends FastRouteBridge
{
    use StaticFileBridgeTrait;

    public static function addRouteCollection(RouteCollector $r, string $staticFiles = '')
    {
        // @checkme use this as group e.g. everything under /static
        if (!empty($staticFiles)) {
            $r->addGroup($staticFiles, function (RouteCollector $r) {
                static::addModuleFileRoutes($r);
                static::addThemeFileRoutes($r);
            });
        } else {
            static::addModuleFileRoutes($r);
            static::addThemeFileRoutes($r);
        }
        parent::addRouteCollection($r);
    }

    public static function addThemeFileRoutes(RouteCollector $r)
    {
        $r->addGroup('/themes', function (RouteCollector $r) {
            $r->addRoute('GET', '/{theme}/{folder}/{file:.+}', [static::class, 'handleThemeFileRequest']);
        });
    }

    public static function addModuleFileRoutes(RouteCollector $r)
    {
        $r->addGroup('/code/modules', function (RouteCollector $r) {
            $r->addRoute('GET', '/{module}/{folder}/{file:.+}', [static::class, 'handleModuleFileRequest']);
        });
    }

    public static function handleThemeFileRequest($vars, &$request = null)
    {
        // path = /themes/{theme}/{folder}/{file:.+}
        $path = static::getThemeFileRequest($vars);
        $vars['path'] = $path;
        //if (!empty($request)) {
        //    $request = $request->withAttribute('mediaType', '...');
        //}
        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        return var_export($vars, true);
    }

    public static function handleModuleFileRequest($vars, &$request = null)
    {
        // path = /code/modules/{module}/{folder}/{file:.+}
        $path = static::getModuleFileRequest($vars);
        $vars['path'] = $path;
        //if (!empty($request)) {
        //    $request = $request->withAttribute('mediaType', '...');
        //}
        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        return var_export($vars, true);
    }
}

class FastRouteBuildTest
{
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
    }

    /**
     * Get available routes, optionally by handler method and/or handler class
     */
    public static function getRoutes(?string $handlerMethod = null, ?string $handlerClass = null)
    {
        //if (empty($handlerMethod) && empty($handlerClass)) {
        //    return TrackRouteCollector::$trackRoutes;
        //}
        $parser = new \FastRoute\RouteParser\Std();
        $routes = [];
        foreach (TrackRouteCollector::$trackRoutes as $info) {
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
            $routeDatas = $parser->parse($route);
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
