<?php

/**
 * Experiment with routing bridges for use with other dispatchers
 */

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
chdir(dirname(__DIR__, 3) . '/html');

// use some routing bridge
use Xaraya\Bridge\Routing\RoutingBridge;
use Xaraya\Bridge\Routing\RoutingApiBridge;
use Xaraya\Bridge\Routing\RoutingStaticBridge;

sys::init();
xarCache::init();
// try out request context class - can't with PSR-17 ::fromGlobals()
//xarServer::setRequestClass(\Xaraya\Context\RequestContext::class);
// try out session context class
xarSession::setSessionClass(\Xaraya\Context\SessionContext::class);
xarCore::xarInit(xarCore::SYSTEM_USER);

// Concatenate and parse string into $_GET: php fastroute.php /object/sample ...
if (php_sapi_name() === 'cli') {
    //parse_str(implode('&', array_slice($argv, 1)), $_GET);
    if ($argc > 1 && str_contains($argv[1], '/')) {
        xarServer::setVar('PATH_INFO', $argv[1]);
        //xarServer::setVar('REQUEST_URI', $argv[0] . $argv[1]);
    }
}

// or direct use of simple route dispatcher
//$bridge = new RoutingBridge();
//[$result, $context] = $bridge->dispatchRequest(xarServer::getVar('REQUEST_METHOD'), xarServer::getVar('PATH_INFO') ?? '/');
//echo $result;
//echo xarTpl::renderPage($result);
//$bridge->run();

// or direct use of simple route dispatcher
$wrapPage = false;
$bridge = new RoutingBridge($wrapPage);
[$result, $context] = $bridge->dispatchRequest(xarServer::getVar('REQUEST_METHOD') ?? 'GET', xarServer::getVar('PATH_INFO') ?? '/');
$transform = function ($result) {
    // strip html comments from templates
    return preg_replace('/\s*<!--.*?-->\s*/s', '', $result);
};
$bridge->output($result, $context, $transform);
