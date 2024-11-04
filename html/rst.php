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
 * @see https://github.com/nikic/FastRoute
 * @see https://github.com/symfony/routing
 * @uses \sys::autoload()
 */
require_once dirname(__DIR__) . '/vendor/autoload.php';

// use the nikic FastRoute library here
use Xaraya\Routing\FastRouter;
// use the Symfony Routing component here
use Xaraya\Routing\Routing;
use Xaraya\Routing\RouterInterface;

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
 * Summary of get_router
 * @param mixed $restHandler
 * @return RouterInterface
 */
function get_router($restHandler)
{
    //$cacheFile = sys::varpath() . '/cache/api/restapi_fastroute.php';
    // @todo move away from static methods for context
    $router = new FastRouter($restHandler::getRoutes(...));
    //$cacheFile = sys::varpath() . '/cache/api/url_matching_routes.php';
    // @todo move away from static methods for context
    //$router = new Routing($restHandler::getRoutes(...));
    return $router;
}

/**
 * Summary of handle_request
 * @param string $method
 * @param string $path
 * @param RouterInterface $router
 * @param mixed $restHandler
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
        $router = get_router($restHandler);
        handle_request(xarServer::getVar('REQUEST_METHOD'), xarServer::getVar('PATH_INFO'), $router, $restHandler);
    }
}

//try_builder();
// @todo move away from static methods for context
$restHandler = DataObjectRESTHandler::class;
try_handler($restHandler);
