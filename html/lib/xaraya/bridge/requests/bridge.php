<?php

/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.8.8
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

use Xaraya\Caching\CacheInterface;
use Xaraya\Caching\CacheTrait;
use Xaraya\Tools\TimerInterface;
use Xaraya\Tools\TimerTrait;

/**
 * Bridge for generic requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 * @phpstan-type RouteDef array{0: string|array<string>, 1: string, 2: mixed, 3: array<string, mixed>}
 */
class BridgeRequest extends BasicRequest implements BasicBridgeInterface, CacheInterface, TimerInterface
{
    use BasicBridgeTrait;
    use TimerTrait;  // activate with $this->enableTimer(true)
    use CacheTrait;  // activate with $this->enableCache(true)

    public function __construct($xar = null)
    {
        $this->setServicesClass($xar);
    }

    /**
     * Get basic handler routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = ''): array
    {
        return [];
    }
}
