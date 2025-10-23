<?php

/**
 * @package core\bridge
 * @subpackage middleware
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
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
use Xaraya\Context\ContextFactory;
use Xaraya\Context\Context;
use Xaraya\Services\xar;
use Exception;
use sys;

sys::import('xaraya.bridge.middleware.router');
sys::import('xaraya.bridge.middleware.staticfiles.router');
sys::import('xaraya.bridge.requests.staticfile');
use Xaraya\Bridge\Requests\StaticFileHandler;

/**
 * PSR-15 compatible middleware for static files (/themes/, /code/modules/, /var/, ...)
 */
class StaticFileMiddleware extends StaticFileRouter implements DefaultRouterInterface, MiddlewareInterface
{
    /** @var array<string> */
    protected array $attributes = ['static', 'source', 'folder', 'file'];
    protected StaticFileHandler $handler;
    protected ResponseUtil $responseUtil;
    public static string $baseUri = '';
    /** @var array<string, string> */
    public static array $locations = [
        'theme' => '/themes',
        'module' => '/code/modules',
        'var' => '/var',
    ];

    /**
     * Initialize the middleware with response factory (or container, ...)
     */
    public function __construct(?ResponseFactoryInterface $responseFactory = null)
    {
        $this->handler = new StaticFileHandler();
        $this->responseUtil = new ResponseUtil($responseFactory);
    }

    /**
     * Process the server request - request attributes are set here with StaticFileRouter::matchRequest()
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface|callable $next): ResponseInterface
    {
        // identify static file requests and set request attributes
        $request = $this->matchRequest($request);

        // check only the request attributes relevant for static file request
        $allowed = array_flip($this->attributes);
        $attribs = array_intersect_key($request->getAttributes(), $allowed);

        // pass the request along to the next handler and return its response
        if (empty($attribs['static']) || empty($attribs['source']) || empty($attribs['folder']) || empty($attribs['file'])) {
            // @checkme signature mismatch for process() with ReactPHP
            if ($next instanceof RequestHandlerInterface) {
                $response = $next->handle($request);
            } else {
                /** @var callable $next */
                $response = $next($request);
            }
            return $response;
        }

        // handle the static file request here and return our response
        $context = ContextFactory::fromRequest($request, __METHOD__);
        // Set context for core services here first!?
        // xar::setServicesContext($context);
        $context['mediatype'] = '';
        // @checkme keep track of the current base uri if filtered in router
        $this->setBaseUri($request);
        $context['baseuri'] = static::$baseUri;
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        //$this->prepareController($attribs['module'], static::$baseUri);
        //$context['module'] = $attribs['module'];
        // @todo where do we decide to use Twig or not
        //$context['twig'] = true;
        // @todo check if we already have a context? (via request or from elsewhere)
        //$this->setContext($context);
        $this->handler->setContext($context);


        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        $params = [
            'If-None-Match' => $request->getHeader('If-None-Match'),
            'If-Modified-Since' => $request->getHeader('If-Modified-Since'),
        ];
        $response = $this->run($attribs, $params, $context);

        return $response;
    }

    /**
     * Summary of run
     * @param array<string, mixed> $attribs
     * @param array<string, mixed> $params not used here
     * @param ?Context<string, mixed> $context not used here
     * @return ResponseInterface
     */
    public function run($attribs, $params, $context = null)
    {
        try {
            $result = $this->handler->getStaticFileRequest($attribs);
        } catch (Exception $e) {
            return $this->responseUtil->createExceptionResponse($e);
        }
        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        return $this->responseUtil->createFileResponse($result);
    }

    // @checkme signature mismatch for process() with ReactPHP
    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        return $this->process($request, $next);
    }
}
