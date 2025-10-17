<?php

/**
 * Module routes class for routing & dispatching outside Xaraya
 *
 * Experiment using module classes and methods as handler
 */

namespace Xaraya\Routing;

use Xaraya\Context\Context;
use Xaraya\Modules\ModuleInterface;
use Xaraya\Modules\ModuleServicesInterface;
use xarClassMap;

/**
 * Module routes class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * ```
 * $pathPrefix/$moduleName/
 * $pathPrefix/$moduleName/admin/{func} (not used here)
 * $pathPrefix/$moduleName[/user]/{func} (not used here)
 * $pathPrefix/$objectName/{entity}
 * $pathPrefix/$objectName/{entity}/{itemid} (numeric)
 * $pathPrefix/$objectName/{entity}/{itemid}/{title}
 * $pathPrefix/$objectName/{entity}/{action} (non-numeric)
 * $pathPrefix/$objectName/{entity}/{action}/{itemid}
 * ```
 * @phpstan-type RouteDef array{0: string|array<string>, 1: string, 2: mixed, 3: array<string, mixed>}
 */
class ModuleRoutes implements RoutesInterface
{
    public static string $moduleName = '';
    public static string $objectName = '';
    /** @var class-string<ModuleServicesInterface> */
    public static string $handlerClass = '';

    /**
     * Get supported handler routes (in generic format)
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = ''): array
    {
        // Use routes class per module here to get the right handler & instance in Dispatcher
        $handler = static::class;
        $extra = [];
        $routes = [];

        if (!empty(static::$moduleName)) {
            $path = $pathPrefix . '/' . static::$moduleName;
            $name = $namePrefix . static::$moduleName . '-';
            $routes = array_merge($routes, static::getModuleRoutes($path, $name, $handler, $extra));
        }

        if (!empty(static::$objectName)) {
            $path = $pathPrefix . '/' . static::$objectName;
            $name = $namePrefix . static::$objectName . '-';
            $routes = array_merge($routes, static::getObjectRoutes($path, $name, $handler, $extra));
        }

        return $routes;
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

        // not supported here
        $path = $pathPrefix . '/admin/{func}';
        $name = $namePrefix . 'admin';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'admingui'], $extra];

        if (static::$moduleName != static::$objectName) {
            // if there is no overlap between module user func and dataobject entity, e.g. dynamicdata
            $path = $pathPrefix . '/{func}';
            $name = $namePrefix . 'user';
            $routes[$name] = [['GET', 'POST'], $path, [$handler, 'usergui'], $extra];
        } else {
            // if there is overlap between module user func and dataobject entity, e.g. library
            $path = $pathPrefix . '/user/{func}';
            $name = $namePrefix . 'user';
            $routes[$name] = [['GET', 'POST'], $path, [$handler, 'usergui'], $extra];
        }

        // Note: {module}/{type}/{func} is handled by the DefaultHandler if not in dataobject routes

        return $routes;
    }

    /**
     * Summary of getObjectRoutes
     * @param string $pathPrefix incl. objectName
     * @param string $namePrefix incl. objectName
     * @param ?string $handler
     * @param array<string, mixed> $extra
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     * @see \Xaraya\Modules\DynamicData\Traits\UserGuiTrait::handle()
     */
    public static function getObjectRoutes(string $pathPrefix = '', string $namePrefix = '', ?string $handler = null, array $extra = []): array
    {
        $handler ??= static::class;
        $routes = [];

        // use entity and action to avoid conflict with module & func or object & method
        $path = $pathPrefix . '/{entity}';
        $name = $namePrefix . 'entity';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handle'], $extra];

        // numeric
        $path = $pathPrefix . '/{entity}/{itemid:\d+}';
        $name = $namePrefix . 'entity-itemid';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handle'], $extra];

        $path = $pathPrefix . '/{entity}/{itemid:\d+}/{title}';
        $name = $namePrefix . 'entity-itemid-title';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handle'], $extra];

        // non-numeric
        $path = $pathPrefix . '/{entity}/{action:\D+}';
        $name = $namePrefix . 'entity-action';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handle'], $extra];

        $path = $pathPrefix . '/{entity}/{action:\D+}/{itemid:\d+}';
        $name = $namePrefix . 'entity-action-itemid';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handle'], $extra];

        return $routes;
    }

    /**
     * Find route uri based on params
     * @param array<string, mixed> $params
     */
    public static function findRoute(RouterInterface $router, array $params): string|null
    {
        // we have a route already
        if (!empty($params[$router::ROUTE_PARAM])) {
            return $router->makeUri(null, $params);
        }
        // this is not the right module
        if (!empty($params['module']) && $params['module'] != static::$moduleName) {
            return null;
        }
        // clean up current module
        unset($params['module']);
        $route = null;
        // find module route uri or dataobject route uri
        if (!isset($route) && !empty(static::$moduleName)) {
            $namePrefix = static::$moduleName . '-';
            $route = static::findModuleRoute($router, $namePrefix, $params);
        }
        if (!isset($route) && !empty(static::$objectName)) {
            $namePrefix = static::$objectName . '-';
            $route = static::findObjectRoute($router, $namePrefix, $params);
        }
        return $route;
    }

    /**
     * Find module route uri based on params
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
                // find dataobject route for this module - @todo with any user func here?
                if (!empty(static::$objectName) && !empty($params['entity'])) {
                    return null;
                }
                // module user func
                if ($params['func'] != 'main') {
                    $route = $namePrefix . 'user';
                } else {
                    // module user main
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
        return $router->makeUri($route, $params);
    }

    /**
     * Find dataobject route uri based on params
     * @param string $namePrefix incl. objectName
     * @param array<string, mixed> $params
     */
    public static function findObjectRoute(RouterInterface $router, string $namePrefix = '', array $params = []): string|null
    {
        // no dataobject here
        if (empty($params['entity'])) {
            $route = $namePrefix . 'main';
            return $router->makeUri($route, $params);
        }
        // clean up default type
        if (!empty($params['type']) && $params['type'] == 'user') {
            unset($params['type']);
            // clean up current func - @todo with any user func here?
            unset($params['func']);
        }
        // dataobject display or action
        if (!empty($params['itemid'])) {
            if (!empty($params['action']) && $params['action'] != 'display') {
                $route = $namePrefix . 'entity-action-itemid';
            } elseif (!empty($params['title'])) {
                $route = $namePrefix . 'entity-itemid-title';
                // clean up default action
                unset($params['action']);
            } else {
                $route = $namePrefix . 'entity-itemid';
                // clean up default action
                unset($params['action']);
            }
            return $router->makeUri($route, $params);
        }
        // dataobject other
        if (!empty($params['action']) && $params['action'] != 'view') {
            $route = $namePrefix . 'entity-action';
        } else {
            // dataobject view
            $route = $namePrefix . 'entity';
            // clean up default action
            unset($params['action']);
        }
        return $router->makeUri($route, $params);
    }

    /**
     * Get route handler for module UserGui class instance like ModuleHandler(\Xaraya\Modules\Base\UserGui())
     * @param string $route
     * @param ?Context<string, mixed> $context
     * @return HandlerInterface
     */
    public static function getHandler(string $route, ?Context $context): HandlerInterface
    {
        // we could provide different instance or handler based on route here
        $instance = static::getInstance($context);
        $handler = new ModuleHandler($instance, $context);
        return $handler;
    }

    /**
     * Get module UserGui class instance like \Xaraya\Modules\Base\UserGui()
     * @param ?Context<string, mixed> $context
     */
    public static function getInstance(?Context $context): ModuleServicesInterface
    {
        $module = static::getModule($context);
        $instance = new (static::$handlerClass)(static::$moduleName, $module, $context);
        return $instance;
    }

    /**
     * Get module class like \Xaraya\Modules\Base\Module()
     * @param ?Context<string, mixed> $context
     */
    public static function getModule(?Context $context): ModuleInterface
    {
        $result = xarClassMap::findModuleClass(static::$moduleName);
        /** @var ModuleInterface $module */
        $module = new $result['classname'](static::$moduleName, $context);
        return $module;
    }
}
