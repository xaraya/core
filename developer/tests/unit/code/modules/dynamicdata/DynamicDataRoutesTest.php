<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Routing\Dispatcher;
use Xaraya\Routing\RouterInterface;
use Xaraya\Routing\Routing;
use Xaraya\Modules\DynamicData\DynamicDataRoutes;
use Xaraya\Services\xar;

final class DynamicDataRoutesTest extends TestHelper
{
    private static RouterInterface $router;
    private bool $hasExceptions = false;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $dispatcher = new Dispatcher();
        $dispatcher->setServicesClass(static::$xarServices);
        $dispatcher->resetController();
        self::$router = $dispatcher->getRouter();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        // make sure we reset the Controller here for later tests
        $dispatcher = new Dispatcher();
        $dispatcher->setServicesClass(static::$xarServices);
        $dispatcher->resetController();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        if ($this->hasExceptions) {
            restore_exception_handler();
            restore_error_handler();
            $this->hasExceptions = false;
        }
    }

    public function testRoutesMain(): void
    {
        xar::tpl()->init();

        $router = new Routing(function () {
            return DynamicDataRoutes::getRoutes();
        });
        $path = '/dynamicdata/';
        [$handler, $vars] = $router->match($path);

        $expected = [DynamicDataRoutes::class, 'main'];
        $this->assertEquals($expected, $handler);

        $route = 'dynamicdata-main';
        $expected = ['_route' => $route];
        $this->assertEquals($expected, $vars);

        $context = $this->createContext();
        [$routesClass, $method] = $handler;
        $moduleHandler = $routesClass::getHandler($route, $context);
        [$result, $context] = $moduleHandler->callHandler($handler, $vars);

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

        $output = $moduleHandler->output($result);

        $expected = 'Sample Object';
        $this->assertStringContainsString($expected, $output);
    }

    public function testRoutesEntity(): void
    {
        xar::tpl()->init();

        $router = new Routing(function () {
            return DynamicDataRoutes::getRoutes();
        });
        $path = '/object/sample/1';
        [$handler, $vars] = $router->match($path);

        $expected = [DynamicDataRoutes::class, 'handle'];
        $this->assertEquals($expected, $handler);

        $route = 'object-entity-itemid';
        $expected = [
            '_route' => $route,
            'entity' => 'sample',
            'itemid' => '1',
        ];
        $this->assertEquals($expected, $vars);

        $context = $this->createContext();
        [$routesClass, $method] = $handler;
        $moduleHandler = $routesClass::getHandler($route, $context);
        [$result, $context] = $moduleHandler->callHandler($handler, $vars);

        $output = $moduleHandler->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = 'Name</label></div><div class="xar-col">Johnny</div>';
        $this->assertStringContainsString($expected, $output);
    }

    public static function getRouteProvider(): array
    {
        $moduleName = 'dynamicdata';
        $class = DynamicDataRoutes::class;
        return [
            // uri => [route, callable, path, params]
            '/dynamicdata/' => ['dynamicdata-main', [$class, 'main'], '/dynamicdata/', ['module' => $moduleName]],
            '/dynamicdata/view/sample' => ['dynamicdata-view-name', [$class, 'view'], '/dynamicdata/view/sample', ['module' => $moduleName, 'type' => 'user', 'func' => 'view', 'name' => 'sample']],
            '/dynamicdata/admin/func' => ['dynamicdata-admin', [$class, 'admingui'], '/dynamicdata/admin/func', ['module' => $moduleName, 'type' => 'admin', 'func' => 'func']],
            '/dynamicdata/admin/func?more=more' => ['dynamicdata-admin', [$class, 'admingui'], '/dynamicdata/admin/func?more=more', ['module' => $moduleName, 'type' => 'admin', 'func' => 'func', 'more' => 'more']],
            '/dynamicdata/search' => ['dynamicdata-user', [$class, 'usergui'], '/dynamicdata/search', ['module' => $moduleName, 'type' => 'user', 'func' => 'search']],
            '/object/sample' => ['object-entity', [$class, 'handle'], '/object/sample', ['module' => $moduleName, 'entity' => 'sample']],
            '/object/sample/1' => ['object-entity-itemid', [$class, 'handle'], '/object/sample/1', ['module' => $moduleName, 'entity' => 'sample', 'itemid' => '1']],
            '/object/sample/1/Johnny' => ['object-entity-itemid-title', [$class, 'handle'], '/object/sample/1/Johnny', ['module' => $moduleName, 'entity' => 'sample', 'itemid' => '1', 'title' => 'Johnny']],
            '/object/sample/search' => ['object-entity-action', [$class, 'handle'], '/object/sample/search', ['module' => $moduleName, 'entity' => 'sample', 'action' => 'search']],
            '/object/sample/update/1' => ['object-entity-action-itemid', [$class, 'handle'], '/object/sample/update/1', ['module' => $moduleName, 'entity' => 'sample', 'action' => 'update', 'itemid' => '1']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRouteProvider')]
    public function testRouterMatch(string $route, array $callable, string $path, array $params): void
    {
        $router = new Routing(function () {
            return DynamicDataRoutes::getRoutes();
        });
        $query = parse_url($path, PHP_URL_QUERY);
        $path = parse_url($path, PHP_URL_PATH);
        [$handler, $vars] = $router->match($path);
        if (!empty($query)) {
            $extra = [];
            parse_str($query, $extra);
            $vars = array_merge($vars, $extra);
        }

        $expected = $callable;
        $this->assertEquals($expected, $handler);

        $expected = [
            '_route' => $route,
        ];
        unset($params['module']);
        unset($params['type']);
        if (!in_array($callable[1], ['usergui', 'admingui'])) {
            // function is passed as part of callable for this route
            unset($params['func']);
        }
        $expected = array_merge($expected, $params);
        $this->assertEquals($expected, $vars);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRouteProvider')]
    public function testFindRoute(string $route, array $callable, string $path, array $params): void
    {
        $uri = DynamicDataRoutes::findRoute(self::$router, $params);

        $expected = $path;
        $this->assertEquals($expected, $uri);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRouteProvider')]
    public function testRouterGenerate(string $route, array $callable, string $path, array $params): void
    {
        $router = new Routing(function () {
            return DynamicDataRoutes::getRoutes();
        });
        unset($params['module']);
        unset($params['type']);
        if (!in_array($callable[1], ['usergui', 'admingui'])) {
            // function is passed as part of callable for this route
            unset($params['func']);
        }
        $uri = $router->generate($route, $params);

        $expected = $path;
        $this->assertEquals($expected, $uri);
    }

    public function testDispatcher(): void
    {
        $dispatcher = new Dispatcher();
        $dispatcher->setServicesClass(static::$xarServices);

        $path = '/dynamicdata/';
        $params = [];
        $method = 'GET';
        [$result, $context] = $dispatcher->dispatch($path, $params, $method);

        $output = $dispatcher->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = '<a href="/object/sample">Sample Object</a>';
        $this->assertStringContainsString($expected, $output);

        // make sure we reset the Controller here for later tests
        $dispatcher->resetController();
    }

    public function testUserGuiViewSample(): void
    {
        $dispatcher = new Dispatcher();
        $dispatcher->setServicesClass(static::$xarServices);

        $path = '/dynamicdata/view/sample';
        $params = [];
        $method = 'GET';
        [$result, $context] = $dispatcher->dispatch($path, $params, $method);

        $output = $dispatcher->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = '<h2>View Sample Object</h2>';
        $this->assertStringContainsString($expected, $output);

        $expected = '<a href="/dynamicdata/view/sample/1">Johnny</a>';
        $this->assertStringContainsString($expected, $output);

        // make sure we reset the Controller here for later tests
        $dispatcher->resetController();
    }
}
