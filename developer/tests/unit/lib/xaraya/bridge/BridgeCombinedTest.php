<?php

use PHPUnit\Framework\TestCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
// use Xaraya PSR-15 compatible request handler + middleware
use Xaraya\Bridge\Middleware\RoutingHandler;
use Xaraya\Context\Context;
use Xaraya\Context\SessionContext;
use Xaraya\Requests\RequestHandler;

#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
final class BridgeCombinedTest extends TestCase
{
    protected static Psr17Factory $psr17Factory;
    protected static ServerRequestCreator $requestCreator;

    public static function setUpBeforeClass(): void
    {
        $psr17Factory = new Psr17Factory();
        $requestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        static::$psr17Factory = $psr17Factory;
        static::$requestCreator = $requestCreator;
        xarCache::init();
        xarSession::setSessionClass(SessionContext::class);
        xarServer::setRequestClass(RequestHandler::class);
        $context = new Context(['source' => __METHOD__]);
        xarCore::xarInit(xarCore::SYSTEM_USER, $context);
    }


    public static function getRequestProvider(): array
    {
        return [
            // uri => [method, path, query, result]
            '/' => ['GET', '/', [], 'Congratulations'],
            '/object/sample' => ['GET', '/object/sample', [], 'View Sample Object'],
            '/object/sample/1' => ['GET', '/object/sample/1', [], 'Location'],
            '/object/sample/search' => ['GET', '/object/sample/search', [], 'Search Sample Object'],
            '/object/sample/1/update' => ['GET', '/object/sample/1/update', [], 'you cannot perform this operation'],
            '/object/sample?sort=name' => ['GET', '/object/sample', ['sort' => 'name'], '<tr class="xar-alt"><td>Johnny</td>'],
            '/restapi/' => ['GET', '/restapi/', [], '"title":"Xaraya REST API"'],
            '/restapi/v1/objects/sample' => ['GET', '/restapi/v1/objects/sample', [], '"name":"Johnny"'],
            '/graphql' => ['POST', '/graphql', [], '  query: Query'],
            '/graphql_hello' => ['POST', '/graphql', ['query' => '{hello}'], '"data":{"hello":"Hello World!"}'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRequestProvider')]
    public function testHandleRequest(string $method = 'GET', string $path = '/', array $query = [], string $output = ''): void
    {
        $requestUri = $path;
        $queryString = '';
        if (!empty($query)) {
            $queryString = http_build_query($query);
            $requestUri .= '?' . $queryString;
        }
        $serverVars = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $requestUri,
            'SCRIPT_NAME' => '/xaraya/index.php',
            'PATH_INFO' => $path,
            'QUERY_STRING' => $queryString,
        ];
        if ($method == 'POST' && str_starts_with($path, '/graphql')) {
            // use json-encoded string as body here
            $body = json_encode($query);
            $request = static::$requestCreator->fromArrays($serverVars, [], [], [], [], [], $body);
        } else {
            $queryVars = $query;
            $request = static::$requestCreator->fromArrays($serverVars, [], [], $queryVars);
        }
        $expected = $output;

        $combined = new RoutingHandler(static::$psr17Factory);

        // handle the request directly, or use as middleware
        $response = $combined->handle($request);
        //$combined->emitResponse($response);
        $result = (string) $response->getBody();
        $result = preg_replace('/<!--.*?-->/s', '', $result);

        $this->assertStringContainsString($expected, $result);
    }
}
