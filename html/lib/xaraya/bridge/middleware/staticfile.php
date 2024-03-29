<?php
/**
 * @package core\bridge
 * @subpackage middleware
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;
use sys;

sys::import('xaraya.bridge.requests.staticfile');
use Xaraya\Bridge\Requests\StaticFileRequest;

/**
 * Static file middleware for PSR-7 and PSR-15 compatible middleware controllers
 */
class StaticFileMiddleware extends DefaultRouter implements DefaultRouterInterface, MiddlewareInterface
{
    /** @var array<string> */
    protected array $attributes = ['static', 'source', 'folder', 'file'];
    protected ResponseUtil $responseUtil;
    public static string $baseUri = '';
    /** @var array<string, string> */
    public static array $locations = [
        'theme' => '/themes',
        'module' => '/code/modules',
        'var' => '/var',
    ];

    /**
     * Initialize the middleware with response factory (or container, ...)
     * @param array<mixed> $options
     */
    public function __construct(?ResponseFactoryInterface $responseFactory = null, array $options = [])
    {
        $this->responseUtil = new ResponseUtil($responseFactory, $options);
    }

    /**
     * Process the server request - this assumes request attributes have been set in earlier middleware, e.g. router
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface|callable $next): ResponseInterface
    {
        // identify static file requests and set request attributes
        $request = static::matchRequest($request);

        // check only the request attributes relevant for static file request
        $allowed = array_flip($this->attributes);
        $attribs = array_intersect_key($request->getAttributes(), $allowed);

        // pass the request along to the next handler and return its response
        if (empty($attribs['static']) || empty($attribs['source']) || empty($attribs['folder']) || empty($attribs['file'])) {
            // @checkme signature mismatch for process() with ReactPHP
            if ($next instanceof RequestHandlerInterface) {
                $response = $next->handle($request);
            } else {
                $response = $next($request);
            }
            return $response;
        }

        // handle the static file request here and return our response

        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        $params = [
            'If-None-Match' => $request->getHeader('If-None-Match'),
            'If-Modified-Since' => $request->getHeader('If-Modified-Since'),
        ];
        $response = $this->run($attribs, $params);

        return $response;
    }

    /**
     * Summary of run
     * @param mixed $attribs
     * @param mixed $params
     * @return ResponseInterface
     */
    public function run($attribs, $params)
    {
        try {
            $result = StaticFileRequest::getStaticFileRequest($attribs);
        } catch (Exception $e) {
            return $this->responseUtil->createExceptionResponse($e);
        }
        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        return $this->responseUtil->createFileResponse($result);
    }

    // @checkme signature mismatch for process() with ReactPHP
    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        return $this->process($request, $next);
    }

    /**
     * Basic route matcher to identify static file requests and set request attributes e.g. in router middleware
     */
    public static function matchRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        // @checkme keep track of the current base uri if filtered in router
        static::setBaseUri($request);

        if ($request->getUri()->getPath() === '/favicon.ico') {
            $request = $request->withAttribute('static', 'other');
            $request = $request->withAttribute('source', 'none');
            $request = $request->withAttribute('folder', 'web');
            $request = $request->withAttribute('file', 'favicon.ico');
            return $request;
        }

        foreach (static::$locations as $type => $prefix) {
            // parse request uri for path + query params
            $params = static::parseUri($request, $prefix, $type);

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
    public static function parseUri(ServerRequestInterface $request, string $prefix = '/themes', string $type = 'theme'): array
    {
        // did we already filter out the base uri in router middleware?
        if ($request->getAttribute('baseUri') !== null) {
            //$prefix = $prefix;
        } else {
            $prefix = static::$baseUri . $prefix;
        }
        $path = $request->getUri()->getPath();
        $params = StaticFileRequest::parseStaticFilePath($path, $request->getQueryParams(), $prefix, $type);
        return $params;
    }

    /**
     * Basic route builder for static file requests e.g. in response output or templates - assuming short url format here
     * @param array<string, mixed> $extra
     */
    public static function buildUri(?string $source = null, ?string $folder = null, string|int|null $file = null, array $extra = [], string $prefix = ''): string
    {
        $uri = static::$baseUri;
        if (!empty($prefix) && strstr($uri, $prefix) !== $prefix) {
            $uri .= $prefix;
        }
        return StaticFileRequest::buildStaticFilePath($source, $folder, $file, $extra, $uri);
    }
}
