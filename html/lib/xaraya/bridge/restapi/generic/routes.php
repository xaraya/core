<?php

/**
 * @package core\bridge
 * @subpackage restapi
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Bridge\RestAPI;

use Xaraya\Routing\RoutesInterface;
use Xaraya\Routing\RouterInterface;

/**
 * Class to define Generic REST API routes
 * @phpstan-import-type RouteDef from RestAPIRoutes
 */
class GenericAPIRoutes extends RestAPIRoutes
{
    public static string $pathPrefix = '';
    public static string $namePrefix = 'generic-';
    public static mixed $handlerClass = GenericAPIHandler::class;

    /**
     * Get Generic REST API routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param mixed $restHandler
     * @param array<mixed> $extra
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = '', mixed $restHandler = null, array $extra = []): array
    {
        $pathPrefix .= self::$pathPrefix;
        $namePrefix .= self::$namePrefix;
        $restHandler ??= self::$handlerClass;
        $routes = [];

        $path = $pathPrefix . '/whoami';
        $name = $namePrefix . 'whoami';
        $routes[$name] = ['GET', $path, [$restHandler, 'whoami'], $extra];

        $path = $pathPrefix . '/context';
        $name = $namePrefix . 'getContext';
        $routes[$name] = ['GET', $path, [$restHandler, 'showContext'], $extra];

        $path = $pathPrefix . '/token';
        $name = $namePrefix . 'postToken';
        $routes[$name] = ['POST', $path, [$restHandler, 'postToken'], $extra];

        $path = $pathPrefix . '/token';
        $name = $namePrefix . 'deleteToken';
        $routes[$name] = ['DELETE', $path, [$restHandler, 'deleteToken'], $extra];

        return $routes;
    }

    /**
     * Find route uri based on params
     * @param array<string, mixed> $params
     */
    public static function findRoute(RouterInterface $router, array $params, string $method = 'GET', string $namePrefix = ''): string|null
    {
        // we have a route already
        if (!empty($params[RoutesInterface::ROUTE_PARAM])) {
            $route = $params[RoutesInterface::ROUTE_PARAM];
            // clean up current route
            unset($params[RoutesInterface::ROUTE_PARAM]);
            return static::makeUri($router, $route, $params);
        }
        $namePrefix .= static::$namePrefix;
        // @todo make use of method here too!?
        $route = null;
        return $route;
    }
}
