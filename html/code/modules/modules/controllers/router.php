<?php
/**
 * Experiment with PSR-7 and PSR-15 compatible middleware controller for modules
 * Sets request attributes 'module', 'type', 'func' for ModuleMiddleware->process()
 *
 * Request Uris: (with module != 'object', otherwise leave it to DataObjectRouter)
 * - {baseUri}?module={module}[&type={type}][&func={func}]
 * - {baseUri}/{module}
 * - {baseUri}/{module}/{func} (for type = user)
 * - {baseUri}/{module}/{type}/{func}
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use sys;

sys::import('xaraya.bridge.middleware.router');
sys::import('xaraya.bridge.requests.module');
use Xaraya\Bridge\Requests\ModuleRequest;

class ModuleRouter extends DefaultRouter implements DefaultRouterInterface
{
    public static string $baseUri = '';
    public static string $prefix = '';

    /**
     * Basic route matcher to identify module requests and set request attributes e.g. in router middleware
     */
    public static function matchRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        // @checkme keep track of the current base uri if filtered in router
        static::setBaseUri($request);

        // parse request uri for path + query params
        $params = static::parseUri($request);

        // identify module requests and set request attributes
        if (!empty($params['module']) && $params['module'] != 'object') {
            $request = $request->withAttribute('module', $params['module']);
            if (!empty($params['type'])) {
                $request = $request->withAttribute('type', $params['type']);
            }
            if (!empty($params['func'])) {
                $request = $request->withAttribute('func', $params['func']);
            }
        }

        return $request;
    }

    /**
     * Basic route parser for module requests e.g. in route matcher for router middleware
     * @param ServerRequestInterface $request
     * @return array<string, mixed>
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
        $params = ModuleRequest::parseModulePath($path, $request->getQueryParams(), $prefix);
        return $params;
    }

    /**
     * Basic route builder for module requests e.g. in response output or templates - assuming short url format here
     * @param ?string $module
     * @param ?string $type
     * @param string|int|null $func
     * @param array<string, mixed> $extra
     * @return string
     */
    public static function buildUri(?string $module = null, ?string $type = null, string|int|null $func = null, array $extra = []): string
    {
        $prefix = static::$baseUri;
        return ModuleRequest::buildModulePath($module, $type, $func, $extra, $prefix);
    }
}
