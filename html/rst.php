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
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * https://github.com/nikic/FastRoute
 * @uses \sys::autoload()
 */
require_once dirname(__DIR__).'/vendor/autoload.php';

// use the FastRoute library here
//use FastRoute\Dispatcher;
//use FastRoute\RouteCollector;
//use function FastRoute\simpleDispatcher;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    DataObjectRESTHandler::sendCORSOptions();
    return;
}

// initialize bootstrap
sys::init();
// initialize caching - delay until we need results
//xarCache::init();
// initialize database - delay until caching fails
//xarDatabase::init();
// initialize modules
//xarMod::init();
// initialize users
//xarUser::init();

/**
 * Summary of try_builder
 * @return void
 */
function try_builder()
{
    DataObjectRESTBuilder::init();
    $objects = DataObjectRESTBuilder::get_objects();
    //DataObjectRESTBuilder::create_openapi();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($objects, JSON_PRETTY_PRINT);
}

/**
 * Summary of send_openapi
 * @param mixed $restHandler
 * @return void
 */
function send_openapi($restHandler)
{
    // @todo move away from static methods for context
    $result = $restHandler::getOpenAPI();
    $restHandler::output($result);
}

/**
 * Summary of get_dispatcher
 * @param mixed $restHandler
 * @return FastRoute\Dispatcher
 */
function get_dispatcher($restHandler)
{
    // @todo move away from static methods for context
    // @todo use FastRoute::recommendedSettings() in v2.x
    $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) use ($restHandler) {
        $r->addGroup('/v1', function (FastRoute\RouteCollector $r) use ($restHandler) {
            $restHandler::registerRoutes($r, $restHandler);
        });
    });
    return $dispatcher;
}

/**
 * Summary of dispatch_request
 * @param string $method
 * @param string $path
 * @param FastRoute\Dispatcher $dispatcher
 * @param mixed $restHandler
 * @return void
 */
function dispatch_request($method, $path, $dispatcher, $restHandler)
{
    // $restHandler::setTimer('register');
    $routeInfo = $dispatcher->dispatch($method, $path);
    // $restHandler::setTimer('dispatch');
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
            try {
                [$result, $context] = $restHandler::callHandler($handler, $vars);
                $restHandler::output($result, 200, $context);
            } catch (UnauthorizedOperationException $e) {
                $restHandler::output('This operation is unauthorized, please authenticate.', 401);
            } catch (ForbiddenOperationException $e) {
                $restHandler::output('This operation is forbidden.', 403);
            } catch (Throwable $e) {
                $result = "Exception: " . $e->getMessage();
                if ($e->getPrevious() !== null) {
                    $result .= "\nPrevious: " . $e->getPrevious()->getMessage();
                }
                $result .= "\nTrace:\n" . $e->getTraceAsString();
                $restHandler::output($result, 422);
            }
            break;
    }
}

/**
 * Summary of try_handler
 * @param mixed $restHandler
 * @return void
 */
function try_handler($restHandler)
{
    if (empty(xarServer::getVar('PATH_INFO'))) {
        send_openapi($restHandler);
    } else {
        // $restHandler::$enableTimer = true;
        // $restHandler::setTimer('start');
        $dispatcher = get_dispatcher($restHandler);
        dispatch_request(xarServer::getVar('REQUEST_METHOD'), xarServer::getVar('PATH_INFO'), $dispatcher, $restHandler);
    }
}

//try_builder();
// @todo move away from static methods for context
$restHandler = DataObjectRESTHandler::class;
try_handler($restHandler);
