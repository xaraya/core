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
    /**
     * Get supported handler routes (in generic format)
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = ''): array;

    /**
     * Call the right handler after matching the route
     * @param array<string, mixed> $vars
     */
    public function callHandler(mixed $handler, array $vars = []): mixed;
}

/**
 * Module handler class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * $pathPrefix/$moduleName/
 * $pathPrefix/$moduleName/admin/... (not used here)
 * $pathPrefix/$moduleName/{entity}/
 * $pathPrefix/$moduleName/{entity}/{itemid} (numeric)
 * $pathPrefix/$moduleName/{entity}/{itemid}/{title}
 * $pathPrefix/$moduleName/{entity}/{action} (non-numeric)
 * $pathPrefix/$moduleName/{entity}/{action}/{itemid}
 */
class ModuleHandler implements HandlerInterface
{
    use ContextTrait;

    public static string $moduleName = '';
    /** @var class-string */
    public static string $handlerClass = '';
    protected ModuleServicesInterface $instance;

    /**
     * Get supported handler routes (in generic format)
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = ''): array
    {
        $pathPrefix .= '/' . static::$moduleName;
        $namePrefix .= static::$moduleName . '-';
        //$handler = static::$handlerClass;
        $handler = static::class;
        $extra = [];
        $routes = [];

        // with trailing /
        $path = $pathPrefix . '/';
        $name = $namePrefix . 'main';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'main'], $extra];

        // not supported here
        $path = $pathPrefix . '/admin/{more:.+}';
        $name = $namePrefix . 'admin';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'admin'], $extra];

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

        // @todo add some /api routes here too?

        return $routes;
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
        $handler = $this->getHandler($handler);
        $result = $handler($vars);
        return [$result, $this->getContext()];
    }

    /**
     * Summary of getHandler
     * @param mixed $handler
     * @see \Xaraya\Bridge\Routing\RoutingBridge::getHandler()
     */
    public function getHandler(mixed $handler)
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
        $handler = new (static::$handlerClass)(static::$moduleName, $module);
        $handler->setContext($this->getContext());
        return $handler;
    }

    /**
     * Summary of getModule
     */
    public function getModule(): ModuleInterface
    {
        $result = xarClassMap::findModuleClass(static::$moduleName);
        $module = new $result['classname'](static::$moduleName);
        $module->setContext($this->getContext());
        return $module;
    }

    /**
     * Summary of output
     * @param mixed $result
     * @param mixed $transform
     * @return string
     * @see \Xaraya\Bridge\Routing\RoutingBridge::output()
     */
    public function output($result, $transform = null)
    {
        if (is_string($result)) {
            return $result;
        }
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
