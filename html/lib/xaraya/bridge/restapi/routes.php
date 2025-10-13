<?php

/**
 * @package core\bridge
 * @subpackage restapi
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Bridge\RestAPI;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Xaraya\Context\Context;
use Xaraya\Routing\HandlerInterface;
use Xaraya\Routing\RoutesInterface;
use Xaraya\Routing\RouterInterface;

/**
 * Class to define REST API routes
 * @phpstan-type RouteDef array{0: string|array<string>, 1: string, 2: mixed, 3: array<string, mixed>}
 */
class RestAPIRoutes implements RoutesInterface
{
    public static string $pathPrefix = '/v1';
    public static string $namePrefix = 'restapi-';
    /** @var class-string<RestAPIHandler>|RestAPIHandler|null */
    public static mixed $handlerClass = null;

    /**
     * Summary of setPathPrefix
     * @param string $pathPrefix
     * @return void
     */
    public static function setPathPrefix(string $pathPrefix): void
    {
        self::$pathPrefix = $pathPrefix;
    }

    /**
     * Summary of setNamePrefix
     * @param string $namePrefix
     * @return void
     */
    public static function setNamePrefix(string $namePrefix): void
    {
        self::$namePrefix = $namePrefix;
    }

    /**
     * Summary of setHandlerClass
     * @param mixed $handlerClass
     * @return void
     */
    public static function setHandlerClass(mixed $handlerClass): void
    {
        self::$handlerClass = $handlerClass;
    }

    /**
     * Get REST API routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param mixed $restHandler
     * @param array<mixed> $extra
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = '', mixed $restHandler = null, array $extra = []): array
    {
        // default null handler here = let specific sub-handlers deal with requests
        $restHandler ??= self::$handlerClass;
        $routes = [];

        // get openapi without /v1 prefix + use generic restapi handler here
        $path = $pathPrefix . '/';
        $name = $namePrefix . 'openapi';
        $routes[$name] = ['GET', $path, [$restHandler ?? RestAPIHandler::class, 'getOpenAPI'], $extra];

        $pathPrefix .= self::$pathPrefix;
        $namePrefix .= self::$namePrefix;

        $routes = array_merge($routes, DataObjectAPIRoutes::getRoutes($pathPrefix, $namePrefix, $restHandler, $extra));
        $routes = array_merge($routes, GenericAPIRoutes::getRoutes($pathPrefix, $namePrefix, $restHandler, $extra));
        $routes = array_merge($routes, ModuleAPIRoutes::getRoutes($pathPrefix, $namePrefix, $restHandler, $extra));

        return $routes;
    }

    /**
     * Find route uri based on params
     * @param array<string, mixed> $params
     */
    public static function findRoute(RouterInterface $router, array $params, string $method = 'GET'): string|null
    {
        // we have a route already
        if (!empty($params[$router::ROUTE_PARAM])) {
            return $router->makeUri(null, $params);
        }
        // @todo make use of method here too!?
        $route = null;
        if (empty($params)) {
            $route = 'openapi';
            return $router->makeUri($route, $params);
        }
        // find module route uri or dataobject route uri
        if (!isset($route) && !empty($params['module'])) {
            $namePrefix = static::$namePrefix;
            $route = static::findModuleRoute($router, $namePrefix, $params, $method);
        }
        if (!isset($route) && !empty($params['object'])) {
            $namePrefix = static::$namePrefix;
            $route = static::findObjectRoute($router, $namePrefix, $params, $method);
        }
        return $route;
    }

    /**
     * Find module route uri based on params
     * @param string $namePrefix incl. moduleName
     * @param array<string, mixed> $params
     */
    public static function findModuleRoute(RouterInterface $router, string $namePrefix = '', array $params = [], string $method = 'GET'): string|null
    {
        // @todo make use of method here too!?
        return ModuleAPIRoutes::findRoute($router, $params, $method, $namePrefix);
    }

    /**
     * Find dataobject route uri based on params
     * @param string $namePrefix incl. objectName
     * @param array<string, mixed> $params
     */
    public static function findObjectRoute(RouterInterface $router, string $namePrefix = '', array $params = [], string $method = 'GET'): string|null
    {
        // @todo make use of method here too!?
        return DataObjectAPIRoutes::findRoute($router, $params, $method, $namePrefix);
    }

    /**
     * Get route handler for restapi routes here - @todo align with restapi handling
     * @param string $route
     * @param ?Context<string, mixed> $context
     * @return HandlerInterface
     */
    public static function getHandler(string $route, ?Context $context): HandlerInterface
    {
        // we could provide different instance or handler based on route here
        $handler = new RestAPIHandler();
        $handler->setContext($context);
        return $handler;
    }
}
