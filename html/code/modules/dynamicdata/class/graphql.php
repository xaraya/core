<?php
/**
 * Classes for handling GraphQL queries on Dynamic Data Objects (POC)
 *
 * Note: this assumes you install graphql-php with composer
 * and use composer autoload in the entrypoint, see e.g. gql.php
 *
 * $ composer require webonyx/graphql-php
 * $ head html/gql.php
 * <?php
 * ...
 * require dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
**/

//sys::import('modules.dynamicdata.class.graphql.dummytype');
//sys::import('modules.dynamicdata.class.graphql.querytype');
//sys::import('modules.dynamicdata.class.graphql.sampletype');
//sys::import('modules.dynamicdata.class.graphql.objecttype');
//sys::import('modules.dynamicdata.class.graphql.propertytype');
//sys::import('modules.dynamicdata.class.graphql.accesstype');

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Error\DebugFlag;
use GraphQL\Utils\SchemaPrinter;

class xarGraphQL extends xarObject
{
    public static $type_cache = [];
    public static $type_mapper = [
        'query'    => 'querytype',
        'sample'   => 'sampletype',
        'object'   => 'objecttype',
        'property' => 'propertytype',
        'access'   => 'accesstype',
    ];
    public static $query_mapper = [
        'hello'      => 'dummytype',
        'echo'       => 'dummytype',
        'schema'     => 'dummytype',
        'samples'    => 'sampletype',
        'sample'     => 'sampletype',
        'objects'    => 'objecttype',
        'object'     => 'objecttype',
        'properties' => 'propertytype',
        'property'   => 'propertytype',
    ];

    /**
     * Get GraphQL Schema with Query type
     */
    public static function get_schema($validate=false)
    {
        $queryType = self::get_type("query");

        $schema = new Schema([
            'query' => $queryType,
            'typeLoader' => function ($name) {
                return self::get_type($name);
            }
        ]);

        if ($validate) {
            $schema->assertValid();
        }
        return $schema;
    }

    /**
     * Get GraphQL Type by name
     */
    public static function get_type($name)
    {
        $name = strtolower($name);
        if (isset(self::$type_cache[$name])) {
            return self::$type_cache[$name];
        }
        if (!array_key_exists($name, self::$type_mapper)) {
            throw new Exception("Unknown graphql type: " . $name);
        }
        // lazy loading?
        //return function() {
        //        return self::queryType();
        //};
        $clazz = self::get_type_class(self::$type_mapper[$name]);
        $type = new $clazz();
        if (!$type) {
            throw new Exception("Unknown graphql type: " . $name);
        }
        self::$type_cache[$name] = $type;
        return $type;
    }

    /**
     * Get class where the GraphQL Type is defined
     */
    public static function get_type_class($type)
    {
        static $class_mapper = [
            'querytype' => xarGraphQLQueryType,
            'dummytype' => xarGraphQLDummyType,
            'sampletype' => xarGraphQLSampleType,
            'objecttype' => xarGraphQLObjectType,
            'propertytype' => xarGraphQLPropertyType,
            'accesstype' => xarGraphQLAccessType,
        ];
        return $class_mapper[$type];
    }
 
    /**
     * Get all root query fields for the GraphQL Query type from the query_mapper above
     */
    public static function get_query_fields()
    {
        $fields = array();
        foreach (self::$query_mapper as $name => $type) {
            $fields = array_merge($fields, self::add_query_field($name, $type));
        }
        return $fields;
    }

    /**
     * Add a root query field as defined in the GraphQL Type class
     */
    public static function add_query_field($name, $type)
    {
        return self::get_type_class($type)::_xar_get_query_field($name);
    }

    /**
     * Utility function to execute a GraphQL query and return the data
     */
    public static function get_data($queryString = '{hello}', $variableValues = [])
    {
        $schema = self::get_schema();
        if ($queryString == '{schema}') {
            return SchemaPrinter::doPrint($schema);
            //return SchemaPrinter::printIntrospectionSchema($schema);
        }
        
        $rootValue = ['prefix' => 'You said: message='];
        $context = ['context' => true, 'object' => null];
        $operationName = null;
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

    /**
     * Utility function to send back the data
     */
    public static function send_data($data)
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
}
