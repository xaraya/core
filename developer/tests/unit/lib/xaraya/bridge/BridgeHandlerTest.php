<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Routing\Routing;
use Xaraya\Modules\DynamicData\DynamicDataHandler;
use Xaraya\Modules\Base\BaseHandler;

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

        $context = $this->createContext();
        [$handlerClass, $method] = $handler;
        $instance = new $handlerClass();
        $instance->setContext($context);
        [$result, $context] = $instance->callHandler($handler, $vars);

        $expected = [
            'startlist' => [
                'sample',
            ],
            'update' => null,
            'context' => null,
        ];
        // @todo depends on whether we apply template in callHandler() or output()
        if (is_array($result)) {
            $this->assertEquals(array_keys($expected), array_keys($result));
        } else {
            $expected = 'Sample Object';
            $this->assertStringContainsString($expected, $result);
        }

        $output = $instance->output($result);

        $expected = 'Sample Object';
        $this->assertStringContainsString($expected, $output);
    }

    public function testHandlerEntity(): void
    {
        xarTpl::init();

        $router = new Routing(function () {
            return DynamicDataHandler::getRoutes();
        });
        $path = '/object/sample/1';
        [$handler, $vars] = $router->match($path);

        $expected = [DynamicDataHandler::class, 'handle'];
        $this->assertEquals($expected, $handler);

        $expected = [
            '_route' => 'object-entity-itemid',
            'entity' => 'sample',
            'itemid' => '1',
        ];
        $this->assertEquals($expected, $vars);

        $context = $this->createContext();
        [$handlerClass, $method] = $handler;
        $instance = new $handlerClass();
        $instance->setContext($context);
        [$result, $context] = $instance->callHandler($handler, $vars);

        $output = $instance->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = 'Name</label></div><div class="xar-col">Johnny</div>';
        $this->assertStringContainsString($expected, $output);
    }

    public static function getRouteProvider(): array
    {
        $moduleName = 'dynamicdata';
        $class = DynamicDataHandler::class;
        return [
            // uri => [route, callable, path, params]
            '/dynamicdata/' => ['dynamicdata-main', [$class, 'main'], '/dynamicdata/', ['module' => $moduleName]],
            '/dynamicdata/admin/func' => ['dynamicdata-admin', [$class, 'admingui'], '/dynamicdata/admin/func', ['module' => $moduleName, 'type' => 'admin', 'func' => 'func']],
            '/dynamicdata/admin/func/more' => ['dynamicdata-admin-more', [$class, 'admingui'], '/dynamicdata/admin/func/more', ['module' => $moduleName, 'type' => 'admin', 'func' => 'func', 'more' => 'more']],
            '/dynamicdata/search' => ['dynamicdata-user', [$class, 'usergui'], '/dynamicdata/search', ['module' => $moduleName, 'type' => 'user', 'func' => 'search']],
            '/object/sample/' => ['object-entity', [$class, 'handle'], '/object/sample/', ['module' => $moduleName, 'entity' => 'sample']],
            '/object/sample/1' => ['object-entity-itemid', [$class, 'handle'], '/object/sample/1', ['module' => $moduleName, 'entity' => 'sample', 'itemid' => '1']],
            '/object/sample/1/Johnny' => ['object-entity-itemid-title', [$class, 'handle'], '/object/sample/1/Johnny', ['module' => $moduleName, 'entity' => 'sample', 'itemid' => '1', 'title' => 'Johnny']],
            '/object/sample/search' => ['object-entity-action', [$class, 'handle'], '/object/sample/search', ['module' => $moduleName, 'entity' => 'sample', 'action' => 'search']],
            '/object/sample/update/1' => ['object-entity-action-itemid', [$class, 'handle'], '/object/sample/update/1', ['module' => $moduleName, 'entity' => 'sample', 'action' => 'update', 'itemid' => '1']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRouteProvider')]
    public function testHandlerMatch(string $route, array $callable, string $path, array $params): void
    {
        $router = new Routing(function () {
            return DynamicDataHandler::getRoutes();
        });
        [$handler, $vars] = $router->match($path);

        $expected = $callable;
        $this->assertEquals($expected, $handler);

        $expected = [
            '_route' => $route,
        ];
        unset($params['module']);
        unset($params['type']);
        $expected = array_merge($expected, $params);
        $this->assertEquals($expected, $vars);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRouteProvider')]
    public function testHandlerFindRoute(string $route, array $callable, string $path, array $params): void
    {
        $name = DynamicDataHandler::findRoute($params);

        $expected = $route;
        $this->assertEquals($expected, $name);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRouteProvider')]
    public function testHandlerGenerate(string $route, array $callable, string $path, array $params): void
    {
        $router = new Routing(function () {
            return DynamicDataHandler::getRoutes();
        });
        unset($params['module']);
        unset($params['type']);
        $uri = $router->generate($route, $params);

        $expected = $path;
        $this->assertEquals($expected, $uri);
    }

    public function testHandlerBaseRoutes(): void
    {
        $params = [
            'module' => 'base',
            'type' => 'user',
            'func' => 'main',
        ];
        $name = BaseHandler::findRoute($params);

        $expected = 'base-main';
        $this->assertEquals($expected, $name);

        $params['page'] = 'docs';
        $name = BaseHandler::findRoute($params);

        $expected = 'base-page';
        $this->assertEquals($expected, $name);

        // unsupported: overlaps with /base/{page}
        $params['func'] = 'other';
        $name = BaseHandler::findRoute($params);

        $expected = null;
        $this->assertEquals($expected, $name);

        $params['more'] = 'more';
        $name = BaseHandler::findRoute($params);

        $expected = 'base-user-more';
        $this->assertEquals($expected, $name);

        $params['type'] = 'admin';
        $name = BaseHandler::findRoute($params);

        $expected = 'base-admin-more';
        $this->assertEquals($expected, $name);
    }

    public function testHandlerBasePage(): void
    {
        xarTpl::init();

        $router = new Routing(function () {
            return BaseHandler::getRoutes();
        });
        $path = '/base/docs';
        [$handler, $vars] = $router->match($path);

        $expected = [BaseHandler::class, 'main'];
        $this->assertEquals($expected, $handler);

        $expected = [
            '_route' => 'base-page',
            'page' => 'docs',
        ];
        $this->assertEquals($expected, $vars);

        $context = $this->createContext();
        [$handlerClass, $method] = $handler;
        $instance = new $handlerClass();
        $instance->setContext($context);
        [$result, $context] = $instance->callHandler($handler, $vars);

        $output = $instance->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = '<h2>Welcome to Xaraya Documentation</h2>';
        $this->assertStringContainsString($expected, $output);
    }
}
