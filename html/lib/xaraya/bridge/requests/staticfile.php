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
 */
class StaticFileHandler extends BasicBridge implements StaticFileBridgeInterface
{
    use StaticFileBridgeTrait;

    /**
     * Summary of getRoutes
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param mixed $handler
     * @param array<mixed> $extra
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = 'static-', mixed $handler = null, array $extra = null)
    {
        return static::getStaticFileRoutes($pathPrefix, $namePrefix, $handler, $extra);
    }
}
