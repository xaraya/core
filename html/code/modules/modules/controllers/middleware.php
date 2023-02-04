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
use xarMod;
use sys;

sys::import('xaraya.bridge.middleware.router');
sys::import('modules.modules.controllers.router');

/**
 * PSR-15 compatible middleware for module GUI functions (user main, admin modifyconfig, ...)
 */
class ModuleMiddleware extends ModuleRouter implements DefaultRouterInterface, MiddlewareInterface
{
    protected array $attributes = ['module', 'type', 'func'];
    protected ResponseFactoryInterface $responseFactory;

    /**
     * Initialize the middleware with response factory (or container, ...)
     */
    public function __construct(?ResponseFactoryInterface $responseFactory = null)
    {
        $this->setResponseFactory($responseFactory);
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
        static::prepareController($attribs['module'], static::$baseUri);

        // filter out request attributes from remaining query params here
        $params = array_diff_key($request->getQueryParams(), $attribs);
        // add body params to query params (if any) - limited to POST requests here
        if ($request->getMethod() === 'POST') {
            $input = $request->getParsedBody();
            if (!empty($input) && is_array($input)) {
                $params = array_merge($params, $input);
            }
        }

        $response = $this->run($attribs, $params);

        // clean up routes for module requests in response output
        //$response = static::cleanResponse($response, $this->getResponseFactory());

        return $response;
    }

    public function run($attribs, $params)
    {
        try {
            $result = static::runModuleGuiRequest($attribs, $params);
        } catch (Exception $e) {
            return $this->createExceptionResponse($e);
        }
        return $this->createResponse($result);
    }
}

class ModuleApiMiddleware extends ModuleMiddleware
{
    public $format = 'json';

    public function run($attribs, $params)
    {
        try {
            $result = static::runModuleApiRequest($attribs, $params);
        } catch (Exception $e) {
            return $this->createExceptionResponse($e);
        }
        // @todo adapt response based on chosen $format
        return $this->createJsonResponse($result);
    }
}
