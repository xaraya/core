<?php

/**
 * @package core\bridge
 * @subpackage graphql
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Bridge\GraphQL;

use Xaraya\Context\Context;
use Xaraya\Routing\HandlerInterface;
use Xaraya\Routing\RoutesInterface;
use Xaraya\Routing\RouterInterface;

/**
 * Class to define GraphQL routes
 * @phpstan-type RouteDef array{0: string|array<string>, 1: string, 2: mixed, 3: array<string, mixed>}
 */
class GraphQLRoutes implements RoutesInterface
{
    public static string $pathPrefix = '/graphql';
    public static string $namePrefix = 'graphql';

    /**
     * Get supported handler routes (in generic format)
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = ''): array
    {
        $handler = GraphQLHandler::class;
        $extra = [];
        $routes = [];

        $path = $pathPrefix . static::$pathPrefix;
        $name = $namePrefix . static::$namePrefix;
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handleRequest'], $extra];

        return $routes;
    }

    /**
     * Find route uri based on params
     * @param array<string, mixed> $params
     */
    public static function findRoute(RouterInterface $router, array $params): ?string
    {
        // we have a route already
        if (!empty($params[$router::ROUTE_PARAM])) {
            return $router->makeUri(null, $params);
        }
        $route = 'graphql';
        return $router->makeUri($route, $params);
    }

    /**
     * Get route handler for graphql routes here - @todo align with graphql handling
     * @param string $route
     * @param ?Context<string, mixed> $context
     * @return HandlerInterface
     */
    public static function getHandler(string $route, ?Context $context, $xar = null): HandlerInterface
    {
        // we could provide different instance or handler based on route here
        $handler = new GraphQLHandler($xar);
        $handler->setContext($context);
        return $handler;
    }
}
