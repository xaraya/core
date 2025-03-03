<?php

/**
 * Default handler class for routing & dispatching outside Xaraya
 *
 * @todo experiment using module classes and methods as handler
 */

namespace Xaraya\Routing;

use Xaraya\Services\ServiceFactory;

/**
 * Default handler class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * ```
 * /
 * /{module}/ (with trailing / here)
 * /{module}/{func}
 * /{module}/{type}/{func}
 * ```
 */
class DefaultHandler extends ModuleHandler
{
    public static string $moduleName = 'default';
    public static string $objectName = '';
    // parent for modules service here
    protected string $modName = '';
    protected string $modType = '';
    protected int $itemType = 0;

    /**
     * Get supported handler routes (in generic format)
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
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
     * @param mixed $handler
     * @param array<string, mixed> $extra
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getModuleRoutes(string $pathPrefix = '', string $namePrefix = '', mixed $handler = null, array $extra = []): array
    {
        $handler ??= static::class;
        $routes = [];

        // home page
        $path = $pathPrefix . '/';
        $name = $namePrefix . 'home';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handle'], $extra];

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
     * Find route name based on params
     * @param array<string, mixed> $params
     */
    public static function findRoute(array $params): string|null
    {
        // we have a route already
        if (!empty($params[HandlerInterface::ROUTE_PARAM])) {
            return $params[HandlerInterface::ROUTE_PARAM];
        }
        // for any module that doesn't have its own handler
        $namePrefix = static::$moduleName . '-';
        $route = static::findModuleRoute($namePrefix, $params);
        return $route;
    }

    /**
     * Find module route name based on params
     * @param string $namePrefix incl. moduleName
     * @param array<string, mixed> $params
     */
    public static function findModuleRoute(string $namePrefix = '', array $params = []): string|null
    {
        // we have no module
        if (empty($params['module'])) {
            return $namePrefix . 'home';
        }
        // module user func
        if (empty($params['type']) || $params['type'] == 'user') {
            if (empty($params['func']) || $params['func'] == 'main') {
                return $namePrefix . 'main';
            }
            return $namePrefix . 'func';
        }
        // module other func
        return $namePrefix . 'type-func';
    }

    /**
     * Summary of getHandler
     * @param mixed $handler
     * @return array{0: HandlerInterface, 1: string}
     */
    public function getHandler(mixed $handler): mixed
    {
        // handle default routes here
        $this->funcName = $handler[1];
        return [$this, $this->funcName];
    }

    /**
     * Handle default routes for other modules
     * @param array<string, mixed> $args
     * @throws \FunctionNotFoundException
     * @return mixed
     */
    public function handle(array $args = [])
    {
        $modName = $args['module'] ?? 'base';
        $modType = $args['type'] ?? 'user';
        $funcName = $args['func'] ?? 'main';
        unset($args['module']);
        unset($args['type']);
        unset($args['func']);
        // parent for modules service here
        $this->modName = $modName;
        $this->modType = $modType;
        $xarMod = ServiceFactory::getModulesService($this);
        $result = $xarMod->guiMethod($modName, $modType, $funcName, $args);
        // always apply template here
        if (is_array($result)) {
            $result = $xarMod->template($funcName, $result);
        }
        return $result;
    }

    public function getModName(): string
    {
        return $this->modName;
    }

    public function getModType(): string
    {
        return $this->modType;
    }

    public function getItemType(): int
    {
        return $this->itemType;
    }
}
