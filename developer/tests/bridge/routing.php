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
use Xaraya\Services\xar;

sys::init();
// try out request context class
xar::req()->setRequestClass(\Xaraya\Context\RequestContext::class);
// try out session context class
xar::session()->setSessionClass(\Xaraya\Context\SessionContext::class);
$xar = xar::load(xarCore::SYSTEM_USER);

// Concatenate and parse string into $_GET: php routing.php /object/sample ...
if (php_sapi_name() === 'cli') {
    //parse_str(implode('&', array_slice($argv, 1)), $_GET);
    if ($argc > 1 && str_contains($argv[1], '/')) {
        $xar->req()->setServerVar('PATH_INFO', $argv[1]);
        //$xar->req()->setServerVar('REQUEST_URI', $argv[0] . $argv[1]);
    }
}

// or direct use of simple route dispatcher
//$bridge = new RoutingBridge();
//[$result, $context] = $bridge->dispatchRequest($xar->req()->getServerVar('REQUEST_METHOD'), $xar->req()->getServerVar('PATH_INFO') ?? '/');
//echo $result;
//echo $xar->tpl()->renderPage($result);
//$bridge->run();

// or direct use of simple route dispatcher
$wrapPage = false;
$bridge = new RoutingBridge($wrapPage);
[$result, $context] = $bridge->dispatchRequest($xar->req()->getServerVar('REQUEST_METHOD') ?? 'GET', $xar->req()->getServerVar('PATH_INFO') ?? '/');
$transform = function ($result) {
    // strip html comments from templates
    return preg_replace('/\s*<!--.*?-->\s*/s', '', $result);
};
$bridge->output($result, $context, $transform);
