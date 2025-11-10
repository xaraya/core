<?php

/**
 * Entrypoint for handling REST API calls
 *
 * Note: this assumes you install symfony/routing with composer
 * and use composer autoload in the entrypoint, see e.g. rst.php
 *
 * $ composer require --dev symfony/routing symfony/config
 * $ head html/rst.php
 * <?php
 * ...
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * @see https://github.com/nikic/FastRoute
 * @see https://github.com/symfony/routing
 * @uses \sys::autoload()
 */
require_once dirname(__DIR__) . '/vendor/autoload.php';

// use the nikic FastRoute library here
//use Xaraya\Routing\FastRouter;
// use the Symfony Routing component here
use Xaraya\Routing\Routing;
use Xaraya\Routing\RouterInterface;
use Xaraya\Bridge\RestAPI\RestAPIBuilder;
use Xaraya\Bridge\RestAPI\RestAPIHandler;
use Xaraya\Bridge\RestAPI\RestAPIRoutes;
use Xaraya\Services\xar;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    RestAPIHandler::sendCORSOptions();
    return;
}

// initialize bootstrap
sys::init();
// initialize caching - delay until we need results
//xar::cache()->init();
// initialize database - delay until caching fails
//xar::db()->init();
// initialize modules
//xar::mod()->init();
// initialize users
//xar::user()->init();

/**
 * Summary of try_builder
 * @return void
 */
function try_builder()
{
    RestAPIBuilder::init();
    $objects = RestAPIBuilder::get_objects();
    //RestAPIBuilder::create_openapi();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($objects, JSON_PRETTY_PRINT);
}

/**
 * Summary of send_openapi
 * @param RestAPIHandler $restHandler
 * @return void
 */
function send_openapi($restHandler)
{
    // move away from static methods for context
    $result = $restHandler->getOpenAPI();
    $restHandler->output($result);
}

/**
 * Summary of get_router
 * @param RestAPIHandler $restHandler
 * @return RouterInterface
 */
function get_router($restHandler)
{
    //$cacheFile = sys::varpath() . '/cache/api/restapi_fastroute.php';
    //$router = new FastRouter(RestAPIRoutes::getRoutes(...));
    $cacheFile = sys::varpath() . '/cache/api/url_matching_routes.php';
    $router = new Routing(RestAPIRoutes::getRoutes(...), $cacheFile);
    return $router;
}

/**
 * Summary of handle_request
 * @param string $method
 * @param string $path
 * @param RouterInterface $router
 * @param RestAPIHandler $restHandler
 * @return void
 */
function handle_request($method, $path, $router, $restHandler)
{
    // $restHandler::setTimer('register');
    [$handler, $vars] = $router->match($path, $method);
    if (empty($handler)) {
        switch ((string) $vars['status']) {
            case '404':
                // ... 404 Not Found
                http_response_code(404);
                break;
            case '405':
                // ... 405 Method Not Allowed
                if (!empty($vars['methods'])) {
                    header('Allow: ' . implode(', ', $vars['methods']));
                }
                http_response_code(405);
                break;
        }
        return;
    }
    // $restHandler::setTimer('dispatch');
    // ... call $handler with $vars
    try {
        [$result, $context] = $restHandler->callHandler($handler, $vars);
        $restHandler->output($result);
    } catch (UnauthorizedOperationException $e) {
        $restHandler->output('This operation is unauthorized, please authenticate.', 401);
    } catch (ForbiddenOperationException $e) {
        $restHandler->output('This operation is forbidden.', 403);
    } catch (Throwable $e) {
        $result = "Exception: " . $e->getMessage();
        if ($e->getPrevious() !== null) {
            $result .= "\nPrevious: " . $e->getPrevious()->getMessage();
        }
        $result .= "\nTrace:\n" . $e->getTraceAsString();
        $restHandler->output($result, 422);
    }
}

/**
 * Summary of try_handler
 * @param RestAPIHandler $restHandler
 * @return void
 */
function try_handler($restHandler)
{
    $req = xar::req();
    if (empty($req->getServerVar('PATH_INFO'))) {
        send_openapi($restHandler);
    } else {
        // $restHandler::enableTimer(true);
        // $restHandler::setTimer('start');
        $router = get_router($restHandler);
        handle_request($req->getServerVar('REQUEST_METHOD'), $req->getServerVar('PATH_INFO'), $router, $restHandler);
    }
}

//try_builder();
// move away from static methods for context
$restHandler = new RestAPIHandler();
try_handler($restHandler);
