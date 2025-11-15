<?php

/**
 * Default routes class for routing & dispatching outside Xaraya
 *
 * Experiment using module classes and methods as handler
 */

namespace Xaraya\Routing;

use Xaraya\Context\Context;

/**
 * Default routes class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * ```
 * /
 * /{module}/ (with trailing / here)
 * /{module}/{func}
 * /{module}/{type}/{func}
 * ```
 * @phpstan-import-type RouteDef from ModuleRoutes
 */
class DefaultRoutes extends ModuleRoutes
{
    public static string $moduleName = 'default';
    public static string $objectName = '';

    /**
     * Get supported handler routes (in generic format)
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = ''): array
    {
        $handler = static::class;
        $extra = [];
        $routes = [];

        // do not use moduleName in path prefix here
        $path = $pathPrefix;
        $name = $namePrefix . static::$moduleName . '-';
        $routes = array_merge($routes, static::getModuleRoutes($path, $name, $handler, $extra));

        return $routes;
    }

    /**
     * Summary of getModuleRoutes
     * @param string $pathPrefix excl. moduleName here
     * @param string $namePrefix incl. moduleName
     * @param ?string $handler
     * @param array<string, mixed> $extra
     * @return array<string, array<mixed>> array of name => [method(s), path, handler, options = []]
     */
    public static function getModuleRoutes(string $pathPrefix = '', string $namePrefix = '', ?string $handler = null, array $extra = []): array
    {
        $handler ??= static::class;
        $routes = [];

        // home page
        $path = $pathPrefix . '/';
        $name = $namePrefix . 'home';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handle'], $extra];

        // routes page
        $path = $pathPrefix . '/routes';
        $name = $namePrefix . 'routes';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'routes'], $extra];

        // with trailing / here?
        $path = $pathPrefix . '/{module}/';
        $name = $namePrefix . 'main';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handle'], $extra];

        $path = $pathPrefix . '/{module}/{func}';
        $name = $namePrefix . 'func';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handle'], $extra];

        $path = $pathPrefix . '/{module}/{type}/{func}';
        $name = $namePrefix . 'type-func';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handle'], $extra];

        return $routes;
    }

    /**
     * Find route uri based on params
     * @param array<string, mixed> $params
     */
    public static function findRoute(RouterInterface $router, array $params): ?string
    {
        // we have a route already
        if (!empty($params[$router::ROUTE_PARAM])) {
            return $router->makeUri(null, $params);
        }
        // for any module that doesn't have its own handler
        $namePrefix = static::$moduleName . '-';
        $route = static::findModuleRoute($router, $namePrefix, $params);
        return $route;
    }

    /**
     * Find module route uri based on params
     * @param string $namePrefix incl. moduleName
     * @param array<string, mixed> $params
     */
    public static function findModuleRoute(RouterInterface $router, string $namePrefix = '', array $params = []): ?string
    {
        // we have no module
        if (empty($params['module'])) {
            $route = $namePrefix . 'home';
            return $router->makeUri($route, $params);
        }
        // module user func
        if (empty($params['type']) || $params['type'] == 'user') {
            if (empty($params['func']) || $params['func'] == 'main') {
                $route = $namePrefix . 'main';
                // clean up default func
                unset($params['func']);
            } else {
                $route = $namePrefix . 'func';
            }
            // clean up default type
            unset($params['type']);
            return $router->makeUri($route, $params);
        }
        // module other func
        $route = $namePrefix . 'type-func';
        return $router->makeUri($route, $params);
    }

    /**
     * Get route handler for default routes here
     * @param string $route
     * @param ?Context<string, mixed> $context
     * @return HandlerInterface
     */
    public static function getHandler(string $route, ?Context $context, $xar = null): HandlerInterface
    {
        // we could provide different instance or handler based on route here
        $handler = new DefaultHandler($xar);
        $handler->setContext($context);
        return $handler;
    }
}
