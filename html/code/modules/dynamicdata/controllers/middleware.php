<?php
/**
 * Experiment with PSR-7 and PSR-15 compatible DD middleware controller
 *
 * Note: single-pass middleware, see https://www.php-fig.org/psr/psr-15/meta/
 *
 * require dirname(__DIR__).'/vendor/autoload.php';
 * sys::init();
 * xarCache::init();
 * xarCore::xarInit(xarCore::SYSTEM_USER);
 *
 * use Nyholm\Psr7\Factory\Psr17Factory;
 * use Nyholm\Psr7Server\ServerRequestCreator;
 * use Middlewares\Utils\Dispatcher;
 * use Xaraya\DataObject\DataObjectMiddleware;
 *
 * $psr17Factory = new Psr17Factory();
 * $requestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
 * $request = $requestCreator->fromGlobals();
 *
 * $middleware = new DataObjectMiddleware($psr17Factory);
 * // some other middleware before or after...
 * $before = function ($request, $next) {
 *     $response = $next->handle($request->withHeader('X-Request-Before', 'Bar'));
 *     return $response->withHeader('X-Response-Before', 'Bar');
 * };
 * $after = function ($request, $next) {
 *     $response = $next->handle($request->withHeader('X-Request-After', 'Baz'));
 *     $response->getBody()->write('Nothing to see here...');
 *     return $response->withHeader('X-Response-After', 'Baz');
 * };
 *
 * $stack = [
 *     $before,
 *     $middleware,
 *     // Warning: we never get here if there's an object to be handled
 *     $after,
 * ];
 *
 * $response = Dispatcher::run($stack, $request);
 * //echo $response->getBody();
 * DataObjectMiddleware::emit($response);
 */

namespace Xaraya\DataObject;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;
use sys;

sys::import('modules.dynamicdata.class.userinterface');
use DataObjectUserInterface;

class DataObjectMiddleware implements MiddlewareInterface
{
    private $responseFactory;

    public function __construct(?ResponseFactoryInterface $responseFactory = null)
    {
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $params = $request->getQueryParams();
        // handle the object request here and return our response
        if (!empty($params['object'])) {
            $interface = new DataObjectUserInterface($params);
            try {
                $body = $interface->handle($params);
                $response = $this->responseFactory->createResponse();
                $response->getBody()->write($body);
            } catch (Exception $e) {
                $body = "Exception: " . $e->getMessage();
                $response = $this->responseFactory->createResponse(422, 'DataObject Middleware Exception');
                $response->getBody()->write($body);
            }
            // From DataObjectUserInterface:
            //...
            return $response;
        }
        // pass the request along to the next handler and return its response
        $response = $next->handle($request);
        return $response->withAddedHeader('X-Middleware-Seen', 'DataObjectMiddleware');
    }

    /**
     * Basic emitter utility to send back response
     */
    public static function emit(ResponseInterface $response)
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
