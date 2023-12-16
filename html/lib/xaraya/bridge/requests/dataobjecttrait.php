<?php
/**
 * Handle DataObject requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use Xaraya\Structures\Context;
use Exception;
use sys;

sys::import('modules.dynamicdata.class.userinterface');
use DataObjectUserInterface;
use DataObjectFactory;

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
    public static function parseDataObjectPath(string $path = '/', array $query = [], string $prefix = ''): array;

    /**
     * Summary of buildDataObjectPath
     * @param string $object
     * @param ?string $method
     * @param string|int|null $itemid
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public static function buildDataObjectPath(string $object = 'sample', ?string $method = null, string|int|null $itemid = null, array $extra = [], string $prefix = '/object'): string;

    /**
     * Summary of runDataObjectGuiRequest
     * @param array<string, mixed> $params
     * @param ?Context<string, mixed> $context
     * @return string|null
     */
    public static function runDataObjectGuiRequest($params, $context = null): ?string;

    /**
     * Summary of runDataObjectApiRequest
     * @param array<string, mixed> $params
     * @param ?Context<string, mixed> $context
     * @return mixed
     */
    public static function runDataObjectApiRequest($params, $context = null): mixed;
}

trait DataObjectBridgeTrait
{
    /**
     * Summary of parseDataObjectPath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public static function parseDataObjectPath(string $path = '/', array $query = [], string $prefix = ''): array
    {
        $params = [];
        if (strlen($path) > strlen($prefix) && strpos($path, $prefix . '/') === 0) {
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
     */
    public static function buildDataObjectPath(string $object = 'sample', ?string $method = null, string|int|null $itemid = null, array $extra = [], string $prefix = '/object'): string
    {
        // see xarDDObject::getObjectURL() and xarServer::getObjectURL()
        $uri = static::$baseUri;
        if (!empty($prefix) && strstr($uri, $prefix) !== $prefix) {
            $uri .= $prefix;
        }
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
     * Summary of runDataObjectGuiRequest
     * @param array<string, mixed> $params
     * @param ?Context<string, mixed> $context
     * @throws \Exception
     * @return string|null
     */
    public static function runDataObjectGuiRequest($params, $context = null): ?string
    {
        if (empty($params['object'])) {
            throw new Exception("Missing object parameter");
        }
        $interface = new DataObjectUserInterface($params);
        return $interface->handle($params, $context);
        // From DataObjectUserInterface:
        //...
    }

    /**
     * Summary of runDataObjectApiRequest
     * @param array<string, mixed> $params
     * @param ?Context<string, mixed> $context
     * @throws \Exception
     * @return mixed
     */
    public static function runDataObjectApiRequest($params, $context = null): mixed
    {
        if (empty($params['object'])) {
            throw new Exception("Missing object parameter");
        }
        // @checkme overriding $params['name'] here
        $params['name'] = $params['object'];
        unset($params['object']);
        $info = DataObjectFactory::getObjectInfo($params);
        if (empty($info) || empty($info['objectid'])) {
            $params = array_merge($params, $info ?? []);
            return $params;
        }
        if (!empty($params['itemid'])) {
            $objectitem = DataObjectFactory::getObject($params);
            if (!empty($params['method']) && method_exists($objectitem, $params['method'])) {
                return "Running method $params[method]() on object '$params[name]' is not advised here - please use REST API or GraphQL API instead";
            }
            $objectitem->getItem();
            $item = $objectitem->getFieldValues();
            return $item;
        }
        $objectlist = DataObjectFactory::getObjectList($params);
        if (!empty($params['method']) && method_exists($objectlist, $params['method'])) {
            return "Running method $params[method]() on object '$params[name]' is not advised here - please use REST API or GraphQL API instead";
        }
        $items = $objectlist->getItems();
        return $items;
    }
}
