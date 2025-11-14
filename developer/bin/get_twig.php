<?php

/**
 * Get Twig environment for Twiggy extension
 * @see https://github.com/moetelo/twiggy/issues/52
 */
//if (php_sapi_name() !== 'cli') {
//    echo 'Test Script for Twig Validator tests';
//    return;
//}

$baseDir = dirname(__DIR__, 2);
require_once $baseDir . '/vendor/autoload.php';
use Xaraya\Bridge\TemplateEngine\TwigConfig;
use Xaraya\Services\xar;
chdir($baseDir . '/html');

// initialize bootstrap
sys::init();
// initialize database to call xar::mod()->apiFunc() for namespaces
xar::cache()->init();
xar::db()->init();

// return Twig environment
return TwigConfig::getTwigEnvironment();
