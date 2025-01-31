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
    /**
     * Get REST API routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param mixed $restHandler
     * @param array<mixed> $extra
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes($pathPrefix = '/v1', $namePrefix = 'restapi-', $restHandler = null, $extra = [])
    {
        //$restHandler ??= RestAPIHandler::class;
        $routes = [];

        $routes = array_merge($routes, DataObjectAPIRoutes::getRoutes($pathPrefix, $namePrefix, $restHandler, $extra));
        $routes = array_merge($routes, GenericAPIRoutes::getRoutes($pathPrefix, $namePrefix, $restHandler, $extra));
        $routes = array_merge($routes, ModuleAPIRoutes::getRoutes($pathPrefix, $namePrefix, $restHandler, $extra));

        return $routes;
    }
}
