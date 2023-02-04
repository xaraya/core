<?php
/**
 * Experiment with PSR-7 and PSR-15 compatible middleware controller for DataObject
 * Uses request attributes 'object', 'method', 'itemid' from DataObjectRouter::matchRequest()
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
use sys;

sys::import('xaraya.bridge.middleware.router');
sys::import('modules.dynamicdata.controllers.router');
sys::import('modules.dynamicdata.class.userinterface');

/**
 * PSR-15 compatible middleware for DataObject UI methods (view, display, search, ...)
 */
class DataObjectMiddleware extends DataObjectRouter implements DefaultRouterInterface, MiddlewareInterface
{
    protected array $attributes = ['object', 'method', 'itemid'];
    protected ResponseFactoryInterface $responseFactory;

    /**
     * Initialize the middleware with response factory (or container, ...)
     */
    public function __construct(?ResponseFactoryInterface $responseFactory = null)
    {
        $this->setResponseFactory($responseFactory);
    }

    /**
     * Process the server request - request attributes are set here with DataObjectRouter::matchRequest()
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        // identify object requests and set request attributes
        $request = static::matchRequest($request);

        // check only the request attributes relevant for object request
        $allowed = array_flip($this->attributes);
        $attribs = array_intersect_key($request->getAttributes(), $allowed);

        // pass the request along to the next handler and return its response
        if (empty($attribs['object'])) {
            $response = $next->handle($request);
            return $response;
        }

        // handle the object request here and return our response

        // @checkme keep track of the current base uri if filtered in router
        static::setBaseUri($request);
        // set current module to 'object' for Xaraya controller - used e.g. in xarMod::getName() in DD list
        static::prepareController('object', static::$baseUri);

        // add remaining query params to request attributes
        $params = array_merge($attribs, $request->getQueryParams());
        // add body params to query params (if any) - limited to POST requests here
        if ($request->getMethod() === 'POST') {
            $input = $request->getParsedBody();
            if (!empty($input) && is_array($input)) {
                $params = array_merge($params, $input);
            }
        }

        // @checkme pass along buildUri() as link function to DD
        $params['linktype'] = 'other';
        $params['linkfunc'] = [static::class, 'buildUri'];

        $response = $this->run($params);

        // clean up routes for object requests in response output
        //$response = static::cleanResponse($response, $this->getResponseFactory());

        return $response;
    }

    public function run($params)
    {
        try {
            $result = static::runDataObjectGuiRequest($params);
        } catch (Exception $e) {
            return $this->createExceptionResponse($e);
        }
        return $this->createResponse($result);
    }
}

class DataObjectApiMiddleware extends DataObjectMiddleware
{
    public $format = 'json';

    public function run($params)
    {
        try {
            $result = static::runDataObjectApiRequest($params);
        } catch (Exception $e) {
            return $this->createExceptionResponse($e);
        }
        // @todo adapt response based on chosen $format
        return $this->createJsonResponse($result);
    }
}
