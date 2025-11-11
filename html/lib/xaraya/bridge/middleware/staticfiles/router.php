<?php

/**
 * Experiment with PSR-7 and PSR-15 compatible middleware controller for static files
 * Sets request attributes 'static', 'source', 'folder', 'file' for StaticFileMiddleware->process()
 *
 * Request Uris:
 * - {baseUri}?static={static}&source={source}&folder={folder}&file={file}
 * - {baseUri}/themes/{source}/{folder}/{file} - static = theme
 * - {baseUri}/code/modules/{source}/{folder}/{file} - static = module
 * - {baseUri}/var/{source}/{folder}/{file} - static = var
 * - {baseUri}/favicon.ico - static = other
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Xaraya\Bridge\Requests\StaticFileHandler;

class StaticFileRouter extends DefaultRouter implements DefaultRouterInterface
{
    public static string $baseUri = '';
    protected StaticFileHandler $handler;
    /** @var array<string, string> */
    public static array $locations = [
        'theme' => '/themes',
        'module' => '/code/modules',
        'var' => '/var',
    ];

    public function __construct()
    {
        $this->handler = new StaticFileHandler();
    }

    /**
     * Basic route matcher to identify static file requests and set request attributes e.g. in router middleware
     */
    public function matchRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        // @checkme keep track of the current base uri if filtered in router
        $this->setBaseUri($request);

        if ($request->getUri()->getPath() === '/favicon.ico') {
            $request = $request->withAttribute('static', 'other');
            $request = $request->withAttribute('source', 'none');
            $request = $request->withAttribute('folder', 'web');
            $request = $request->withAttribute('file', 'favicon.ico');
            return $request;
        }

        foreach (static::$locations as $type => $prefix) {
            // parse request uri for path + query params
            $params = $this->parseUri($request, $prefix, $type);

            // identify static file requests and set request attributes
            if ((!empty($params['static']) && $params['static'] == $type) && !empty($params['source']) && !empty($params['folder']) && !empty($params['file'])) {
                $request = $request->withAttribute('static', $params['static']);
                $request = $request->withAttribute('source', $params['source']);
                $request = $request->withAttribute('folder', $params['folder']);
                $request = $request->withAttribute('file', $params['file']);
                return $request;
            }
        }

        return $request;
    }

    /**
     * Basic route parser for static file requests e.g. in route matcher for router middleware
     * @return array<string, mixed>
     */
    public function parseUri(ServerRequestInterface $request, string $prefix = '/themes', string $type = 'theme'): array
    {
        // did we already filter out the base uri in router middleware?
        if ($request->getAttribute('baseUri') !== null) {
            //$prefix = $prefix;
        } else {
            $prefix = static::$baseUri . $prefix;
        }
        $path = $request->getUri()->getPath();
        $params = $this->handler->parseStaticFilePath($path, $request->getQueryParams(), $prefix, $type);
        return $params;
    }

    /**
     * Basic route builder for static file requests e.g. in response output or templates - assuming short url format here
     * @param array<string, mixed> $extra
     */
    public function buildUri(?string $source = null, ?string $folder = null, string|int|null $file = null, array $extra = [], string $prefix = ''): string
    {
        $uri = static::$baseUri;
        if (!empty($prefix) && strstr($uri, $prefix) !== $prefix) {
            $uri .= $prefix;
        }
        return $this->handler->buildStaticFilePath($source, $folder, $file, $extra, $uri);
    }
}
