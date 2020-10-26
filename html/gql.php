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

use GraphQL\GraphQL;
use GraphQL\Error\DebugFlag;
use GraphQL\Utils\SchemaPrinter;

// initialize bootstrap
sys::init();
// initialize database
xarDatabase::init();


function xarGraphQLGetData($queryString = '{hello}', $variableValues = [], $operationName = null)
{
    $schema = xarGraphQL::get_schema();
    if ($queryString == '{schema}') {
        return SchemaPrinter::doPrint($schema);
        //return SchemaPrinter::printIntrospectionSchema($schema);
    }
    
    $rootValue = ['prefix' => 'You said: message='];
    $context = ['context' => true, 'object' => null];
    $fieldResolver = null;
    $validationRules = null;
    
    $result = GraphQL::executeQuery(
        $schema,
        $queryString,
        $rootValue,
        $context,
        $variableValues,
        $operationName,
        $fieldResolver,
        $validationRules
    );
    //$serializableResult = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
    $serializableResult = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE);
    return $serializableResult;
}

function xarGraphQLSendData($data)
{
    if (is_string($data)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $data;
        return;
    }
    try {
        $data = json_encode($data, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        $data = json_last_error_msg();
    }
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=utf-8');
    echo $data;
}

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
$data = xarGraphQLGetData($query, $variables, $operationName);

xarGraphQLSendData($data);
