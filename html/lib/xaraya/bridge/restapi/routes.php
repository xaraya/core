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

/**
 * Class to define REST API routes
 */
class RestAPIRoutes
{
    public static string $pathPrefix = '/v1';
    public static string $namePrefix = 'restapi-';
    /** @var class-string<RestAPIHandler>|RestAPIHandler|null */
    public static mixed $handlerClass = null;

    /**
     * Summary of setPathPrefix
     * @param string $pathPrefix
     * @return void
     */
    public static function setPathPrefix(string $pathPrefix): void
    {
        self::$pathPrefix = $pathPrefix;
    }

    /**
     * Summary of setNamePrefix
     * @param string $namePrefix
     * @return void
     */
    public static function setNamePrefix(string $namePrefix): void
    {
        self::$namePrefix = $namePrefix;
    }

    /**
     * Summary of setHandlerClass
     * @param mixed $handlerClass
     * @return void
     */
    public static function setHandlerClass(mixed $handlerClass): void
    {
        self::$handlerClass = $handlerClass;
    }

    /**
     * Get REST API routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param mixed $restHandler
     * @param array<mixed> $extra
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = '', mixed $restHandler = null, array $extra = []): array
    {
        // default null handler here = let specific sub-handlers deal with requests
        $restHandler ??= self::$handlerClass;
        $routes = [];

        // get openapi without /v1 prefix + use generic restapi handler here
        $path = $pathPrefix . '/';
        $name = $namePrefix . 'openapi';
        $routes[$name] = ['GET', $path, [$restHandler ?? RestAPIHandler::class, 'getOpenAPI'], $extra];

        $pathPrefix .= self::$pathPrefix;
        $namePrefix .= self::$namePrefix;

        $routes = array_merge($routes, DataObjectAPIRoutes::getRoutes($pathPrefix, $namePrefix, $restHandler, $extra));
        $routes = array_merge($routes, GenericAPIRoutes::getRoutes($pathPrefix, $namePrefix, $restHandler, $extra));
        $routes = array_merge($routes, ModuleAPIRoutes::getRoutes($pathPrefix, $namePrefix, $restHandler, $extra));

        return $routes;
    }
}
