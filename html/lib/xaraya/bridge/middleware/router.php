<?php
/**
 * Default router for PSR-7 and PSR-15 compatible middleware controllers
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use sys;

sys::import('xaraya.bridge.requests.commontrait');
use Xaraya\Bridge\Requests\CommonBridgeInterface;
use Xaraya\Bridge\Requests\CommonBridgeTrait;

interface DefaultRouterInterface
{
    public static function matchRequest(ServerRequestInterface $request): ServerRequestInterface;

    /**
     * Summary of parseUri
     * @param ServerRequestInterface $request
     * @return array<string, mixed>
     */
    public static function parseUri(ServerRequestInterface $request): array;

    /**
     * Summary of buildUri - @checkme signature might be different for other routers - keep it generic here
     * @param ?string $arg1
     * @param ?string $arg2
     * @param string|int|null $arg3
     * @param array<string, mixed> $extra
     * @return string
     */
    public static function buildUri(?string $arg1 = null, ?string $arg2 = null, string|int|null $arg3 = null, array $extra = []): string;

    public static function stripBaseUri(ServerRequestInterface $request): ServerRequestInterface;
    public static function setBaseUri(string|ServerRequestInterface $request): void;
    public static function cleanResponse(ResponseInterface $response, StreamFactoryInterface|ResponseFactoryInterface $factory): ResponseInterface;
    public static function emitResponse(ResponseInterface $response): void;
}

/**
 * Middleware should be built by creating a customized router and then adding the processsing - extend this to create your router
 */
abstract class DefaultRouter implements DefaultRouterInterface, CommonBridgeInterface
{
    use CommonBridgeTrait;

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
        // did we already filter out the base uri in router middleware?
        if ($request->getAttribute('baseUri') !== null) {
            return $request;
        }
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
