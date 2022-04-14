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
//sys::import('xaraya.caching.cachetrait');
//sys::import('modules.dynamicdata.class.timertrait');

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Error\DebugFlag;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaPrinter;
use GraphQL\Type\Definition\Type;

use GraphQL\Validator\Rules;
use GraphQL\Validator\DocumentValidator;

/**
 * See xardocs/graphql.txt for class structure
 */
class xarGraphQL extends xarObject
{
    use xarCacheTrait;
    use xarTimerTrait;

    public static $endpoint = 'gql.php';
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
        'token'    => 'tokentype',
        'serial'   => 'serialtype',
        'mixed'    => 'mixedtype',
        'mutation' => 'mutationtype',
        //'node'     => 'nodetype',
        //'ddnode'   => 'ddnodetype',
    ];
    public static $extra_types = [];
    public static $trace_path = false;
    public static $paths = [];
    public static $query_plan = null;
    public static $type_fields = [];
    public static $cache_plan = false;
    public static $cache_data = false;
    public static $object_type = [];
    public static $queryComplexity = 0;
    public static $queryDepth = 0;
    public static $tokenExpires = 12 * 60 * 60;  // 12 hours
    public static $storageType = 'database';  // database or apcu
    public static $tokenStorage;
    public static $userId;
    public static $objectSecurity = [];

    /**
     * Get GraphQL Schema with Query type and typeLoader
     */
    public static function get_schema($extraTypes = null, $validate = false)
    {
        if (!empty($extraTypes)) {
            self::$extra_types = $extraTypes;
        }
        self::map_objects();
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

    public static function map_objects()
    {
        foreach (self::$type_mapper as $name => $type) {
            $clazz = self::get_type_class($type);
            if (property_exists($clazz, '_xar_object') && !empty($clazz::$_xar_object)) {
                self::$object_type[$clazz::$_xar_object] = $name;
                if (property_exists($clazz, '_xar_security') && isset($clazz::$_xar_security)) {
                    self::$objectSecurity[$clazz::$_xar_object] = $clazz::$_xar_security;
                }
            }
        }
        $clazz = self::get_type_class("buildtype");
        foreach (self::$extra_types as $type) {
            [$name, $type, $object, $list, $item] = $clazz::sanitize($type);
            self::$object_type[$object] = $name;
        }
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
        $name = strtolower($name);
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
            'tokentype' => xarGraphQLTokenType::class,
            'serialtype' => xarGraphQLSerialType::class,
            'mixedtype' => xarGraphQLMixedType::class,
            'mutationtype' => xarGraphQLMutationType::class,
            //'nodetype' => xarGraphQLNodeType::class,
            //'ddnodetype' => xarGraphQLDDNodeType::class,
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

    /**
     * Utility function to execute a GraphQL query and get the data
     */
    public static function get_data($queryString = '{schema}', $variableValues = [], $operationName = null, $extraTypes = [], $schemaFile = null)
    {
        if (self::$trace_path) {
            self::$enableTimer = true;
        }
        if (self::$cache_plan || self::$cache_data) {
            self::$enableCache = true;
        }
        if (self::$enableCache) {
            $cacheScope = 'GraphQLAPI.QueryPlan';
            self::setCacheScope($cacheScope);
        }
        self::setTimer('start');
        if (!empty($schemaFile)) {
            $schema = self::build_schema($schemaFile, $extraTypes);
        } else {
            $schema = self::get_schema($extraTypes);
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
        self::setTimer('query');
        //$serializableResult = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
        $serializableResult = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE);
        self::setTimer('array');
        if (self::$cache_data && self::hasCacheKey()) {
            $cacheKey = self::getCacheKey();
            if (self::isCached($cacheKey)) {
                $serializableResult = self::getCached($cacheKey);
                self::setTimer('cache');
            } else {
                self::setCached($cacheKey, $serializableResult);
            }
        }
        $extensions = [];
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
            //$data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            $data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            $data = json_last_error_msg();
        }
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=utf-8');
        echo $data;
    }

    public static function checkUser($context)
    {
        $userId = self::checkToken($context['server']);
        if (!empty($userId)) {
            return $userId;
        }
        return self::checkCookie($context['server']);
    }

    public static function checkToken($serverVars)
    {
        if (empty($serverVars) || empty($serverVars['HTTP_X_AUTH_TOKEN'])) {
            return;
        }
        if (self::$trace_path) {
            self::$paths[] = "checkToken";
        }
        $token = $serverVars['HTTP_X_AUTH_TOKEN'];
        if (empty($token) || !(self::getTokenStorage()->isCached($token))) {
            return;
        }
        $userInfo = self::getTokenStorage()->getCached($token);
        if (!empty($userInfo)) {
            $userInfo = @json_decode($userInfo, true);
            if (!empty($userInfo['userId']) && ($userInfo['created'] > (time() - self::$tokenExpires))) {
                return $userInfo['userId'];
            }
        }
    }

    public static function checkCookie($serverVars)
    {
        if (empty($serverVars) || empty($serverVars['HTTP_COOKIE'])) {
            return;
        }
        if (self::$trace_path) {
            self::$paths[] = "checkCookie";
        }
        xarSession::init();
        //xarUser::init();
        if (!xarUser::isLoggedIn()) {
            return;
        }
        return xarSession::getVar('role_id');
    }

    public static function createToken($userInfo)
    {
        if (function_exists('random_bytes')) {
            $token = bin2hex(random_bytes(32));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $token = bin2hex(openssl_random_pseudo_bytes(32));
        } else {
            return;
        }
        // @checkme clean up cachestorage occasionally based on size
        self::getTokenStorage()->sizeLimitReached();
        self::getTokenStorage()->setCached($token, json_encode($userInfo));
        return $token;
    }

    public static function deleteToken($serverVars)
    {
        if (empty($serverVars) || empty($serverVars['HTTP_X_AUTH_TOKEN'])) {
            return;
        }
        $token = $serverVars['HTTP_X_AUTH_TOKEN'];
        if (empty($token) || !(self::getTokenStorage()->isCached($token))) {
            return;
        }
        self::getTokenStorage()->delCached($token);
    }

    public static function getTokenStorage()
    {
        if (!isset(self::$tokenStorage)) {
            //self::loadConfig();
            // @checkme access cachestorage directly here
            self::$tokenStorage = xarCache::getStorage([
                'storage' => self::$storageType,
                'type' => 'token',
                'expire' => self::$tokenExpires,
                'sizelimit' => 2000000,
            ]);
        }
        return self::$tokenStorage;
    }

    public static function hasSecurity($object, $method = null)
    {
        return !empty(self::$objectSecurity[$object]) ? true : false;
    }

    public static function dump_schema($extraTypes = null, $storage = 'database', $expires = 12 * 60 * 60, $complexity = 0, $depth = 0, $timer = false, $trace = false, $cache = false, $plan = false, $data = false)
    {
        $configFile = sys::varpath() . '/cache/api/graphql_config.json';
        $configData = [];
        $configData['generated'] = date('c');
        $configData['caution'] = 'This file is updated when you rebuild the schema.graphql document in Dynamic Data - Utilities - Test APIs';
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
        file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));
        $schemaFile = sys::varpath() . '/cache/api/schema.graphql';
        $content = '"""GraphQL Endpoint: ' . xarServer::getBaseURL() . self::$endpoint . '"""' . "\n";
        $content .= self::get_data('{schema}', [], null, $extraTypes);
        $content .= "\n" . '"""Generated: ' . date('c') . '"""';
        file_put_contents($schemaFile, $content);
    }
}
