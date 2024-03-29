<?php
/**
 * Experiment with PSR-7 and PSR-15 compatible middleware controllers
 *
 * See dynamicdata/controllers/middleware.php and modules/controllers/middleware.php
 *
 * require_once dirname(__DIR__).'/vendor/autoload.php';
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
 * use Xaraya\Bridge\Middleware\ResponseUtil;
 *
 * // get server request from somewhere
 * $psr17Factory = new Psr17Factory();
 * $requestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
 * $request = $requestCreator->fromGlobals();
 *
 * // the Xaraya PSR-15 middleware here (with option to wrap output in page)
 * $objects = new DataObjectMiddleware($psr17Factory, false);
 * $modules = new ModuleMiddleware($psr17Factory, false);
 *
 * // some other middleware before or after...
 * $filter = function ($request, $next) {
 *     // @checkme strip baseUri from request path and set 'baseUri' request attribute here?
 *     $request = DefaultMiddleware::stripBaseUri($request);
 *     $response = $next->handle($request);
 *     return $response;
 * };
 * // page wrapper for object/module requests in response (if not specified above)
 * $wrapper = function ($request, $next) use ($psr17Factory) {
 *     $response = $next->handle($request);
 *     $response = ResponseUtil::wrapResponse($response, $psr17Factory);
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
 *     //$wrapper,
 *     $objects,
 *     // Warning: we never get here if there's an object to be handled
 *     $modules,
 *     // Warning: we never get here if there's a module to be handled
 *     $notfound,
 * ];
 *
 * $response = Dispatcher::run($stack, $request);
 * //echo $response->getBody();
 * ResponseUtil::emitResponse($response);
 *
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

/**
 * Middleware should be built by creating a customized router and then adding the processsing - do *not* extend this directly
 */
class DefaultMiddleware extends DefaultRouter implements DefaultRouterInterface, MiddlewareInterface
{
    protected ResponseUtil $responseUtil;

    /**
     * Initialize the middleware with response factory (or container, ...) and options
     * @param array<string, mixed> $options
     */
    public function __construct(?ResponseFactoryInterface $responseFactory = null, array $options = [])
    {
        $this->responseUtil = new ResponseUtil($responseFactory, $options);
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
     * @return array<string, mixed>
     */
    public static function parseUri(ServerRequestInterface $request): array
    {
        return [];
    }

    /**
     * Basic route builder for object/module requests e.g. in response output or templates - assuming short url format here
     * @param array<string, mixed> $extra
     */
    public static function buildUri(?string $arg1 = null, ?string $arg2 = null, string|int|null $arg3 = null, array $extra = []): string
    {
        return '/';
    }
}
