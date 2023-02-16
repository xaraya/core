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

require dirname(dirname(__DIR__)).'/vendor/autoload.php';
sys::init();
xarCache::init();
xarCore::xarInit(xarCore::SYSTEM_USER);
// @checkme we need to set at least the $basurl here
xarServer::setBaseURL('https://owncloud.mikespub.net/test/');

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
// use some PSR-7 factory and PSR-15 dispatcher
use Nyholm\Psr7\Factory\Psr17Factory;
// use Xaraya PSR-15 compatible middleware(s)
use Xaraya\Bridge\Middleware\FastRouteHandler;
use Xaraya\Bridge\Middleware\StaticFileMiddleware;
use Xaraya\Bridge\Middleware\SingleSessionMiddleware;

// @todo find some way to re-use React\Http\Message\Response
$psr17Factory = new Psr17Factory();

// the Xaraya PSR-15 request handler + middleware here
$fastrouted = new FastRouteHandler($psr17Factory);

$logger = function (ServerRequestInterface $request, callable $next): ResponseInterface {
    echo date('Y-m-d H:i:s') . ' ' . $request->getMethod() . ' ' . $request->getUri() . PHP_EOL;
    return $next($request);
};

// add Xaraya static file middleware here too - unless they're already handled by web server or reverse proxy up-front
$files = new StaticFileMiddleware($psr17Factory);
$static = function (ServerRequestInterface $request, callable $next) use ($files): ResponseInterface {
    return $files->process($request, $next);
};

$onesession = new SingleSessionMiddleware();

// See https://github.com/php-pm/php-pm/blob/master/src/ProcessSlave.php to set server environment
$handler = function (ServerRequestInterface $request) use ($fastrouted) {
    // setting this makes xarServer::getCurrentURL() work again, but we need to set PATH_INFO too for getBaseURI()
    $requestUri = $request->getRequestTarget();
    xarServer::setVar('REQUEST_URI', $requestUri);
    xarServer::setVar('PATH_INFO', explode('?', $requestUri)[0]);
    return $fastrouted->handle($request);
};

$http = new React\Http\HttpServer(
    $logger,
    $static,
    $onesession,
    $handler
);

$http->on('error', function (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    if ($e->getPrevious() !== null) {
        echo 'Previous: ' . $e->getPrevious()->getMessage() . PHP_EOL;
    }
});

$socket = new React\Socket\SocketServer('0.0.0.0:8080');
$http->listen($socket);

echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;
