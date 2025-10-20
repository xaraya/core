<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Bridge\Test\TestClient;

final class BridgeClientTest extends TestCase
{
    protected static string $endpoint = 'http://localhost/xaraya';
    protected static TestClient $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = new TestClient(self::$endpoint);
    }

    public static function tearDownAfterClass(): void {}

    public static function getRequestProvider(): array
    {
        return [
            // uri => [method, path, query, result]
            '/' => ['GET', '/', [], 'Congratulations'],
            '/object/sample' => ['GET', '/index.php/object/sample', [], 'View Sample Object'],
            '/object/sample/1' => ['GET', '/index.php/object/sample/display', ['itemid' => 1], 'Location'],
            '/object/sample/search' => ['GET', '/index.php/object/sample/search', [], 'Search Sample Object'],
            '/object/sample/1/update' => ['GET', '/index.php/object/sample/update', ['itemid' => 1], 'HTTP/1.0 403 Forbidden'],
            '/object/sample?sort=name' => ['GET', '/index.php/object/sample', ['sort' => 'name'], '<tr class="xar-alt"><td>Johnny</td>'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRequestProvider')]
    public function testSendRequest(string $method = 'GET', string $path = '/', array $query = [], string $output = ''): void
    {
        if ($method == 'GET') {
            $result = self::$client->get($path, $query);
        } elseif ($method == 'POST') {
            $result = self::$client->post($path, $query);
        } else {
            $this->markTestSkipped('Method is not supported in TestClient yet');
        }
        $result = preg_replace('/\s*<!--.*?-->\s*/s', '', $result);
        $result = preg_replace('/>\s+/s', '>', $result);
        $expected = $output;
        $this->assertStringContainsString($expected, $result);
    }
}
