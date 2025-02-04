<?php

/**
 * @package core\bridge
 * @subpackage test
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Bridge\Test;

if (!class_exists('Xaraya\Bridge\Test\TestClient')) {
    include_once dirname(__DIR__) . '/testclient.php';
}

/**
 * Class to test GraphQL queries
 */
class GraphQLClient extends TestClient
{
    protected string $endpoint = 'http://localhost/xaraya/gql.php';

    /**
     * Summary of __construct
     * @param ?string $schema GraphQL schema
     * @param ?string $endpoint GraphQL endpoint
     */
    public function __construct(?string $schema = null, ?string $endpoint = null)
    {
        if (empty($schema)) {
            $schema = $this->getSchemaFile();
        }
        if (!empty($endpoint)) {
            $this->endpoint = $endpoint;
        } elseif (!empty($schema) && file_exists($schema)) {
            $contents = file_get_contents($schema);
            $matches = [];
            if (preg_match('/^# GraphQL Endpoint: (.+)$/', $contents, $matches)) {
                $this->endpoint = $matches[1];
            }
        }
    }

    public function getSchemaFile(): string
    {
        return dirname(__DIR__, 4) . '/var/cache/api/schema.graphql';
    }

    public function getOperationName(string $operation): string
    {
        return 'get' . ucfirst($operation);
    }

    public function getQueryString(string $operation): string
    {
        $query = 'query ' . $this->getOperationName($operation) . " {\n";
        $query .= "  $operation {\n";
        $query .= "    id\n";
        $query .= "    name\n";
        $query .= "    __typename\n";
        //$query .= "    keys\n";
        $query .= "  }\n";
        $query .= "}\n";
        return $query;
    }

    /**
     * Send GraphQL query
     * @param array<mixed> $variables
     * @return array<string, mixed>
     */
    public function query(string $operationName, string $query, array $variables = [])
    {
        $data = [
            'operationName' => $operationName,
            'query' => $query,
            'variables' => $variables,
        ];
        $output = $this->post('', $data);
        $result = json_decode($output, true);
        return $result;
    }

    /**
     * Build GraphQL query operation
     * @param array<mixed> $params
     * @return array<string, mixed>
     */
    public function operation(string $operation, array $params = [])
    {
        $operationName = $this->getOperationName($operation);
        $query = $this->getQueryString($operation);
        return $this->query($operationName, $query, $params);
    }
}

/**
 * Summary of graphql_client
 * @param int $argc
 * @param array<mixed> $argv
 * @return void
 */
function graphql_client($argc, $argv)
{
    [$operation, $params] = parse_cli_arguments($argc, $argv);
    var_dump($operation);
    $client = new GraphQLClient();
    if (!empty($operation)) {
        $result = $client->operation($operation, $params);
        echo json_encode($result, JSON_PRETTY_PRINT);
        // $client->login($user, $pass);  // or
        // $client->setAuthToken('...');
        // echo $client->post('', ['query' => 'query {...}']);
    } else {
        // ...
    }
}

/**
 * Usage:
 * ```
 * $ php graphql_client.php samples
 * ```
 */
if (php_sapi_name() === 'cli') {
    graphql_client($argc, $argv);
}
