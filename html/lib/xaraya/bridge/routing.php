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
        static::$trackRoutes[] = [$this->currentGroupPrefix . $route, $httpMethod];
        //$route = $this->currentGroupPrefix . $route;
        parent::addRoute($httpMethod, $route, $handler);
    }

    public function addGroup(string $prefix, callable $callback): void
    {
        static::$trackRoutes[] = [$this->currentGroupPrefix . $prefix, static::$groupStarted];
        parent::addGroup($prefix, $callback);
        static::$trackRoutes[] = [$this->currentGroupPrefix . $prefix, static::$groupStopped];
    }
}

class FastRouteBridge
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
            //$r->addRoute(['GET', 'POST'], '/', [static::class, 'handleObjectRequest']);
        });
        $r->addGroup('/block', function (RouteCollector $r) {
            $r->addRoute('GET', '/{instance}', [static::class, 'handleBlockRequest']);
        });
        $r->addGroup('/restapi', function (RouteCollector $r) {
            DataObjectRESTHandler::registerRoutes($r);
            $r->addRoute('GET', '/', [DataObjectRESTHandler::class, 'getOpenAPI']);
        });
        $r->addRoute(['GET', 'POST'], '/graphql', [static::class, 'handleGraphQLRequest']);
        $r->addRoute(['GET', 'POST'], '/routes', [static::class, 'handleRoutesRequest']);
        $r->addRoute(['GET', 'POST'], '/{module}[/{type}[/{func}]]', [static::class, 'handleModuleRequest']);
        $r->addRoute(['GET', 'POST'], '/', [static::class, 'handleModuleRequest']);
        $r->addRoute('OPTIONS', '*', [DataObjectRESTHandler::class, 'sendCORSOptions']);
    }

    public static function getSimpleDispatcher(string $group = '')
    {
        // override standard routeCollector here
        if (empty($group)) {
            $dispatcher = simpleDispatcher(function (RouteCollector $r) {
                static::addRouteCollection($r);
            }, [
                'routeCollector' => TrackRouteCollector::class
            ]);
            return $dispatcher;
        }
        $dispatcher = simpleDispatcher(function (RouteCollector $r) use ($group) {
            $r->addGroup($group, function (RouteCollector $r) {
                static::addRouteCollection($r);
            });
        }, [
            'routeCollector' => TrackRouteCollector::class
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
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                header('Allow: ' . implode(', ', $allowedMethods));
                http_response_code(405);
                return 'Method ' . htmlspecialchars($method) . ' is not allowed for ' . htmlspecialchars($path);
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                // ... call $handler with $vars
                if (strpos($path, $group . '/restapi/') === 0) {
                    // different processing for REST API - see rst.php
                    DataObjectRESTHandler::$endpoint = static::getBaseUri() . $group . '/restapi';
                    $result = static::callRestApiHandler($handler, $vars);
                } else {
                    $result = static::callHandler($handler, $vars);
                }
                return $result;
                break;
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
            header('Content-Type: application/json; charset=utf-8');
            try {
                //$output = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR);
                $output = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $output = '{"JSON Exception": ' . json_encode($e->getMessage()) . '}';
            }
            echo $output;
        }
    }

    public static function callHandler($handler, $vars)
    {
        if (empty($vars)) {
            $vars = [];
        }
        $query = static::getQueryParams();
        // handle php://input for POST etc. - let Xaraya handle it
        //$input = file_get_contents('php://input');
        //if (!empty($input)) {
        //    $input = json_decode($input, true);
        //}
        $result = call_user_func($handler, $vars, $query);
        return $result;
    }

    // different processing for REST API - see rst.php
    public static function callRestApiHandler($handler, $vars)
    {
        $params = [];
        $params['path'] = $vars;
        $params['query'] = static::getQueryParams();
        // handle php://input for POST etc.
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $params['input'] = json_decode($rawInput, true);
        }
        // DataObjectRESTHandler::setTimer('parse');
        $result = DataObjectRESTHandler::getResult($handler, $params);
        if ($handler[1] === 'getOpenAPI') {
            header('Access-Control-Allow-Origin: *');
            // @checkme set server url to current path here
            //$result['servers'][0]['url'] = DataObjectRESTHandler::getBaseURL();
            $result['servers'][0]['url'] = xarServer::getProtocol() . '://' . xarServer::getHost() . DataObjectRESTHandler::$endpoint;
        }
        return $result;
    }

    // different processing for GraphQL API - see gql.php
    public static function handleGraphQLRequest($vars, $params = null, $input = null)
    {
        // dispatcher doesn't provide query params by default
        if (!isset($params)) {
            $params = static::getQueryParams();
        }
        // handle php://input for POST etc.
        if (!isset($input)) {
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $input = json_decode($rawInput, true);
            }
        }
        if (!empty($input)) {
            $query = $input['query'] ?? '{schema}';
            $variables = $input['variables'] ?? null;
            $operationName = $input['operationName'] ?? null;
        } else {
            $query = $params['query'] ?? '{schema}';
            $variables = $params['variables'] ?? null;
            $operationName = $params['operationName'] ?? null;
        }
        // /gql.php?query=query($id:ID!){object(id:$id){name}}&variables={"id":"2"}
        if (!empty($variables) && is_string($variables)) {
            $variables = json_decode($variables, true);
        }
        $result = xarGraphQL::get_data($query, $variables, $operationName);
        if ($query == '{schema}') {
            static::$mediaType = 'text/plain';
        }
        return $result;
    }

    public static function handleObjectRequest($vars, $query = null, $input = null)
    {
        // dispatcher doesn't provide query params by default
        if (!isset($query)) {
            $query = static::getQueryParams();
        }
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
        // add remaining query params to path vars
        $params = array_merge($vars, $query);
        // add body params to query params
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

    public static function handleModuleRequest($vars, $query = null, $input = null)
    {
        // dispatcher doesn't provide query params by default
        if (!isset($query)) {
            $query = static::getQueryParams();
        }
        // path = /
        $vars['module'] ??= 'base';
        // path = /object[/...]
        if ($vars['module'] == 'object') {
            return static::handleObjectRequest($vars, $query, $input);
        }
        // path = /{module}/{func}
        if (!empty($vars['type']) && empty($vars['func'])) {
            $vars['func'] = $vars['type'];
            $vars['type'] = 'user';
        }
        // path = /{module}/{type}/{func}
        // filter out path vars from remaining query params here
        $params = array_diff_key($query, $vars);
        // add body params to query params (if any)
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

    public static function handleBlockRequest($vars, $query = null, $input = null)
    {
        // @checkme limited to renderBlock() or getinfo() for now, so no query params or body params taken into account yet

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
    public static function handleRoutesRequest($vars, $query = null, $input = null)
    {
        $result = "<ul>";
        foreach (TrackRouteCollector::$trackRoutes as $info) {
            if (is_array($info[1])) {
                $result .= "<li>[" . implode(', ', $info[1]) . "] " . $info[0] . "</li>";
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
                    $result .= "<li>" . $info[1] . " " . $info[0] . "</li>";
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

    public static function handleThemeFileRequest($vars, $query = null, $input = null)
    {
        // path = /themes/{theme}/{folder}/{file:.+}
        $path = static::getThemeFileRequest($vars);
        $vars['path'] = $path;
        //static::$mediaType = '';
        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        return var_export($vars, true);
    }

    public static function handleModuleFileRequest($vars, $query = null, $input = null)
    {
        // path = /code/modules/{module}/{folder}/{file:.+}
        $path = static::getModuleFileRequest($vars);
        $vars['path'] = $path;
        //static::$mediaType = '';
        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        return var_export($vars, true);
    }
}
