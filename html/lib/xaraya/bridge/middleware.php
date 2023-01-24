<?php
/**
 * Experiment with PSR-7 and PSR-15 compatible middleware controllers
 *
 * See dynamicdata/controllers/middleware.php and modules/controllers/middleware.php
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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

/**
 * Middleware should be built by creating a customized router and then adding the processsing - extend this to create your router
 */
abstract class DefaultRouter implements DefaultRouterInterface
{
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
     * Strip the base uri for the calling script from the request path
     */
    public static function stripBaseUri(ServerRequestInterface $request): ServerRequestInterface
    {
        $server = $request->getServerParams();
        $requestUri = explode('?', $server['REQUEST_URI'] ?? '')[0];
        if (!empty($server['PATH_INFO'])) {
            if (!empty($server['SCRIPT_NAME']) && strpos($requestUri, $server['SCRIPT_NAME'] . $server['PATH_INFO']) === 0) {
                // {request_uri} = {/baseurl/script.php}{/path_info}?{query_string}
                $uri = $request->getUri()->withPath($server['PATH_INFO']);
                static::$baseUri = $server['SCRIPT_NAME'];
                $request = $request->withUri($uri)->withAttribute('baseUri', static::$baseUri);
            } elseif (strpos($requestUri, $server['PATH_INFO']) !== false) {
                // {request_uri} = {/otherurl}{/path_info}?{query_string} = mod_rewrite possibly unrelated to {/baseurl/script.php}
                $uri = $request->getUri()->withPath($server['PATH_INFO']);
                static::$baseUri = substr($requestUri, 0, strlen($requestUri) - strlen($server['PATH_INFO']));
                $request = $request->withUri($uri)->withAttribute('baseUri', static::$baseUri);
            } else {
                // how did we end up here?
            }
        } elseif (!empty($server['SCRIPT_NAME']) && strpos($requestUri, $server['SCRIPT_NAME']) === 0) {
            // {request_uri} = {/baseurl/script.php}?{query_string}
            $uri = $request->getUri()->withPath('');
            static::$baseUri = $server['SCRIPT_NAME'];
            $request = $request->withUri($uri)->withAttribute('baseUri', static::$baseUri);
        } else {
            // {request_uri} = {/otherurl}?{query_string} = mod_rewrite possibly unrelated to {/baseurl/script.php}
            $uri = $request->getUri()->withPath('');
            // @checkme could be some other rewrite going on here
            static::$baseUri = $requestUri;
            $request = $request->withUri($uri)->withAttribute('baseUri', static::$baseUri);
        }
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
                // @todo see above if SCRIPT_NAME is not part of REQUEST_URI
                //$server = $request->getServerParams();
                //static::$baseUri = $server['SCRIPT_NAME'] ?? '';
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
abstract class DefaultMiddleware extends DefaultRouter implements DefaultRouterInterface, MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;

    /**
     * Initialize the middleware with response factory (or container, ...)
     */
    final public function __construct(?ResponseFactoryInterface $responseFactory = null)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Process the server request - this assumes request attributes have been set in earlier middleware, e.g. router
     */
    final public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        return $next->handle($request);
    }
}
