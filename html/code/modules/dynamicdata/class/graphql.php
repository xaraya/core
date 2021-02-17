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
//sys::import('modules.dynamicdata.class.graphql.keyvaltype');

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Error\DebugFlag;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaPrinter;
use GraphQL\Type\Definition\Type;

/**
 * See xardocs/graphql.txt for class structure
 */
class xarGraphQL extends xarObject
{
    public static $type_cache = [];
    public static $type_mapper = [
        'query'    => 'querytype',
        'sample'   => 'sampletype',
        'object'   => 'objecttype',
        'property' => 'propertytype',
        'access'   => 'accesstype',
        'keyval'   => 'keyvaltype',
        'multival' => 'multivaltype',
        'user'     => 'usertype',
        'serial'   => 'serialtype',
        'mixed'    => 'mixedtype',
        'mutation' => 'mutationtype',
    ];
    public static $extra_types = [];
    public static $trace_path = false;
    public static $paths = [];
    public static $trace_time = false;
    public static $times = [];
    public static $prev_time = 0;
    public static $cache_plan = false;
    public static $cache_data = false;
    public static $cacheScope = 'GraphQLAPI.QueryPlan';
    public static $cacheKey = null;

    /**
     * Get GraphQL Schema with Query type and typeLoader
     */
    public static function get_schema($extraTypes = null, $validate = false)
    {
        if (!empty($extraTypes)) {
            self::$extra_types = $extraTypes;
        }
        $queryType = self::get_type("query");
        $mutationType = self::get_type("mutation");

        $schema = new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
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
        // Schema doesn't accept lazy loading of query type (besides typeLoader)
        if (in_array($name, ['query', 'mutation'])) {
            return self::load_lazy_type($name);
        }
        //if (!self::has_type($name)) {
        //    throw new Exception("Unknown graphql type: " . $name);
        //}
        // See https://github.com/webonyx/graphql-php/pull/557
        return function () use ($name) {
            return self::load_lazy_type($name);
        };
    }

    public static function has_type($name)
    {
        if (in_array($name, self::$extra_types) || array_key_exists($name, self::$type_mapper)) {
            return true;
        }
        return false;
    }

    // 'type' => Type::listOf(xarGraphQL::get_type(static::$_xar_type)), doesn't accept lazy loading
    public static function get_type_list($name)
    {
        $name = strtolower($name);
        // See https://github.com/webonyx/graphql-php/pull/557
        return function () use ($name) {
            return Type::listOf(self::load_lazy_type($name));
        };
    }

    public static function load_lazy_type($name)
    {
        if (isset(self::$type_cache[$name])) {
            return self::$type_cache[$name];
        }
        //self::$paths[] = ['load_lazy_type', $name];
        $page_ext = '_page';
        if (substr($name, -strlen($page_ext)) === $page_ext) {
            return self::get_page_type(substr($name, 0, strlen($name) - strlen($page_ext)));
        }
        $input_ext = '_input';
        if (substr($name, -strlen($input_ext)) === $input_ext) {
            return self::get_input_type(substr($name, 0, strlen($name) - strlen($input_ext)));
        }
        // make Object Type from BuildType for extra dynamicdata object types
        if (in_array($name, self::$extra_types) || in_array(ucfirst($name), self::$extra_types)) {
            $clazz = self::get_type_class("buildtype");
            $type = $clazz::make_type($name);
            if (!$type) {
                throw new Exception("Unknown graphql type: " . $name);
            }
            self::$type_cache[$name] = $type;
            return $type;
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
     * Get GraphQL Type by name with pagination
     */
    public static function get_page_type($name)
    {
        $name = strtolower($name);
        $page = $name . '_page';
        if (isset(self::$type_cache[$page])) {
            return self::$type_cache[$page];
        }
        // make Object Type from BuildType for extra dynamicdata object types
        if (in_array($name, self::$extra_types) || in_array(ucfirst($name), self::$extra_types)) {
            $clazz = self::get_type_class("buildtype");
            $type = $clazz::make_page_type($name);
            if (!$type) {
                throw new Exception("Unknown graphql type: " . $page);
            }
            self::$type_cache[$page] = $type;
            return $type;
        }
        // @todo get paginated Object Type for existing type classes?
        throw new Exception("Unknown graphql type: " . $page);
    }

    /**
     * Get GraphQL Input Type by name
     */
    public static function get_input_type($name)
    {
        $name = strtolower($name);
        $input = $name . '_input';
        if (isset(self::$type_cache[$input])) {
            return self::$type_cache[$input];
        }
        // make Object Type from BuildType for extra dynamicdata object types
        if (in_array($name, self::$extra_types) || in_array(ucfirst($name), self::$extra_types)) {
            $clazz = self::get_type_class("buildtype");
            $type = $clazz::make_input_type($name);
            if (!$type) {
                throw new Exception("Unknown graphql type: " . $input);
            }
            self::$type_cache[$input] = $type;
            return $type;
        }
        if (!array_key_exists($name, self::$type_mapper)) {
            throw new Exception("Unknown graphql type: " . $input);
        }
        $clazz = self::get_type_class(self::$type_mapper[$name]);
        // get input type from existing type class
        $type = $clazz::_xar_get_input_type();
        if (!$type) {
            throw new Exception("Unknown graphql type: " . $input);
        }
        self::$type_cache[$input] = $type;
        return $type;
    }

    /**
     * Get class where the GraphQL Type is defined
     */
    public static function get_type_class($type)
    {
        static $class_mapper = [
            'querytype' => xarGraphQLQueryType::class,
            'dummytype' => xarGraphQLDummyType::class,
            'buildtype' => xarGraphQLBuildType::class,
            'basetype' => xarGraphQLBaseType::class,
            'sampletype' => xarGraphQLSampleType::class,
            'objecttype' => xarGraphQLObjectType::class,
            'propertytype' => xarGraphQLPropertyType::class,
            'accesstype' => xarGraphQLAccessType::class,
            'keyvaltype' => xarGraphQLKeyValType::class,
            'multivaltype' => xarGraphQLMultiValType::class,
            'usertype' => xarGraphQLUserType::class,
            'serialtype' => xarGraphQLSerialType::class,
            'mixedtype' => xarGraphQLMixedType::class,
            'mutationtype' => xarGraphQLMutationType::class,
        ];
        if (!array_key_exists($type, $class_mapper) && array_key_exists($type, self::$type_mapper)) {
            $type = self::$type_mapper[$type];
        }
        // from deferred_field_resolver()
        if (!array_key_exists($type, $class_mapper) && in_array($type, self::$extra_types)) {
            $type = 'basetype';
        }
        return $class_mapper[$type];
    }
 
    /**
     * Build GraphQL Schema based on schema.graphql file and type config decorator
     */
    public static function build_schema($schemaFile, $extraTypes = null, $validate = false)
    {
        $contents = file_get_contents($schemaFile);
        // @todo add extraTypes to schema contents if needed?
        $typeConfigDecorator = function ($typeConfig, $typeDefinitionNode) {
            return self::type_config_decorator($typeConfig, $typeDefinitionNode);
        };
        $schema = BuildSchema::build($contents, $typeConfigDecorator);
        return $schema;
    }

    /**
     * Type config decorator for Query and Object types when using BuildSchema
     */
    public static function type_config_decorator($typeConfig, $typeDefinitionNode)
    {
        $name = $typeConfig['name'];
        //var_dump($name);
        //var_dump(implode(":", array_keys($typeConfig['fields'])));
        //print_r($typeDefinitionNode);
        // https://github.com/diasfs/graphql-php-resolvers/blob/master/src/FieldResolver.php
        // $typeConfig['resolveField'] = function($value, $args, $ctx, $info) use ($resolver) {
        //     return static::ResolveField($value, $args, $ctx, $info, $resolver);
        // };
        $clazz = self::get_type_class("buildtype");
        if ($name == 'Query') {
            $typeConfig['resolveField'] = $clazz::object_query_resolver($name);
        } else {
            $typeConfig['resolveField'] = $clazz::object_field_resolver($name);
        }
        return $typeConfig;
    }

    public static function timer($label)
    {
        if (self::$trace_time) {
            $now = microtime(true);
            if (empty(self::$prev_time)) {
                self::$prev_time = !empty($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : 0;
                self::$times[] = array('request' => self::$prev_time);
            }
            self::$times[] = array($label => ($now - self::$prev_time));
            self::$prev_time = $now;
        }
    }

    /**
     * Utility function to execute a GraphQL query and get the data
     */
    public static function get_data($queryString = '{schema}', $variableValues = [], $operationName = null, $extraTypes = [], $schemaFile = null)
    {
        self::timer('start');
        if (!empty($schemaFile)) {
            $schema = self::build_schema($schemaFile, $extraTypes);
        } else {
            $schema = self::get_schema($extraTypes);
        }
        self::timer('schema');
        if ($queryString == '{schema}') {
            $header = "schema {\n  query: Query\n  mutation: Mutation\n}\n\n";
            return $header . SchemaPrinter::doPrint($schema);
            //return SchemaPrinter::printIntrospectionSchema($schema);
        }
        
        $rootValue = ['prefix' => 'You said: message='];
        $context = ['request' => $_REQUEST, 'server' => $_SERVER];
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
        self::timer('query');
        //$serializableResult = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
        $serializableResult = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE);
        self::timer('array');
        if (self::$cache_data && !empty(self::$cacheKey)) {
            if (xarVariableCache::isCached(self::$cacheKey)) {
                $serializableResult = xarVariableCache::getCached(self::$cacheKey);
                self::timer('cache');
            } else {
                xarVariableCache::setCached(self::$cacheKey, $serializableResult);
            }
        }
        $extensions = array();
        if (self::$trace_path) {
            $extensions['paths'] = self::$paths;
        }
        self::timer('stop');
        if (self::$trace_time) {
            $extensions['times'] = self::$times;
        }
        if (!empty($extensions)) {
            $serializableResult['extensions'] = $extensions;
        }
        return $serializableResult;
    }

    /**
     * Utility function to send the data to the browser or app
     */
    public static function send_data($data)
    {
        if (is_string($data)) {
            //header('Access-Control-Allow-Origin: *');
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

    public static function dump_schema($extraTypes = null)
    {
        $schemaFile = sys::varpath() . '/cache/schema.graphql';
        $content = self::get_data('{schema}', [], null, $extraTypes);
        file_put_contents($schemaFile, $content);
    }
}
