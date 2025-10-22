<?php

/**
 * Classes for handling GraphQL queries
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
 * @package core\bridge
 * @subpackage graphql
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Bridge\GraphQL;

use Xaraya\Bridge\GraphQL\Types\BuildType;
use Xaraya\Bridge\GraphQL\Types\GraphQLObjects;
use Xaraya\Bridge\GraphQL\Types\GraphQLTypes;
use Xaraya\Caching\CacheInterface;
use Xaraya\Caching\CacheTrait;
use Xaraya\Tools\TimerInterface;
use Xaraya\Tools\TimerTrait;
use Xaraya\Bridge\Requests\CommonRequestInterface;
use Xaraya\Bridge\Requests\CommonRequestTrait;
use Xaraya\Context\ContextFactory;
use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use Xaraya\Context\Context;
use Xaraya\Services\xar;
use GraphQL\GraphQL;
use GraphQL\Error\DebugFlag;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Validator\Rules;
use GraphQL\Validator\DocumentValidator;
use xarObject;
use sys;
use Exception;
use FunctionNotFoundException;

sys::import('xaraya.bridge.requests.requesttrait');

/**
 * See xardocs/graphql.txt for class structure
 * @uses \sys::autoload()
 */
class GraphQLHandler extends xarObject implements CommonRequestInterface, ContextInterface, CacheInterface, TimerInterface
{
    use CommonRequestTrait;
    use ContextTrait;
    use TimerTrait;  // activate with self::enableTimer(true)
    use CacheTrait;  // activate with self::enableCache(true)

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
     * Utility function to execute a GraphQL query and get the data
     * @param string $queryString
     * @param mixed $variableValues
     * @param ?string $operationName
     * @param ?array<string> $extraTypes
     * @param ?string $schemaFile
     * @return mixed
     */
    public function getData($queryString = '{schema}', $variableValues = [], $operationName = null, $extraTypes = [], $schemaFile = null)
    {
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
        $graphQLBuilder = new GraphQLBuilder();
        //$schemaFile = self::$schemaFile;  // if we want to test buildSchema without using $schemaFile in gql.php
        if (!empty($schemaFile) && file_exists($schemaFile)) {
            // @checkme try out default object field resolver instead of type config decorator
            $schema = $graphQLBuilder->buildSchema($schemaFile, $extraTypes);
            //$fieldResolver = null;
            // @checkme don't use type classes by default for BuildSchema?
            //$fieldResolver = BuildType::default_field_resolver();
            $fieldResolver = BuildType::default_field_resolver(false);
        } else {
            $schema = $graphQLBuilder->getSchema($extraTypes);
            $fieldResolver = null;
        }
        self::setTimer('schema');
        if ($queryString == '{schema}') {
            return $graphQLBuilder->printSchema($schema);
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
            $this->getContext(),
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
        //if (self::$tracePath) {
        //    $extensions['paths'] = self::$paths;
        //}
        if ($this->context->enableTrace()) {
            $extensions['paths'] = $this->context->getTrace();
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
     * @return void
     */
    public function output($data)
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
     * @param string $message
     * @param mixed $infoPath
     * @return void
     */
    public static function tracePath($message, $infoPath = null)
    {
        if (!self::$tracePath) {
            return;
        }
        if (isset($infoPath)) {
            self::$paths[] = $infoPath;
        }
        self::$paths[] = $message;
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
        // load config before setting the context
        $this->loadConfig();
        // $request from RoutingBridge overrides any existing context here
        if (isset($request)) {
            $context = ContextFactory::fromRequest($request, __METHOD__);
        } else {
            $context = $this->getContext() ?? ContextFactory::fromGlobals(__METHOD__);
        }
        $context['mediatype'] = '';
        $context->enableTrace(self::$tracePath);
        // @todo check if we already have a context? (via request or from elsewhere)
        $this->setContext($context);
        // set context for core services here too
        xar::setServicesContext($context);
        $result = $this->getData($query, $variables, $operationName);
        if ($query == '{schema}') {
            $context['mediatype'] = 'text/plain';
            if (!empty($request)) {
                $request = $request->withAttribute('mediaType', 'text/plain');
            }
        }
        return [$result, $context];
    }

    /**
     * Summary of callHandler - different processing for GraphQL API - see gql.php
     * @param mixed $handler
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return mixed
     */
    public function callHandler($handler, $vars, &$request = null)
    {
        if (!is_array($handler)) {
            throw new FunctionNotFoundException($handler::class, 'Invalid handler #(1)');
        }
        if (!is_a($handler[0], $this::class, true)) {
            throw new FunctionNotFoundException($handler[0], 'Invalid handler #(1)');
        }
        return $this->handleRequest($vars, $request);
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
        return GraphQLObjects::hasSecurity($object, $method);
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
            GraphQLTypes::setExtraTypes(self::$config['extraTypes']);
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
    }
}
