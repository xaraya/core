<?php

use PHPUnit\Framework\TestCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Xaraya\Bridge\Requests\BasicRequest;
use Xaraya\Bridge\Requests\DataObjectGuiRequest;
use Xaraya\Bridge\Requests\DataObjectApiRequest;

final class BridgeRequestsTest extends TestCase
{
    protected static Psr17Factory $psr17Factory;
    protected static ServerRequestCreator $requestCreator;

    public static function setUpBeforeClass(): void
    {
        $psr17Factory = new Psr17Factory();
        $requestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        static::$psr17Factory = $psr17Factory;
        static::$requestCreator = $requestCreator;
    }

    protected function getServerVars()
    {
        return [
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/xaraya/index.php/site?all=yes',
            'SCRIPT_NAME' => '/xaraya/index.php',
            'PATH_INFO' => '/site',
            'QUERY_STRING' => 'all=yes',
        ];
    }


    protected function getQueryVars()
    {
        return [
            'all' => 'yes',
        ];
    }

    public function testBasicRequestGlobal(): void
    {
        $serverVars = $this->getServerVars();
        $queryVars = $this->getQueryVars();
        $_SERVER = array_replace($_SERVER ?? [], $serverVars);
        $_GET = array_replace($_GET ?? [], $queryVars);
        $expected = $serverVars;

        $basicRequest = new BasicRequest();
        $this->assertEquals($expected['REQUEST_METHOD'], $basicRequest->getMethod());
        $this->assertEquals($expected['PATH_INFO'], $basicRequest->getPathInfo());
        $allowed = array_flip(array_keys($expected));
        $this->assertEquals($expected, array_intersect_key($basicRequest->getServerParams(), $allowed));

        // {request_uri} = {/baseurl/script.php}{/path_info}?{query_string}
        $this->assertEquals($expected['SCRIPT_NAME'], $basicRequest->getBaseUri());

        // {request_uri} = {/otherurl}{/path_info}?{query_string} = mod_rewrite possibly unrelated to {/baseurl/script.php}
        $expected['REQUEST_URI'] = '/home/fastroute.php/site?all=yes';
        $_SERVER = array_replace($_SERVER ?? [], $expected);
        $this->assertEquals('/home/fastroute.php', $basicRequest->getBaseUri());

        // {request_uri} = {/otherurl}?{query_string} = mod_rewrite possibly unrelated to {/baseurl/script.php}
        $expected['REQUEST_URI'] = '/home/fastroute.php/other?hello=world';
        $_SERVER = array_replace($_SERVER ?? [], $expected);
        $this->assertEquals('/home/fastroute.php/other', $basicRequest->getBaseUri());

        $expected = $queryVars;
        $allowed = array_flip(array_keys($expected));
        $this->assertEquals($expected, array_intersect_key($basicRequest->getQueryParams(), $allowed));

        $_SERVER = [];
        $_GET = [];
    }

    public function testBasicRequestPsr7(): void
    {
        $serverVars = $this->getServerVars();
        $queryVars = $this->getQueryVars();
        $request = static::$requestCreator->fromArrays($serverVars);
        $expected = $serverVars;

        $basicRequest = new BasicRequest();
        $this->assertEquals($expected['REQUEST_METHOD'], $basicRequest->getMethod($request));
        $this->assertEquals($expected['PATH_INFO'], $basicRequest->getPathInfo($request));
        $this->assertEquals($expected, $basicRequest->getServerParams($request));

        // {request_uri} = {/baseurl/script.php}{/path_info}?{query_string}
        $this->assertEquals($expected['SCRIPT_NAME'], $basicRequest->getBaseUri($request));

        // {request_uri} = {/otherurl}{/path_info}?{query_string} = mod_rewrite possibly unrelated to {/baseurl/script.php}
        $expected['REQUEST_URI'] = '/home/fastroute.php/site?all=yes';
        $request = static::$requestCreator->fromArrays($expected);
        $this->assertEquals('/home/fastroute.php', $basicRequest->getBaseUri($request));

        // {request_uri} = {/otherurl}?{query_string} = mod_rewrite possibly unrelated to {/baseurl/script.php}
        $expected['REQUEST_URI'] = '/home/fastroute.php/other?hello=world';
        $request = static::$requestCreator->fromArrays($expected);
        $this->assertEquals('/home/fastroute.php/other', $basicRequest->getBaseUri($request));

        // did we already filter out the base uri in router middleware?
        $expected = 'hi there!';
        $request = $request->withAttribute('baseUri', $expected);
        $this->assertEquals($expected, $basicRequest->getBaseUri($request));

        $expected = $queryVars;
        $request = static::$requestCreator->fromArrays($serverVars, [], [], $queryVars);
        $allowed = array_flip(array_keys($expected));
        $this->assertEquals($expected, array_intersect_key($basicRequest->getQueryParams($request), $allowed));
    }

    public static function getDataObjectProvider(): array
    {
        return [
            // uri => [path, query, prefix, (result) params] for parseDataObjectPath
            // (result) uri => [path, extra, prefix, (ignore) params, object, method, itemid] for buildDataObjectPath
            '/object/sample' => ['/object/sample', [], '/object', ['object' => 'sample'], 'sample'],
            '/object/sample/1' => ['/object/sample/1', [], '/object', ['object' => 'sample', 'itemid' => '1'], 'sample', null, 1],
            '/object/sample/search' => ['/object/sample/search', [], '/object', ['object' => 'sample', 'method' => 'search'], 'sample', 'search'],
            '/object/sample/1/update' => ['/object/sample/1/update', [], '/object', ['object' => 'sample', 'itemid' => '1', 'method' => 'update'], 'sample', 'update', 1],
            '/object/sample/1?hello=world' => ['/object/sample/1', ['hello' => 'world'], '/object', ['object' => 'sample', 'itemid' => '1', 'hello' => 'world'], 'sample', null, 1],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getDataObjectProvider')]
    public function testParseDataObjectPath(
        string $path = '/',
        array $query = [],
        string $prefix = '',
        array $params = [],
        // ignore the rest
    ): void {
        $expected = $params;
        $handler = new DataObjectGuiRequest();
        $this->assertEquals($expected, $handler->parseDataObjectPath($path, $query, $prefix));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getDataObjectProvider')]
    public function testBuildDataObjectPath(
        string $path = '/',
        array $extra = [],
        string $prefix = '',
        array $params = [],
        string $object = 'sample',
        ?string $method = null,
        string|int|null $itemid = null
    ): void {
        $expected = $path;
        if (!empty($extra)) {
            $expected .= '?' . http_build_query($extra);
        }
        $handler = new DataObjectGuiRequest();
        $this->assertEquals($expected, $handler->buildDataObjectPath($object, $method, $itemid, $extra, $prefix));
    }

    public function testPrepareOutput(): void
    {
        xarServer::setBaseURL('http://localhost/');
        xarServer::setVar('REQUEST_URI', '/index.php');

        //xarCore::xarInit(xarCore::SYSTEM_USER);
        xarCache::init();
        xarDatabase::init();
        // needed to initialize the template cache
        xarTpl::init();
        // needed for security checks later...
        xarSession::setAnonId(xarConfigVars::get(null, 'Site.User.AnonymousUID', 5));
        //$_SESSION[xarSession::PREFIX . 'role_id'] = xarSession::getAnonId();
        // needed to check security for the view options
        xarUser::init();

        $expected = '5';
        $this->assertEquals($expected, xarSession::getAnonId());
    }

    protected function getFixtureFile($name)
    {
        return dirname(__DIR__, 3) . '/code/modules/dynamicdata/fixtures/' . $name;
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPrepareOutput')]
    public function testRunDataObjectGuiRequest()
    {
        // should be the same output as DataObjectTest::testObjectInterface()
        $filename = $this->getFixtureFile('ui_handlers.view.html');
        $expected = filesize($filename);

        $params = ['object' => 'sample'];
        $context = null;
        $handler = new DataObjectGuiRequest();
        $handler->setContext($context);
        $output = $handler->runDataObjectRequest($params);
        $output = preg_replace('/<!--.*?-->/s', '', $output);
        $this->assertEquals($expected, strlen($output));

        // @todo try out with different context
    }

    public function testRunDataObjectApiRequest()
    {
        // should be the same output as DataObjectTest::testObjectInterface()
        $filename = $this->getFixtureFile('ui_handlers.view.json');
        if (file_exists($filename)) {
            $expected = json_decode(file_get_contents($filename), true);
        } else {
            $expected = [];
        }

        $params = ['object' => 'sample'];
        $context = null;
        $handler = new DataObjectApiRequest();
        $handler->setContext($context);
        $output = $handler->runDataObjectRequest($params);
        if (!file_exists($filename)) {
            file_put_contents($filename, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        $this->assertEquals($expected, $output);

        // @todo try out with different context
    }
}
