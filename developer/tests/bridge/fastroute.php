<?php
/**
 * Experiment with routing bridges for use with other dispatchers
 */

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

// use some routing bridge
use Xaraya\Bridge\Routing\FastRouteBridge;
use Xaraya\Bridge\Routing\FastRouteApiBridge;
use Xaraya\Bridge\Routing\FastRouteStaticBridge;
use Xaraya\Bridge\Routing\FastRouteBuildTest;

sys::init();
xarCache::init();
xarCore::xarInit(xarCore::SYSTEM_USER);

// Concatenate and parse string into $_GET: php fastroute.php /object/sample ...
if (php_sapi_name() === 'cli') {
    //parse_str(implode('&', array_slice($argv, 1)), $_GET);
    if ($argc > 1 && str_contains($argv[1], '/')) {
        $_SERVER['PATH_INFO'] = $argv[1];
        //$_SERVER['REQUEST_URI'] = $argv[0] . $argv[1];
    }
}

/**
// add route collection to your own dispatcher
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    // ...
    // FastRouteBridge::addRouteCollection($r);
    $r->addGroup('/xaraya', function (FastRoute\RouteCollector $r) {
        FastRouteBridge::addRouteCollection($r);
    });
});
$routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'] ?? '/');
if ($routeInfo[0] == FastRoute\Dispatcher::FOUND) {
    $handler = $routeInfo[1];
    $vars = $routeInfo[2];
    // ... call $handler with $vars
    echo var_export($handler, true) . " with " . var_export($vars, true);
}
 */

// or direct use of simple route dispatcher
//[$result, $context] = FastRouteBridge::dispatchRequest($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'] ?? '/');
//echo $result;
//echo xarTpl::renderPage($result);
//FastRouteBridge::run();

// or direct use of simple route dispatcher
[$result, $context] = FastRouteBridge::dispatchRequest($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['PATH_INFO'] ?? '/');
FastRouteBridge::output($result, $context);

/**
$dispatcher = FastRouteBridge::getSimpleDispatcher();
//$routes = FastRouteBuildTest::getRoutes();
//echo var_export($routes, true);
$params = ['object' => 'sample', 'method' => 'update', 'itemid' => 4];
$params = ['object' => 'sample', 'itemid' => 4];
$params = ['object' => 'sample', 'method' => 'create'];
$params = ['object' => 'sample'];
//$params = ['abject' => 'sample'];
$route = FastRouteBuildTest::getObjectRoute($params);
echo $route . "\n";
$params = ['module' => 'base', 'type' => 'admin', 'func' => 'main'];
//$params = ['module' => 'base', 'func' => 'main'];
//$params = ['module' => 'base', 'type' => 'user'];
//$params = ['module' => 'base'];
//$params = ['madule' => 'base'];
$route = FastRouteBuildTest::getModuleRoute($params);
echo $route . "\n";
 */
