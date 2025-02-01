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
 * Class to test REST API routes
 */
class RestApiClient extends TestClient
{
    protected string $endpoint = 'http://localhost/xaraya/rst.php/v1';

    /**
     * Summary of __construct
     * @param ?string $openapi OpenApi document
     * @param ?string $endpoint REST API endpoint
     */
    public function __construct(?string $openapi = null, ?string $endpoint = null)
    {
        if (empty($openapi)) {
            $openapi = $this->getOpenApiFile();
        }
        if (!empty($endpoint)) {
            $this->endpoint = $endpoint;
        } elseif (!empty($openapi) && file_exists($openapi)) {
            $contents = file_get_contents($openapi);
            $openapi = json_decode($contents, true);
            if (!empty($openapi['servers']) && !empty($openapi['servers'][0]['url'])) {
                $this->endpoint = $openapi['servers'][0]['url'];
            }
        }
    }

    public function getOpenApiFile(): string
    {
        return dirname(__DIR__, 4) . '/var/cache/api/openapi.json';
    }

    /**
     * List modules available for REST API calls by path
     * @return array<string, mixed>
     */
    public function listModules()
    {
        $output = $this->get('/modules');
        $result = json_decode($output, true);
        $modules = [];
        foreach ($result['items'] as $item) {
            $path = substr($item['_links']['self']['href'], strlen($this->endpoint));
            $modules[$path] = $item;
        }
        return $modules;
    }

    /**
     * List data objects available for REST API calls by path
     * @return array<string, mixed>
     */
    public function listObjects()
    {
        $output = $this->get('/objects');
        $result = json_decode($output, true);
        $objects = [];
        foreach ($result['items'] as $item) {
            $path = substr($item['_links']['self']['href'], strlen($this->endpoint));
            $objects[$path] = $item;
        }
        return $objects;
    }

    /**
     * Login to get auth token
     * @param string $uname
     * @param string $pass
     * @param string $access
     * @return array<mixed>
     */
    public function login(string $uname, string $pass, string $access = 'display')
    {
        $data = [
            'uname' => $uname,
            'pass' => $pass,
            'access' => $access,
        ];
        $output = $this->post('/token', $data);
        $result = json_decode($output, true);
        $this->setAuthToken($result['access_token']);
        return $result;
    }

    /**
     * Summary of logout
     * @return string
     */
    public function logout()
    {
        if (empty($this->authToken)) {
            return 'Done.';
        }
        return '@todo DELETE request';
    }
}

/**
 * Summary of restapi_client
 * @param int $argc
 * @param array<mixed> $argv
 * @return void
 */
function restapi_client($argc, $argv)
{
    [$path, $params] = parse_cli_arguments($argc, $argv);
    $client = new RestApiClient();
    if (!empty($path)) {
        echo $client->get($path, $params);
        // $client->login($user, $pass);  // or
        // $client->setAuthToken('...');
        // echo $client->post('/modules/dynamicdata/hello', ['name' => 'testclient']);
    } else {
        $modules = $client->listModules();
        echo "Modules:\n - " . implode("\n - ", array_keys($modules)) . "\n";
        $objects = $client->listObjects();
        echo "Objects:\n - " . implode("\n - ", array_keys($objects)) . "\n";
        // ...
        // $client->login($user, $pass);
        // echo $client->post('/modules/dynamicdata/hello', ['name' => 'testclient']);
    }
}

/**
 * Usage:
 * ```
 * $ php restapiclient.php /objects/sample
 * ```
 */
if (php_sapi_name() === 'cli') {
    restapi_client($argc, $argv);
}
