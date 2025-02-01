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

/**
 * Class to test HTTP requests
 */
class TestClient
{
    protected string $endpoint = 'http://localhost/xaraya';
    /** @var array<mixed> */
    protected array $headers = [];
    /** @var array<mixed> */
    protected array $options = [
        'user_agent' => 'TestClient/2.6.2',
    ];
    protected string $cookieName = 'XARAYASID';
    protected string $sessionId = '';
    protected string $tokenName = 'X-Auth-Token';
    protected string $authToken = '';

    /**
     * Summary of __construct
     * @param ?string $endpoint server endpoint
     */
    public function __construct(?string $endpoint = null)
    {
        if (!empty($endpoint)) {
            $this->endpoint = $endpoint;
        }
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    /**
     * Set or unset http header
     * @param string $key
     * @param string|null $value
     * @return void
     */
    public function setHeader(string $key, ?string $value)
    {
        if (is_null($value)) {
            unset($this->headers[$key]);
            return;
        }
        $this->headers[$key] = $value;
    }

    /**
     * Set or unset http context option
     * @see https://www.php.net/manual/en/context.http.php
     * @param string $key
     * @param string|null $value
     * @return void
     */
    public function setOption(string $key, ?string $value)
    {
        if (is_null($value)) {
            unset($this->options[$key]);
            return;
        }
        $this->options[$key] = $value;
    }

    /**
     * Set or unset session id
     * @param string|null $sessionId
     * @return void
     */
    public function setSessionId(?string $sessionId = nul)
    {
        if (is_null($sessionId)) {
            $this->sessionId = '';
            return;
        }
        $this->sessionId = $sessionId;
    }

    /**
     * Set or unset auth token
     * @param string|null $authToken
     * @return void
     */
    public function setAuthToken(?string $authToken = nul)
    {
        if (is_null($authToken)) {
            $this->authToken = '';
            return;
        }
        $this->authToken = $authToken;
    }

    /**
     * Summary of get
     * @param string $path
     * @param array<mixed> $params
     * @return string|false
     */
    public function get(string $path, array $params = [])
    {
        if (!ini_get('allow_url_fopen')) {
            echo "Please enable 'allow_url_fopen' in your cli php.ini file\n";
            return false;
        }
        // add params to the path for GET requests
        if (!empty($params)) {
            $path .= str_contains($path, '?') ? '&' : '?';
            $path .= http_build_query($params);
        }
        $url = $this->endpoint . $path;
        // use empty stream context for GET requests
        $context = $this->getStreamContext();
        // GET request
        $contents = file_get_contents($url, false, $context);
        if ($contents === false && !empty($http_response_header)) {
            var_dump($http_response_header); // variable is populated in the local scope
        }
        return $contents;
    }

    /**
     * Summary of post
     * @param string $path
     * @param array<mixed>|string|null $data
     * @param string $format encode array in json (default) or form (url-encoded) format
     * @return string|false
     */
    public function post(string $path, mixed $data = null, string $format = 'json')
    {
        if (!ini_get('allow_url_fopen')) {
            echo "Please enable 'allow_url_fopen' in your cli php.ini file\n";
            return false;
        }
        // add data to stream context for POST requests
        $url = $this->endpoint . $path;
        if (is_array($data) && count($data) > 0) {
            // this assumes Content-Type: application/json for GraphQL or REST API
            if ($format == 'json') {
                $headers = ['Content-Type' => 'application/json'];
                $content = json_encode($data);
            } else {
                $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
                $content = http_build_query($data);
            }
        } elseif (is_string($data)) {
            // $headers = ['Content-Type' => 'application/graphql'];
            $headers = [];
            $content = $data;
        } else {
            $headers = [];
            $content = null;
        }
        $context = $this->getStreamContext('POST', $headers, $content);
        // POST request
        $contents = file_get_contents($url, false, $context);
        if ($contents === false && !empty($http_response_header)) {
            var_dump($http_response_header); // variable is populated in the local scope
        }
        return $contents;
    }

    /**
     * Summary of getStreamContext
     * @param string $method GET, POST, ...
     * @param array<mixed> $headers ['Content-Type' => 'application/json'] or ['Content-Type' => 'application/x-www-form-urlencoded']
     * @param ?string $content json_encode(['hello' => 'world', 'goodbye' => 'earth']) or http_build_query(...)
     * @return resource|null
     */
    protected function getStreamContext(string $method = '', array $headers = [], ?string $content = null)
    {
        $http_options = $this->options;
        if (!empty($method)) {
            $http_options['method'] = strtoupper($method);
        }
        $headers = array_replace($this->headers, $headers);
        if (!empty($this->sessionId)) {
            if (!empty($headers['Cookie'])) {
                $headers['Cookie'] .= '; ' . $this->cookieName . '=' . $this->sessionId;
            } else {
                $headers['Cookie'] = $this->cookieName . '=' . $this->sessionId;
            }
        }
        if (!empty($this->authToken)) {
            $headers[$this->tokenName] = $this->authToken;
        }
        if (!empty($headers)) {
            $http_options['header'] = [];
            foreach ($headers as $key => $value) {
                $http_options['header'][] = "$key: $value";
            }
        }
        if (isset($content)) {
            // this assumes content is pre-formatted
            $http_options['content'] = $content;
        }
        $context_options = ['http' => $http_options];
        $context = stream_context_create($context_options);
        return $context;
    }
}

/**
 * Parse CLI arguments for basic GET requests
 * ```
 * $ php script.php /path param1=value1 param2=value2 ...
 * ```
 * @param mixed $argc
 * @param mixed $argv
 * @return array<mixed>
 */
function parse_cli_arguments($argc, $argv)
{
    $path = '';
    $params = [];
    if ($argc > 1 && str_contains($argv[1], '/')) {
        $path = $argv[1];
        if ($argc > 2) {
            $query = implode('&', array_slice($argv, 2));
            parse_str($query, $params);
        }
    }
    return [$path, $params];
}

/**
 * Summary of test_client
 * @param int $argc
 * @param array<mixed> $argv
 * @return void
 */
function test_client($argc, $argv)
{
    [$path, $params] = parse_cli_arguments($argc, $argv);
    $client = new TestClient();
    if (!empty($path)) {
        echo $client->get($path, $params);
    } else {
        // ...
    }
}

/**
 * Usage:
 * ```
 * $ php testclient.php /ws.php/webhook/test
 * $ php testclient.php /ws.php/webhook/test param1=value1 param2=value2
 * $ php testclient.php '/ws.php/webhook/test?param1=value1&param2=value2'
 * ```
 */
if (php_sapi_name() === 'cli') {
    // test_client($argc, $argv);
}
