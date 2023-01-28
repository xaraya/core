<?php
/**
 * Handle DataObject requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use Exception;
use sys;

sys::import('modules.dynamicdata.class.userinterface');
use DataObjectUserInterface;
use DataObjectMaster;

/**
 * For documentation purposes only - available via DataObjectBridgeTrait
 */
interface DataObjectBridgeInterface extends CommonRequestInterface
{
    public static function parseDataObjectPath(string $path = '/', array $query = [], string $prefix = ''): array;
    public static function buildDataObjectPath(string $object = 'sample', ?string $method = null, string|int|null $itemid = null, array $extra = [], string $prefix = '/object'): string;
    public static function runDataObjectGuiRequest($params): string;
    public static function runDataObjectApiRequest($params): mixed;
}

trait DataObjectBridgeTrait
{
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

    public static function runDataObjectGuiRequest($params): string
    {
        if (empty($params['object'])) {
            throw new Exception("Missing object parameter");
        }
        $interface = new DataObjectUserInterface($params);
        return $interface->handle($params);
        // From DataObjectUserInterface:
        //...
    }

    public static function runDataObjectApiRequest($params): mixed
    {
        if (empty($params['object'])) {
            throw new Exception("Missing object parameter");
        }
        // @checkme overriding $params['name'] here
        $params['name'] = $params['object'];
        unset($params['object']);
        $info = DataObjectMaster::getObjectInfo($params);
        if (empty($info) || empty($info['objectid'])) {
            $params = array_merge($params, $info ?? []);
            return $params;
        }
        if (!empty($params['itemid'])) {
            $objectitem = DataObjectMaster::getObject($params);
            if (!empty($params['method']) && method_exists($objectitem, $params['method'])) {
                return "Running method $params[method]() on object '$params[name]' is not advised here - please use REST API or GraphQL API instead";
            }
            $objectitem->getItem();
            $item = $objectitem->getFieldValues();
            return $item;
        }
        $objectlist = DataObjectMaster::getObjectList($params);
        if (!empty($params['method']) && method_exists($objectlist, $params['method'])) {
            return "Running method $params[method]() on object '$params[name]' is not advised here - please use REST API or GraphQL API instead";
        }
        $items = $objectlist->getItems();
        return $items;
    }
}
