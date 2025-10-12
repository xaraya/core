<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Routing\Dispatcher;
use Xaraya\Routing\RouterInterface;
use Xaraya\Routing\Routing;
use Xaraya\Bridge\RestAPI\RestAPIRoutes;
use Xaraya\Bridge\RestAPI\RestAPIHandler;

final class RestApiRoutesTest extends TestHelper
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
        $expected = 22;
        $routes = RestAPIRoutes::getRoutes();
        $this->assertCount($expected, $routes);

        $expected = 'openapi';
        $route = array_key_first($routes);
        $this->assertEquals($expected, $route);
    }

    public function testFindRoute(): void
    {
        $router = new Routing(function () {
            return RestAPIRoutes::getRoutes();
        });

        $params = [];
        $uri = RestAPIRoutes::findRoute($router, $params);
        $expected = '/';
        $this->assertEquals($expected, $uri);

        $params['module'] = 'mail';
        $uri = RestAPIRoutes::findRoute($router, $params);
        $expected = '/v1/modules/mail';
        $this->assertEquals($expected, $uri);

        $params['path'] = 'display';
        $uri = RestAPIRoutes::findRoute($router, $params);
        $expected = '/v1/modules/mail/display';
        $this->assertEquals($expected, $uri);

        $params['more'] = '1/Johnny';
        $uri = RestAPIRoutes::findRoute($router, $params);
        $expected = '/v1/modules/mail/display/1/Johnny';
        $this->assertEquals($expected, $uri);
    }

    public function testOpenAPI(): void
    {
        xarTpl::init();

        $router = new Routing(function () {
            return RestAPIRoutes::getRoutes();
        });
        $path = '/';
        [$handler, $vars] = $router->match($path);

        $expected = [RestAPIHandler::class, 'getOpenAPI'];
        $this->assertEquals($expected, $handler);

        $route = 'openapi';
        $expected = [
            '_route' => $route,
        ];
        $this->assertEquals($expected, $vars);

        $context = $this->createContext();
        [$routesClass, $method] = $handler;
        // @todo $routesClass is already handler class for restapi
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
