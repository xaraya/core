<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Bridge\Routing\RoutingBridge;
use Xaraya\Context\SessionContext;
use Xaraya\Requests\RequestHandler;

final class BridgeRoutingTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        xarCache::init();
        xarServer::setRequestClass(RequestHandler::class);
        xarSession::setSessionClass(SessionContext::class);
        xarCore::xarInit(xarCore::SYSTEM_USER);
    }

    public static function tearDownAfterClass(): void
    {
        $_GET = [];
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
    public function testDispatchRequest(string $method = 'GET', string $path = '/', array $query = [], string $output = ''): void
    {
        $bridge = new RoutingBridge();
        $expected = $output;
        if ($method == 'POST' && str_starts_with($path, '/graphql')) {
            if (!empty($query)) {
                // set content for php://input = not possible as such, but use setRawInput() on GraphQLHandler() here
                //$_POST = json_encode($query);
                //$this->markTestSkipped('Unable to test POST-ed content here');
                $bridge->getGraphQLHandler()->setRawInput(json_encode($query));
            }
        } else {
            $_GET = $query;
        }
        [$result, $context] = $bridge->dispatchRequest($method, $path);
        if (is_array($result)) {
            $result = json_encode($result);
        }
        $result = preg_replace('/<!--.*?-->/s', '', $result);
        $this->assertStringContainsString($expected, $result);
        //var_dump($context);
        $_GET = [];
    }

    public static function getRouteProvider(): array
    {
        return [
            // uri => [route, path, params]
            '/' => ['root', '/', []],
            '/base/admin/main' => ['module-type-func', '/base/admin/main', ['module' => 'base', 'type' => 'admin', 'func' => 'main']],
            '/base/main' => ['module-func', '/base/main', ['module' => 'base', 'func' => 'main']],
            '/base' => ['module', '/base', ['module' => 'base']],
            '/object/sample' => ['object-list', '/object/sample', ['object' => 'sample']],
            '/object/sample/1' => ['object-item', '/object/sample/1', ['object' => 'sample', 'itemid' => 1]],
            '/object/sample/search' => ['object-method', '/object/sample/search', ['object' => 'sample', 'method' => 'search']],
            '/object/sample/1/update' => ['object-item-method', '/object/sample/1/update', ['object' => 'sample', 'itemid' => 1, 'method' => 'update']],
            '/object/sample?sort=name' => ['object-list', '/object/sample?sort=name', ['object' => 'sample', 'sort' => 'name']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRouteProvider')]
    public function testGenerateUri(string $route, string $path, array $params): void
    {
        $bridge = new RoutingBridge();
        $router = $bridge->getRouter();
        $expected = $path;
        $path = $router->generate($route, $params);
        $this->assertEquals($expected, $path);
    }
}
