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
 * Class to define DataObject REST API routes
 */
class DataObjectAPIRoutes extends RestAPIRoutes
{
    public static string $pathPrefix = '/objects';
    public static mixed $handlerClass = DataObjectAPIHandler::class;

    /**
     * Get DataObject REST API routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param mixed $restHandler
     * @param array<mixed> $extra
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = '', mixed $restHandler = null, array $extra = []): array
    {
        $pathPrefix .= self::$pathPrefix;
        $namePrefix .= self::$namePrefix;
        $restHandler ??= self::$handlerClass;
        $routes = [];

        $path = $pathPrefix;
        $name = $namePrefix . 'getObjects';
        $routes[$name] = ['GET', $path, [$restHandler, 'getObjects'], $extra];

        $path = $pathPrefix . '/{object}';
        $name = $namePrefix . 'getObjectList';
        $routes[$name] = ['GET', $path, [$restHandler, 'getObjectList'], $extra];

        $path = $pathPrefix . '/{object}/{itemid}';
        $name = $namePrefix . 'getObjectItem';
        $routes[$name] = ['GET', $path, [$restHandler, 'getObjectItem'], $extra];

        $path = $pathPrefix . '/{object}';
        $name = $namePrefix . 'createObjectItem';
        $routes[$name] = ['POST', $path, [$restHandler, 'createObjectItem'], $extra];

        $path = $pathPrefix . '/{object}/{itemid}';
        $name = $namePrefix . 'updateObjectItem';
        $routes[$name] = ['PUT', $path, [$restHandler, 'updateObjectItem'], $extra];

        $path = $pathPrefix . '/{object}/{itemid}';
        $name = $namePrefix . 'deleteObjectItem';
        $routes[$name] = ['DELETE', $path, [$restHandler, 'deleteObjectItem'], $extra];

        //$path = $pathPrefix . '/{object}';
        //$name = $namePrefix . 'patchObjectDefinition';
        //$routes[$name] = ['PATCH', $path, [$restHandler, 'patchObjectDefinition'], $extra];

        return $routes;
    }
}
