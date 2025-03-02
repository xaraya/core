<?php

/**
 * Generic handler interface for routing & dispatching outside Xaraya
 *
 * @todo experiment using module classes and methods as handler
 */

namespace Xaraya\Routing;

use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use Xaraya\Modules\ModuleInterface;
use Xaraya\Modules\ModuleServicesInterface;
use xarClassMap;

/**
 * Generic handler interface for routing & dispatching outside Xaraya
 */
interface HandlerInterface extends ContextInterface
{
    public const ROUTE_PARAM = '_route';

    /**
     * Get supported handler routes (in generic format)
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = ''): array;

    /**
     * Find route name based on params
     * @param array<string, mixed> $params
     */
    public static function findRoute(array $params): string|null;

    /**
     * Call the right handler after matching the route
     * @param array<string, mixed> $vars
     */
    public function callHandler(mixed $handler, array $vars = []): mixed;

    /**
     * Create output for result
     */
    public function output(mixed $result, mixed $transform = null): string;
}

/**
 * Module handler class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * ```
 * $pathPrefix/$moduleName/
 * $pathPrefix/$moduleName/admin/{func} (not used here)
 * $pathPrefix/$moduleName/admin/{func}/{more} (not used here)
 * $pathPrefix/$moduleName[/user]/{func} (not used here)
 * $pathPrefix/$moduleName[/user]/{func}/{more} (not used here)
 * $pathPrefix/$objectName/{entity}/
 * $pathPrefix/$objectName/{entity}/{itemid} (numeric)
 * $pathPrefix/$objectName/{entity}/{itemid}/{title}
 * $pathPrefix/$objectName/{entity}/{action} (non-numeric)
 * $pathPrefix/$objectName/{entity}/{action}/{itemid}
 * ```
 */
class ModuleHandler implements HandlerInterface
{
    use ContextTrait;

    public static string $moduleName = '';
    public static string $objectName = '';
    /** @var class-string<ModuleServicesInterface> */
    public static string $handlerClass = '';
    protected ModuleServicesInterface $instance;

    /**
     * Get supported handler routes (in generic format)
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = ''): array
    {
        //$handler = static::$handlerClass;
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

        // not supported here
        $path = $pathPrefix . '/admin/{func}';
        $name = $namePrefix . 'admin';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'admin'], $extra];

        // not supported here
        $path = $pathPrefix . '/admin/{func}/{more:.+}';
        $name = $namePrefix . 'admin-more';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'admin'], $extra];

        if (static::$moduleName != static::$objectName) {
            // if there is no overlap between module user func and dataobject entity, e.g. dynamicdata
            $path = $pathPrefix . '/{func}';
            $name = $namePrefix . 'user';
            $routes[$name] = [['GET', 'POST'], $path, [$handler, 'user'], $extra];

            $path = $pathPrefix . '/{func}/{more:.+}';
            $name = $namePrefix . 'user-more';
            $routes[$name] = [['GET', 'POST'], $path, [$handler, 'user'], $extra];
        } else {
            // if there is overlap between module user func and dataobject entity, e.g. library
            $path = $pathPrefix . '/user/{func}';
            $name = $namePrefix . 'user';
            $routes[$name] = [['GET', 'POST'], $path, [$handler, 'user'], $extra];

            $path = $pathPrefix . '/user/{func}/{more:.+}';
            $name = $namePrefix . 'user-more';
            $routes[$name] = [['GET', 'POST'], $path, [$handler, 'user'], $extra];
        }

        // @todo add some /api routes here too?

        return $routes;
    }

    /**
     * Summary of getObjectRoutes
     * @param string $pathPrefix incl. objectName
     * @param string $namePrefix incl. objectName
     * @param mixed $handler
     * @param array<string, mixed> $extra
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getObjectRoutes(string $pathPrefix = '', string $namePrefix = '', mixed $handler = null, array $extra = []): array
    {
        $handler ??= static::class;
        $routes = [];

        // use entity and action to avoid conflict with module & func or object & method
        $path = $pathPrefix . '/{entity}/';
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
     * Find route name based on params
     * @param array<string, mixed> $params
     */
    public static function findRoute(array $params): string|null
    {
        // we have a route already
        if (!empty($params[HandlerInterface::ROUTE_PARAM])) {
            return $params[HandlerInterface::ROUTE_PARAM];
        }
        // this is not the right module
        if (!empty($params['module']) && $params['module'] != static::$moduleName) {
            return null;
        }
        $route = null;
        // find module route name or dataobject route name
        if (!isset($route) && !empty(static::$moduleName)) {
            $namePrefix = static::$moduleName . '-';
            $route = static::findModuleRoute($namePrefix, $params);
        }
        if (!isset($route) && !empty(static::$objectName)) {
            $namePrefix = static::$objectName . '-';
            $route = static::findObjectRoute($namePrefix, $params);
        }
        return $route;
    }

    /**
     * Find module route name based on params
     * @param string $namePrefix incl. moduleName
     * @param array<string, mixed> $params
     */
    public static function findModuleRoute(string $namePrefix = '', array $params = []): string|null
    {
        // module admin func
        if (!empty($params['type']) && $params['type'] == 'admin') {
            if (!empty($params['func']) && !empty($params['more'])) {
                return $namePrefix . 'admin-more';
            }
            return $namePrefix . 'admin';
        }
        // module user func
        if (!empty($params['type']) && $params['type'] == 'user') {
            if (!empty($params['func']) && !empty($params['more'])) {
                return $namePrefix . 'user-more';
            }
            if (!empty($params['func']) && $params['func'] != 'main') {
                return $namePrefix . 'user';
            }
            return $namePrefix . 'main';
        }
        // module user main
        if (empty($params['entity'])) {
            return $namePrefix . 'main';
        }
        return null;
    }

    /**
     * Find dataobject route name based on params
     * @param string $namePrefix incl. objectName
     * @param array<string, mixed> $params
     */
    public static function findObjectRoute(string $namePrefix = '', array $params = []): string|null
    {
        if (empty($params['entity'])) {
            return $namePrefix . 'main';
        }
        // dataobject display
        if (!empty($params['itemid'])) {
            if (!empty($params['action']) && $params['action'] != 'display') {
                return $namePrefix . 'entity-action-itemid';
            }
            if (!empty($params['title'])) {
                return $namePrefix . 'entity-itemid-title';
            }
            return $namePrefix . 'entity-itemid';
        }
        // dataobject other
        if (!empty($params['action']) && $params['action'] != 'view') {
            return $namePrefix . 'entity-action';
        }
        // dataobject view
        return $namePrefix . 'entity';
    }

    public function __construct()
    {
        // ...
    }

    /**
     * Call the right handler after matching the route
     * @param array<string, mixed> $vars
     * @see \Xaraya\Bridge\Routing\RoutingBridge::callHandler()
     */
    public function callHandler(mixed $handler, array $vars = []): mixed
    {
        $this->getContext()?->tracePath(__METHOD__, $handler);
        $handler = $this->getHandler($handler);
        if ($handler[1] == 'admin') {
            // ... replace usergui instance with admingui instance
        }
        if ($handler[1] == 'user') {
            // ... replace method with $vars['func']
        }
        $result = $handler($vars);
        return [$result, $this->getContext()];
    }

    /**
     * Summary of getHandler
     * @param mixed $handler
     * @see \Xaraya\Bridge\Routing\RoutingBridge::getHandler()
     */
    public function getHandler(mixed $handler): mixed
    {
        $handler[0] = $this->getInstance();
        return $handler;
    }

    /**
     * Summary of getInstance
     */
    public function getInstance(): ModuleServicesInterface
    {
        $module = static::getModule();
        $instance = new (static::$handlerClass)(static::$moduleName, $module);
        $instance->setContext($this->getContext());
        return $instance;
    }

    /**
     * Summary of getModule
     */
    public function getModule(): ModuleInterface
    {
        $result = xarClassMap::findModuleClass(static::$moduleName);
        /** @var ModuleInterface $module */
        $module = new $result['classname'](static::$moduleName);
        $module->setContext($this->getContext());
        return $module;
    }

    /**
     * Create output for result - @todo
     * @see \Xaraya\Bridge\Routing\RoutingBridge::output()
     */
    public function output(mixed $result, mixed $transform = null): string
    {
        if (is_string($result)) {
            return $result;
        }
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
