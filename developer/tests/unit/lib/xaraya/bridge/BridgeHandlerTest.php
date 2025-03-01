<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Routing\Routing;
use Xaraya\Modules\DynamicData\DynamicDataHandler;

final class BridgeHandlerTest extends TestHelper
{
    public function testHandlerMain(): void
    {
        $router = new Routing(function () {
            return DynamicDataHandler::getRoutes();
        });
        $path = '/dynamicdata/';
        [$handler, $vars] = $router->match($path);

        $expected = [DynamicDataHandler::class, 'main'];
        $this->assertEquals($expected, $handler);

        $expected = ['_route' => 'dynamicdata-main'];
        $this->assertEquals($expected, $vars);

        [$handlerClass, $method] = $handler;
        $instance = new $handlerClass();
        [$result, $context] = $instance->callHandler($handler, $vars);

        $expected = [
            'startlist' => [
                'sample',
            ],
            'update' => null,
            'context' => null,
        ];
        $this->assertEquals(array_keys($expected), array_keys($result));

        $output = $instance->output($result);

        $expected = '    "sample"';
        $this->assertStringContainsString($expected, $output);
    }

    public function testHandlerEntity(): void
    {
        xarTpl::init();

        $router = new Routing(function () {
            return DynamicDataHandler::getRoutes();
        });
        $path = '/dynamicdata/sample/1';
        [$handler, $vars] = $router->match($path);

        $expected = [DynamicDataHandler::class, 'handle'];
        $this->assertEquals($expected, $handler);

        $expected = [
            '_route' => 'dynamicdata-entity-itemid',
            'entity' => 'sample',
            'itemid' => '1',
        ];
        $this->assertEquals($expected, $vars);

        [$handlerClass, $method] = $handler;
        $instance = new $handlerClass();
        [$result, $context] = $instance->callHandler($handler, $vars);

        $output = $instance->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = 'Name</label></div><div class="xar-col">Johnny</div>';
        $this->assertStringContainsString($expected, $output);
    }
}
