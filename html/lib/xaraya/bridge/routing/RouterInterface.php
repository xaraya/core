<?php

/**
 * Common router interface for FastRouter and Symfony Routing
 */

namespace Xaraya\Routing;

/**
 * Common router interface for FastRouter and Symfony Routing
 */
interface RouterInterface
{
    public const ROUTE_PARAM = '_route';

    /**
     * Summary of __construct
     * @param callable $callable get array of name => [method(s), path, handler, options = []]
     * @param string $cacheFile leave empty to disable router cache
     */
    public function __construct($callable, $cacheFile = '');

    /**
     * Match path with optional method
     * @param string $path
     * @param ?string $method
     * @return array<mixed> array of [handler, path params] or [null, ['status' => 40x]] if not found
     */
    public function match($path, $method = null);

    /**
     * Generate URL path for route name and params or throw exception
     * @param string $name
     * @param array<string, mixed> $params
     * @return string|null
     */
    public function generate($name, $params);

    /**
     * Make URI for route name and params or return null
     * @param array<string, mixed> $params
     */
    public function makeUri(?string $route, array $params = []): ?string;

    /**
     * Check last modified cache file against reference file
     * @param string $filePath
     * @return void
     */
    public function checkCache($filePath);

    /**
     * Get list of loaded routes
     * @return array<mixed>
     */
    public function getRoutes();
}
