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
 * Class to define REST API routes
 * @phpstan-import-type RouteDef from RestAPIRoutes
 */
class ModuleAPIRoutes extends RestAPIRoutes
{
    public static string $pathPrefix = '/modules';
    public static string $namePrefix = 'modules-';
    public static mixed $handlerClass = ModuleAPIHandler::class;

    /**
     * Get Module REST API routes (in generic format)
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

        $path = $pathPrefix;
        $name = $namePrefix . 'getModules';
        $routes[$name] = ['GET', $path, [$restHandler, 'getModules'], $extra];

        $path = $pathPrefix . '/{module}';
        $name = $namePrefix . 'getModuleApis';
        $routes[$name] = ['GET', $path, [$restHandler, 'getModuleApis'], $extra];

        // support optional part(s) after path, either with {path}/{more:.+} or with {path:.+}
        $path = $pathPrefix . '/{module}/{path}';
        $name = $namePrefix . 'getModuleCall';
        $routes[$name] = ['GET', $path, [$restHandler, 'getModuleCall'], $extra];

        // with symfony routing we need to use a separate route for optional parameters
        $path = $pathPrefix . '/{module}/{path}/{more:.+}';
        $name = $namePrefix . 'getModuleCallMore';
        $routes[$name] = ['GET', $path, [$restHandler, 'getModuleCall'], $extra];

        $path = $pathPrefix . '/{module}/{path}';
        $name = $namePrefix . 'postModuleCall';
        $routes[$name] = ['POST', $path, [$restHandler, 'postModuleCall'], $extra];

        $path = $pathPrefix . '/{module}/{path}/{more:.+}';
        $name = $namePrefix . 'postModuleCallMore';
        $routes[$name] = ['POST', $path, [$restHandler, 'postModuleCall'], $extra];

        $path = $pathPrefix . '/{module}/{path}';
        $name = $namePrefix . 'putModuleCall';
        $routes[$name] = ['PUT', $path, [$restHandler, 'putModuleCall'], $extra];

        $path = $pathPrefix . '/{module}/{path}/{more:.+}';
        $name = $namePrefix . 'putModuleCallMore';
        $routes[$name] = ['PUT', $path, [$restHandler, 'putModuleCall'], $extra];

        $path = $pathPrefix . '/{module}/{path}';
        $name = $namePrefix . 'deleteModuleCall';
        $routes[$name] = ['DELETE', $path, [$restHandler, 'deleteModuleCall'], $extra];

        $path = $pathPrefix . '/{module}/{path}/{more:.+}';
        $name = $namePrefix . 'deleteModuleCallMore';
        $routes[$name] = ['DELETE', $path, [$restHandler, 'deleteModuleCall'], $extra];

        return $routes;
    }

    /**
     * Find route uri based on params
     * @param array<string, mixed> $params
     */
    public static function findRoute(RouterInterface $router, array $params, string $method = 'GET', string $namePrefix = ''): string|null
    {
        // we have a route already
        if (!empty($params[$router::ROUTE_PARAM])) {
            return $router->makeUri(null, $params);
        }
        $namePrefix .= static::$namePrefix;
        // @todo make use of method here too!?
        $route = null;
        if (empty($params['path'])) {
            $route = $namePrefix . 'getModuleApis';
            return $router->makeUri($route, $params);
        }
        if (empty($params['more'])) {
            switch ($method) {
                case 'POST':
                    $route = $namePrefix . 'postModuleCall';
                    break;
                case 'PUT':
                    $route = $namePrefix . 'putModuleCall';
                    break;
                case 'DELETE':
                    $route = $namePrefix . 'deleteModuleCall';
                    break;
                case 'GET':
                default:
                    $route = $namePrefix . 'getModuleCall';
                    break;
            }
            return $router->makeUri($route, $params);
        }
        switch ($method) {
            case 'POST':
                $route = $namePrefix . 'postModuleCallMore';
                break;
            case 'PUT':
                $route = $namePrefix . 'putModuleCallMore';
                break;
            case 'DELETE':
                $route = $namePrefix . 'deleteModuleCallMore';
                break;
            case 'GET':
            default:
                $route = $namePrefix . 'getModuleCallMore';
                break;
        }
        return $router->makeUri($route, $params);
    }
}
