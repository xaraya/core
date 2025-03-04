<?php

/**
 * Module handler class for routing & dispatching outside Xaraya
 *
 * @todo experiment using module classes and methods as handler
 */

namespace Xaraya\Routing;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Xaraya\Context\ContextTrait;
use Xaraya\Modules\ModuleInterface;
use Xaraya\Modules\ModuleServicesInterface;
use xarClassMap;
use FunctionNotFoundException;

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
    protected string $funcName;

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
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'admingui'], $extra];

        // not supported here
        $path = $pathPrefix . '/admin/{func}/{more:.+}';
        $name = $namePrefix . 'admin-more';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'admingui'], $extra];

        if (static::$moduleName != static::$objectName) {
            // if there is no overlap between module user func and dataobject entity, e.g. dynamicdata
            $path = $pathPrefix . '/{func}';
            $name = $namePrefix . 'user';
            $routes[$name] = [['GET', 'POST'], $path, [$handler, 'usergui'], $extra];

            $path = $pathPrefix . '/{func}/{more:.+}';
            $name = $namePrefix . 'user-more';
            $routes[$name] = [['GET', 'POST'], $path, [$handler, 'usergui'], $extra];
        } else {
            // if there is overlap between module user func and dataobject entity, e.g. library
            $path = $pathPrefix . '/user/{func}';
            $name = $namePrefix . 'user';
            $routes[$name] = [['GET', 'POST'], $path, [$handler, 'usergui'], $extra];

            $path = $pathPrefix . '/user/{func}/{more:.+}';
            $name = $namePrefix . 'user-more';
            $routes[$name] = [['GET', 'POST'], $path, [$handler, 'usergui'], $extra];
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
     * Find route uri based on params
     * @param array<string, mixed> $params
     */
    public static function findRoute(RouterInterface $router, array $params): string|null
    {
        // we have a route already
        if (!empty($params[HandlerInterface::ROUTE_PARAM])) {
            $route = $params[HandlerInterface::ROUTE_PARAM];
            // clean up current route
            unset($params[HandlerInterface::ROUTE_PARAM]);
            return static::makeUri($router, $route, $params);
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
                if (!empty($params['more'])) {
                    $route = $namePrefix . 'admin-more';
                } else {
                    $route = $namePrefix . 'admin';
                }
                break;

            case 'user':
                // find dataobject route for this module - @todo with any user func here?
                if (!empty(static::$objectName) && !empty($params['entity'])) {
                    return null;
                }
                // module user func
                if (!empty($params['more'])) {
                    $route = $namePrefix . 'user-more';
                } elseif ($params['func'] != 'main') {
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
        return static::makeUri($router, $route, $params);
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
            return static::makeUri($router, $route, $params);
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
            return static::makeUri($router, $route, $params);
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
        return static::makeUri($router, $route, $params);
    }

    /**
     * Summary of makeUri
     * @param array<string, mixed> $params
     */
    public static function makeUri(RouterInterface $router, string $route, array $params = []): string|null
    {
        if (empty($route)) {
            return null;
        }
        try {
            return $router->generate($route, $params);
            // @todo replace 1234567890 with [itemid] for defer* properties
        } catch (RouteNotFoundException $e) {
            // ...
            return null;
        }
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
        // assuming $handler[0] is \Xaraya\Modules\...\UserGui class here
        if ($handler[1] == 'admingui') {
            // ... replace usergui instance with admingui instance
            $handler[0] = $handler[0]->admingui();
            if (empty($handler[0])) {
                throw new FunctionNotFoundException('AdminGui');
            }
            $this->instance = $handler[0];
            $handler[1] = $vars['func'] ?? 'main';
            if (!$handler[0]->hasMethod($handler[1], 'gui')) {
                throw new FunctionNotFoundException($handler[1]);
            }
            $this->funcName = $handler[1];
        }
        if ($handler[1] == 'usergui') {
            // ... replace method with $vars['func']
            $handler[1] = $vars['func'] ?? 'main';
            if (!$handler[0]->hasMethod($handler[1], 'gui')) {
                throw new FunctionNotFoundException($handler[1]);
            }
            $this->funcName = $handler[1];
        }
        $result = $handler($vars);
        // @todo do not apply template here (yet)?
        if (is_array($result)) {
            $result = $handler[0]->mod()->template($handler[1], $result);
        }
        return [$result, $this->getContext()];
    }

    /**
     * Summary of getHandler
     * @param mixed $handler
     * @return array{0: ModuleServicesInterface, 1: string}
     * @see \Xaraya\Bridge\Routing\RoutingBridge::getHandler()
     */
    public function getHandler(mixed $handler): mixed
    {
        $this->instance = $this->getInstance();
        $this->funcName = $handler[1];
        return [$this->instance, $this->funcName];
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
        // @todo apply template here?
        //if (is_array($result)) {
        //    $result = $this->instance->mod()->template($this->funcName, $result);
        //}
        if (is_string($result)) {
            return $result;
        }
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
