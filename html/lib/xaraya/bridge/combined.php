<?php
/**
 * Make use of the FastRouteBridge in routing.php for an all-in-one PSR-15 middleware + requesthandler
 *
 * Note: see also lib/xaraya/bridge/reactphp.php for an example with ReactPHP (not fully functional with links)
 *
 * require dirname(__DIR__).'/vendor/autoload.php';
 * sys::init();
 * xarCache::init();
 * xarCore::xarInit(xarCore::SYSTEM_USER);
 *
 * // use some PSR-7 factory
 * use Nyholm\Psr7\Factory\Psr17Factory;
 * use Nyholm\Psr7Server\ServerRequestCreator;
 * // use Xaraya PSR-15 compatible request handler + middleware
 * use Xaraya\Bridge\Middleware\DefaultMiddleware;
 * use Xaraya\Bridge\Middleware\FastRouteHandler;
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
 * DefaultMiddleware::emitResponse($response);
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;
use ForbiddenOperationException;
use sys;

sys::import('xaraya.bridge.routing');
use Xaraya\Bridge\Routing\FastRouteBridge;
use Xaraya\Bridge\Routing\FastRouteApiBridge;
use Xaraya\Bridge\Routing\TrackRouteCollector;
// @checkme rename FastRoute dispatcher as router here to avoid confusion with PSR-15 naming
use FastRoute\Dispatcher as FastRouter;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

class FastRouteHandler implements MiddlewareInterface, RequestHandlerInterface
{
    use DefaultResponseTrait;

    protected $router;

    /**
     * Initialize the middleware with response factory (or container, ...)
     */
    public function __construct(?ResponseFactoryInterface $responseFactory = null, ?FastRouter $router = null, array $options = [])
    {
        $this->setResponseFactory($responseFactory);
        $this->options = $options;
        if (empty($router)) {
            $router = simpleDispatcher(function (RouteCollector $r) {
                $r->addGroup('/api', function (RouteCollector $r) {
                    FastRouteApiBridge::addRouteCollection($r);
                });
                FastRouteBridge::addRouteCollection($r);
            }, [
                'routeCollector' => TrackRouteCollector::class
            ]);
        }
        $this->setRouter($router);
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
                $response = $this->getResponseFactory()->createResponse();
                $response = $response->withStatus(404);
                $response->getBody()->write('Nothing to see here at ' . htmlspecialchars($path));
                return $response;
                break;
            case FastRouter::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                $response = $this->getResponseFactory()->createResponse();
                $response = $response->withStatus(405)->withHeader('Allow', implode(', ', $allowedMethods));
                $response->getBody()->write('Method ' . htmlspecialchars($method) . ' is not allowed for ' . htmlspecialchars($path));
                return $response;
                break;
            case FastRouter::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                foreach ($vars as $key => $value) {
                    $request = $request->withAttribute($key, $value);
                }
                //return $routeInfo[1]($request)
                //return $next->handle($request);
                // ... call $handler with $vars
                try {
                    $query = $request->getQueryParams();
                    $input = null;
                    // pass along body params too (if any) - limited to POST or PUT requests here
                    if ($method === 'POST' || $method === 'PUT') {
                        if (strpos($path, '/restapi/') === false) {
                            $input = $request->getParsedBody();
                        } else {
                            // @checkme for REST API we need to json_decode the raw body here
                            $rawInput = (string) $request->getBody();
                            if (!empty($rawInput)) {
                                $input = json_decode($rawInput, true);
                            }
                        }
                    }
                    // @checkme can't really use this here to pass back the mediaType
                    FastRouteBridge::$mediaType = '';
                    $result = call_user_func($handler, $vars, $query, $input);
                    // @checkme can't really handle REST API differently here yet
                    if ($handler[1] === 'getOpenAPI') {
                        //header('Access-Control-Allow-Origin: *');
                        // @checkme set server url to current path here
                        //$result['servers'][0]['url'] = DataObjectRESTHandler::getBaseURL();
                        //$result['servers'][0]['url'] = xarServer::getProtocol() . '://' . xarServer::getHost() . DataObjectRESTHandler::$endpoint;
                    }
                } catch (ForbiddenOperationException $e) {
                    $status = http_response_code();
                    $response = $this->getResponseFactory()->createResponse();
                    if ($status === 401) {
                        $response = $response->withStatus(401)->withHeader('WWW-Authenticate', 'Token realm="Xaraya Site Login", created=');
                        $response->getBody()->write('This operation is unauthorized, please authenticate.');
                    } else {
                        $response = $response->withStatus(403);
                        $response->getBody()->write('This operation is forbidden.');
                    }
                    return $response;
                } catch (Exception $e) {
                    return $this->createExceptionResponse($e);
                }
                if (is_string($result)) {
                    return $this->createResponse($result);
                }
                return $this->createJsonResponse($result);
                break;
            default:
                $result = "Unknown result from FastRoute Dispatcher: " . var_export($routeInfo, true);
                return $this->createResponse($result);
                break;
        }
    }
}
