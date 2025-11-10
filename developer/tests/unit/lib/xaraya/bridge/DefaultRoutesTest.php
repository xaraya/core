<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Routing\Dispatcher;
use Xaraya\Routing\RouterInterface;
use Xaraya\Routing\Routing;
use Xaraya\Routing\DefaultRoutes;
use Xaraya\Services\xar;

final class DefaultRoutesTest extends TestHelper
{
    private static RouterInterface $router;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $dispatcher = new Dispatcher();
        self::$router = $dispatcher->getRouter();
    }

    public function testGetRoutes(): void
    {
        $expected = 5;
        $routes = DefaultRoutes::getRoutes();
        $this->assertCount($expected, $routes);

        $expected = 'default-home';
        $route = array_key_first($routes);
        $this->assertEquals($expected, $route);
    }

    public function testFindRoute(): void
    {
        $params = [];
        $uri = DefaultRoutes::findRoute(self::$router, $params);
        $expected = '/';
        $this->assertEquals($expected, $uri);

        $params['module'] = 'mail';
        $uri = DefaultRoutes::findRoute(self::$router, $params);
        $expected = '/mail/';
        $this->assertEquals($expected, $uri);

        $params['func'] = 'display';
        $uri = DefaultRoutes::findRoute(self::$router, $params);
        $expected = '/mail/display';
        $this->assertEquals($expected, $uri);

        $params['type'] = 'admin';
        $uri = DefaultRoutes::findRoute(self::$router, $params);
        $expected = '/mail/admin/display';
        $this->assertEquals($expected, $uri);
    }

    public function testDefaultHome(): void
    {
        xar::tpl()->init();

        $router = new Routing(function () {
            return DefaultRoutes::getRoutes();
        });
        $path = '/';
        [$handler, $vars] = $router->match($path);

        $expected = [DefaultRoutes::class, 'handle'];
        $this->assertEquals($expected, $handler);

        $route = 'default-home';
        $expected = [
            '_route' => $route,
        ];
        $this->assertEquals($expected, $vars);

        $context = $this->createContext();
        [$routesClass, $method] = $handler;
        $moduleHandler = $routesClass::getHandler($route, $context);
        [$result, $context] = $moduleHandler->callHandler($handler, $vars);

        $output = $moduleHandler->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = '<h2>Congratulations!</h2>';
        $this->assertStringContainsString($expected, $output);
    }

    public function testDispatcher(): void
    {
        $dispatcher = new Dispatcher();

        $path = '/';
        $params = [];
        $method = 'GET';
        [$result, $context] = $dispatcher->dispatch($path, $params, $method);

        $output = $dispatcher->output($result);
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        $expected = '<h2>Congratulations!</h2>';
        $this->assertStringContainsString($expected, $output);

        // make sure we reset the Controller here for later tests
        $dispatcher->resetController();
    }
}
