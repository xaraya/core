<?php

/**
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

use Xaraya\Bridge\GraphQL\Types\GraphQLObjects;
use Xaraya\Bridge\GraphQL\Types\GraphQLTypes;
use Xaraya\Bridge\RestAPI\RestAPIBuilder;
use Xaraya\Services\xar;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQL\Language\Parser;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaPrinter;
use GraphQL\Type\Definition\Type;
use sys;

/**
 * See xardocs/graphql.txt for class structure
 * @uses \sys::autoload()
 */
class GraphQLBuilder
{
    public static string $endpoint = 'gql.php';

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
            GraphQLTypes::setExtraTypes($extraTypes);
        }
        // GraphQLObjects::mapObjects();
        GraphQLObjects::loadObjects();
        // Schema doesn't accept lazy loading of query type (besides typeLoader)
        $queryType = GraphQLTypes::getType("query");
        $mutationType = GraphQLTypes::getType("mutation");

        $schema = new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
            //'types' => [self::getType("ddnode")],  // invisible types
            'typeLoader' => function ($name) {
                return GraphQLTypes::getType($name);
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
        // @todo handle $context for tracePath()
        //$typeConfigDecorator = static function ($typeConfig, $typeDefinitionNode, $allNodesMap) {
        //    return GraphQLTypes::type_config_decorator($typeConfig, $typeDefinitionNode, $allNodesMap);
        //};
        //$schema = BuildSchema::build($contents, $typeConfigDecorator);
        $schema = BuildSchema::build($document);
        return $schema;
    }

    /**
     * Summary of findSelectedList
     * @param ?array<string> $selectedList
     * @return array<mixed>
     */
    public static function findSelectedList($selectedList = null)
    {
        // @checkme get list of modules here before filtering out for $extraTypes - note: dependency on REST API
        $modules = RestAPIBuilder::get_potential_modules($selectedList);
        $extraTypes = GraphQLTypes::findExtraTypes($selectedList);
        return [$modules, $extraTypes];
    }

    /**
     * Summary of dumpSchema
     * @param ?array<string> $selectedList
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
    public function dumpSchema($selectedList = null, $storage = 'database', $expires = 12 * 60 * 60, $complexity = 0, $depth = 0, $timer = false, $trace = false, $cache = false, $plan = false, $data = false, $operation = false)
    {
        [$modules, $extraTypes] = $this->findSelectedList($selectedList);

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
        GraphQLTypes::setExtraTypes($extraTypes);
        $configData['objects'] = GraphQLObjects::dumpObjects();
        file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));

        $configFile = sys::varpath() . '/cache/api/graphql_modules.json';
        $configData = $infoData;
        $configData['modules'] = $modules;
        file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));

        $schemaFile = sys::varpath() . '/cache/api/schema.graphql';
        $schema = $this->getSchema($extraTypes);
        $content = '# GraphQL Endpoint: ' . xar::ctl()->getBaseURL() . self::$endpoint . "\n";
        $content .= '# Generated: ' . date('c') . "\n";
        $content .= $this->printSchema($schema);
        file_put_contents($schemaFile, $content);
    }

    /**
     * Summary of printSchema
     * @param Schema $schema
     * @return string
     */
    public function printSchema($schema)
    {
        $header = "schema {\n  query: Query\n  mutation: Mutation\n}\n\n";
        return $header . SchemaPrinter::doPrint($schema);
        //return SchemaPrinter::printIntrospectionSchema($schema);
    }
}
