<?php
/**
 * Experiment with routing bridges for use with other dispatchers
 */

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
chdir(dirname(__DIR__, 3) . '/html');

// use some routing bridge
use Xaraya\Bridge\Routing\RoutingBridge;
use Xaraya\Bridge\Routing\RoutingApiBridge;
use Xaraya\Bridge\Routing\RoutingStaticBridge;
use Xaraya\Bridge\Routing\FastRouteBuildTest;

sys::init();
xarCache::init();
// try out request context class - can't with PSR-17 ::fromGlobals()
//xarServer::setRequestClass(\Xaraya\Context\RequestContext::class);
// try out session context class
xarSession::setSessionClass(\Xaraya\Context\SessionContext::class);
xarCore::xarInit(xarCore::SYSTEM_USER);

// Concatenate and parse string into $_GET: php fastroute.php /object/sample ...
if (php_sapi_name() === 'cli') {
    //parse_str(implode('&', array_slice($argv, 1)), $_GET);
    if ($argc > 1 && str_contains($argv[1], '/')) {
        xarServer::setVar('PATH_INFO', $argv[1]);
        //xarServer::setVar('REQUEST_URI', $argv[0] . $argv[1]);
    }
}

/**
// add route collection to your own dispatcher
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    // ...
    // RoutingBridge::addRouteCollection($r);
    $r->addGroup('/xaraya', function (FastRoute\RouteCollector $r) {
        RoutingBridge::addRouteCollection($r);
    });
});
$routeInfo = $dispatcher->dispatch(xarServer::getVar('REQUEST_METHOD'), xarServer::getVar('PATH_INFO') ?? '/');
if ($routeInfo[0] == FastRoute\Dispatcher::FOUND) {
    $handler = $routeInfo[1];
    $vars = $routeInfo[2];
    // ... call $handler with $vars
    echo var_export($handler, true) . " with " . var_export($vars, true);
}
 */

// or direct use of simple route dispatcher
//$bridge = new RoutingBridge();
//[$result, $context] = $bridge->dispatchRequest(xarServer::getVar('REQUEST_METHOD'), xarServer::getVar('PATH_INFO') ?? '/');
//echo $result;
//echo xarTpl::renderPage($result);
//$bridge->run();

// or direct use of simple route dispatcher
$wrapPage = false;
$bridge = new RoutingBridge($wrapPage);
[$result, $context] = $bridge->dispatchRequest(xarServer::getVar('REQUEST_METHOD') ?? 'GET', xarServer::getVar('PATH_INFO') ?? '/');
$bridge->output($result, $context);

/**
$dispatcher = RoutingBridge::getSimpleDispatcher();
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
