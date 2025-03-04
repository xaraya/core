<?php

/**
 * Base handler class for routing & dispatching outside Xaraya
 *
 * @todo experiment using module classes and methods as handler
 */

namespace Xaraya\Modules\Base;

use Xaraya\Routing\ModuleHandler;
use Xaraya\Routing\RouterInterface;

/**
 * Base handler class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * ```
 * /base/
 * /base/{page}
 * /base/admin/{func} (not used here)
 * /base/admin/{func}/{more} (not used here)
 * /base/{func}/{more} (not used here)
 * ```
 */
class BaseHandler extends ModuleHandler
{
    public static string $moduleName = 'base';
    public static string $objectName = '';
    /** @var class-string */
    public static string $handlerClass = UserGui::class;

    /**
     * Get supported handler routes (in generic format)
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = ''): array
    {
        return parent::getRoutes($pathPrefix, $namePrefix);
    }

    /**
     * Summary of getModuleRoutes
     * @param string $pathPrefix incl. moduleName
     * @param string $namePrefix incl. moduleName
     * @param mixed $handler
     * @param array<string, mixed> $extra
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getModuleRoutes(string $pathPrefix = '', string $namePrefix = '', mixed $handler = null, array $extra = []): array
    {
        $handler ??= static::class;
        $routes = [];

        // with trailing /
        $path = $pathPrefix . '/';
        $name = $namePrefix . 'main';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'main'], $extra];

        $path = $pathPrefix . '/errors';
        $name = $namePrefix . 'errors';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'errors'], $extra];

        $path = $pathPrefix . '/{page}';
        $name = $namePrefix . 'page';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'main'], $extra];

        // not supported here
        $path = $pathPrefix . '/admin/{func}';
        $name = $namePrefix . 'admin';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'admingui'], $extra];

        // not supported here
        $path = $pathPrefix . '/admin/{func}/{more:.+}';
        $name = $namePrefix . 'admin-more';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'admingui'], $extra];

        // if there is no overlap between module user func and dataobject entity, e.g. base
        $path = $pathPrefix . '/{func}/{more:.+}';
        $name = $namePrefix . 'user-more';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'usergui'], $extra];

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
                if (!empty($params['more'])) {
                    $route = $namePrefix . 'admin-more';
                } else {
                    $route = $namePrefix . 'admin';
                }
                break;

            case 'user':
                // module user func
                if (!empty($params['more'])) {
                    $route = $namePrefix . 'user-more';
                } elseif ($params['func'] == 'errors') {
                    $route = $namePrefix . 'errors';
                    // clean up current func
                    unset($params['func']);
                } elseif ($params['func'] != 'main') {
                    // unsupported: overlaps with /base/{page} here
                    //$route = $namePrefix . 'user';
                    return null;
                } elseif (!empty($params['page'])) {
                    $route = $namePrefix . 'page';
                    // clean up default func
                    unset($params['func']);
                } else {
                    $route = $namePrefix . 'main';
                    // clean up default func
                    unset($params['func']);
                }
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
