<?php
/**
 * Entrypoint for handling REST API calls on Dynamic Data Objects (POC)
 *
 * Note: this assumes you install fast-route with composer
 * and use composer autoload in the entrypoint, see e.g. rst.php
 *
 * $ composer require --dev nikic/fast-route
 * $ head html/rst.php
 * <?php
 * ...
 * require dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * https://github.com/nikic/FastRoute
 */
require dirname(__DIR__).'/vendor/autoload.php';

// use the FastRoute library here
//use FastRoute\Dispatcher;
//use FastRoute\RouteCollector;
//use function FastRoute\simpleDispatcher;

// initialize bootstrap
sys::init();
// initialize caching
//xarCache::init();
// initialize database
xarDatabase::init();
// initialize modules
//xarMod::init();
// initialize users
//xarUser::init();

function try_builder()
{
    DataObjectRESTBuilder::init();
    $objects = DataObjectRESTBuilder::get_objects();
    //DataObjectRESTBuilder::create_openapi();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($objects, JSON_PRETTY_PRINT);
}

function send_openapi()
{
    $result = DataObjectRESTHandler::getOpenAPI();
    DataObjectRESTHandler::output($result);
}

function get_dispatcher()
{
    $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
        $r->addGroup('/v1', function (FastRoute\RouteCollector $r) {
            DataObjectRESTHandler::registerRoutes($r);
        });
    });
    return $dispatcher;
}

function dispatch_request($method, $path)
{
    $dispatcher = get_dispatcher();
    $routeInfo = $dispatcher->dispatch($method, $path);
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            // ... 404 Not Found
            http_response_code(404);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            // ... 405 Method Not Allowed
            header('Allow: ' . implode(', ', $allowedMethods));
            http_response_code(405);
            break;
        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            // ... call $handler with $vars
            if (!empty($_SERVER['QUERY_STRING'])) {
                parse_str($_SERVER['QUERY_STRING'], $more);
                $vars = array_merge($more, $vars);
            }
            // @todo handle php://input for POST etc.
            $result = call_user_func($handler, $vars);
            DataObjectRESTHandler::output($result);
            break;
    }
}

function try_handler()
{
    if (empty($_SERVER['PATH_INFO'])) {
        send_openapi();
    } else {
        dispatch_request($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO']);
    }
}

//try_builder();
try_handler();
