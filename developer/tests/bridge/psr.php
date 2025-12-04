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
use Xaraya\Services\WithServicesClass;
use Xaraya\Services\xar;

class LocalTimer implements TimerInterface
{
    use TimerTrait;  // activate with $this->enableTimer(true)
    use WithServicesClass;
}
$timer = new LocalTimer();

$timer->enableTimer(true);
//$timer->setTimer('autoload');
sys::init();
$timer->setTimer('sys');
// try out request context class
xar::req()->setRequestClass(\Xaraya\Context\RequestContext::class);
// try out session context class
xar::session()->setSessionClass(\Xaraya\Context\SessionContext::class);
$xar = xar::load(xarCore::SYSTEM_BLOCKS);
$timer->setTimer('core');

// Concatenate and parse string into $_GET: php psr.php object=sample ...
if (php_sapi_name() === 'cli') {
    $params = [];
    parse_str(implode('&', array_slice($argv, 1)), $params);
    foreach ($params as $name => $value) {
        $xar->req()->setVar($name, $value);
    }
    $xar->req()->setServerVar('REQUEST_URI', $argv[0]);
    $_GET = $params;
}

function getRequest($psr17Factory)
{
    $requestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    $request = $requestCreator->fromGlobals();
    return $request;
}

function getStack($psr17Factory, $api = false, $wrapPage = false, $timer = null)
{
    // the Xaraya PSR-15 middleware here (with option to wrap output in page)
    if ($api) {
        $objects = new DataObjectApiMiddleware($psr17Factory);
        $modules = new ModuleApiMiddleware($psr17Factory);
    } else {
        $objects = new DataObjectMiddleware($psr17Factory, $wrapPage);
        $modules = new ModuleMiddleware($psr17Factory, $wrapPage);
    }
    $timer->setTimer('middleware');

    // some other middleware before or after...
    $filter = function ($request, $next) use ($modules, $timer) {
        $timer->setTimer('filter_in');
        // @checkme strip baseUrl from request path here?
        $request = $modules->stripBaseUri($request);
        $timer->setTimer('filter_stripped');
        $response = $next->handle($request->withHeader('X-Request-Before', 'Bar'));
        $timer->setTimer('filter_handled');
        return $response->withHeader('X-Response-Before', 'Bar');
    };
    // page wrapper for object requests in response output (if not specified above)
    $responseUtil = new ResponseUtil($psr17Factory);
    $wrapper = function ($request, $next) use ($responseUtil, $timer) {
        $timer->setTimer('wrapper_in');
        $response = $next->handle($request->withAddedHeader('X-Middleware-Seen', 'Wrapper'));
        $timer->setTimer('wrapper_handled');
        $response = $responseUtil->wrapResponse($response);
        $timer->setTimer('wrapper_wrapped');
        return $response->withAddedHeader('X-Middleware-Seen', 'Wrapper');
    };
    // ...
    $notfound = function ($request, $next) use ($timer) {
        $timer->setTimer('notfound_in');
        $response = $next->handle($request->withHeader('X-Request-After', 'Baz'));
        $timer->setTimer('notfound_handled');
        $server = $request->getServerParams();
        $attribs = $request->getAttributes();
        $response->getBody()->write('Nothing to see here: ' . $request->getUri()->getPath() . "\n<pre>" . var_export($server, true) . "</pre>" . "\n<pre>" . var_export($attribs, true) . "</pre>");
        $timer->setTimer('notfound_write');
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
    $timer->setTimer('stack');
    return $stack;
}

// get server request from somewhere
$psr17Factory = new Psr17Factory();
$request = getRequest($psr17Factory);
$timer->setTimer('request');

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
$stack = getStack($psr17Factory, $api, $wrapPage, $timer);

$response = Dispatcher::run($stack, $request);
//$response = $fastroute->handle($request);
$timer->setTimer('run');
ResponseUtil::emitResponse($response);
$timer->setTimer('emit');

if (php_sapi_name() === 'cli') {
    //echo "Path: " . $request->getUri()->getPath() . "\n";
    //echo "Request: " . var_export($request, true) . "\n";
    //echo "Response: " . var_export($response, true) . "\n";
    echo "Timers: " . json_encode($timer->getTimers(), JSON_PRETTY_PRINT);
}
