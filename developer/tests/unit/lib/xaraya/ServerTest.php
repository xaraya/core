<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Context\RequestContext;
use Xaraya\Context\Context;

final class ServerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        xarCache::init();
    }

    public function tearDown(): void
    {
        $_SERVER = [];
        xarServer::setRequestClass(xarRequestHandler::class);
    }

    public function testStandardInit(): void
    {
        $expected = xarRequestHandler::class;
        xarServer::init();

        $instance = xarServer::getInstance();
        $this->assertEquals($expected, $instance::class);
    }

    public function testContextInit(): void
    {
        xarServer::setRequestClass(RequestContext::class);
        $expected = RequestContext::class;
        xarServer::init();

        $instance = xarServer::getInstance();
        $this->assertEquals($expected, $instance::class);
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

    public function testStandardGetVar(): void
    {
        $expected = $this->getServerVars();
        $_SERVER = array_replace($_SERVER ?? [], $expected);
        xarServer::init();
        // @todo we need to reset xarSystemVars::get(sys::LAYOUT, 'BaseURI')

        $this->assertEquals($expected['REQUEST_URI'], xarServer::getVar('REQUEST_URI'));
        $this->assertEquals('/xaraya', xarServer::getBaseURI());
        $this->assertEquals('http://:/xaraya/index.php', xarServer::getModuleURL());
        $this->assertEquals('http://:/xaraya/index.php?module=base&amp;type=user&amp;func=main', xarServer::getModuleURL('base'));
        $this->assertEquals('http://:/xaraya/index.php?object=sample&amp;method=view', xarServer::getObjectURL('sample'));
    }

    public function testContextGetVar(): void
    {
        xarServer::setRequestClass(RequestContext::class);
        $expected = $this->getServerVars();
        $_SERVER = array_replace($_SERVER ?? [], $expected);
        xarServer::init();
        // @todo we need to reset xarSystemVars::get(sys::LAYOUT, 'BaseURI')

        // default empty context for the request
        $this->assertEquals(null, xarServer::getVar('REQUEST_URI'));

        // set current context for the request
        $expected['REQUEST_URI'] = '/home/site.php/more?hello=world';
        $expected['SCRIPT_NAME'] = '/home/site.php';
        //$expected['PATH_INFO'] = '/more';
        //$expected['QUERY_STRING'] = 'hello=world';
        $context = new Context([
            'server' => $expected,
        ]);
        xarServer::getInstance()->setContext($context);

        // @todo update xarController::$endpoint based on actual SCRIPT_NAME?
        $this->assertEquals($expected['REQUEST_URI'], xarServer::getVar('REQUEST_URI'));
        $this->assertEquals('/home', xarServer::getBaseURI());
        $this->assertEquals('http://:/home/index.php', xarServer::getModuleURL());
        $this->assertEquals('http://:/home/index.php?module=base&amp;type=user&amp;func=main', xarServer::getModuleURL('base'));
        $this->assertEquals('http://:/home/index.php?object=sample&amp;method=view', xarServer::getObjectURL('sample'));
    }
}
