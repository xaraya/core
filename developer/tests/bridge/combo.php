<?php
/**
 * Make use of the RoutingBridge in routing.php for an all-in-one PSR-15 middleware + requesthandler
 *
 * Note: see also lib/xaraya/bridge/reactphp.php for an example with ReactPHP (not fully functional with links)
 */
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
chdir(dirname(__DIR__, 3) . '/html');

// use some PSR-7 factory and PSR-15 dispatcher
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
// use Xaraya PSR-15 compatible request handler + middleware
use Xaraya\Bridge\Middleware\RoutingHandler;
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
// try out request context class - can't with PSR-17 ::fromGlobals()
//xarServer::setRequestClass(\Xaraya\Context\RequestContext::class);
// try out session context class
xarSession::setSessionClass(\Xaraya\Context\SessionContext::class);
xarCore::xarInit(xarCore::SYSTEM_USER);
LocalTimer::setTimer('core');

// Concatenate and parse string into $_GET: php combo.php /object/sample ...
if (php_sapi_name() === 'cli') {
    //parse_str(implode('&', array_slice($argv, 1)), $_GET);
    if ($argc > 1 && str_contains($argv[1], '/')) {
        xarServer::setVar('PATH_INFO', $argv[1]);
        xarServer::setVar('REQUEST_URI', $argv[0] . $argv[1]);
    }
}

function getRequest($psr17Factory)
{
    $requestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    $request = $requestCreator->fromGlobals();
    LocalTimer::setTimer('request');
    return $request;
}

// get server request from somewhere
$psr17Factory = new Psr17Factory();
$request = getRequest($psr17Factory);

// the Xaraya PSR-15 request handler + middleware here
$combined = new RoutingHandler($psr17Factory);

// handle the request directly, or use as middleware
$response = $combined->handle($request);
LocalTimer::setTimer('run');
$combined->emitResponse($response);
LocalTimer::setTimer('emit');

if (php_sapi_name() === 'cli') {
    //echo "Path: " . $request->getUri()->getPath() . "\n";
    //echo "Request: " . var_export($request, true) . "\n";
    //echo "Response: " . var_export($response, true) . "\n";
    echo "Timers: " . json_encode(LocalTimer::getTimers(), JSON_PRETTY_PRINT);
}
