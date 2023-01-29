<?php
/**
 * Try out the combined request handler with ReactPHP (work in progress)
 */

require dirname(__DIR__).'/vendor/autoload.php';
sys::init();
xarCache::init();
xarCore::xarInit(xarCore::SYSTEM_USER);
// @checkme we need to set at least the $basurl here
xarServer::$baseurl = 'https://owncloud.mikespub.net/test/';

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
// use some PSR-7 factory and PSR-15 dispatcher
use Nyholm\Psr7\Factory\Psr17Factory;
// use Xaraya PSR-15 compatible middleware(s)
use Xaraya\Bridge\Middleware\DefaultMiddleware;
use Xaraya\Bridge\Middleware\FastRouteHandler;
use Xaraya\Bridge\Middleware\StaticFileMiddleware;

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

// See https://github.com/php-pm/php-pm/blob/master/src/ProcessSlave.php to set server environment
$handler = function (ServerRequestInterface $request) use ($fastrouted) {
    $message = "Request: " . $request->getUri() . PHP_EOL;
    $server = DefaultMiddleware::getServerParams($request);
    $message .= 'Server:<pre>' . var_export($server, true) . '</pre>' . PHP_EOL;
    $cookies = DefaultMiddleware::getCookieParams($request);
    $message .= 'Cookies:<pre>' . var_export($cookies, true) . '</pre>' . PHP_EOL;
    //echo $message;
    return $fastrouted->handle($request);
};

$http = new React\Http\HttpServer(
    $logger,
    $static,
    $handler
);

$http->on('error', function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    if ($e->getPrevious() !== null) {
        echo 'Previous: ' . $e->getPrevious()->getMessage() . PHP_EOL;
    }
});

$socket = new React\Socket\SocketServer('0.0.0.0:8080');
$http->listen($socket);

echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;
