<?php

/**
 * Make use of the RoutingBridge in routing.php for an all-in-one PSR-15 middleware + requesthandler
 *
 * Note: see also lib/xaraya/bridge/reactphp.php for an example with ReactPHP (not fully functional with links)
 *
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * sys::init();
 * xarCache::init();
 * xarCore::xarInit(xarCore::SYSTEM_USER);
 *
 * // use some PSR-7 factory
 * use Nyholm\Psr7\Factory\Psr17Factory;
 * use Nyholm\Psr7Server\ServerRequestCreator;
 * // use Xaraya PSR-15 compatible request handler + middleware
 * use Xaraya\Bridge\Middleware\RoutingHandler;
 * use Xaraya\Bridge\Middleware\ResponseUtil;
 *
 * // get server request from somewhere
 * $psr17Factory = new Psr17Factory();
 * $requestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
 * $request = $requestCreator->fromGlobals();
 *
 * // the Xaraya PSR-15 request handler + middleware here
 * $combined = new RoutingHandler($psr17Factory);
 *
 * // handle the request directly, or use as middleware
 * $response = $combined->handle($request);
 *
 * //echo $response->getBody();
 * $combined->emitResponse($response);
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use ForbiddenOperationException;
use UnauthorizedOperationException;
use xarController;
use sys;

sys::import('xaraya.bridge.routing');
use Xaraya\Bridge\Routing\RoutingBridge;
use Xaraya\Bridge\Routing\RoutingApiBridge;
use Xaraya\Routing\RouterInterface;
use Xaraya\Bridge\RestAPI\RestAPIHandler;
use Xaraya\Services\xar;

/**
 * Combined routing middleware + request handler
 */
class RoutingHandler implements MiddlewareInterface, RequestHandlerInterface
{
    public const COMBINED_CACHE_FILE = 'url_combined_cache.php';

    /** @var ResponseUtil */
    protected $responseUtil;
    /** @var RouterInterface */
    protected $router;
    /** @var RoutingBridge */
    protected $bridge;
    /** @var RoutingApiBridge */
    protected $apibridge;

    /**
     * Initialize the middleware with response factory (or container, ...) and options
     * @param array<string, mixed> $options
     */
    public function __construct(?ResponseFactoryInterface $responseFactory = null, ?RouterInterface $router = null, array $options = [])
    {
        $this->responseUtil = new ResponseUtil($responseFactory, $options);
        $this->bridge = new RoutingBridge();
        if (empty($router)) {
            $router = $this->getRouter();
        }
        $this->setRouter($router);
    }

    public function getRouter(): RouterInterface
    {
        // get api routes (with default /api prefix) first
        $routes = RoutingApiBridge::getRoutes();
        // add normal routes - must be after /api or /{module}/{type}/{func} will match first
        $routes = array_replace($routes, $this->bridge::getRoutes());
        // get router for all routes
        // @todo move elsewhere than /cache/ and /cache/api/
        $cacheFile = sys::varpath() . '/cache/bridge/' . self::COMBINED_CACHE_FILE;
        $router = $this->bridge->getRouter($routes, $cacheFile);
        return $router;
    }

    public function setRouter(RouterInterface $router): void
    {
        $this->router = $router;
    }

    /**
     * Handle the server request for RequestHandlerInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->execute($request, null);
    }

    /**
     * Process the server request for MiddlewareInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        return $this->execute($request, $next);
    }

    public function prepareRequestCallback(ServerRequestInterface &$request): void
    {
        // @checkme we need to somehow update $request here to do any good!?
        $callback = function (string $redirectURL, int $status = 302, mixed $context = null) use (&$request) {
            $request = $request->withAttribute('redirectURL', $redirectURL);
            $request = $request->withAttribute('status', $status);
        };
        xar::ctl()->setCallback('redirectTo', $callback);
    }

    /**
     * Execute the server request - this will set request attributes based on path variables + handle the request
     */
    public function execute(ServerRequestInterface $request, ?RequestHandlerInterface $next = null): ResponseInterface
    {
        // @checkme not applicable for ReactPHP etc.
        // Strip the base uri for the calling script from the request path and set 'baseUri' request attribute
        $request = $this->bridge->stripBaseUri($request);
        $method = $request->getMethod();
        // @checkme see https://github.com/middlewares/fast-route/blob/master/src/FastRoute.php on using rawurldecode() here
        $path = $request->getUri()->getPath();

        // Let Router identify the right handler and match the path variables
        [$handler, $vars] = $this->router->match($path, $method);
        if (empty($handler)) {
            switch ((string) $vars['status']) {
                case '404':
                    // ... 404 Not Found - pass along to the next handler or return 404 error here
                    if (!empty($next)) {
                        return $next->handle($request);
                    }
                    return $this->responseUtil->createNotFoundResponse($path);

                case '405':
                    // ... 405 Method Not Allowed
                    $response = $this->responseUtil->getResponseFactory()->createResponse();
                    if (!empty($vars['methods'])) {
                        $response = $response->withStatus(405)->withHeader('Allow', implode(', ', $vars['methods']));
                    } else {
                        $response = $response->withStatus(405);
                    }
                    $response->getBody()->write('Method ' . htmlspecialchars($method) . ' is not allowed for ' . htmlspecialchars($path));
                    return $response;
            }
        }

        foreach ($vars as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }
        //return $routeInfo[1]($request)
        //return $next->handle($request);
        // ... call $handler with $vars
        $numeric = true;
        $context = null;
        try {
            // @checkme we need to somehow update $request here to do any good!?
            $this->prepareRequestCallback($request);
            if ($this->bridge->isGraphQLHandler($handler)) {
                // @checkme GraphQL playground doesn't like JSON_NUMERIC_CHECK for introspection, e.g. default value for offset = 0 instead of "0"
                $numeric = false;
            }
            // don't use call_user_func here anymore because $request is passed by reference
            [$result, $context] = $this->bridge->callHandler($handler, $vars, $request);
            $redirectURL = $request->getAttribute('redirectURL');
            if (!empty($redirectURL)) {
                echo "Location: " . $redirectURL . "\n";
                return $this->responseUtil->createRedirectResponse($redirectURL, $request->getAttribute('status', 302));
            }
            // @checkme can't really handle REST API differently here yet
            if ($handler[1] === 'getOpenAPI') {
                //header('Access-Control-Allow-Origin: *');
                // @checkme set server url to current path here
                //$result['servers'][0]['url'] = RestAPIHandler::getBaseURL();
                //$result['servers'][0]['url'] = xar::req()->getProtocol() . '://' . xar::req()->getHost() . RestAPIHandler::$endpoint;
            }
        } catch (UnauthorizedOperationException) {
            return $this->responseUtil->createUnauthorizedResponse();
        } catch (ForbiddenOperationException) {
            return $this->responseUtil->createForbiddenResponse();
        } catch (Throwable $e) {
            return $this->responseUtil->createExceptionResponse($e);
        }
        if (!empty($context) && !empty($context['mediatype'])) {
            return $this->responseUtil->createResponse($result, $context['mediatype']);
        }
        if (is_string($result)) {
            $mediaType = $request->getAttribute('mediaType', 'text/html');
            return $this->responseUtil->createResponse($result, $mediaType);
        }
        return $this->responseUtil->createJsonResponse($result, 'application/json', $numeric);
    }

    public function emitResponse(ResponseInterface $response): void
    {
        $this->responseUtil->emitResponse($response);
    }
}
