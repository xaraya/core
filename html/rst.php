<?php

/**
 * Entrypoint for handling REST API calls
 *
 * Note: this assumes you install symfony/routing with composer
 * and use composer autoload in the entrypoint, see e.g. rst.php
 *
 * $ composer require --dev symfony/routing symfony/config
 * $ head html/rst.php
 * <?php
 * ...
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * @see https://github.com/nikic/FastRoute
 * @see https://github.com/symfony/routing
 * @uses \sys::autoload()
 */
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Xaraya\Bridge\RestAPI\RestAPIBuilder;
use Xaraya\Bridge\RestAPI\RestAPIHandler;
use Xaraya\Services\xar;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    RestAPIHandler::sendCORSOptions();
    return;
}

// initialize bootstrap
sys::init();
// get Xaraya Services Class
$xar = xar::getServicesClass();
// initialize caching - delay until we need results
//$xar->cache()->init();
// initialize database - delay until caching fails
//$xar->db()->init();
// initialize modules
//$xar->mod()->init();
// initialize users
//$xar->user()->init();

/**
 * Summary of try_builder
 * @return void
 */
function try_builder()
{
    RestAPIBuilder::init();
    $objects = RestAPIBuilder::get_objects();
    //RestAPIBuilder::create_openapi();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($objects, JSON_PRETTY_PRINT);
}

//try_builder();

// Get RestAPI handler
$restHandler = new RestAPIHandler($xar);
// $restHandler->enableTimer(true);

// Handle request
$req = $xar->req();
$method = $req->getServerVar('REQUEST_METHOD') ?? 'GET';
$path = $req->getServerVar('PATH_INFO') ?? '';
$restHandler->handleRequest($method, $path);
