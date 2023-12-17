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
 * require_once dirname(__DIR__).'/vendor/autoload.php';
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
//sys::import('modules.dynamicdata.class.graphql.inflector');
//sys::import('xaraya.traits.cachetrait');
//sys::import('xaraya.traits.timertrait');
sys::import('xaraya.bridge.requests.requesttrait');
use Xaraya\Core\Traits\CacheInterface;
use Xaraya\Core\Traits\CacheTrait;
use Xaraya\Core\Traits\TimerInterface;
use Xaraya\Core\Traits\TimerTrait;
use Xaraya\Bridge\Requests\CommonRequestInterface;
use Xaraya\Bridge\Requests\CommonRequestTrait;
use Xaraya\Structures\Context;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Error\DebugFlag;
use GraphQL\Language\Parser;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaPrinter;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;

use GraphQL\Validator\Rules;
use GraphQL\Validator\DocumentValidator;

/**
 * See xardocs/graphql.txt for class structure
 */
class xarGraphQL extends xarObject implements CommonRequestInterface, CacheInterface, TimerInterface
{
    use CommonRequestTrait;
    use TimerTrait;  // activate with self::$enableTimer = true
    use CacheTrait;  // activate with self::$enableCache = true

    public static string $endpoint = 'gql.php';
    /** @var array<string, mixed> */
    public static $config = [];
    /** @var string|null */
    public static $schemaFile = null;
    /** @var array<string, mixed> */
    public static $type_cache = [];
    /** @var array<string, string> */
    public static $type_mapper = [
        'query'    => 'querytype',
        'sample'   => 'sampletype',
        'object'   => 'objecttype',
        'property' => 'propertytype',
        'access'   => 'accesstype',
        'keyval'   => 'keyvaltype',
        'multival' => 'multivaltype',
        'user'     => 'usertype',
        'token'    => 'tokentype',
        'serial'   => 'serialtype',
        'mixed'    => 'mixedtype',
        'mutation' => 'mutationtype',
        //'node'     => 'nodetype',
        //'ddnode'   => 'ddnodetype',
        'module_api' => 'moduleapitype',
    ];
    /** @var array<string, string> */
    public static $base_types = [
        'id'      => 'id',
        'string'  => 'string',
        'integer' => 'int',
        'boolean' => 'boolean',
        'number'  => 'float',
    ];
    /** @var array<string> */
    public static $extra_types = [];
    public static bool $trace_path = false;
    /** @var array<string> */
    public static $paths = [];
    /** @var mixed */
    public static $query_plan = null;
    /** @var array<string, mixed> */
    public static $type_fields = [];
    public static bool $cache_plan = false;
    public static bool $cache_data = false;
    public static bool $cache_operation = false;
    /** @var array<string, string> */
    public static $object_type = [];
    public static int $queryComplexity = 0;
    public static int $queryDepth = 0;
    /** @var array<string, mixed> */
    public static $objectSecurity = [];
    /** @var array<string, mixed> */
    public static $objectFieldSpecs = [];
    /** @var array<string, mixed> */
    public static $object_ref = [];

    /**
     * Get GraphQL Schema with Query type and typeLoader
     * @param ?array<string> $extraTypes
     * @param bool $validate
     * @return Schema
     */
    public static function get_schema($extraTypes = null, $validate = false)
    {
        if (!empty($extraTypes)) {
            self::$extra_types = $extraTypes;
        }
        // self::map_objects();
        self::loadObjects();
        // Schema doesn't accept lazy loading of query type (besides typeLoader)
        $queryType = self::get_type("query");
        $mutationType = self::get_type("mutation");

        $schema = new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
            //'types' => [self::get_type("ddnode")],  // invisible types
            'typeLoader' => function ($name) {
                return self::get_type($name);
            },
        ]);

        if ($validate) {
            $schema->assertValid();
        }
        return $schema;
    }

    /**
     * Summary of map_objects
     * @return void
     */
    public static function map_objects()
    {
        if (!empty(self::$object_type)) {
            return;
        }
        foreach (self::$type_mapper as $name => $type) {
            $clazz = self::get_type_class($type);
            if (property_exists($clazz, '_xar_object') && !empty($clazz::$_xar_object)) {
                self::$object_type[$clazz::$_xar_object] = $name;
                if (property_exists($clazz, '_xar_security') && isset($clazz::$_xar_security)) {
                    self::$objectSecurity[$clazz::$_xar_object] = $clazz::$_xar_security;
                }
            }
        }
        foreach (self::$extra_types as $type) {
            [$name, $type, $object] = xarGraphQLInflector::sanitize($type);
            self::$object_type[$object] = $name;
        }
        self::setTimer('mapped');
    }

    /**
     * Get GraphQL Type by name
     * @param string $name
     * @return mixed|void
     */
    public static function get_type($name)
    {
        $name = strtolower($name);
        if (isset(self::$type_cache[$name])) {
            return self::$type_cache[$name];
        }
        // Schema doesn't accept lazy loading of query type or scalar type (besides typeLoader)
        if (in_array($name, ['query', 'mutation', 'mixed', 'serial'])) {
            return self::load_lazy_type($name);
        }
        if (in_array($name, ['subscription'])) {
            return;
        }
        //if (!self::has_type($name)) {
        //    throw new Exception("Unknown graphql type: " . $name);
        //}
        // See https://github.com/webonyx/graphql-php/pull/557
        return static function () use ($name) {
            return self::load_lazy_type($name);
        };
    }

    /**
     * Summary of has_type
     * @param string $name
     * @return bool
     */
    public static function has_type($name)
    {
        $name = strtolower($name);
        if (in_array($name, self::$extra_types) || array_key_exists($name, self::$type_mapper)) {
            return true;
        }
        // @checkme for dynamically created types like the module api input types per function
        if (isset(self::$type_cache[$name])) {
            return true;
        }
        return false;
    }

    /**
     * @checkme for dynamically created types like the module api input types per function
     * Summary of set_type
     * @param string $name
     * @param mixed $type
     * @return void
     */
    public static function set_type($name, $type)
    {
        $name = strtolower($name);
        self::$type_cache[$name] = $type;
    }

    /**
     * Summary of get_type_list
     * 'type' => Type::listOf(xarGraphQL::get_type(static::$_xar_type)), doesn't accept lazy loading
     * @param string $name
     * @return Closure
     */
    public static function get_type_list($name)
    {
        $name = strtolower($name);
        // See https://github.com/webonyx/graphql-php/pull/557
        return static function () use ($name) {
            // return Type::listOf(self::get_type($name));
            return Type::listOf(self::load_lazy_type($name));
        };
    }

    /**
     * Summary of get_input_type_list
     * 'type' => Type::listOf(xarGraphQL::get_input_type(static::$_xar_type)), doesn't accept lazy loading
     * @param string $name
     * @return Closure
     */
    public static function get_input_type_list($name)
    {
        $name = strtolower($name);
        $input = $name . '_input';
        // See https://github.com/webonyx/graphql-php/pull/557
        return static function () use ($name) {
            // return Type::listOf(self::load_lazy_type($input));
            return Type::listOf(self::get_input_type($name));
        };
    }

    /**
     * Summary of load_lazy_type
     * @param string $name
     * @throws \Exception
     * @return mixed
     */
    public static function load_lazy_type($name)
    {
        if (isset(self::$type_cache[$name])) {
            return self::$type_cache[$name];
        }
        // @checkme use openapi data types and/or graphql base types + see buildtype get_field_basetypes()
        if (array_key_exists($name, self::$base_types)) {
            return Type::{self::$base_types[$name]}();
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
            $type = xarGraphQLBuildType::make_type($name);
            if (!$type) {
                throw new Exception("Unknown graphql type: " . $name);
            }
            self::$type_cache[$name] = $type;
            return $type;
        }
        if (!array_key_exists($name, self::$type_mapper)) {
            throw new Exception("Unknown graphql type: " . $name);
        }
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
     * @param string $name
     * @throws \Exception
     * @return mixed
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
            $type = xarGraphQLBuildType::make_page_type($name);
            if (!$type) {
                throw new Exception("Unknown graphql type: " . $page);
            }
            self::$type_cache[$page] = $type;
            return $type;
        }
        if (!array_key_exists($name, self::$type_mapper)) {
            throw new Exception("Unknown graphql type: " . $page);
        }
        $clazz = self::get_type_class(self::$type_mapper[$name]);
        // get page type from existing type class
        $type = $clazz::_xar_get_page_type($page);
        if (!$type) {
            throw new Exception("Unknown graphql type: " . $page);
        }
        self::$type_cache[$page] = $type;
        return $type;
    }

    /**
     * Get GraphQL Input Type by name
     * @param string $name
     * @throws \Exception
     * @return mixed
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
            $type = xarGraphQLBuildType::make_input_type($name);
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
        $type = $clazz::_xar_get_input_type($input);
        if (!$type) {
            throw new Exception("Unknown graphql type: " . $input);
        }
        self::$type_cache[$input] = $type;
        return $type;
    }

    /**
     * Get class where the GraphQL Type is defined
     * @param string $type
     * @return string
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
            'tokentype' => xarGraphQLTokenType::class,
            'serialtype' => xarGraphQLSerialType::class,
            'mixedtype' => xarGraphQLMixedType::class,
            'mutationtype' => xarGraphQLMutationType::class,
            //'nodetype' => xarGraphQLNodeType::class,
            //'ddnodetype' => xarGraphQLDDNodeType::class,
            'moduleapitype' => xarGraphQLModuleApiType::class,
        ];
        if (!array_key_exists($type, $class_mapper) && array_key_exists($type, self::$type_mapper)) {
            $type = self::$type_mapper[$type];
        }
        // from deferred_field_resolver()
        if (!array_key_exists($type, $class_mapper) && in_array($type, self::$extra_types)) {
            $type = 'basetype';
        }
        // from deferred_field_resolver() for unknown type e.g. category
        if (!array_key_exists($type, $class_mapper)) {
            $type = 'basetype';
        }
        return $class_mapper[$type];
    }

    /**
     * Build GraphQL Schema based on schema.graphql file and type config decorator
     * @param string $schemaFile
     * @param ?array<string> $extraTypes
     * @param bool $validate
     * @return Schema
     */
    public static function build_schema($schemaFile, $extraTypes = null, $validate = false)
    {
        $parsedFile = $schemaFile . '_parsed.php';
        if (file_exists($parsedFile) && filemtime($parsedFile) > filemtime($schemaFile)) {
            $document = AST::fromArray(require $parsedFile);  // fromArray() is a lazy operation as well
        } else {
            $document = Parser::parse(file_get_contents($schemaFile));
            file_put_contents($parsedFile, "<?php\nreturn " . var_export(AST::toArray($document), true) . ";\n");
        }
        // @todo add extraTypes to schema contents if needed?
        //$typeConfigDecorator = static function ($typeConfig, $typeDefinitionNode, $allNodesMap) {
        //    return self::type_config_decorator($typeConfig, $typeDefinitionNode, $allNodesMap);
        //};
        //$schema = BuildSchema::build($contents, $typeConfigDecorator);
        $schema = BuildSchema::build($document);
        return $schema;
    }

    /**
     * Type config decorator for Query and Object types when using BuildSchema
     * @param array<string, mixed> $typeConfig
     * @param mixed $typeDefinitionNode
     * @param mixed $allNodesMap
     * @return mixed
     */
    public static function type_config_decorator($typeConfig, $typeDefinitionNode, $allNodesMap)
    {
        $name = $typeConfig['name'];
        // https://github.com/diasfs/graphql-php-resolvers/blob/master/src/FieldResolver.php
        // $typeConfig['resolveField'] = function($value, $args, $ctx, $info) use ($resolver) {
        //     return static::ResolveField($value, $args, $ctx, $info, $resolver);
        // };
        // @checkme forget about trying to override individual field resolve functions here - use fieldspecs later
        if (self::has_type($name)) {
            $type = strtolower($name);
            //$clazz = self::get_type_class($type);
            //if ($clazz !== "xarGraphQLBaseType" && method_exists($clazz, "_xar_get_type_config")) {
            //    self::$paths[] = "type config $name defined in $clazz";
            //    $classConfig = $clazz::_xar_get_type_config($name);
            //    //return $classConfig;
            //}
        }
        // @todo skip this and override default field resolver in executeQuery, or use one in basetype?
        if ($name == 'Query') {
            self::$paths[] = "query config $name";
            //$fields = $typeConfig['fields']();
            //self::$paths[] = "query config fields " . implode(',', array_keys($fields));
            //$typeConfig['fields'] = static function () use ($name) {
            //    $typeDef = xarGraphQLBuildType::object_type_definition($name);
            //    //return $typeDef->getFields();
            //    return $typeDef;
            //};
            // @checkme not possible to override page/list/item resolvers in child class by type here
            $typeConfig['resolveField'] = xarGraphQLBuildType::_xar_query_field_resolver($name);
        } elseif ($name == 'Mutation') {
            self::$paths[] = "mutation config $name";
            // @checkme not possible to override create/update/delete resolvers in child class by type here
            $typeConfig['resolveField'] = xarGraphQLBuildType::_xar_mutation_field_resolver($name);
        } else {
            self::$paths[] = "type config $name";
            //$typeConfig['fields'] = static function () use ($name) {
            //    $typeDef = xarGraphQLBuildType::object_type_definition($name);
            //    return $typeDef->getFields();
            //};
            $typeConfig['resolveField'] = xarGraphQLBuildType::object_type_resolver($name);
        }
        return $typeConfig;
    }

    /**
     * Utility function to execute a GraphQL query and get the data
     * @param string $queryString
     * @param mixed $variableValues
     * @param ?string $operationName
     * @param ?array<string> $extraTypes
     * @param ?string $schemaFile
     * @param mixed $context
     * @return mixed
     */
    public static function get_data($queryString = '{schema}', $variableValues = [], $operationName = null, $extraTypes = [], $schemaFile = null, $context = null)
    {
        self::loadConfig();
        self::setTimer('start');
        if (!empty($schemaFile)) {
            self::$schemaFile = $schemaFile;
        }
        $cacheOperationKey = null;
        if (self::$cache_operation) {
            $queryId = md5($queryString) . '-' . ($operationName ?? 'null');
            if (!empty($variableValues) && is_array($variableValues)) {
                ksort($variableValues);
            }
            if (!empty($variableValues)) {
                $queryId .= '-' . md5(json_encode($variableValues));
            } else {
                $queryId .= '-empty';
            }
            $cacheOperationKey = self::getCacheKey($queryId);
            if (!empty($cacheOperationKey) && self::isCached($cacheOperationKey)) {
                $serializableResult = self::getCached($cacheOperationKey);
                $extensions = [];
                $extensions['cached'] = self::keyCached($cacheOperationKey);
                // $extensions['cached'] = true;
                self::setTimer('cache');
                if (self::$enableTimer) {
                    $extensions['times'] = self::getTimers();
                }
                if (!empty($extensions)) {
                    $serializableResult['extensions'] = $extensions;
                }
                return $serializableResult;
            }
        }
        //$schemaFile = self::$schemaFile;  // if we want to test build_schema without using $schemaFile in gql.php
        if (!empty($schemaFile) && file_exists($schemaFile)) {
            // @checkme try out default object field resolver instead of type config decorator
            $schema = self::build_schema($schemaFile, $extraTypes);
            //$fieldResolver = null;
            // @checkme don't use type classes by default for BuildSchema?
            //$fieldResolver = xarGraphQLBuildType::default_field_resolver();
            $fieldResolver = xarGraphQLBuildType::default_field_resolver(false);
        } else {
            $schema = self::get_schema($extraTypes);
            $fieldResolver = null;
        }
        self::setTimer('schema');
        if ($queryString == '{schema}') {
            $header = "schema {\n  query: Query\n  mutation: Mutation\n}\n\n";
            return $header . SchemaPrinter::doPrint($schema);
            //return SchemaPrinter::printIntrospectionSchema($schema);
        }

        // Add to standard set of rules globally (values from GraphQL Playground IntrospectionQuery)
        if (!empty(self::$queryComplexity)) {
            DocumentValidator::addRule(new Rules\QueryComplexity(self::$queryComplexity));  // 181
        }
        if (!empty(self::$queryDepth)) {
            DocumentValidator::addRule(new Rules\QueryDepth(self::$queryDepth));  // 11
        }
        // DocumentValidator::addRule(new Rules\DisableIntrospection());

        $rootValue = ['prefix' => 'You said: message='];
        //$context ??= ['server' => $_SERVER, 'cookie' => $_COOKIE];
        // $fieldResolver = null;
        $validationRules = null;
        $validationRules = [];
        // $validationRules = array_merge(
        //     GraphQL::getStandardValidationRules(),
        //     [
        //         // new Rules\QueryComplexity(self::$queryComplexity),
        //         // new Rules\QueryDepth(self::$queryDepth),
        //         // new Rules\DisableIntrospection()
        //     ]
        // );

        self::setTimer('ready');
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
        self::setTimer('query');
        //$serializableResult = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
        $serializableResult = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE);
        self::setTimer('array');
        $extensions = [];
        if (self::$cache_data && self::hasCacheKey()) {
            $cacheKey = self::getCacheKey();
            if (self::isCached($cacheKey)) {
                $serializableResult = self::getCached($cacheKey);
                $extensions['cached'] = self::keyCached($cacheKey);
                // $extensions['cached'] = true;
                self::setTimer('cache');
            } else {
                self::setCached($cacheKey, $serializableResult);
            }
        }
        if (self::$trace_path) {
            $extensions['paths'] = self::$paths;
        }
        self::setTimer('stop');
        if (self::$enableTimer) {
            $extensions['times'] = self::getTimers();
        }
        if (!empty($extensions)) {
            $serializableResult['extensions'] = $extensions;
        }
        if (self::$cache_operation && !empty($cacheOperationKey)) {
            self::setCached($cacheOperationKey, $serializableResult);
        }
        return $serializableResult;
    }

    /**
     * Utility function to send the data to the browser or app
     * @param mixed $data
     * @return void
     */
    public static function output($data)
    {
        if (is_string($data)) {
            //header('Access-Control-Allow-Origin: *');
            header('Content-Type: text/plain; charset=utf-8');
            echo $data;
            return;
        }
        try {
            // @checkme GraphQL playground doesn't like JSON_NUMERIC_CHECK for introspection, e.g. default value for offset = 0 instead of "0"
            //$data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            $data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            $data = json_last_error_msg();
        }
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=utf-8');
        echo $data;
    }

    /**
     * Send CORS options to the browser in preflight checks
     * @return void
     */
    public static function send_cors_options()
    {
        // See https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
        http_response_code(204);
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        // @checkme X-Apollo-Tracing is used in the GraphQL Playground
        header('Access-Control-Allow-Headers: X-Auth-Token, Content-Type, X-Apollo-Tracing');
        // header('Access-Control-Allow-Credentials: true');
    }

    /**
     * Summary of dump_query_plan
     * @param mixed $plan
     * @return mixed
     */
    public static function dump_query_plan($plan)
    {
        if (!is_array($plan)) {
            return $plan;
        }
        $info = [];
        foreach ($plan as $key => $value) {
            if ($key === 'type' && !is_array($value)) {
                $info[$key] = (string) $value;
            } else {
                $info[$key] = self::dump_query_plan($value);
            }
        }
        return $info;
    }

    /**
     * Summary of has_cached_data
     * @param mixed $queryType
     * @param mixed $rootValue
     * @param mixed $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return bool
     */
    public static function has_cached_data($queryType, $rootValue, $args, $context, ResolveInfo $info)
    {
        if (!empty(self::$query_plan)) {
            return false;
        }
        self::setTimer('check');
        // disable caching for mutations
        if ($info->operation->operation === 'mutation') {
            self::$enableCache = false;
            self::$cache_plan = false;
            self::$cache_data = false;
        }
        $operationName = '';
        if ($info->operation->name) {
            $operationName = $info->operation->name->value;
        }
        $queryPlan = $info->lookAhead();
        self::$query_plan = $queryPlan;
        self::$type_fields = [];
        foreach ($queryPlan->getReferencedTypes() as $type) {
            self::$type_fields[strtolower($type)] = array_values($queryPlan->subFields($type));
        }
        //self::$paths[] = self::$type_fields;
        $dumpPlan = self::dump_query_plan($queryPlan->queryPlan());
        $queryId = $queryType . '-' . md5(json_encode($dumpPlan));
        if (!empty($args) && is_array($args)) {
            ksort($args);
        }
        // @checkme cache query plan + (later) perhaps result based on args
        if (self::$cache_plan) {
            $cacheKey = self::getCacheKey($queryId);
            if (!empty($cacheKey)) {
                if (!self::isCached($cacheKey)) {
                    self::setCached($cacheKey, $dumpPlan);
                }
                if (self::$cache_data) {
                    // @checkme add current arguments to cacheKey to cache results
                    if (!empty($args)) {
                        $cacheKey .= '-' . md5(json_encode($args));
                    } else {
                        $cacheKey .= '-result';
                    }
                    self::setCacheKey($cacheKey);
                }
            }
        }
        if (self::$trace_path) {
            self::$paths[] = [
                'queryId' => $queryId,
                'queryType' => $queryType,
                'queryPlan' => $dumpPlan,
                'operationName' => $operationName,
                'rootValue' => $rootValue,
                'args' => $args,
            ];
        }
        self::setTimer('plan');
        // @checkme don't try to resolve anything further if the result is already cached?
        if (self::$cache_data && self::hasCacheKey() && self::isCached(self::getCacheKey())) {
            return true;
        }
        return false;
    }

    /**
     * Summary of handleRequest - different processing for GraphQL API - see gql.php
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return mixed
     */
    public static function handleRequest($vars = [], &$request = null)
    {
        // dispatcher doesn't provide query params by default
        $params = static::getQueryParams($request);
        // handle php://input for POST etc.
        $input = static::getJsonBody($request);
        if (!empty($input)) {
            $query = $input['query'] ?? '{schema}';
            $variables = $input['variables'] ?? null;
            $operationName = $input['operationName'] ?? null;
        } else {
            $query = $params['query'] ?? '{schema}';
            $variables = $params['variables'] ?? null;
            $operationName = $params['operationName'] ?? null;
        }
        // /gql.php?query=query($id:ID!){object(id:$id){name}}&variables={"id":"2"}
        if (!empty($variables) && is_string($variables)) {
            $variables = json_decode($variables, true);
        }
        $context = new Context();
        $context['server'] = static::getServerParams($request);
        $context['cookie'] = static::getCookieParams($request);
        if (!empty($request)) {
            $context['request'] = &$request;
            $context['requestId'] = $request->getAttribute('requestId');
        } else {
            $context['request'] = null;
        }
        $result = self::get_data($query, $variables, $operationName, [], null, $context);
        if ($query == '{schema}') {
            if (!empty($request)) {
                $request = $request->withAttribute('mediaType', 'text/plain');
            }
        }
        return $result;
    }

    /**
     * Summary of checkUser
     * @param Context<string, mixed> $context
     * @return int
     */
    public static function checkUser($context)
    {
        return $context->getUserId();
    }

    /**
     * Summary of hasSecurity
     * @param string $object
     * @param ?string $method
     * @return bool
     */
    public static function hasSecurity($object, $method = null)
    {
        return !empty(self::$objectSecurity[$object]) ? true : false;
    }

    /**
     * Summary of loadConfig
     * @return void
     */
    public static function loadConfig()
    {
        if (!empty(self::$config)) {
            return;
        }
        self::$config = [];
        $configFile = sys::varpath() . '/cache/api/graphql_config.json';
        if (file_exists($configFile)) {
            $contents = file_get_contents($configFile);
            self::$config = json_decode($contents, true);
        }
        if (!empty(self::$config['extraTypes'])) {
            self::$extra_types = self::$config['extraTypes'];
        }
        if (!empty(self::$config['queryComplexity'])) {
            self::$queryComplexity = self::$config['queryComplexity'];
        }
        if (!empty(self::$config['queryDepth'])) {
            self::$queryDepth = self::$config['queryDepth'];
        }
        /**
        if (!empty(self::$config['tokenExpires'])) {
            AuthToken::$tokenExpires = self::$config['tokenExpires'];
        }
        if (!empty(self::$config['storageType'])) {
            AuthToken::$storageType = self::$config['storageType'];
        }
         */
        // use xarTimerTrait
        if (!empty(self::$config['enableTimer'])) {
            self::$enableTimer = true;
        }
        if (!empty(self::$config['tracePath'])) {
            self::$trace_path = true;
        }
        if (self::$trace_path) {
            self::$enableTimer = true;
        }
        // use xarCacheTrait
        if (!empty(self::$config['enableCache'])) {
            self::$enableCache = true;
        }
        if (!empty(self::$config['cachePlan'])) {
            self::$cache_plan = true;
        }
        if (!empty(self::$config['cacheData'])) {
            self::$cache_data = true;
            // this is needed for cache_data to work
            self::$cache_plan = true;
        }
        if (!empty(self::$config['cacheOperation'])) {
            self::$cache_operation = true;
        }
        if (self::$cache_plan || self::$cache_data || self::$cache_operation) {
            self::$enableCache = true;
        }
        if (self::$enableCache) {
            $cacheScope = 'GraphQLAPI.QueryPlan';
            self::setCacheScope($cacheScope);
        }
        self::$schemaFile = sys::varpath() . '/cache/api/schema.graphql';
        self::setTimer('config');
        // @deprecated for existing _config files before rebuild
        if (!empty(self::$config['objects'])) {
            self::loadObjects(self::$config);
        }
    }

    /**
     * Summary of loadObjects
     * @param array<string, mixed> $config
     * @return void
     */
    public static function loadObjects($config = [])
    {
        if (!empty(self::$config['objects'])) {
            return;
        }
        $configFile = sys::varpath() . '/cache/api/graphql_objects.json';
        if (empty($config) && file_exists($configFile)) {
            $contents = file_get_contents($configFile);
            $config = json_decode($contents, true);
        }
        if (!empty($config['objects'])) {
            self::$config['objects'] = $config['objects'];
        } else {
            self::$config['objects'] = [];
        }
        foreach (self::$config['objects'] as $object => $info) {
            self::$object_type[$object] = $info['name'];
            self::$objectSecurity[$object] = $info['security'];
            self::$objectFieldSpecs[$object] = $info['fieldspecs'] ?? false;
        }
        self::setTimer('objects');
    }

    /**
     * Summary of loadModules
     * @param array<string, mixed> $config
     * @return void
     */
    public static function loadModules($config = [])
    {
        if (!empty(self::$config['modules'])) {
            return;
        }
        $configFile = sys::varpath() . '/cache/api/graphql_modules.json';
        if (empty($config) && file_exists($configFile)) {
            $contents = file_get_contents($configFile);
            $config = json_decode($contents, true);
        }
        if (!empty($config['modules'])) {
            self::$config['modules'] = $config['modules'];
        } else {
            self::$config['modules'] = [];
        }
        self::setTimer('modules');
    }

    /**
     * Summary of find_extra_types
     * @param ?array<string> $objectNames
     * @return array<string>
     */
    public static function find_extra_types($objectNames = null)
    {
        // @checkme set list of modules here before filtering out for $extraTypes - note: dependency on REST API
        self::$config['modules'] = DataObjectRESTBuilder::get_potential_modules($objectNames);
        $extraTypes = [];
        if (!empty($objectNames)) {
            foreach ($objectNames as $name) {
                if (strpos($name, '.') !== false) {
                    continue;
                }
                $type = xarGraphQLInflector::singularize($name);
                if (self::has_type($type)) {
                    continue;
                }
                $extraTypes[] = $type;
            }
        }
        return $extraTypes;
    }

    /**
     * Summary of dump_schema
     * @param ?array<string> $extraTypes
     * @param string $storage
     * @param int $expires
     * @param int $complexity
     * @param int $depth
     * @param bool $timer
     * @param bool $trace
     * @param bool $cache
     * @param bool $plan
     * @param bool $data
     * @param bool $operation
     * @return void
     */
    public static function dump_schema($extraTypes = null, $storage = 'database', $expires = 12 * 60 * 60, $complexity = 0, $depth = 0, $timer = false, $trace = false, $cache = false, $plan = false, $data = false, $operation = false)
    {
        $infoData = [];
        $infoData['generated'] = date('c');
        $infoData['caution'] = 'This file is updated when you rebuild the schema.graphql document in Dynamic Data - Utilities - Test APIs';

        $configFile = sys::varpath() . '/cache/api/graphql_config.json';
        $configData = $infoData;
        $configData['extraTypes'] = $extraTypes;
        $configData['tokenExpires'] = intval($expires);
        $configData['storageType'] = $storage;
        $configData['queryComplexity'] = intval($complexity);
        $configData['queryDepth'] = intval($depth);
        $configData['enableTimer'] = !empty($timer) ? true : false;
        $configData['tracePath'] = !empty($trace) ? true : false;
        $configData['enableCache'] = !empty($cache) ? true : false;
        $configData['cachePlan'] = !empty($plan) ? true : false;
        $configData['cacheData'] = !empty($data) ? true : false;
        $configData['cacheOperation'] = !empty($operation) ? true : false;
        file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));

        $configFile = sys::varpath() . '/cache/api/graphql_objects.json';
        $configData = $infoData;
        $configData['objects'] = [];
        self::$extra_types = $extraTypes;
        self::$object_type = [];
        self::map_objects();
        self::$objectFieldSpecs = [];
        foreach (self::$object_type as $object => $name) {
            $configData['objects'][$object] = [];
            $configData['objects'][$object]['name'] = $name;
            $name = strtolower($name);
            $type = self::$type_mapper[$name] ?? $name;
            $configData['objects'][$object]['type'] = $type;
            $configData['objects'][$object]['security'] = self::$objectSecurity[$object] ?? false;
            $configData['objects'][$object]['class'] = self::get_type_class($type);
            if (!empty(self::$type_mapper[$name])) {
                $configData['objects'][$object]['fieldspecs'] = [];
                $objectType = self::load_lazy_type($name);
                foreach ($objectType->getFields() as $field) {
                    $configData['objects'][$object]['fieldspecs'][$field->getName()] = ['fieldtype', $field->getType()->toString()];
                }
                $fieldspecs = xarGraphQLBuildType::find_object_fieldspecs($object, true);
                foreach ($fieldspecs as $prop_name => $fieldspec) {
                    if (array_key_exists($prop_name, $configData['objects'][$object]['fieldspecs'])) {
                        $configData['objects'][$object]['fieldspecs'][$prop_name] = array_merge($configData['objects'][$object]['fieldspecs'][$prop_name], $fieldspec);
                    } else {
                        $configData['objects'][$object]['fieldspecs'][$prop_name] = $fieldspec;
                    }
                }
            } else {
                $configData['objects'][$object]['maketype'] = true;
            }
        }
        $fieldspecs = [];
        foreach (self::$extra_types as $type) {
            [$name, $type, $object] = xarGraphQLInflector::sanitize($type);
            $fieldspecs[$object] = xarGraphQLBuildType::find_object_fieldspecs($object, true);
        }
        foreach ($fieldspecs as $object => $fieldspec) {
            $configData['objects'][$object]['fieldspecs'] = $fieldspec;
        }
        file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));

        $configFile = sys::varpath() . '/cache/api/graphql_modules.json';
        $configData = $infoData;
        $configData['modules'] = self::$config['modules'] ?? [];
        file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));

        $schemaFile = sys::varpath() . '/cache/api/schema.graphql';
        self::$schemaFile = null;
        $content = '# GraphQL Endpoint: ' . xarServer::getBaseURL() . self::$endpoint . "\n";
        $content .= '# Generated: ' . date('c') . "\n";
        $content .= self::get_data('{schema}', [], null, $extraTypes);
        file_put_contents($schemaFile, $content);
    }
}
