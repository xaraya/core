<?php

/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use Xaraya\Services\DataObjectInterface;
use Xaraya\Services\ServiceFactory;
use Xaraya\Context\ContextFactory;
use Exception;
use DataObjectUserInterface;

/**
 * For documentation purposes only - available via DataObjectBridgeTrait
 */
interface DataObjectBridgeInterface extends CommonRequestInterface
{
    /**
     * Summary of parseDataObjectPath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public function parseDataObjectPath(string $path = '/', array $query = [], string $prefix = ''): array;

    /**
     * Summary of buildDataObjectPath
     * @param string $object
     * @param ?string $method
     * @param string|int|null $itemid
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public function buildDataObjectPath(string $object = 'sample', ?string $method = null, string|int|null $itemid = null, array $extra = [], string $prefix = '/object'): string;

    /**
     * Summary of runDataObjectGuiRequest
     * @param array<string, mixed> $params
     * @return string|null
     */
    public function runDataObjectGuiRequest($params): ?string;

    /**
     * Summary of runDataObjectApiRequest
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function runDataObjectApiRequest($params): mixed;
}

/**
 * Handle DataObject requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 * @phpstan-import-type RouteDef from BridgeRequest
 */
trait DataObjectBridgeTrait
{
    public static string $baseUri = '';
    public static string $prefix = '';
    protected ?DataObjectInterface $xarData = null;

    public function data(): DataObjectInterface
    {
        if (!isset($this->xarData)) {
            // from BasicBridgeTrait -> WithServicesTrait
            $xar = $this->getServicesClass();
            $this->xarData = $xar->data();
        }
        return $this->xarData;
    }

    /**
     * Get DataObject handler routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param ?string $handler
     * @param array<mixed> $extra
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getDataObjectRoutes(string $pathPrefix = '', string $namePrefix = '', ?string $handler = null, array $extra = []): array
    {
        $handler ??= static::class;
        $routes = [];

        // without trailing /
        $path = $pathPrefix . '/object/{object}';
        $name = $namePrefix . 'object-list';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handleObjectRequest'], $extra];

        $path = $pathPrefix . '/object/{object}/{itemid:\d+}';
        $name = $namePrefix . 'object-item';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handleObjectRequest'], $extra];

        $path = $pathPrefix . '/object/{object}/{itemid:\d+}/{method}';
        $name = $namePrefix . 'object-item-method';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handleObjectRequest'], $extra];

        $path = $pathPrefix . '/object/{object}/{itemid:[0-9a-f]{24}}';
        $name = $namePrefix . 'object-document';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handleObjectRequest'], $extra];

        $path = $pathPrefix . '/object/{object}/{itemid:[0-9a-f]{24}}/{method}';
        $name = $namePrefix . 'object-document-method';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handleObjectRequest'], $extra];

        // something other than itemid matching \d+ or [0-9a-f]{24}
        $path = $pathPrefix . '/object/{object}/{method}';
        $name = $namePrefix . 'object-method';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handleObjectRequest'], $extra];

        //$path = $pathPrefix . '/object/';
        //$name = $namePrefix . 'object-root';
        //$routes[$name] = [['GET', 'POST'], $path, [$handler, 'handleObjectRequest'], $extra];

        return $routes;
    }

    /**
     * Summary of parseDataObjectPath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public function parseDataObjectPath(string $path = '/', array $query = [], string $prefix = ''): array
    {
        $params = [];
        if (strlen($path) > strlen($prefix) && str_starts_with($path, $prefix . '/')) {
            $pieces = explode('/', substr($path, strlen($prefix) + 1));
            // {prefix}/{object} = view
            $params['object'] = $pieces[0];
            if (count($pieces) > 1) {
                if (!is_numeric($pieces[1])) {
                    // {prefix}/{object}/{method} = new, query, stats, ...
                    $params['method'] = $pieces[1];
                } else {
                    // {prefix}/{object}/{itemid} = display
                    $params['itemid'] = $pieces[1];
                    if (count($pieces) > 2) {
                        // {prefix}/{object}/{itemid}/{$method} = update, delete, ...
                        $params['method'] = $pieces[2];
                    }
                }
            }
        }
        // add remaining query params to path params
        $params = array_merge($params, $query);
        return $params;
    }

    /**
     * Summary of buildDataObjectPath
     * @param string $object
     * @param ?string $method
     * @param string|int|null $itemid
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     * @todo do we want to keep this static?
     */
    public function buildDataObjectPath(string $object = 'sample', ?string $method = null, string|int|null $itemid = null, array $extra = [], string $prefix = '/object'): string
    {
        // see xar::ctl()->getObjectURL() and xarDDObject::getObjectURL()
        $uri = $prefix;
        // {prefix}/{object} = view
        $uri .= '/' . $object;
        if (empty($itemid)) {
            if (!empty($method) && $method != 'view') {
                // {prefix}/{object}/{method} = new, query, stats, ...
                $uri .= '/' . $method;
            }
        } else {
            // {prefix}/{object}/{itemid} = display
            $uri .= '/' . $itemid;
            if (!empty($method) && $method != 'display') {
                // {prefix}/{object}/{itemid}/{$method} = update, delete, ...
                $uri .= '/' . $method;
            }
        }
        if (!empty($extra)) {
            $uri .= '?' . http_build_query($extra);
        }
        return $uri;
    }

    /**
     * Basic route builder for object requests e.g. in response output or templates - assuming short url format here
     * @param ?string $object
     * @param ?string $method
     * @param string|int|null $itemid
     * @param array<string, mixed> $extra
     * @see \Xaraya\Bridge\Requests\BasicBridgeTrait::prepareController()
     * @return string
     */
    public function buildUri(?string $object = null, ?string $method = null, string|int|null $itemid = null, array $extra = []): string
    {
        $prefix = static::$baseUri;
        return $this->buildDataObjectPath($object, $method, $itemid, $extra, $prefix);
    }

    /**
     * Summary of handleObjectRequest
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     * @see \xarDDObject::getActionURL()
     */
    public function handleObjectRequest($vars, &$request = null)
    {
        // if coming from module request handler, convert to object request
        if (empty($vars['object']) && $vars['module'] == 'object') {
            // path = /object/{object}
            $vars['object'] = $vars['type'] ?? '';
            if (!empty($vars['func'])) {
                if (is_numeric($vars['func'])) {
                    // path = /object/{object}/{itemid}
                    $vars['itemid'] = $vars['func'];
                } else {
                    // path = /object/{object}/{method}
                    $vars['method'] = $vars['func'];
                }
                unset($vars['func']);
            }
            unset($vars['module']);
            unset($vars['type']);
        }
        // path = /{object}/{itemid}/{method} or /{object}/{method}
        // dispatcher doesn't provide query params by default
        $query = $this->getQueryParams($request);
        // add remaining query params to path vars
        $params = array_merge($vars, $query);
        // add body params to query params
        $input = $this->getParsedBody($request);
        if (!empty($input) && is_array($input)) {
            $params = array_merge($params, $input);
        }

        // @checkme pass along buildUri() as link function to DD
        $params['linktype'] = 'other';
        $params['linkfunc'] = [$this, 'buildDataObjectPath'];

        if ($params['object'] == 'roles_users') {
            $params['fieldlist'] = ['id', 'name', 'uname', 'state'];
        }

        $context = ContextFactory::fromRequest($request, __METHOD__);
        // Set context for core services here first!?
        // xar::setServicesContext($context);
        $context['mediatype'] = '';
        static::$baseUri = $this->getBaseUri($request) . static::$prefix;
        $context['baseuri'] = static::$baseUri;
        // set current module to 'object' for Xaraya controller - used e.g. in xar::mod()->getName()
        $this->prepareController('object', static::$baseUri . '/object');
        $context['module'] = 'object';
        // @todo check if we already have a context? (via request or from elsewhere)
        $this->setContext($context);

        // @todo allow overriding this for RoutingApiBridge vs. RoutingBridge
        $result = $this->runDataObjectRequest($params);
        return [$result, $context];
    }

    /**
     * Summary of runDataObjectGuiRequest
     * @param array<string, mixed> $params
     * @throws \Exception
     * @return string|null
     */
    public function runDataObjectGuiRequest($params): ?string
    {
        if (empty($params['object'])) {
            throw new Exception("Missing object parameter");
        }
        // from BasicBridgeTrait -> WithServicesTrait
        $interface = new DataObjectUserInterface($params, $this->getContext(), $this->getServicesClass());
        return $interface->handle($params);
        // From DataObjectUserInterface:
        //...
    }

    /**
     * Summary of runDataObjectApiRequest
     * @param array<string, mixed> $params
     * @throws \Exception
     * @return mixed
     */
    public function runDataObjectApiRequest($params): mixed
    {
        if (empty($params['object'])) {
            throw new Exception("Missing object parameter");
        }
        // @checkme overriding $params['name'] here
        $params['name'] = $params['object'];
        unset($params['object']);
        $info = $this->data()->getObjectInfo($params);
        if (empty($info) || empty($info['objectid'])) {
            $params = array_merge($params, $info ?? []);
            return $params;
        }
        if (!empty($params['itemid'])) {
            $objectitem = $this->data()->getObject($params);
            if (!empty($params['method']) && method_exists($objectitem, $params['method'])) {
                return "Running method $params[method]() on object '$params[name]' is not advised here - please use REST API or GraphQL API instead";
            }
            $objectitem->getItem();
            $item = $objectitem->getFieldValues();
            return $item;
        }
        $objectlist = $this->data()->getObjectList($params);
        if (!empty($params['method']) && method_exists($objectlist, $params['method'])) {
            return "Running method $params[method]() on object '$params[name]' is not advised here - please use REST API or GraphQL API instead";
        }
        $items = $objectlist->getItems();
        return $items;
    }
}
