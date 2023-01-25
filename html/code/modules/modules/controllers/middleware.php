<?php
/**
 * Experiment with PSR-7 and PSR-15 compatible middleware controller for modules
 * Uses request attributes 'module', 'type', 'func' from ModuleRouter::matchRequest()
 *
 * Note: single-pass middleware, see https://www.php-fig.org/psr/psr-15/meta/
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;
use xarController;
use xarMod;
use xarServer;
use xarSystemVars;
use sys;

sys::import('xaraya.bridge.middleware');
sys::import('modules.modules.controllers.router');

/**
 * PSR-15 compatible middleware for module GUI functions (user main, admin modifyconfig, ...)
 */
class ModuleMiddleware extends ModuleRouter implements DefaultRouterInterface, MiddlewareInterface
{
    protected array $attibutes = ['module', 'type', 'func'];
    protected ResponseFactoryInterface $responseFactory;

    /**
     * Initialize the middleware with response factory (or container, ...)
     */
    public function __construct(?ResponseFactoryInterface $responseFactory = null)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Process the server request - request attributes are set here with ModuleRouter::matchRequest()
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        // identify module requests and set request attributes
        $request = static::matchRequest($request);

        // check only the request attributes relevant for module request
        $allowed = array_flip($this->attributes);
        $attribs = array_intersect_key($request->getAttributes(), $allowed);

        // pass the request along to the next handler and return its response
        if (empty($attribs['module'])) {
            $response = $next->handle($request);
            return $response;
        }

        // handle the module request here and return our response

        // @checkme keep track of the current base uri if filtered in router
        static::setBaseUri($request);
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        xarController::getRequest()->setModule($attribs['module']);
        // @checkme override system config here, since xarController does re-init() for each URL() for some reason...
        $entryPoint = str_replace(xarServer::getBaseURI(), '', static::$baseUri);
        //xarSystemVars::set(sys::LAYOUT, 'BaseURI');
        xarSystemVars::set(sys::LAYOUT, 'BaseModURL', $entryPoint);
        xarController::$entryPoint = $entryPoint;
        xarController::$buildUri = [static::class, 'buildUri'];

        // filter out request attributes from remaining query params here
        $params = array_diff_key($request->getQueryParams(), $attribs);

        try {
            $body = xarMod::guiFunc($attribs['module'], $attribs['type'] ?? 'user', $attribs['func'] ?? 'main', $params);
            $response = $this->responseFactory->createResponse();
            $response->getBody()->write($body);
        } catch (Exception $e) {
            $body = "Exception: " . $e->getMessage();
            $response = $this->responseFactory->createResponse(422, 'Module Middleware Exception');
            $response->getBody()->write($body);
        }

        // clean up routes for module requests in response output
        //$response = static::cleanResponse($response, $this->responseFactory);

        return $response;
    }
}
