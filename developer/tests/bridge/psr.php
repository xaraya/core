<?php

/**
 * Experiment with PSR-7 and PSR-15 compatible DD controller
 *
 * Note: see also combo.php for experiments with RoutingHandler
 */
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
chdir(dirname(__DIR__, 3) . '/html');

// use some PSR-7 factory and PSR-15 dispatcher
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Middlewares\Utils\Dispatcher;
// use Xaraya PSR-15 compatible middleware(s)
use Xaraya\Bridge\Middleware\DataObjectMiddleware;
use Xaraya\Bridge\Middleware\DataObjectApiMiddleware;
use Xaraya\Bridge\Middleware\ModuleMiddleware;
use Xaraya\Bridge\Middleware\ModuleApiMiddleware;
use Xaraya\Bridge\Middleware\ResponseUtil;
use Xaraya\Tools\TimerInterface;
use Xaraya\Tools\TimerTrait;

class LocalTimer implements TimerInterface
{
    use TimerTrait;  // activate with self::enableTimer(true)
}

LocalTimer::enableTimer(true);
//LocalTimer::setTimer('autoload');
sys::init();
LocalTimer::setTimer('sys');
xarCache::init();
LocalTimer::setTimer('cache');
// try out request context class
xarServer::setRequestClass(\Xaraya\Context\RequestContext::class);
// try out session context class
xarSession::setSessionClass(\Xaraya\Context\SessionContext::class);
xarCore::xarInit(xarCore::SYSTEM_USER);
LocalTimer::setTimer('core');

// Concatenate and parse string into $_GET: php psr.php object=sample ...
if (php_sapi_name() === 'cli') {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
    xarServer::setVar('REQUEST_URI', $argv[0]);
}

function getRequest($psr17Factory)
{
    $requestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    $request = $requestCreator->fromGlobals();
    LocalTimer::setTimer('request');
    return $request;
}

function getStack($psr17Factory, $api = false, $wrapPage = false)
{
    // the Xaraya PSR-15 middleware here (with option to wrap output in page)
    if ($api) {
        $objects = new DataObjectApiMiddleware($psr17Factory);
        $modules = new ModuleApiMiddleware($psr17Factory);
    } else {
        $objects = new DataObjectMiddleware($psr17Factory, $wrapPage);
        $modules = new ModuleMiddleware($psr17Factory, $wrapPage);
    }
    LocalTimer::setTimer('middleware');

    // some other middleware before or after...
    $filter = function ($request, $next) use ($modules) {
        LocalTimer::setTimer('filter_in');
        // @checkme strip baseUrl from request path here?
        $request = $modules->stripBaseUri($request);
        LocalTimer::setTimer('filter_stripped');
        $response = $next->handle($request->withHeader('X-Request-Before', 'Bar'));
        LocalTimer::setTimer('filter_handled');
        return $response->withHeader('X-Response-Before', 'Bar');
    };
    // page wrapper for object requests in response output (if not specified above)
    $responseUtil = new ResponseUtil($psr17Factory);
    $wrapper = function ($request, $next) use ($responseUtil) {
        LocalTimer::setTimer('wrapper_in');
        $response = $next->handle($request->withAddedHeader('X-Middleware-Seen', 'Wrapper'));
        LocalTimer::setTimer('wrapper_handled');
        $response = $responseUtil->wrapResponse($response);
        LocalTimer::setTimer('wrapper_wrapped');
        return $response->withAddedHeader('X-Middleware-Seen', 'Wrapper');
    };
    // ...
    $notfound = function ($request, $next) {
        LocalTimer::setTimer('notfound_in');
        $response = $next->handle($request->withHeader('X-Request-After', 'Baz'));
        LocalTimer::setTimer('notfound_handled');
        $server = $request->getServerParams();
        $attribs = $request->getAttributes();
        $response->getBody()->write('Nothing to see here: ' . $request->getUri()->getPath() . "\n<pre>" . var_export($server, true) . "</pre>" . "\n<pre>" . var_export($attribs, true) . "</pre>");
        LocalTimer::setTimer('notfound_write');
        return $response->withHeader('X-Response-After', 'Baz');
    };

    $stack = [];
    $stack[] = $filter;
    if (!$wrapPage) {
        $stack[] = $wrapper;
    }
    $stack[] = $objects;
    // Warning: we never get here if there's an object to be handled
    $stack[] = $modules;
    // Warning: we never get here if there's a module to be handled
    //$stack[] = $fastroute;
    $stack[] = $notfound;
    LocalTimer::setTimer('stack');
    return $stack;
}

// get server request from somewhere
$psr17Factory = new Psr17Factory();
$request = getRequest($psr17Factory);

/**
$middleware = new class () implements Psr\Http\Server\MiddlewareInterface {
    public function process(
        Psr\Http\Message\ServerRequestInterface $request,
        Psr\Http\Server\RequestHandlerInterface $next
    ): Psr\Http\Message\ResponseInterface {
        //return new Response();
        //$response = new Response();
        $response = $next->handle($request);
        $response->getBody()->write('body');
        return $response;
    }
};
 */

$api = false;
$wrapPage = false;
$stack = getStack($psr17Factory, $api, $wrapPage);

$response = Dispatcher::run($stack, $request);
//$response = $fastroute->handle($request);
LocalTimer::setTimer('run');
ResponseUtil::emitResponse($response);
LocalTimer::setTimer('emit');

if (php_sapi_name() === 'cli') {
    //echo "Path: " . $request->getUri()->getPath() . "\n";
    //echo "Request: " . var_export($request, true) . "\n";
    //echo "Response: " . var_export($response, true) . "\n";
    echo "Timers: " . json_encode(LocalTimer::getTimers(), JSON_PRETTY_PRINT);
}
