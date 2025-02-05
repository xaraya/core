<?php
/**
 * GraphQLTest
 */

/**
 * Xaraya GraphQL
 */

namespace GraphQL\Client;

use Xaraya\Bridge\GraphQL\GraphQLBuilder;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Schema;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;

/**
 * GraphQLTest Class Doc Comment
 */
class GraphQLTest extends TestCase
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var string
     */
    protected static $endpoint;

    /**
     * @var string|null
     */
    protected static $token = null;

    /**
     * @var Schema|null
     */
    protected static $schema = null;

    /**
     * Setup before running any test cases
     */
    public static function setUpBeforeClass(): void
    {
        $schemaFile = dirname(__DIR__, 3) . '/html/var/cache/api/schema.graphql';
        if (file_exists($schemaFile)) {
            $contents = file_get_contents($schemaFile);
            $pattern = '/# GraphQL Endpoint: (\S+)/';
            preg_match($pattern, $contents, $matches);
            if (!empty($matches) && !empty($matches[1])) {
                self::$endpoint = $matches[1];
            }
            $graphQLBuilder = new GraphQLBuilder();
            self::$schema = $graphQLBuilder->buildSchema($schemaFile);
        }
        chdir(__DIR__);
    }

    /**
     * Setup before running each test case
     */
    public function setUp(): void
    {
        $this->client = $this->client ?: new Client();
    }

    /**
     * Clean up after running each test case
     */
    public function tearDown(): void {}

    /**
     * Clean up after running all test cases
     */
    public static function tearDownAfterClass(): void {}

    protected static function getQueryFields()
    {
        $queries = [];
        if (empty(self::$schema)) {
            return $queries;
        }
        if (!is_dir(__DIR__ . '/queries')) {
            mkdir(__DIR__ . '/queries');
        }
        // @see https://github.com/mikespub-org/seblucas-cops/blob/main/tests/GraphQLHandlerTest.php
        $queryType = self::$schema->getQueryType();
        /** @var FieldDefinition $field */
        foreach ($queryType->getVisibleFields() as $name => $field) {
            $queries[$name] = $field;
            if ($field->getType() instanceof ListOfType) {
                $responsetype = $field->getType()->getWrappedType();
                $typeName = $responsetype->toString();
                echo "$name: [$typeName] " . $field->description . "\n";
                $operation = 'get' . ucfirst($name);
                $fileName = __DIR__ . '/queries/' . $name . '.graphql';
                // @todo
                $fields = implode("\n    ", ['id']);
                $query = <<<GRAPHQL
query $operation {
  $name {
    __typename
    $fields
  }
}
GRAPHQL;
                //if (!file_exists($fileName)) {
                //    file_put_contents($fileName, $query);
                //}
                $vars = [];
                $params = [
                    'query' => $query,
                    'operationName' => $operation,
                    'variables' => $vars,
                ];
                $fileName = __DIR__ . '/queries/' . $name . '.query.json';
                //if (!file_exists($fileName)) {
                //    file_put_contents($fileName, json_encode($params, JSON_PRETTY_PRINT));
                //}
                continue;
            } elseif (str_ends_with($name, '_page')) {
                // @todo
            }
            $responsetype = $field->getType();
            $typeName = $responsetype->toString();
            echo "$name: $typeName " . $field->description . "\n";
            $operation = 'get' . ucfirst($name);
            $query = '';
            $vars = [];
            /** @var Argument $arg */
            foreach ($field->args as $arg) {
                if ($arg->isRequired()) {
                    echo "  " . $arg->name . " " . $arg->getType()->toString() . "\n";
                } else {
                    // ...
                }
            }
            $fileName = __DIR__ . '/queries/' . $name . '.graphql';
            //if (!file_exists($fileName)) {
            //    file_put_contents($fileName, $query);
            //}
            $params = [
                'query' => $query,
                'operationName' => $operation,
                'variables' => $vars,
            ];
            $fileName = __DIR__ . '/queries/' . $name . '.query.json';
            //if (!file_exists($fileName)) {
            //    file_put_contents($fileName, json_encode($params, JSON_PRETTY_PRINT));
            //}
        }
        return $queries;
    }

    public function testQueries()
    {
        $queries = self::getQueryFields();
        //var_dump($queries);
        $expected = 36;
        $this->assertCount($expected, $queries);
    }

    /**
     * Get API auth token
     */
    public function getAuthToken()
    {
        if (!empty(self::$token)) {
            return self::$token;
        }
        $tokenFile = 'login.result.json';
        if (!file_exists($tokenFile)) {
            $this->testQueryFiles("login.graphql", "login.query.json", "login.result.json");
        }
        $contents = file_get_contents($tokenFile);
        $result = json_decode($contents, true);
        if (empty($result) || empty($result['data']) || empty($result['data']['getToken']) || $result['data']['getToken']['expiration'] < date('c')) {
            unlink($tokenFile);
            $this->testQueryFiles("login.graphql", "login.query.json", "login.result.json");
            $contents = file_get_contents($tokenFile);
            $result = json_decode($contents, true);
        }
        self::$token = $result['data']['getToken']['access_token'];
        return self::$token;
    }

    /**
     * Data provider for testQueryFiles
     */
    public static function provideQueryFiles()
    {
        return [
            "Get schema" => [],
            // "Check login" => ["login.graphql", "login.query.json", "login.result.json"],
            "Test whoami" => ["whoami.graphql", "", "whoami.result.json", true],
            "Test samples with filter" => ["samples.graphql", "samples.query.json", "samples.result.json"],
            "Test objects" => ["objects.graphql", "", "objects.result.json", true],
        ];
    }

    /**
     * Test case for query files
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideQueryFiles')]
    public function testQueryFiles($queryFile = "", $bodyFile = "", $resultFile = "", $authToken = false)
    {
        //$this->markTestIncomplete('Not implemented');
        if (!empty($queryFile) && file_exists($queryFile)) {
            $query = file_get_contents($queryFile);
        } else {
            $query = "{schema}";
        }

        if (!empty($bodyFile) && file_exists($bodyFile)) {
            $contents = file_get_contents($bodyFile);
            $body = json_decode($contents, true);
            if ($body['query'] !== $query) {
                $body['query'] = $query;
                file_put_contents($bodyFile, json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        } else {
            $variables = null;
            $operation = null;
            $body = [
                'query' => $query,
                'variables' => $variables,
                'operationName' => $operation,
            ];
        }

        $httpBody = json_encode($body);
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];
        if (!empty($authToken)) {
            $headers['X-Auth-Token'] = $this->getAuthToken();
        }

        if (!empty($resultFile) && file_exists($resultFile)) {
            $contents = file_get_contents($resultFile);
            $expected = json_decode($contents, true);
        } elseif ($query === "{schema}") {
            $schemaFile = dirname(__DIR__, 3) . '/html/var/cache/api/schema.graphql';
            $contents = file_get_contents($schemaFile);
            $expected = implode("\n", array_slice(explode("\n", $contents), 2));
        } else {
            $expected = [
                'data' => [],
            ];
        }

        $result = true;

        try {
            $response = $this->client->request('POST', self::$endpoint, ['headers' => $headers, 'body' => $httpBody]);
            $content = (string) $response->getBody();
            if ($query !== "{schema}") {
                $result = json_decode($content, true);
                // print_r($result);
                if (is_array($result) and !empty($result['extensions'])) {
                    unset($result['extensions']);
                }
                if (empty($resultFile) && !empty($queryFile)) {
                    $resultFile = str_replace(".graphql", ".result.json", $queryFile);
                }
                if (!empty($resultFile) && !file_exists($resultFile)) {
                    file_put_contents($resultFile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
                }
            } else {
                $result = $content;
            }
        } catch (\Exception $e) {
            echo 'Exception when calling GraphQLTest->testQueryFiles: ', $e->getMessage(), PHP_EOL;
        }

        $this->assertEquals($expected, $result);
    }
}
