<?php

/**
 * Authsystem routes class for routing & dispatching outside Xaraya
 *
 * Experiment using module classes and methods as handler
 */

namespace Xaraya\Modules\Authsystem;

use Xaraya\Routing\ModuleRoutes;
use Xaraya\Routing\RouterInterface;

/**
 * Authsystem routes class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * ```
 * /authsystem/
 * /authsystem/login
 * /authsystem/auth
 * /authsystem/logout
 * /authsystem/password
 * /authsystem/{func} (not used here)
 * /authsystem/admin/{func} (not used here)
 * ```
 * @phpstan-import-type RouteDef from ModuleRoutes
 */
class AuthsystemRoutes extends ModuleRoutes
{
    public static string $moduleName = 'authsystem';
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

        $path = $pathPrefix . '/login';
        $name = $namePrefix . 'login';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'showloginform'], $extra];

        $path = $pathPrefix . '/auth';
        $name = $namePrefix . 'auth';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'login'], $extra];

        $path = $pathPrefix . '/logout';
        $name = $namePrefix . 'logout';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'logout'], $extra];

        $path = $pathPrefix . '/password';
        $name = $namePrefix . 'password';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'password'], $extra];

        // default method here - see short.php
        $path = $pathPrefix . '/{func}';
        $name = $namePrefix . 'func';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'showloginform'], $extra];

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
                $route = match ($params['func']) {
                    'main' => $namePrefix . 'main',
                    'showloginform' => $namePrefix . 'login',
                    'login' => $namePrefix . 'auth',
                    'logout' => $namePrefix . 'logout',
                    'password' => $namePrefix . 'password',
                    default => $namePrefix . 'login',  // should be 'func' but maps to login anyway
                };
                // clean up current func
                unset($params['func']);
                break;

            default:
                // module other func
                return null;
        }
        // clean up current type
        unset($params['type']);
        return $router->makeUri($route, $params);
    }
}
