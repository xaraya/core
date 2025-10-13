<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Routing\Dispatcher;

final class DispatcherTest extends TestHelper
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        xarTpl::init();
    }

    public function testInRoot(): void
    {
        $dispatcher = new Dispatcher('http://localhost/');

        $path = '/';
        $params = [];
        $method = 'GET';
        [$result, $context] = $dispatcher->dispatch($path, $params, $method);

        $output = $dispatcher->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = '<h2>Congratulations!</h2>';
        $this->assertStringContainsString($expected, $output);

        $expected = 'The <a href="/base/admin/main">Base</a> module';
        $this->assertStringContainsString($expected, $output);

        // make sure we reset the Controller here for later tests
        $dispatcher->resetController();
    }

    public function testWithEntrypoint(): void
    {
        $dispatcher = new Dispatcher('http://localhost/index.php');

        // use PATH_INFO or path component of REQUEST_URI here
        $path = '/';
        $params = [];
        $method = 'GET';
        [$result, $context] = $dispatcher->dispatch($path, $params, $method);

        $output = $dispatcher->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = '<h2>Congratulations!</h2>';
        $this->assertStringContainsString($expected, $output);

        $expected = 'The <a href="/index.php/base/admin/main">Base</a> module';
        $this->assertStringContainsString($expected, $output);

        // make sure we reset the Controller here for later tests
        $dispatcher->resetController();
    }

    public function testInSubdir(): void
    {
        $dispatcher = new Dispatcher('http://localhost/xaraya/');

        $path = '/xaraya/';
        $params = [];
        $method = 'GET';
        [$result, $context] = $dispatcher->dispatch($path, $params, $method);

        $output = $dispatcher->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = '<h2>Congratulations!</h2>';
        $this->assertStringContainsString($expected, $output);

        $expected = 'The <a href="/xaraya/base/admin/main">Base</a> module';
        $this->assertStringContainsString($expected, $output);

        // make sure we reset the Controller here for later tests
        $dispatcher->resetController();
    }

    public function testInSubdirWithEntrypoint(): void
    {
        $dispatcher = new Dispatcher('http://localhost/xaraya/index.php');

        // use PATH_INFO or path component of REQUEST_URI here
        $path = '/';
        $params = [];
        $method = 'GET';
        [$result, $context] = $dispatcher->dispatch($path, $params, $method);

        $output = $dispatcher->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = '<h2>Congratulations!</h2>';
        $this->assertStringContainsString($expected, $output);

        $expected = 'The <a href="/xaraya/index.php/base/admin/main">Base</a> module';
        $this->assertStringContainsString($expected, $output);

        // make sure we reset the Controller here for later tests
        $dispatcher->resetController();
    }
}
