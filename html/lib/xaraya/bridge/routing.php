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
 * $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
 *     // ...
 *     // FastRouteBridge::addRouteCollection($r);
 *     $r->addGroup('/xaraya', function (FastRoute\RouteCollector $r) {
 *         FastRouteBridge::addRouteCollection($r);
 *     });
 * });
 * // $routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'] ?? '/');
 * // if ($routeInfo[0] == FastRoute\Dispatcher::FOUND) {
 * //     $handler = $routeInfo[1];
 * //     $vars = $routeInfo[2];
 * //     // ... call $handler with $vars
 * // }
 *
 * // or simply use the route dispatcher directly
 * $result = FastRouteBridge::dispatchRequest($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'] ?? '/');
 * echo $result;
 *
 */

namespace Xaraya\Bridge\Routing;

// use the FastRoute library here - see https://github.com/nikic/FastRoute
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use xarController;
// use some Xaraya classes
use xarServer;
use xarSystemVars;
use xarMod;
use xarBlock;
use sys;
use Xaraya\Bridge\CommonBridgeTrait;

sys::import('xaraya.bridge.commontrait');
use DataObjectUserInterface;

sys::import('modules.dynamicdata.class.userinterface');
use function FastRoute\simpleDispatcher;

class FastRouteBridge
{
    use CommonBridgeTrait;

    public static string $baseUri = '';
    public static string $prefix = '';

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

    public static function getSimpleDispatcher(string $group = '')
    {
        if (empty($group)) {
            $dispatcher = simpleDispatcher(function (RouteCollector $r) {
                static::addRouteCollection($r);
            });
            return $dispatcher;
        }
        $dispatcher = simpleDispatcher(function (RouteCollector $r) use ($group) {
            $r->addGroup($group, function (RouteCollector $r) {
                static::addRouteCollection($r);
            });
        });
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
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                header('Allow: ' . implode(', ', $allowedMethods));
                http_response_code(405);
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                // ... call $handler with $vars
                $result = static::callHandler($handler, $vars);
                return $result;
                break;
        }
    }

    public static function callHandler($handler, $vars)
    {
        if (empty($vars)) {
            $vars = [];
        }
        $query = [];
        if (!empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $query);
        }
        // handle php://input for POST etc. - let Xaraya handle it
        //$input = file_get_contents('php://input');
        //if (!empty($input)) {
        //    $input = json_decode($input, true);
        //}
        $result = call_user_func($handler, $vars, $query);
        return $result;
    }

    public static function handleObjectRequest($vars, $query, $input = null)
    {
        // add remaining query params to path vars
        $params = array_merge($vars, $query);

        // @checkme pass along buildUri() as link function to DD
        $params['linktype'] = 'other';
        $params['linkfunc'] = [static::class, 'buildDataObjectPath'];

        static::$baseUri = $_SERVER['SCRIPT_NAME'];
        // set current module to 'object' for Xaraya controller - used e.g. in xarMod::getName()
        static::prepareController('object', static::$baseUri . '/object');

        $interface = new DataObjectUserInterface($params);
        return $interface->handle($params);
    }

    public static function handleModuleRequest($vars, $query, $input = null)
    {
        $vars['module'] ??= 'base';
        // path = /object[/...]
        if ($vars['module'] == 'object') {
            $vars['object'] = $vars['type'] ?? '';
            if (!empty($vars['func'])) {
                if (is_numeric($vars['func'])) {
                    $vars['itemid'] = $vars['func'];
                } else {
                    $vars['method'] = $vars['func'];
                }
                unset($vars['func']);
            }
            unset($vars['module']);
            unset($vars['type']);
            return static::handleObjectRequest($vars, $query, $input);
        }
        // path = /{module}/{func}
        if (!empty($vars['type']) && empty($vars['func'])) {
            $vars['func'] = $vars['type'];
            $vars['type'] = 'user';
        }

        static::$baseUri = $_SERVER['SCRIPT_NAME'];
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        static::prepareController($vars['module'], static::$baseUri);

        return xarMod::guiFunc($vars['module'], $vars['type'] ?? 'user', $vars['func'] ?? 'main', $query);
    }

    public static function handleBlockRequest($vars, $query, $input = null)
    {
        static::$baseUri = $_SERVER['SCRIPT_NAME'];
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        static::prepareController($vars['module'] ?? 'base', static::$baseUri);

        return xarBlock::renderBlock($vars);
    }
}
