<?php

/**
 * Entrypoint for handling GraphQL queries
 *
 * Note: this assumes you install graphql-php with composer
 * and use composer autoload in the entrypoint, see e.g. gql.php
 *
 * $ composer require --dev webonyx/graphql-php
 * $ head html/gql.php
 * <?php
 * ...
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * https://webonyx.github.io/graphql-php/getting-started/
 * https://github.com/webonyx/graphql-php/tree/master/examples/01-blog
 * @uses \sys::autoload()
 */
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Xaraya\Bridge\GraphQL\GraphQLHandler;
use Xaraya\Services\xar;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    GraphQLHandler::sendCORSOptions();
    return;
}

// initialize bootstrap
sys::init();
// initialize caching
xar::cache()->init();
// initialize database
xar::db()->init();
// initialize modules
//xar::mod()->init();
// initialize users
//xar::user()->init();

/**
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $input = json_decode($rawInput, true);
    $query = isset($input['query']) ? $input['query'] : '{schema}';
    $variables = isset($input['variables']) ? $input['variables'] : null;
    $operationName = isset($input['operationName']) ? $input['operationName'] : null;
} else {
    $query = isset($_REQUEST['query']) ? $_REQUEST['query'] : '{schema}';
    $variables = isset($_REQUEST['variables']) ? $_REQUEST['variables'] : null;
    $operationName = isset($_REQUEST['operationName']) ? $_REQUEST['operationName'] : null;
}
// /gql.php?query=query($id:ID!){object(id:$id){name}}&variables={"id":"2"}
if (!empty($variables) && is_string($variables)) {
    $variables = json_decode($variables, true);
}
$context = new \Xaraya\Context\Context(['request' => $_REQUEST, 'server' => $_SERVER]);

//$query = '{hello}';
//$query = 'query { echo(message: "Hello World") }';
//$query = '{samples { id, name, age } }';
//$query = '{samples { name, age } }';
//$query = '{sample(id: 0) { name, age } }';
//$query = '{schema}';
//GraphQLHandler::$tracePath = true;
//GraphQLHandler::enableTimer(true);
//GraphQLHandler::$cachePlan = true;
//GraphQLHandler::$cacheData = true;
//GraphQLHandler::enableCache(true);
$data = GraphQLHandler::getData($query, $variables, $operationName);
//$extraTypes = ['module', 'theme', 'category', 'configuration'];
//$data = GraphQLHandler::getData($query, $variables, $operationName, $extraTypes);
//$schemaFile = __DIR__ . '/code/modules/dynamicdata/class/graphql/schema.graphql';
//$data = GraphQLHandler::getData($query, $variables, $operationName, $extraTypes, $schemaFile);
 */
$graphQLHandler = new GraphQLHandler();
[$data, $context] = $graphQLHandler->handleRequest();
$graphQLHandler->output($data);
