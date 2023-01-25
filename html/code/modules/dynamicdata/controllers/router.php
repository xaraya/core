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
        $params = [];
        if (strlen($path) > strlen($prefix) && strpos($path, $prefix . '/') === 0) {
            $pieces = explode('/', substr($path, strlen($prefix) + 1));
            // {prefix}/{object} = view
            $params['object'] = $pieces[0];
            if (count($pieces) > 1) {
                if (!is_numeric($pieces[1])) {
                    // {prefix}/{object}/{method} = new, query, stats, ...
                    $params['method'] = $pieces[1];
                } else {
                    // {prefix}/{object}/{itemid} = display
                    $params['itemid'] = $pieces[1];
                    if (count($pieces) > 2) {
                        // {prefix}/{object}/{itemid}/{$method} = update, delete, ...
                        $params['method'] = $pieces[2];
                    }
                }
            }
        }
        // add remaining query params to request params
        $params = array_merge($params, $request->getQueryParams());
        return $params;
    }

    /**
     * Basic route builder for object requests e.g. in response output or templates - assuming short url format here
     */
    public static function buildUri(?string $object = null, ?string $method = null, string|int|null $itemid = null, array $extra = []): string
    {
        // see xarDDObject::getObjectURL() and xarServer::getObjectURL()
        $uri = static::$baseUri . static::$prefix;
        // {prefix}/{object} = view
        $uri .= '/' . $object;
        if (empty($itemid)) {
            if (!empty($method) && $method != 'view') {
                // {prefix}/{object}/{method} = new, query, stats, ...
                $uri .= '/' . $method;
            }
        } else {
            // {prefix}/{object}/{itemid} = display
            $uri .= '/' . $itemid;
            if (!empty($method) && $method != 'display') {
                // {prefix}/{object}/{itemid}/{$method} = update, delete, ...
                $uri .= '/' . $method;
            }
        }
        if (!empty($extra)) {
            $uri .= '?' . http_build_query($extra);
        }
        return $uri;
    }
}
