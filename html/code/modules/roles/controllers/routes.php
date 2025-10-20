<?php

/**
 * Roles routes class for routing & dispatching outside Xaraya
 *
 * Experiment using module classes and methods as handler
 */

namespace Xaraya\Modules\Roles;

use Xaraya\Routing\ModuleRoutes;
use Xaraya\Routing\RouterInterface;

/**
 * Roles routes class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * ```
 * /roles/
 * /roles/account
 * /roles/account/{tab}
 * /roles/language
 * /roles/password
 * /roles/usermenu
 * /roles/validate
 * /roles/{func} (not used here)
 * /roles/admin/{func} (not used here)
 * ```
 * @phpstan-import-type RouteDef from ModuleRoutes
 */
class RolesRoutes extends ModuleRoutes
{
    public static string $moduleName = 'roles';
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

        $path = $pathPrefix . '/account';
        $name = $namePrefix . 'account';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'account'], $extra];

        $path = $pathPrefix . '/account/{tab}';
        $name = $namePrefix . 'account-tab';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'account'], $extra];

        $path = $pathPrefix . '/language';
        $name = $namePrefix . 'language';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'changelanguage'], $extra];

        $path = $pathPrefix . '/password';
        $name = $namePrefix . 'password';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'lostpassword'], $extra];

        $path = $pathPrefix . '/usermenu';
        $name = $namePrefix . 'usermenu';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'usermenu'], $extra];

        $path = $pathPrefix . '/validate';
        $name = $namePrefix . 'validation';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'getvalidation'], $extra];

        // @todo add/check other short urls

        // default method here - see short.php
        $path = $pathPrefix . '/{func}';
        $name = $namePrefix . 'func';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'account'], $extra];

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
    public static function findRoute(RouterInterface $router, array $params): ?string
    {
        return parent::findRoute($router, $params);
    }

    /**
     * Find module route name based on params
     * @param string $namePrefix incl. moduleName
     * @param array<string, mixed> $params
     */
    public static function findModuleRoute(RouterInterface $router, string $namePrefix = '', array $params = []): ?string
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
                switch ($params['func']) {
                    case 'main':
                        $route = $namePrefix . 'main';
                        break;
                    case 'account':
                        if (!empty($params['tab'])) {
                            $route = $namePrefix . 'account-tab';
                        } else {
                            $route = $namePrefix . 'account';
                        }
                        // @todo add moduleload?
                        break;
                    case 'changelanguage':
                        $route = $namePrefix . 'language';
                        break;
                    case 'lostpassword':
                        $route = $namePrefix . 'password';
                        break;
                    case 'usermenu':
                        $route = $namePrefix . 'usermenu';
                        break;
                    case 'getvalidation':
                        $route = $namePrefix . 'validate';
                        break;
                    default:
                        // should be 'func' but maps to account anyway
                        $route = $namePrefix . 'account';
                        break;
                }
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
