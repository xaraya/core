<?php
/**
 * Experiment with PSR-7 and PSR-15 compatible middleware controller for DataObject
 * Sets request attributes 'object', 'method', 'itemid' for DataObjectMiddleware->process()
 *
 * Request Uris:
 * - {baseUri}?object={object}[&method={method}][&itemid={itemid}]
 * - {baseUri}/object/{object}
 * - {baseUri}/object/{object}/{itemid}
 * - {baseUri}/object/{object}/{method}
 * - {baseUri}/object/{object}/{itemid}/{method}
 * - {baseUri}?module=object&type={object}&func={method} (from ModuleRouter)
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use sys;

sys::import('xaraya.bridge.middleware');

class DataObjectRouter extends DefaultRouter implements DefaultRouterInterface
{
    public static string $baseUri = '';
    public static string $prefix = '/object';

    /**
     * Basic route matcher to identify object requests and set request attributes e.g. in router middleware
     */
    public static function matchRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        // @checkme keep track of the current base uri if filtered in router
        static::setBaseUri($request);

        // parse request uri for path + query params
        $params = static::parseUri($request);

        // identify object requests and set request attributes
        if (!empty($params['object'])) {
            $request = $request->withAttribute('object', $params['object']);
            if (!empty($params['method'])) {
                $request = $request->withAttribute('method', $params['method']);
            }
            if (!empty($params['itemid'])) {
                $request = $request->withAttribute('itemid', $params['itemid']);
            }
        }

        return $request;
    }

    /**
     * Basic route parser for object requests e.g. in route matcher for router middleware
     */
    public static function parseUri(ServerRequestInterface $request): array
    {
        // did we already filter out the base uri in router middleware?
        if ($request->getAttribute('baseUri') !== null) {
            $prefix = static::$prefix;
        } else {
            $prefix = static::$baseUri . static::$prefix;
        }
        $path = $request->getUri()->getPath();
        $params = static::parseDataObjectPath($path, $request->getQueryParams(), $prefix);
        return $params;
    }

    /**
     * Basic route builder for object requests e.g. in response output or templates - assuming short url format here
     */
    public static function buildUri(?string $object = null, ?string $method = null, string|int|null $itemid = null, array $extra = []): string
    {
        return static::buildDataObjectPath($object, $method, $itemid, $extra);
    }
}
