<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Bridge\Routing\FastRouteBridge;
use Xaraya\Context\SessionContext;

final class BridgeRoutingTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        xarCache::init();
        xarSession::setSessionClass(SessionContext::class);
        xarCore::xarInit(xarCore::SYSTEM_USER);
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
        ];
    }

    /**
     * @dataProvider getRequestProvider
     */
    public function testDispatchRequest(string $method = 'GET', string $path = '/', array $query = [], string $output = ''): void
    {
        $expected = $output;
        $_GET = $query;
        [$result, $context] = FastRouteBridge::dispatchRequest($method, $path);
        $this->assertStringContainsString($expected, $result);
        var_dump($context);
        $_GET = [];
    }
}
