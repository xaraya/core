<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Routing\Dispatcher;
use Xaraya\Routing\RouterInterface;
use Xaraya\Routing\Routing;
use Xaraya\Modules\Base\BaseRoutes;
use Xaraya\Services\xar;

final class BaseRoutesTest extends TestHelper
{
    private static RouterInterface $router;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $dispatcher = new Dispatcher();
        $dispatcher->setServicesClass(static::$xarServices);
        self::$router = $dispatcher->getRouter();
    }

    public function testFindRoute(): void
    {
        $params = [
            'module' => 'base',
            'type' => 'user',
            'func' => 'main',
        ];
        $uri = BaseRoutes::findRoute(self::$router, $params);

        $expected = '/base/';
        $this->assertEquals($expected, $uri);

        $params['page'] = 'docs';
        $uri = BaseRoutes::findRoute(self::$router, $params);
        unset($params['page']);

        $expected = '/base/docs';
        $this->assertEquals($expected, $uri);

        // unsupported: overlaps with /base/{page}
        $params['func'] = 'other';
        $uri = BaseRoutes::findRoute(self::$router, $params);

        $expected = null;
        $this->assertEquals($expected, $uri);

        $params['more'] = 'more';
        $uri = BaseRoutes::findRoute(self::$router, $params);

        $expected = null;
        $this->assertEquals($expected, $uri);

        $params['type'] = 'admin';
        $uri = BaseRoutes::findRoute(self::$router, $params);

        $expected = '/base/admin/other?more=more';
        $this->assertEquals($expected, $uri);

        unset($params['more']);
        $uri = BaseRoutes::findRoute(self::$router, $params);

        $expected = '/base/admin/other';
        $this->assertEquals($expected, $uri);
    }

    public function testBasePage(): void
    {
        xar::tpl()->init();
        xar::block()->init();

        $router = new Routing(function () {
            return BaseRoutes::getRoutes();
        });
        $path = '/base/docs';
        [$handler, $vars] = $router->match($path);

        $expected = [BaseRoutes::class, 'main'];
        $this->assertEquals($expected, $handler);

        $route = 'base-page';
        $expected = [
            '_route' => $route,
            'page' => 'docs',
        ];
        $this->assertEquals($expected, $vars);

        $context = $this->createContext();
        [$routesClass, $method] = $handler;
        $moduleHandler = $routesClass::getHandler($route, $context);
        [$result, $context] = $moduleHandler->callHandler($handler, $vars);

        $output = $moduleHandler->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = '<h2>Welcome to Xaraya Documentation</h2>';
        $this->assertStringContainsString($expected, $output);
    }

    public function testDispatcher(): void
    {
        $dispatcher = new Dispatcher();
        $dispatcher->setServicesClass(static::$xarServices);

        $path = '/base/docs';
        $params = [];
        $method = 'GET';
        [$result, $context] = $dispatcher->dispatch($path, $params, $method);

        $output = $dispatcher->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = '<h2>Welcome to Xaraya Documentation</h2>';
        $this->assertStringContainsString($expected, $output);

        // make sure we reset the Controller here for later tests
        $dispatcher->resetController();
    }
}
