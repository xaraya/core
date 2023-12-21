<?php
/**
 * Make use of the FastRouteBridge in routing.php for an all-in-one PSR-15 middleware + requesthandler
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
 * use Xaraya\Bridge\Middleware\FastRouteHandler;
 * use Xaraya\Bridge\Middleware\ResponseUtil;
 *
 * // get server request from somewhere
 * $psr17Factory = new Psr17Factory();
 * $requestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
 * $request = $requestCreator->fromGlobals();
 *
 * // the Xaraya PSR-15 request handler + middleware here
 * $fastrouted = new FastRouteHandler($psr17Factory);
 *
 * // handle the request directly, or use as middleware
 * $response = $fastrouted->handle($request);
 *
 * //echo $response->getBody();
 * ResponseUtil::emitResponse($response);
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
use Xaraya\Bridge\Routing\FastRouteBridge;
use Xaraya\Bridge\Routing\FastRouteApiBridge;
use Xaraya\Bridge\Routing\TrackRouteCollector;
use DataObjectRESTHandler;
// @checkme rename FastRoute dispatcher as router here to avoid confusion with PSR-15 naming
use FastRoute\Dispatcher as FastRouter;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

class FastRouteHandler implements MiddlewareInterface, RequestHandlerInterface
{
    /** @var ResponseUtil */
    protected $responseUtil;
    /** @var FastRouter */
    protected $router;

    /**
     * Initialize the middleware with response factory (or container, ...) and options
     * @param array<string, mixed> $options
     */
    public function __construct(?ResponseFactoryInterface $responseFactory = null, ?FastRouter $router = null, array $options = [])
    {
        $this->responseUtil = new ResponseUtil($responseFactory, $options);
        if (empty($router)) {
            $router = $this->getRouter();
        }
        $this->setRouter($router);
    }

    public function getRouter(): FastRouter
    {
        // override standard routeCollector here
        $router = simpleDispatcher(function (RouteCollector $r) {
            $r->addGroup('/api', function (RouteCollector $r) {
                FastRouteApiBridge::addRouteCollection($r);
            });
            FastRouteBridge::addRouteCollection($r);
        }, [
            'routeCollector' => TrackRouteCollector::class,
        ]);
        return $router;
    }

    public function setRouter(FastRouter $router): void
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
        xarController::$redirectTo = $callback;
    }

    /**
     * Execute the server request - this will set request attributes based on path variables + handle the request
     */
    public function execute(ServerRequestInterface $request, ?RequestHandlerInterface $next = null): ResponseInterface
    {
        // @checkme not applicable for ReactPHP etc.
        // Strip the base uri for the calling script from the request path and set 'baseUri' request attribute
        $request = DefaultMiddleware::stripBaseUri($request);
        $method = $request->getMethod();
        // @checkme see https://github.com/middlewares/fast-route/blob/master/src/FastRoute.php on using rawurldecode() here
        $path = $request->getUri()->getPath();

        // Let FastRoute identify the right handler and match the path variables
        $routeInfo = $this->router->dispatch($method, $path);
        switch ($routeInfo[0]) {
            case FastRouter::NOT_FOUND:
                // ... 404 Not Found - pass along to the next handler or return 404 error here
                if (!empty($next)) {
                    return $next->handle($request);
                }
                return $this->responseUtil->createNotFoundResponse($path);

            case FastRouter::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                $response = $this->responseUtil->getResponseFactory()->createResponse();
                $response = $response->withStatus(405)->withHeader('Allow', implode(', ', $allowedMethods));
                $response->getBody()->write('Method ' . htmlspecialchars($method) . ' is not allowed for ' . htmlspecialchars($path));
                return $response;

            case FastRouter::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
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
                    // don't use call_user_func here anymore because $request is passed by reference
                    if (strpos($path, '/restapi/') === 0) {
                        // different processing for REST API - see rst.php
                        [$result, $context] = DataObjectRESTHandler::callHandler($handler, $vars, $request);
                    } elseif (strpos($path, '/graphql') === 0) {
                        // different processing for GraphQL API - see gql.php
                        [$result, $context] = $handler($vars, $request);
                        $numeric = false;
                    } else {
                        [$result, $context] = $handler($vars, $request);
                    }
                    $redirectURL = $request->getAttribute('redirectURL');
                    if (!empty($redirectURL)) {
                        echo "Location: " . $redirectURL . "\n";
                        return $this->responseUtil->createRedirectResponse($redirectURL, $request->getAttribute('status', 302));
                    }
                    // @checkme can't really handle REST API differently here yet
                    if ($handler[1] === 'getOpenAPI') {
                        //header('Access-Control-Allow-Origin: *');
                        // @checkme set server url to current path here
                        //$result['servers'][0]['url'] = DataObjectRESTHandler::getBaseURL();
                        //$result['servers'][0]['url'] = xarServer::getProtocol() . '://' . xarServer::getHost() . DataObjectRESTHandler::$endpoint;
                    }
                } catch (UnauthorizedOperationException $e) {
                    return $this->responseUtil->createUnauthorizedResponse();
                } catch (ForbiddenOperationException $e) {
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

            default:
                $result = "Unknown result from FastRoute Dispatcher: " . var_export($routeInfo, true);
                return $this->responseUtil->createResponse($result);
        }
    }
}
