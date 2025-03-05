<?php

/**
 * DynamicData handler class for routing & dispatching outside Xaraya
 *
 * @todo experiment using module classes and methods as handler
 */

namespace Xaraya\Modules\DynamicData;

use Xaraya\Routing\ModuleHandler;
use Xaraya\Routing\RouterInterface;

/**
 * DynamicData handler class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * ```
 * /dynamicdata/
 * /dynamicdata/admin/{func} (not used here)
 * /dynamicdata/{func} (not used here)
 * /object/{entity}/
 * /object/{entity}/{itemid} (numeric)
 * /object/{entity}/{itemid}/{title}
 * /object/{entity}/{action} (non-numeric)
 * /object/{entity}/{action}/{itemid}
 * ```
 */
class DynamicDataHandler extends ModuleHandler
{
    public static string $moduleName = 'dynamicdata';
    public static string $objectName = 'object';
    /** @var class-string */
    public static string $handlerClass = UserGui::class;

    /**
     * Get supported handler routes (in generic format)
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = ''): array
    {
        return parent::getRoutes($pathPrefix, $namePrefix);
    }

    /**
     * Find route uri based on params
     * @param array<string, mixed> $params
     */
    public static function findRoute(RouterInterface $router, array $params): string|null
    {
        return parent::findRoute($router, $params);
    }
}
