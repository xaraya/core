<?php
/**
 * Entrypoint for handling GraphQL queries on Dynamic Data Objects (POC)
 *
 * Note: this assumes you install graphql-php with composer
 * and use composer autoload in the entrypoint, see e.g. gql.php
 *
 * $ composer require --dev webonyx/graphql-php
 * $ head html/gql.php
 * <?php
 * ...
 * require dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * https://webonyx.github.io/graphql-php/getting-started/
 * https://github.com/webonyx/graphql-php/tree/master/examples/01-blog
 */
require dirname(__DIR__).'/vendor/autoload.php';

// use the GraphQL PHP library here
use GraphQL\GraphQL;

// initialize bootstrap
sys::init();
// initialize caching
xarCache::init();
// initialize database
xarDatabase::init();
// initialize modules
//xarMod::init();
// initialize users
//xarUser::init();

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

//$query = '{hello}';
//$query = 'query { echo(message: "Hello World") }';
//$query = '{samples { id, name, age } }';
//$query = '{samples { name, age } }';
//$query = '{sample(id: 0) { name, age } }';
//$query = '{schema}';
//xarGraphQL::$trace_path = true;
//xarGraphQL::$enableTimer = true;
//xarGraphQL::$cache_plan = true;
//xarGraphQL::$cache_data = true;
//xarGraphQL::$enableCache = true;
//$data = xarGraphQL::get_data($query, $variables, $operationName);
$extraTypes = ['module', 'theme', 'category', 'configuration'];
//$extraTypes = ['module', 'theme', 'category', 'configuration', 'categories_linkage', 'extra', 'api_film', 'api_people', 'api_planet', 'api_species', 'api_starship', 'api_vehicle'];
$configFile = sys::varpath() . '/cache/api/graphql_config.json';
if (file_exists($configFile)) {
    $contents = file_get_contents($configFile);
    $configData = json_decode($contents, true);
    if (!empty($configData) && !empty($configData['extraTypes'])) {
        $extraTypes = $configData['extraTypes'];
    }
}
$data = xarGraphQL::get_data($query, $variables, $operationName, $extraTypes);
//$schemaFile = __DIR__ . '/code/modules/dynamicdata/class/graphql/schema.graphql';
//$data = xarGraphQL::get_data($query, $variables, $operationName, $extraTypes, $schemaFile);

xarGraphQL::send_data($data);
