<?php
/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

/**
 * Handle Module requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 *
 * Note: requests with module = object or prefix = /object are handed off to DataObjectRequest
 */
class ModuleRequest extends BasicRequest implements ModuleBridgeInterface
{
    use ModuleBridgeTrait;

    /**
     * Summary of parseDataObjectPath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public static function parseDataObjectPath(string $path = '/', array $query = [], string $prefix = '/object'): array
    {
        //DataObjectRequest::$baseUri = static::$baseUri;
        return DataObjectRequest::parseDataObjectPath($path, $query, $prefix);
    }

    /**
     * Summary of buildDataObjectPath
     * @param string $object
     * @param ?string $method
     * @param string|int|null $itemid
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public static function buildDataObjectPath(string $object = 'sample', ?string $method = null, string|int|null $itemid = null, array $extra = [], string $prefix = '/object'): string
    {
        //DataObjectRequest::$baseUri = static::$baseUri;
        return DataObjectRequest::buildDataObjectPath($object, $method, $itemid, $extra, $prefix);
    }
}
