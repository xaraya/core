<?php

/**
 * Generic routes interface for routing & dispatching outside Xaraya
 *
 * @todo experiment using module classes and methods as handler
 */

namespace Xaraya\Routing;

/**
 * Generic routes interface for routing & dispatching outside Xaraya
 */
interface RoutesInterface
{
    public const ROUTE_PARAM = '_route';

    /**
     * Get supported handler routes (in generic format)
     * @return array<string, array<mixed>> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = ''): array;

    /**
     * Find route uri based on params
     * @param array<string, mixed> $params
     */
    public static function findRoute(RouterInterface $router, array $params): string|null;
}
