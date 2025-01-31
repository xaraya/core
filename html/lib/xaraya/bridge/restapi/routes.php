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
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes($pathPrefix = '/v1', $namePrefix = 'restapi-', $restHandler = null)
    {
        // @todo move away from static methods for context
        $restHandler ??= RestAPIHandler::class;
        $routes = [];
        $extra = [];

        $path = $pathPrefix . '/objects';
        $name = $namePrefix . 'getObjects';
        $routes[$name] = ['GET', $path, [$restHandler, 'getObjects'], $extra];

        $path = $pathPrefix . '/objects/{object}';
        $name = $namePrefix . 'getObjectList';
        $routes[$name] = ['GET', $path, [$restHandler, 'getObjectList'], $extra];

        $path = $pathPrefix . '/objects/{object}/{itemid}';
        $name = $namePrefix . 'getObjectItem';
        $routes[$name] = ['GET', $path, [$restHandler, 'getObjectItem'], $extra];

        $path = $pathPrefix . '/objects/{object}';
        $name = $namePrefix . 'createObjectItem';
        $routes[$name] = ['POST', $path, [$restHandler, 'createObjectItem'], $extra];

        $path = $pathPrefix . '/objects/{object}/{itemid}';
        $name = $namePrefix . 'updateObjectItem';
        $routes[$name] = ['PUT', $path, [$restHandler, 'updateObjectItem'], $extra];

        $path = $pathPrefix . '/objects/{object}/{itemid}';
        $name = $namePrefix . 'deleteObjectItem';
        $routes[$name] = ['DELETE', $path, [$restHandler, 'deleteObjectItem'], $extra];

        //$path = $pathPrefix . '/objects/{object}';
        //$name = $namePrefix . 'patchObjectDefinition';
        //$routes[$name] = ['PATCH', $path, [$restHandler, 'patchObjectDefinition'], $extra];

        $path = $pathPrefix . '/whoami';
        $name = $namePrefix . 'whoami';
        $routes[$name] = ['GET', $path, [$restHandler, 'whoami'], $extra];

        $path = $pathPrefix . '/context';
        $name = $namePrefix . 'getContext';
        $routes[$name] = ['GET', $path, [$restHandler, 'getContext'], $extra];

        $path = $pathPrefix . '/token';
        $name = $namePrefix . 'postToken';
        $routes[$name] = ['POST', $path, [$restHandler, 'postToken'], $extra];

        $path = $pathPrefix . '/token';
        $name = $namePrefix . 'deleteToken';
        $routes[$name] = ['DELETE', $path, [$restHandler, 'deleteToken'], $extra];

        $path = $pathPrefix . '/modules';
        $name = $namePrefix . 'getModules';
        $routes[$name] = ['GET', $path, [$restHandler, 'getModules'], $extra];

        $path = $pathPrefix . '/modules/{module}';
        $name = $namePrefix . 'getModuleApis';
        $routes[$name] = ['GET', $path, [$restHandler, 'getModuleApis'], $extra];

        // @checkme support optional part(s) after path, either with {path}[/{more}] or with {path:.+}
        $path = $pathPrefix . '/modules/{module}/{path}[/{more:.+}]';
        $name = $namePrefix . 'getModuleCall';
        $routes[$name] = ['GET', $path, [$restHandler, 'getModuleCall'], $extra];

        $path = $pathPrefix . '/modules/{module}/{path}[/{more:.+}]';
        $name = $namePrefix . 'postModuleCall';
        $routes[$name] = ['POST', $path, [$restHandler, 'postModuleCall'], $extra];

        $path = $pathPrefix . '/modules/{module}/{path}[/{more:.+}]';
        $name = $namePrefix . 'putModuleCall';
        $routes[$name] = ['PUT', $path, [$restHandler, 'putModuleCall'], $extra];

        $path = $pathPrefix . '/modules/{module}/{path}[/{more:.+}]';
        $name = $namePrefix . 'deleteModuleCall';
        $routes[$name] = ['DELETE', $path, [$restHandler, 'deleteModuleCall'], $extra];

        return $routes;
    }
}
