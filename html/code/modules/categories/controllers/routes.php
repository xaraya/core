<?php

/**
 * Categories routes class for routing & dispatching outside Xaraya
 *
 * Experiment using module classes and methods as handler
 */

namespace Xaraya\Modules\Categories;

use Xaraya\Routing\ModuleRoutes;
use Xaraya\Routing\RouterInterface;

/**
 * Categories routes class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * ```
 * /categories/
 * /categories/{catid}
 * /categories/{catid}/{path}
 * /categories/admin/{func} (not used here)
 * ```
 * @phpstan-import-type RouteDef from ModuleRoutes
 */
class CategoriesRoutes extends ModuleRoutes
{
    public static string $moduleName = 'categories';
    public static string $objectName = '';
    /** @var class-string */
    public static string $handlerClass = UserGui::class;

    /**
     * Get supported handler routes (in generic format)
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = ''): array
    {
        return parent::getRoutes($pathPrefix, $namePrefix);
    }

    /**
     * Summary of getModuleRoutes
     * @param string $pathPrefix incl. moduleName
     * @param string $namePrefix incl. moduleName
     * @param ?string $handler
     * @param array<string, mixed> $extra
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getModuleRoutes(string $pathPrefix = '', string $namePrefix = '', ?string $handler = null, array $extra = []): array
    {
        $handler ??= static::class;
        $routes = [];

        // with trailing /
        $path = $pathPrefix . '/';
        $name = $namePrefix . 'main';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'main'], $extra];

        $path = $pathPrefix . '/{catid:\d+}';
        $name = $namePrefix . 'catid';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'main'], $extra];

        $path = $pathPrefix . '/{catid:\d+}/{path:.+}';
        $name = $namePrefix . 'catid-path';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'main'], $extra];

        // not supported here
        $path = $pathPrefix . '/admin/{func}';
        $name = $namePrefix . 'admin';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'admingui'], $extra];

        return $routes;
    }

    /**
     * Find route uri based on params
     * @param array<string, mixed> $params
     */
    public static function findRoute(RouterInterface $router, array $params): string|null
    {
        return parent::findRoute($router, $params);
    }

    /**
     * Find module route name based on params
     * @param string $namePrefix incl. moduleName
     * @param array<string, mixed> $params
     */
    public static function findModuleRoute(RouterInterface $router, string $namePrefix = '', array $params = []): string|null
    {
        $params['type'] ??= 'user';
        $params['func'] ??= 'main';
        $route = null;
        switch ($params['type']) {
            case 'admin':
                // module admin func
                $route = $namePrefix . 'admin';
                break;

            case 'user':
                // module user func
                if ($params['func'] != 'main') {
                    // unsupported: nothing to see here
                    //$route = $namePrefix . 'user';
                    return null;
                }
                // module user main
                if (!empty($params['catid'])) {
                    if (!empty($params['path'])) {
                        $route = $namePrefix . 'catid-path';
                    } else {
                        $route = $namePrefix . 'catid';
                    }
                } else {
                    $route = $namePrefix . 'main';
                }
                // clean up default func
                unset($params['func']);
                break;

            default:
                // module other func
                return null;
        }
        // clean up current type
        unset($params['type']);
        return static::makeUri($router, $route, $params);
    }
}
