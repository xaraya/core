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
use Xaraya\Context\ContextFactory;
use Xaraya\Context\Context;
use Exception;
use sys;

sys::import('xaraya.bridge.middleware.router');
sys::import('modules.modules.controllers.router');
sys::import('xaraya.bridge.requests.module');
use Xaraya\Bridge\Requests\ModuleRequest;

/**
 * PSR-15 compatible middleware for module GUI functions (user main, admin modifyconfig, ...)
 */
class ModuleMiddleware extends ModuleRouter implements DefaultRouterInterface, MiddlewareInterface
{
    /** @var array<string> */
    protected array $attributes = ['module', 'type', 'func'];
    protected ResponseUtil $responseUtil;
    protected bool $wrapPage = false;

    /**
     * Initialize the middleware with response factory (or container, ...) and options
     */
    public function __construct(?ResponseFactoryInterface $responseFactory = null, bool $wrapPage = false)
    {
        $this->responseUtil = new ResponseUtil($responseFactory);
        $this->wrapPage = $wrapPage;
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
        $context = ContextFactory::fromRequest($request, __METHOD__);
        $context['mediatype'] = '';
        // @checkme keep track of the current base uri if filtered in router
        static::setBaseUri($request);
        $context['baseuri'] = static::$baseUri;
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        static::prepareController($attribs['module'], static::$baseUri);
        $context['module'] = $attribs['module'];
        // @todo where do we decide to use Twig or not
        //$context['twig'] = true;

        // filter out request attributes from remaining query params here
        $params = array_diff_key($request->getQueryParams(), $attribs);
        // add body params to query params (if any) - limited to POST requests here
        if ($request->getMethod() === 'POST') {
            $input = $request->getParsedBody();
            if (!empty($input) && is_array($input)) {
                $params = array_merge($params, $input);
            }
        }

        $response = $this->run($attribs, $params, $context);

        // clean up routes for module requests in response output
        //$response = ResponseUtil::cleanResponse($response, $this->getResponseFactory());

        return $response;
    }

    /**
     * Summary of run
     * @param array<string, mixed> $attribs
     * @param array<string, mixed> $params
     * @param ?Context<string, mixed> $context
     * @return ResponseInterface
     */
    public function run($attribs, $params, $context = null)
    {
        try {
            $result = ModuleRequest::runModuleGuiRequest($attribs, $params, $context);
        } catch (Exception $e) {
            return $this->responseUtil->createExceptionResponse($e);
        }
        if ($this->wrapPage) {
            $result = $this->responseUtil->wrapOutputInPage($result, $context);
        }
        if (!empty($context) && !empty($context['mediatype'])) {
            return $this->responseUtil->createResponse($result, $context['mediatype']);
        }
        return $this->responseUtil->createResponse($result);
    }
}

class ModuleApiMiddleware extends ModuleMiddleware
{
    public string $format = 'json';

    /**
     * Summary of run
     * @param array<string, mixed> $attribs
     * @param array<string, mixed> $params
     * @param ?Context<string, mixed> $context
     * @return ResponseInterface
     */
    public function run($attribs, $params, $context = null)
    {
        try {
            $result = ModuleRequest::runModuleApiRequest($attribs, $params, $context);
        } catch (Exception $e) {
            return $this->responseUtil->createExceptionResponse($e);
        }
        // @todo adapt response based on chosen $format
        return $this->responseUtil->createJsonResponse($result);
    }
}
