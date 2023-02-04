<?php
/**
 * Experiment with PSR-7 and PSR-15 compatible middleware controllers
 *
 * See dynamicdata/controllers/middleware.php and modules/controllers/middleware.php
 *
 * require dirname(__DIR__).'/vendor/autoload.php';
 * sys::init();
 * xarCache::init();
 * xarCore::xarInit(xarCore::SYSTEM_USER);
 *
 * // use some PSR-7 factory and PSR-15 dispatcher
 * use Nyholm\Psr7\Factory\Psr17Factory;
 * use Nyholm\Psr7Server\ServerRequestCreator;
 * use Middlewares\Utils\Dispatcher;
 * // use Xaraya PSR-15 compatible middleware(s)
 * use Xaraya\Bridge\Middleware\DefaultMiddleware;
 * use Xaraya\Bridge\Middleware\DataObjectMiddleware;
 * use Xaraya\Bridge\Middleware\ModuleMiddleware;
 *
 * // get server request from somewhere
 * $psr17Factory = new Psr17Factory();
 * $requestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
 * $request = $requestCreator->fromGlobals();
 *
 * // the Xaraya PSR-15 middleware here
 * $objects = new DataObjectMiddleware($psr17Factory);
 * $modules = new ModuleMiddleware($psr17Factory);
 *
 * // some other middleware before or after...
 * $filter = function ($request, $next) {
 *     // @checkme strip baseUri from request path and set 'baseUri' request attribute here?
 *     $request = DefaultMiddleware::stripBaseUri($request);
 *     $response = $next->handle($request);
 *     return $response;
 * };
 * $cleaner = function ($request, $next) use ($psr17Factory) {
 *     $response = $next->handle($request);
 *     // clean up routes for object/module requests in response
 *     $response = DefaultMiddleware::cleanResponse($response, $psr17Factory);
 *     return $response;
 * };
 * // ...
 * $notfound = function ($request, $next) {
 *     $response = $next->handle($request);
 *     $path = $request->getUri()->getPath();
 *     $response->getBody()->write('Nothing to see here at ' . htmlspecialchars($path));
 *     return $response;
 * };
 *
 * $stack = [
 *     $filter,
 *     //$cleaner,
 *     $objects,
 *     // Warning: we never get here if there's an object to be handled
 *     $modules,
 *     // Warning: we never get here if there's a module to be handled
 *     $notfound,
 * ];
 *
 * $response = Dispatcher::run($stack, $request);
 * //echo $response->getBody();
 * DefaultMiddleware::emitResponse($response);
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;
use JsonException;
use sys;

sys::import('xaraya.bridge.requests.commontrait');
use Xaraya\Bridge\Requests\CommonBridgeTrait;
use Xaraya\Bridge\Requests\StaticFileBridgeTrait;

interface DefaultRouterInterface
{
    public static function matchRequest(ServerRequestInterface $request): ServerRequestInterface;
    public static function parseUri(ServerRequestInterface $request): array;
    // @checkme signature might be different for other routers - keep it generic here
    public static function buildUri(?string $arg1 = null, ?string $arg2 = null, string|int|null $arg3 = null, array $extra = []): string;
    public static function stripBaseUri(ServerRequestInterface $request): ServerRequestInterface;
    public static function setBaseUri(string|ServerRequestInterface $request): void;
    public static function setPrefix(string $prefix): void;
    public static function cleanResponse(ResponseInterface $response, StreamFactoryInterface|ResponseFactoryInterface $factory): ResponseInterface;
    public static function emitResponse(ResponseInterface $response): void;
}

//interface DefaultMiddlewareInterface extends MiddlewareInterface;

trait DefaultResponseTrait
{
    protected ResponseFactoryInterface $responseFactory;
    protected StreamFactoryInterface $streamFactory;
    protected array $options = [];

    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    public function setResponseFactory(ResponseFactoryInterface $responseFactory): void
    {
        $this->responseFactory = $responseFactory;
    }

    public function getStreamFactory(): StreamFactoryInterface
    {
        // @todo replace with actual stream factory instead of re-using response factory (= same for nyholm/psr7)
        if (empty($this->streamFactory) && $this->responseFactory instanceof StreamFactoryInterface) {
            return $this->responseFactory;
        }
        return $this->streamFactory;
    }

    public function setStreamFactory(StreamFactoryInterface $streamFactory): void
    {
        $this->streamFactory = $streamFactory;
    }

    public function createResponse(string $body, string $mediaType = 'text/html; charset=utf-8'): ResponseInterface
    {
        if (strpos($mediaType, '; charset=') === false) {
            $mediaType .= '; charset=utf-8';
        }
        $response = $this->getResponseFactory()->createResponse()->withHeader('Content-Type', $mediaType);
        $response->getBody()->write($body);
        return $response;
    }

    public function createJsonResponse(mixed $result, string $mediaType = 'application/json; charset=utf-8'): ResponseInterface
    {
        if (strpos($mediaType, '; charset=') === false) {
            $mediaType .= '; charset=utf-8';
        }
        $response = $this->getResponseFactory()->createResponse()->withHeader('Content-Type', $mediaType);
        try {
            //$output = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR);
            $body = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $body = '{"JSON Exception": ' . json_encode($e->getMessage()) . '}';
        }
        $response->getBody()->write($body);
        return $response;
    }

    public function createNotFoundResponse(string $path)
    {
        $response = $this->getResponseFactory()->createResponse();
        $response = $response->withStatus(404);
        $response->getBody()->write('Nothing to see here at ' . htmlspecialchars($path));
        return $response;
    }

    public function createUnauthorizedResponse($status = 401)
    {
        $response = $this->getResponseFactory()->createResponse();
        $response = $response->withStatus(401)->withHeader('WWW-Authenticate', 'Token realm="Xaraya Site Login", created=');
        $response->getBody()->write('This operation is unauthorized, please authenticate.');
        return $response;
    }

    public function createForbiddenResponse($status = 403)
    {
        $response = $this->getResponseFactory()->createResponse();
        $response = $response->withStatus(403);
        $response->getBody()->write('This operation is forbidden.');
        return $response;
    }

    public function createRedirectResponse(string $redirectURL, int $status = 302)
    {
        $response = $this->getResponseFactory()->createResponse();
        $response = $response->withStatus($status)->withHeader('Location', $redirectURL);
        $response->getBody()->write('Nothing to see here...');
        return $response;
    }

    public function createExceptionResponse(Exception $e, mixed $result = null): ResponseInterface
    {
        $body = "Exception: " . $e->getMessage();
        $here = explode('\\', static::class);
        $class = array_pop($here);
        $response = $this->getResponseFactory()->createResponse(422, $class . ' Exception')->withHeader('Content-Type', 'text/plain; charset=utf-8');
        $response->getBody()->write($body);
        return $response;
    }

    public function createFileResponse(string $path, ?string $mediaType = null): ResponseInterface
    {
        if (!empty($mediaType)) {
            if (strpos($mediaType, '; charset=') === false) {
                $mediaType .= '; charset=utf-8';
            }
            $response = $this->getResponseFactory()->createResponse()->withHeader('Content-Type', $mediaType);
        } else {
            $response = $this->getResponseFactory()->createResponse();
        }
        // @todo replace with actual stream factory instead of re-using response factory (= same for nyholm/psr7)
        $response = $response->withBody($this->getStreamFactory()->createStreamFromFile($path));
        //$response = $response->withBody($this->getStreamFactory()->createStream(file_get_contents($path)));
        return $response;
    }
}

/**
 * Middleware should be built by creating a customized router and then adding the processsing - extend this to create your router
 */
abstract class DefaultRouter implements DefaultRouterInterface
{
    use CommonBridgeTrait;
    use DefaultResponseTrait;

    public static string $baseUri = '';
    public static string $prefix = '';

    /**
     * Basic route matcher to identify object/module requests and set request attributes e.g. in router middleware
     */
    abstract public static function matchRequest(ServerRequestInterface $request): ServerRequestInterface;

    /**
     * Basic route parser for object/module requests e.g. in route matcher for router middleware
     */
    abstract public static function parseUri(ServerRequestInterface $request): array;

    /**
     * Basic route builder for object/module requests e.g. in response output or templates - assuming short url format here
     *
     * @checkme signature might be different for other routers - keep it generic here
     * public static function buildUri(string $object, string $method = '', string|int|null $itemid = null, array $extra = []): string;
     * public static function buildUri($modName = null, $modType = 'user', $funcName = 'main', $args = []): string;
     */
    abstract public static function buildUri(?string $arg1 = null, ?string $arg2 = null, string|int|null $arg3 = null, array $extra = []): string;

    /**
     * Strip the base uri for the calling script from the request path and set 'baseUri' request attribute
     */
    public static function stripBaseUri(ServerRequestInterface $request): ServerRequestInterface
    {
        $baseUri = static::getBaseUri($request);
        if (!empty($baseUri)) {
            $path = static::getPathInfo($request);
            $uri = $request->getUri()->withPath($path);
            $request = $request->withUri($uri)->withAttribute('baseUri', $baseUri);
        } else {
            $request = $request->withAttribute('baseUri', $baseUri);
        }
        static::$baseUri = $baseUri;
        return $request;
    }

    /**
     * Set the base uri for the calling script
     */
    public static function setBaseUri(string|ServerRequestInterface $request): void
    {
        if ($request instanceof ServerRequestInterface) {
            // did we already filter out the base uri in router middleware?
            if ($request->getAttribute('baseUri') !== null) {
                static::$baseUri = $request->getAttribute('baseUri');
            } else {
                // @checkme we don't actually update the request path of the on-going request here
                static::stripBaseUri($request);
            }
        } else {
            static::$baseUri = $request;
        }
    }

    /**
     * Set the path prefix used in object/module requests (after the script name if filtered in router)
     */
    public static function setPrefix(string $prefix): void
    {
        static::$prefix = $prefix;
    }

    /**
     * Basic route cleaner for object/module requests in response e.g. in router middleware
     */
    public static function cleanResponse(ResponseInterface $response, StreamFactoryInterface|ResponseFactoryInterface $factory): ResponseInterface
    {
        $content = (string) $response->getBody();
        // @todo replace object/module request links and return response with updated body
        if ($factory instanceof StreamFactoryInterface) {
            $body = $factory->createStream($content);
        } else {
            $temp = $factory->createResponse();
            $temp->getBody()->write($content);
            $body = $temp->getBody();
        }
        $body->rewind();
        return $response->withBody($body);
    }

    /**
     * Basic emitter utility to send back response once request has been handled
     */
    public static function emitResponse(ResponseInterface $response): void
    {
        $status = $response->getStatusCode();
        if ($status !== 200) {
            $reason = $response->getReasonPhrase();
            if (!empty($reason) && !headers_sent()) {
                header("HTTP/1.1 $status $reason");
            } else {
                http_response_code($status);
            }
        }
        if (!headers_sent()) {
            foreach ($response->getHeaders() as $name => $values) {
                //header(sprintf('%s: %s', $name, implode(', ', $value)), false);
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }
        echo $response->getBody();
    }
}

/**
 * Middleware should be built by creating a customized router and then adding the processsing - do *not* extend this directly
 */
class DefaultMiddleware extends DefaultRouter implements DefaultRouterInterface, MiddlewareInterface
{
    protected array $options = [];

    /**
     * Initialize the middleware with response factory (or container, ...)
     */
    public function __construct(?ResponseFactoryInterface $responseFactory = null, array $options = [])
    {
        $this->setResponseFactory($responseFactory);
        $this->options = $options;
    }

    /**
     * Process the server request - this assumes request attributes have been set in earlier middleware, e.g. router
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        return $next->handle($request);
    }

    /**
     * Basic route matcher to identify object/module requests and set request attributes e.g. in router middleware
     */
    public static function matchRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request;
    }

    /**
     * Basic route parser for object/module requests e.g. in route matcher for router middleware
     */
    public static function parseUri(ServerRequestInterface $request): array
    {
        return [];
    }

    /**
     * Basic route builder for object/module requests e.g. in response output or templates - assuming short url format here
     */
    public static function buildUri(?string $arg1 = null, ?string $arg2 = null, string|int|null $arg3 = null, array $extra = []): string
    {
        return '/';
    }
}

class StaticFileMiddleware extends DefaultRouter implements DefaultRouterInterface, MiddlewareInterface
{
    use StaticFileBridgeTrait;

    protected array $attributes = ['module', 'theme', 'folder', 'file'];
    protected array $options = [];
    public static string $baseUri = '';
    public static string $prefix = '/themes';
    public static array $locations = [
        'theme' => '/themes',
        'module' => '/code/modules',
    ];

    /**
     * Initialize the middleware with response factory (or container, ...)
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

        foreach (static::$locations as $type => $prefix) {
            // parse request uri for path + query params
            static::setPrefix($prefix);
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
     */
    public static function parseUri(ServerRequestInterface $request, string $prefix = '/themes', string $type = 'theme'): array
    {
        // did we already filter out the base uri in router middleware?
        if ($request->getAttribute('baseUri') !== null) {
            //$prefix = static::$prefix;
        } else {
            $prefix = static::$baseUri . $prefix;
        }
        $path = $request->getUri()->getPath();
        $params = static::parseStaticFilePath($path, $request->getQueryParams(), $prefix, $type);
        return $params;
    }

    /**
     * Basic route builder for static file requests e.g. in response output or templates - assuming short url format here
     */
    public static function buildUri(?string $source = null, ?string $folder = null, string|int|null $file = null, array $extra = [], string $prefix = ''): string
    {
        return static::buildStaticFilePath($source, $folder, $file, $extra, $prefix);
    }
}
