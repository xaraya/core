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
 * @version 2.6.2
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
//sys::import('xaraya.caching.cachetrait');
//sys::import('xaraya.tools.timertrait');
sys::import('xaraya.bridge.requests.requesttrait');
use Xaraya\Caching\CacheInterface;
use Xaraya\Caching\CacheTrait;
use Xaraya\Tools\TimerInterface;
use Xaraya\Tools\TimerTrait;
use Xaraya\Bridge\Requests\CommonRequestInterface;
use Xaraya\Bridge\Requests\CommonRequestTrait;
use Xaraya\Context\ContextFactory;
use Xaraya\Context\Context;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
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
 * @uses \sys::autoload()
 */
class xarGraphQL extends xarObject implements CommonRequestInterface, CacheInterface, TimerInterface
{
    use CommonRequestTrait;
    use TimerTrait;  // activate with self::enableTimer(true)
    use CacheTrait;  // activate with self::enableCache(true)

    public static string $endpoint = 'gql.php';
    /** @var array<string, mixed> */
    public static $config = [];
    /** @var string|null */
    public static $schemaFile = null;
    public static bool $tracePath = false;
    /** @var array<string> */
    public static $paths = [];
    /** @var mixed */
    public static $queryPlan = null;
    /** @var array<string, mixed> */
    public static $queryFields = [];
    public static bool $cachePlan = false;
    public static bool $cacheData = false;
    public static bool $cacheOperation = false;
    public static int $queryComplexity = 0;
    public static int $queryDepth = 0;

    /**
     * Get GraphQL Schema with Query type and typeLoader
     * @param ?array<string> $extraTypes
     * @param bool $validate
     * @phpstan-import-type SchemaConfigOptions from SchemaConfig
     * @return Schema
     */
    public function getSchema($extraTypes = null, $validate = false)
    {
        if (!empty($extraTypes)) {
            xarGraphQLTypes::setExtraTypes($extraTypes);
        }
        // xarGraphQLObjects::mapObjects();
        self::loadObjects();
        // Schema doesn't accept lazy loading of query type (besides typeLoader)
        $queryType = xarGraphQLTypes::getType("query");
        $mutationType = xarGraphQLTypes::getType("mutation");

        $schema = new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
            //'types' => [self::getType("ddnode")],  // invisible types
            'typeLoader' => function ($name) {
                return xarGraphQLTypes::getType($name);
            },
        ]);

        if ($validate) {
            $schema->assertValid();
        }
        return $schema;
    }

    /**
     * Build GraphQL Schema based on schema.graphql file and type config decorator
     * @param string $schemaFile
     * @param ?array<string> $extraTypes
     * @param bool $validate
     * @return Schema
     */
    public function buildSchema($schemaFile, $extraTypes = null, $validate = false)
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
        //    return xarGraphQLTypes::type_config_decorator($typeConfig, $typeDefinitionNode, $allNodesMap);
        //};
        //$schema = BuildSchema::build($contents, $typeConfigDecorator);
        $schema = BuildSchema::build($document);
        return $schema;
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
    public function getData($queryString = '{schema}', $variableValues = [], $operationName = null, $extraTypes = [], $schemaFile = null, $context = null)
    {
        $this->loadConfig();
        self::setTimer('start');
        if (!empty($schemaFile)) {
            self::$schemaFile = $schemaFile;
        }
        $cacheOperationKey = null;
        if (self::$cacheOperation) {
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
                if (self::enableTimer()) {
                    $extensions['times'] = self::getTimers();
                }
                if (!empty($extensions)) {
                    $serializableResult['extensions'] = $extensions;
                }
                return $serializableResult;
            }
        }
        //$schemaFile = self::$schemaFile;  // if we want to test buildSchema without using $schemaFile in gql.php
        if (!empty($schemaFile) && file_exists($schemaFile)) {
            // @checkme try out default object field resolver instead of type config decorator
            $schema = $this->buildSchema($schemaFile, $extraTypes);
            //$fieldResolver = null;
            // @checkme don't use type classes by default for BuildSchema?
            //$fieldResolver = xarGraphQLBuildType::default_field_resolver();
            $fieldResolver = xarGraphQLBuildType::default_field_resolver(false);
        } else {
            $schema = $this->getSchema($extraTypes);
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
        if (self::$cacheData && self::hasCacheKey()) {
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
        if (self::$tracePath) {
            $extensions['paths'] = self::$paths;
        }
        self::setTimer('stop');
        if (self::enableTimer()) {
            $extensions['times'] = self::getTimers();
        }
        if (!empty($extensions)) {
            $serializableResult['extensions'] = $extensions;
        }
        if (self::$cacheOperation && !empty($cacheOperationKey)) {
            self::setCached($cacheOperationKey, $serializableResult);
        }
        return $serializableResult;
    }

    /**
     * Utility function to send the data to the browser or app
     * @param mixed $data
     * @param mixed $context
     * @return void
     */
    public function output($data, $context = null)
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
        } catch (Exception) {
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
    public static function sendCORSOptions()
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
     * Summary of dumpQueryPlan
     * @param mixed $plan
     * @return mixed
     */
    public static function dumpQueryPlan($plan)
    {
        if (!is_array($plan)) {
            return $plan;
        }
        $info = [];
        foreach ($plan as $key => $value) {
            if ($key === 'type' && !is_array($value)) {
                $info[$key] = (string) $value;
            } else {
                $info[$key] = self::dumpQueryPlan($value);
            }
        }
        return $info;
    }

    /**
     * Summary of tracePath
     * @param mixed $path
     * @return void
     */
    public static function tracePath($path)
    {
        if (!self::$tracePath) {
            return;
        }
        self::$paths[] = $path;
    }

    /**
     * Summary of hasCachedData
     * @param mixed $queryType
     * @param mixed $rootValue
     * @param mixed $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return bool
     */
    public static function hasCachedData($queryType, $rootValue, $args, $context, ResolveInfo $info)
    {
        if (!empty(self::$queryPlan)) {
            return false;
        }
        self::setTimer('check');
        // disable caching for mutations
        if ($info->operation->operation === 'mutation') {
            self::enableCache(false);
            self::$cachePlan = false;
            self::$cacheData = false;
        }
        $operationName = '';
        if ($info->operation->name) {
            $operationName = $info->operation->name->value;
        }
        $queryPlan = $info->lookAhead();
        self::$queryPlan = $queryPlan;
        self::$queryFields = [];
        foreach ($queryPlan->getReferencedTypes() as $type) {
            self::$queryFields[strtolower($type)] = array_values($queryPlan->subFields($type));
        }
        //self::$paths[] = self::$queryFields;
        $dumpPlan = self::dumpQueryPlan($queryPlan->queryPlan());
        $queryId = $queryType . '-' . md5(json_encode($dumpPlan));
        if (!empty($args) && is_array($args)) {
            ksort($args);
        }
        // @checkme cache query plan + (later) perhaps result based on args
        if (self::$cachePlan) {
            $cacheKey = self::getCacheKey($queryId);
            if (!empty($cacheKey)) {
                if (!self::isCached($cacheKey)) {
                    self::setCached($cacheKey, $dumpPlan);
                }
                if (self::$cacheData) {
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
        if (self::$tracePath) {
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
        if (self::$cacheData && self::hasCacheKey() && self::isCached(self::getCacheKey())) {
            return true;
        }
        return false;
    }

    /**
     * Summary of hasQueryFields
     * @param string $typeName
     * @return bool
     */
    public static function hasQueryFields($typeName)
    {
        return array_key_exists($typeName, self::$queryFields);
    }

    /**
     * Summary of getQueryFields
     * @param string $typeName
     * @return array<mixed>
     */
    public static function getQueryFields($typeName)
    {
        return self::$queryFields[$typeName];
    }

    /**
     * Summary of handleRequest - different processing for GraphQL API - see gql.php
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return mixed
     */
    public function handleRequest($vars = [], &$request = null)
    {
        // dispatcher doesn't provide query params by default
        $params = $this->getQueryParams($request);
        // handle php://input for POST etc.
        $input = $this->getJsonBody($request);
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
        $context = ContextFactory::fromRequest($request, __METHOD__);
        $context['mediatype'] = '';
        $result = $this->getData($query, $variables, $operationName, [], null, $context);
        if ($query == '{schema}') {
            $context['mediatype'] = 'text/plain';
            if (!empty($request)) {
                $request = $request->withAttribute('mediaType', 'text/plain');
            }
        }
        return [$result, $context];
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
        return xarGraphQLObjects::hasSecurity($object, $method);
    }

    /**
     * Summary of loadConfig
     * @return void
     */
    public function loadConfig()
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
            xarGraphQLTypes::setExtraTypes(self::$config['extraTypes']);
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
            self::enableTimer(true);
        }
        if (!empty(self::$config['tracePath'])) {
            self::$tracePath = true;
        }
        if (self::$tracePath) {
            self::enableTimer(true);
        }
        // use xarCacheTrait
        if (!empty(self::$config['enableCache'])) {
            self::enableCache(true);
        }
        if (!empty(self::$config['cachePlan'])) {
            self::$cachePlan = true;
        }
        if (!empty(self::$config['cacheData'])) {
            self::$cacheData = true;
            // this is needed for cache_data to work
            self::$cachePlan = true;
        }
        if (!empty(self::$config['cacheOperation'])) {
            self::$cacheOperation = true;
        }
        if (self::$cachePlan || self::$cacheData || self::$cacheOperation) {
            self::enableCache(true);
        }
        if (self::enableCache()) {
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
     * Summary of getObjects
     * @return array<mixed>
     */
    public static function getObjects()
    {
        return self::$config['objects'];
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
        xarGraphQLObjects::loadObjects(self::$config['objects']);
        self::setTimer('objects');
    }

    /**
     * Summary of getModules
     * @return array<mixed>
     */
    public static function getModules()
    {
        return self::$config['modules'];
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
     * Summary of findExtraTypes
     * @param ?array<string> $objectNames
     * @return array<string>
     */
    public static function findExtraTypes($objectNames = null)
    {
        // @checkme set list of modules here before filtering out for $extraTypes - note: dependency on REST API
        self::$config['modules'] = DataObjectRESTBuilder::get_potential_modules($objectNames);
        return xarGraphQLTypes::findExtraTypes($objectNames);
    }

    /**
     * Summary of dumpSchema
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
    public function dumpSchema($extraTypes = null, $storage = 'database', $expires = 12 * 60 * 60, $complexity = 0, $depth = 0, $timer = false, $trace = false, $cache = false, $plan = false, $data = false, $operation = false)
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
        xarGraphQLTypes::setExtraTypes($extraTypes);
        $configData['objects'] = xarGraphQLObjects::dumpObjects();
        file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));

        $configFile = sys::varpath() . '/cache/api/graphql_modules.json';
        $configData = $infoData;
        $configData['modules'] = self::$config['modules'] ?? [];
        file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));

        $schemaFile = sys::varpath() . '/cache/api/schema.graphql';
        self::$schemaFile = null;
        $content = '# GraphQL Endpoint: ' . xarServer::getBaseURL() . self::$endpoint . "\n";
        $content .= '# Generated: ' . date('c') . "\n";
        $content .= $this->getData('{schema}', [], null, $extraTypes);
        file_put_contents($schemaFile, $content);
    }
}
