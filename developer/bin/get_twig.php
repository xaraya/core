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
chdir($baseDir . '/html');

// initialize bootstrap
sys::init();
// initialize database to call xarMod::apiFunc() for namespaces
xarCache::init();
xarDatabase::init();

// return Twig environment
return xarTwigTpl::getTwigEnvironment();
