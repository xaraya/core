<?php

/**
 * Try out the combined request handler with ReactPHP (work in progress)
 *
 * $ composer require react/http
 * $ cp html/lib/xaraya/bridge/reactphp.php developer/bin/react.php
 * $ php developer/bin/react.php
 * Listening on http://0.0.0.0:8080
 * ...
 *
 * Caution: this does not support sessions or authentication, and is not meant for production (at all)
 */

if (php_sapi_name() !== 'cli') {
    echo "This example can only be launched via command line\n";
    exit;
}

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use React\Promise\Promise;
use Middlewares\Utils\Dispatcher;
// use some PSR-7 factory and PSR-15 dispatcher
use Nyholm\Psr7\Factory\Psr17Factory;
// use Xaraya PSR-15 compatible middleware(s)
use Xaraya\Bridge\Middleware\RoutingHandler;
use Xaraya\Bridge\Middleware\ResponseUtil;
use Xaraya\Bridge\Middleware\StaticFileMiddleware;
use Xaraya\Bridge\Middleware\SingleSessionMiddleware;
use Xaraya\Services\FiberServiceStorage;
use Xaraya\Services\xar;
use Xaraya\Context\Context;

use function React\Async\async;

// initialize bootstrap
sys::init();
$xar = xar::getServicesClass();
$xar->cache()->init();
// try out request context class
$xar->req()->setRequestClass(\Xaraya\Context\RequestContext::class);
// try out session context class
$xar->session()->setSessionClass(\Xaraya\Context\SessionContext::class);
$xar->load(xarCore::SYSTEM_USER);
// @checkme we need to set at least the $basurl here
//xar::ctl()->setBaseURL('https://owncloud.mikespub.net/test/');
$xar->ctl()->setBaseURL('http://localhost:8080/');
$serverVars = $xar->req()->getInstance()->getContext()['server'];
var_dump($serverVars);
// switch to web directory to find library database relative to code()
chdir(sys::web());


// use FiberServiceStorage here
xar::setStorageClass(FiberServiceStorage::class);

// @todo find some way to re-use React\Http\Message\Response
$psr17Factory = new Psr17Factory();

// the Xaraya PSR-15 request handler + middleware here
$combined = new RoutingHandler($psr17Factory);

// add Xaraya static file middleware here too - unless they're already handled by web server or reverse proxy up-front
$files = new StaticFileMiddleware($psr17Factory);

$onesession = new SingleSessionMiddleware();

$responseUtil = new ResponseUtil($psr17Factory);

// The main request handler, wrapped in an async Fiber for each request
$main = async(function (ServerRequestInterface $request) use ($combined, $files, $onesession, $responseUtil, $serverVars): ResponseInterface {
    // 1. Create a request-specific context
    $requestUri = $request->getRequestTarget();
    $context = new Context(['server' => $serverVars]);
    $context['server']['REQUEST_URI'] = $requestUri;
    $context['server']['PATH_INFO'] = explode('?', $requestUri)[0];
    // This now uses FiberServiceStorage because we are inside a Fiber
    $xar = xar::setServicesContext($context);
    echo spl_object_id($xar) . "\n";

    // 2. Define a simple middleware to wrap the final response if needed (e.g. for htmx)
    $wrapper = function (ServerRequestInterface $request, RequestHandlerInterface $next) use ($responseUtil): ResponseInterface {
        $response = $next->handle($request);
        return $responseUtil->wrapResponse($response);
    };

    // 3. Define the middleware stack to be executed inside the Fiber
    $stack = [
        $files,
        $onesession,
        $wrapper,
        // The final handler is the last item in the stack
        $combined,
    ];

    // 4. Create a dispatcher and handle the request through the stack
    $dispatcher = new Dispatcher($stack);
    return $dispatcher->handle($request);
});

// The main entry point for the ReactPHP server
$http = new React\Http\HttpServer(function (ServerRequestInterface $request) use ($main): ResponseInterface|Promise {
    echo date('Y-m-d H:i:s') . ' ' . $request->getMethod() . ' ' . $request->getUri() . PHP_EOL;

    // Execute the main handler. Because it's an `async` function, it returns a Promise.
    // ReactPHP's HttpServer knows how to handle a Promise that resolves to a Response.
    return $main($request);
});

$http->on('error', function (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    if ($e->getPrevious() !== null) {
        echo 'Previous: ' . $e->getPrevious()->getMessage() . PHP_EOL;
    }
});

$socket = new React\Socket\SocketServer('0.0.0.0:8080');
$http->listen($socket);

echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;
