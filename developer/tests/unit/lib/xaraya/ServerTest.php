<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Context\RequestContext;
use Xaraya\Context\Context;
use Xaraya\Requests\RequestHandler;
use Xaraya\Services\xar;

#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
final class ServerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        xar::cache()->init();
        // preload Config.Variables here for xar::config()->getVar() in xar::req()->getConfig()
        xar::mem()->set('CoreCache.Preload', 'Config.Variables', 1);
    }

    public function tearDown(): void
    {
        $_SERVER = [];
        xar::req()->setRequestClass(RequestHandler::class);
    }

    public function testStandardInit(): void
    {
        $expected = RequestHandler::class;
        xar::req()->init(xar::req()->getConfig());

        $instance = xar::req()->getInstance();
        $this->assertEquals($expected, $instance::class);
    }

    public function testContextInit(): void
    {
        xar::req()->setRequestClass(RequestContext::class);
        $expected = RequestContext::class;
        xar::req()->init(xar::req()->getConfig());

        $instance = xar::req()->getInstance();
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
            'HTTP_HOST' => 'test:123',
        ];
    }

    public function testStandardGetVar(): void
    {
        $expected = $this->getServerVars();
        $_SERVER = array_replace($_SERVER ?? [], $expected);
        xar::req()->init(xar::req()->getConfig());
        // @todo we need to reset xar::sysConfig()->getVar('BaseURI', sys::LAYOUT)

        $this->assertEquals($expected['REQUEST_URI'], xar::req()->getServerVar('REQUEST_URI'));
        $this->assertEquals('/xaraya', xar::req()->getBaseURI());
        $this->assertEquals('http://test:123/xaraya/index.php', xar::ctl()->getModuleURL());
        $this->assertEquals('http://test:123/xaraya/index.php?module=base&amp;type=user&amp;func=main', xar::ctl()->getModuleURL('base'));
        $this->assertEquals('http://test:123/xaraya/index.php?object=sample&amp;method=view', xar::ctl()->getObjectURL('sample'));
    }

    public function testContextGetVar(): void
    {
        xar::req()->setRequestClass(RequestContext::class);
        $context = new Context(['source' => __METHOD__]);
        xar::setServicesContext($context);
        $expected = $this->getServerVars();
        $_SERVER = array_replace($_SERVER ?? [], $expected);
        xar::req()->init(xar::req()->getConfig());
        // @todo we need to reset xar::sysConfig()->getVar('BaseURI', sys::LAYOUT)

        // default empty context for the request
        $this->assertEquals(null, xar::req()->getServerVar('REQUEST_URI'));

        // set current context for the request
        $expected['REQUEST_URI'] = '/home/site.php/more?hello=world';
        $expected['SCRIPT_NAME'] = '/home/site.php';
        //$expected['PATH_INFO'] = '/more';
        //$expected['QUERY_STRING'] = 'hello=world';
        $context = new Context([
            'server' => $expected,
        ]);
        xar::req()->getInstance()->setContext($context);

        // @todo update xarController::$endpoint based on actual SCRIPT_NAME?
        $this->assertEquals($expected['REQUEST_URI'], xar::req()->getServerVar('REQUEST_URI'));
        $this->assertEquals('/home', xar::req()->getBaseURI());
        $this->assertEquals('http://test:123/home/index.php', xar::ctl()->getModuleURL());
        $this->assertEquals('http://test:123/home/index.php?module=base&amp;type=user&amp;func=main', xar::ctl()->getModuleURL('base'));
        $this->assertEquals('http://test:123/home/index.php?object=sample&amp;method=view', xar::ctl()->getObjectURL('sample'));
    }
}
