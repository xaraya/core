<?php
/**
 * Static file middleware for PSR-7 and PSR-15 compatible middleware controllers
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;
use sys;

sys::import('xaraya.bridge.requests.staticfiletrait');
use Xaraya\Bridge\Requests\StaticFileBridgeTrait;

class StaticFileMiddleware extends DefaultRouter implements DefaultRouterInterface, MiddlewareInterface, DefaultResponseInterface
{
    use StaticFileBridgeTrait;
    use DefaultResponseTrait;

    /** @var array<string> */
    protected array $attributes = ['module', 'theme', 'folder', 'file'];
    /** @var array<mixed> */
    protected array $options = [];
    public static string $baseUri = '';
    /** @var array<string, string> */
    public static array $locations = [
        'theme' => '/themes',
        'module' => '/code/modules',
    ];

    /**
     * Initialize the middleware with response factory (or container, ...)
     * @param array<mixed> $options
     */
    public function __construct(?ResponseFactoryInterface $responseFactory = null, array $options = [])
    {
        $this->setResponseFactory($responseFactory);
        $this->options = $options;
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
        if ((empty($attribs['theme']) && empty($attribs['module'])) || empty($attribs['folder']) || empty($attribs['file'])) {
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
            $result = static::getStaticFileRequest($attribs);
        } catch (Exception $e) {
            return $this->createExceptionResponse($e);
        }
        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        return $this->createFileResponse($result);
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
            $request = $request->withAttribute('theme', 'none');
            $request = $request->withAttribute('folder', 'web');
            $request = $request->withAttribute('file', 'favicon.ico');
            return $request;
        }

        foreach (static::$locations as $type => $prefix) {
            // parse request uri for path + query params
            $params = static::parseUri($request, $prefix, $type);

            // identify static file requests and set request attributes
            if (!empty($params[$type]) && !empty($params['folder']) && !empty($params['file'])) {
                $request = $request->withAttribute($type, $params[$type]);
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
        $params = static::parseStaticFilePath($path, $request->getQueryParams(), $prefix, $type);
        return $params;
    }

    /**
     * Basic route builder for static file requests e.g. in response output or templates - assuming short url format here
     * @param array<string, mixed> $extra
     */
    public static function buildUri(?string $source = null, ?string $folder = null, string|int|null $file = null, array $extra = [], string $prefix = ''): string
    {
        return static::buildStaticFilePath($source, $folder, $file, $extra, $prefix);
    }
}
