<?php

/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

/**
 * Handle static file requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 * @phpstan-import-type RouteDef from BasicBridge
 */
class StaticFileHandler extends BasicBridge implements StaticFileBridgeInterface
{
    use StaticFileBridgeTrait;

    /**
     * Summary of getRoutes
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param ?string $handler
     * @param array<mixed> $extra
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = 'static-', ?string $handler = null, array $extra = null): array
    {
        return static::getStaticFileRoutes($pathPrefix, $namePrefix, $handler, $extra);
    }
}
