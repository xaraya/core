<?php

/**
 * DynamicData handler class for routing & dispatching outside Xaraya
 *
 * @todo experiment using module classes and methods as handler
 */

namespace Xaraya\Modules\DynamicData;

use Xaraya\Routing\ModuleHandler;
use Xaraya\Routing\RouterInterface;

/**
 * DynamicData handler class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * ```
 * /dynamicdata/
 * /dynamicdata/view/{itemtype}
 * /dynamicdata/view/{name}
 * /dynamicdata/view/{name}/{itemid}
 * /dynamicdata/search/{itemtype}
 * /dynamicdata/search/{name}
 * /dynamicdata/admin/{func} (not used here)
 * /dynamicdata/{func} (not used here)
 * /object/{entity}/
 * /object/{entity}/{itemid} (numeric)
 * /object/{entity}/{itemid}/{title}
 * /object/{entity}/{action} (non-numeric)
 * /object/{entity}/{action}/{itemid}
 * ```
 */
class DynamicDataHandler extends ModuleHandler
{
    public static string $moduleName = 'dynamicdata';
    public static string $objectName = 'object';
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

        // custom routes first
        $path = $pathPrefix . '/view/{itemtype:\d+}';
        $name = $namePrefix . 'view-itemtype';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'view'], $extra];

        $path = $pathPrefix . '/view/{name:\w+}';
        $name = $namePrefix . 'view-name';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'view'], $extra];

        $path = $pathPrefix . '/view/{name:\w+}/{itemid}';
        $name = $namePrefix . 'display-itemid';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'display'], $extra];

        $path = $pathPrefix . '/search/{itemtype:\d+}';
        $name = $namePrefix . 'search-itemtype';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'search'], $extra];

        $path = $pathPrefix . '/search/{name:\w+}';
        $name = $namePrefix . 'search-name';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'search'], $extra];

        // add standard routes
        $routes = array_merge($routes, parent::getModuleRoutes($pathPrefix, $namePrefix, $handler, $extra));

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
        // custom routes first
        $name = static::findCustomRouteName($params);
        if (!empty($name)) {
            $route = $namePrefix . $name;
            unset($params['type']);
            unset($params['func']);
            // clean up tplmodule if possible
            if (!empty($params['tplmodule']) && $params['tplmodule'] == static::$moduleName) {
                unset($params['tplmodule']);
            }
            return static::makeUri($router, $route, $params);
        }

        // add standard routes
        return parent::findModuleRoute($router, $namePrefix, $params);
    }

    /**
     * Find custom route name for this module
     * @param array<string, mixed> $params
     */
    public static function findCustomRouteName(array $params): string|null
    {
        $params['type'] ??= 'user';
        if ($params['type'] != 'user') {
            return null;
        }
        // find dataobject route for this module - @todo with any user func here?
        if (!empty($params['entity'])) {
            return null;
        }
        $params['func'] ??= 'main';
        switch ($params['func']) {
            case 'view':
                if (!empty($params['itemtype'])) {
                    return 'view-itemtype';
                }
                if (!empty($params['name'])) {
                    return 'view-name';
                }
                break;
            case 'display':
                if (!empty($params['name']) && !empty($params['itemid'])) {
                    return 'display-itemid';
                }
                break;
            case 'search':
                if (!empty($params['itemtype'])) {
                    return 'search-itemtype';
                }
                if (!empty($params['name'])) {
                    return 'search-name';
                }
                break;
            default:
                break;
        }
        return null;
    }
}
