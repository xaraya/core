<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Routing\Dispatcher;
use Xaraya\Routing\RouterInterface;
use Xaraya\Routing\RoutesInterface;
use Xaraya\Routing\Routing;
use Xaraya\Bridge\RestAPI\RestAPIRoutes;
use Xaraya\Bridge\RestAPI\RestAPIHandler;
use Xaraya\Bridge\RestAPI\DataObjectAPIHandler;

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

    public function testGetOpenAPI(): void
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
        // @todo $routesClass is already handler class for restapi here
        if (is_subclass_of($routesClass, RoutesInterface::class)) {
            $moduleHandler = $routesClass::getHandler($route, $context);
        } else {
            $moduleHandler = is_object($routesClass) ? $routesClass : new $routesClass();
            $moduleHandler->setContext($context);
        }
        [$result, $context] = $moduleHandler->callHandler($handler, $vars);

        // @todo restapi handler = set headers + echo output
        $output = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $expected = '"title": "Xaraya REST API"';
        $this->assertStringContainsString($expected, $output);
    }

    public function testGetObjectList(): void
    {
        xarTpl::init();

        $router = new Routing(function () {
            return RestAPIRoutes::getRoutes();
        });
        $path = '/v1/objects/sample';
        [$handler, $vars] = $router->match($path);

        $expected = [DataObjectAPIHandler::class, 'getObjectList'];
        $this->assertEquals($expected, $handler);

        $route = 'restapi-objects-getObjectList';
        $expected = [
            '_route' => $route,
            'object' => 'sample',
        ];
        $this->assertEquals($expected, $vars);

        $context = $this->createContext();
        [$routesClass, $method] = $handler;
        // @todo $routesClass is already handler class for restapi here
        if (is_subclass_of($routesClass, RoutesInterface::class)) {
            $moduleHandler = $routesClass::getHandler($route, $context);
        } else {
            $moduleHandler = is_object($routesClass) ? $routesClass : new $routesClass();
            $moduleHandler->setContext($context);
        }
        [$result, $context] = $moduleHandler->callHandler($handler, $vars);

        // @todo restapi handler = set headers + echo output
        $output = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $expected = '"name": "Johnny"';
        $this->assertStringContainsString($expected, $output);
    }

    public function testDispatcher(): void
    {
        // @todo add restapi to Dispatcher routes
        $dispatcher = new Dispatcher();

        $path = '/restapi/v1/objects/sample';
        $params = [];
        $method = 'GET';
        [$result, $context] = $dispatcher->dispatch($path, $params, $method);

        $output = $dispatcher->output($result);

        $expected = '"name": "Johnny"';
        $this->assertStringContainsString($expected, $output);

        // make sure we reset the Controller here for later tests
        $dispatcher->resetController();
    }
}
