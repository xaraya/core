<?php
/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.dynamicdata.class.objects.master');

/**
 * Class to handle DataObject REST API calls
 */
class DataObjectRESTHandler extends xarObject
{
    public static $endpoint = 'rst.php/v1';
    public static $objects = array();
    public static $schemas = array();

    public static function getOpenAPI($args = null)
    {
        $openapi = sys::varpath() . '/cache/openapi.json';
        if (!file_exists($openapi)) {
            return array('TODO' => 'generate var/cache/openapi.json with builder');
        }
        $content = file_get_contents($openapi);
        $doc = json_decode($content, true);
        return $doc;
    }

    public static function getURL($object = null, $itemid = null)
    {
        if (empty($object)) {
            return xarServer::getBaseURL() . self::$endpoint . '/objects';
        }
        if (empty($itemid)) {
            return xarServer::getBaseURL() . self::$endpoint . '/objects/' . $object;
        }
        return xarServer::getBaseURL() . self::$endpoint . '/objects/' . $object . '/' . $itemid;
    }

    public static function getObjects($args)
    {
        if (empty(self::$objects)) {
            $object = 'objects';
            $fieldlist = array('objectid', 'name', 'label', 'module_id', 'itemtype', 'datastore');
            $params = array('name' => $object, 'fieldlist' => $fieldlist);
            $objectlist = DataObjectMaster::getObjectList($params);
            self::$objects = $objectlist->getItems();
        }
        $result = array('items' => array(), 'count' => count(self::$objects));
        foreach (self::$objects as $itemid => $item) {
            if ($item['datastore'] !== 'dynamicdata') {
                continue;
            }
            $item['_links'] = array('self' => array('href' => self::getURL($item['name'])));
            array_push($result['items'], $item);
        }
        $result['filter'] = 'datastore,eq,dynamicdata';
        //return array('method' => 'getObjects', 'args' => $args, 'result' => $result);
        return $result;
    }

    public static function getObjectList($args)
    {
        $object = $args['object'];
        $schema = 'view-' . $object;
        if (!self::hasSchema($schema)) {
            return array('method' => 'getObjectList', 'args' => $args, 'schema' => $schema, 'error' => 'Unknown schema');
        }
        $fieldlist = array_keys(self::getViewSchemaProperties($schema));
        $limit = 20;
        if (!empty($args['limit']) && is_numeric($args['limit'])) {
            $limit = intval($args['limit']);
        }
        $offset = 0;
        if (!empty($args['offset']) && is_numeric($args['offset'])) {
            $offset = intval($args['offset']);
        }
        $order = '';
        if (!empty($args['order'])) {
            $order = $args['order'];
        }
        $filter = [];
        if (!empty($args['filter'])) {
            $filter = $args['filter'];
        }
        $params = array('name' => $object, 'fieldlist' => $fieldlist);
        $objectlist = DataObjectMaster::getObjectList($params);
        $result = array('items' => array(), 'count' => $objectlist->countItems(), 'limit' => $limit, 'offset' => $offset);
        $params = array('numitems' => $limit);
        if (!empty($offset) && !empty($result['count'])) {
            if ($offset < $result['count']) {
                $params['startnum'] = $offset + 1;
            } else {
                throw new Exception('Invalid offset ' . $offset);
            }
        }
        /**
        if (!empty($order)) {
            $params['sort'] = array();
            $sorted = explode(',', $order);
            foreach ($sorted as $sortme) {
                if (substr($sortme, 0, 1) === '-') {
                    $params['sort'][] = substr($sortme, 1) . ' DESC';
                    continue;
                }
                $params['sort'][] = $sortme;
            }
            //$params['sort'] = implode(',', $params['sort']);
        }
         */
        $items = $objectlist->getItems($params);
        $deferred = array();
        foreach ($fieldlist as $key) {
            if (!empty($objectlist->properties[$key]) && method_exists($objectlist->properties[$key], 'getDeferredData')) {
                array_push($deferred, $key);
            }
        }
        foreach ($items as $itemid => $item) {
            // @todo filter out fieldlist in dynamic_data datastore
            $diff = array_diff(array_keys($item), $fieldlist);
            foreach ($diff as $key) {
                unset($item[$key]);
            }
            foreach ($deferred as $key) {
                $data = $objectlist->properties[$key]->getDeferredData(array('value' => $item[$key], '_itemid' => $itemid));
                $item[$key] = $data['value'];
            }
            $item['_links'] = array('self' => array('href' => self::getURL($object, $itemid)));
            array_push($result['items'], $item);
        }
        //return array('method' => 'getObjectList', 'args' => $args, 'schema' => $schema, 'fieldlist' => $fieldlist, 'result' => $result);
        return $result;
    }

    public static function getObjectItem($args)
    {
        $object = $args['object'];
        $itemid = $args['itemid'];
        $schema = 'display-' . $object;
        if (!self::hasSchema($schema)) {
            return array('method' => 'getObjectItem', 'args' => $args, 'schema' => $schema, 'error' => 'Unknown schema');
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        $fieldlist = array_keys(self::getDisplaySchemaProperties($schema));
        $params = array('name' => $object, 'itemid' => $itemid, 'fieldlist' => $fieldlist);
        $objectitem = DataObjectMaster::getObject($params);
        $itemid = $objectitem->getItem();
        if ($itemid != $params['itemid']) {
            throw new Exception('Unknown item ' . $object);
        }
        // @checkme this throws exception for userlist property when xarUser::init() is not called first
        //$result = $objectitem->getFieldValues();
        // @checkme bypass getValue() and get the raw values from the properties to allow deferred handling
        $item = $objectitem->getFieldValues(array(), 1);
        // @todo filter out fieldlist in dynamic_data datastore
        $diff = array_diff(array_keys($item), $fieldlist);
        foreach ($diff as $key) {
            unset($item[$key]);
        }
        foreach ($fieldlist as $key) {
            if (!empty($objectitem->properties[$key]) && method_exists($objectitem->properties[$key], 'getDeferredData')) {
                // @checkme take value and itemid directly from the property here, to set deferred data if needed
                $data = $objectitem->properties[$key]->getDeferredData();
                $item[$key] = $data['value'];
            }
        }
        //$item['_links'] = array('self' => array('href' => self::getURL($object, $itemid)));
        //return array('method' => 'getObjectItem', 'args' => $args, 'schema' => $schema, 'fieldlist' => $fieldlist, 'result' => $item);
        return $item;
    }

    public static function createObjectItem($args)
    {
        $object = $args['object'];
        $schema = 'create-' . $object;
        if (!self::hasSchema($schema)) {
            return array('method' => 'createObjectItem', 'args' => $args, 'schema' => $schema, 'error' => 'Unknown schema');
        }
        $properties = self::getCreateSchemaProperties($schema);
        // @todo sanity check on input based on properties
        if (empty($args['input'])) {
            throw new Exception('Unknown input ' . $object);
        }
        if (!empty($args['input']['id'])) {
            unset($args['input']['id']);
        }
        $params = array('name' => $object);
        $objectitem = DataObjectMaster::getObject($params);
        $itemid = $objectitem->createItem($args['input']);
        if (empty($itemid)) {
            throw new Exception('Unknown item ' . $object);
        }
        return array('method' => 'createObjectItem', 'args' => $args, 'schema' => $schema, 'properties' => $properties, 'result' => $itemid);
    }

    public static function updateObjectItem($args)
    {
        $object = $args['object'];
        $itemid = $args['itemid'];
        $schema = 'update-' . $object;
        if (!self::hasSchema($schema)) {
            return array('method' => 'updateObjectItem', 'args' => $args, 'schema' => $schema, 'error' => 'Unknown schema');
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        $properties = self::getUpdateSchemaProperties($schema);
        // @todo sanity check on input based on properties
        if (empty($args['input'])) {
            throw new Exception('Unknown input ' . $object);
        }
        if (!empty($args['input']['id']) && $itemid != $args['input']['id']) {
            throw new Exception('Unknown id ' . $object);
        }
        $params = array('name' => $object, 'itemid' => $itemid);
        $objectitem = DataObjectMaster::getObject($params);
        $itemid = $objectitem->updateItem($args['input']);
        if ($itemid != $params['itemid']) {
            throw new Exception('Unknown item ' . $object);
        }
        return array('method' => 'updateObjectItem', 'args' => $args, 'schema' => $schema, 'properties' => $properties, 'result' => $itemid);
    }

    public static function deleteObjectItem($args)
    {
        $object = $args['object'];
        $itemid = $args['itemid'];
        $schema = 'update-' . $object;
        if (!self::hasSchema($schema)) {
            return array('method' => 'deleteObjectItem', 'args' => $args, 'schema' => $schema, 'error' => 'Unknown schema');
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        $params = array('name' => $object, 'itemid' => $itemid);
        $objectitem = DataObjectMaster::getObject($params);
        $itemid = $objectitem->deleteItem();
        if ($itemid != $params['itemid']) {
            throw new Exception('Unknown item ' . $object);
        }
        return array('method' => 'deleteObjectItem', 'args' => $args, 'schema' => $schema, 'result' => $itemid);
    }

    public static function loadSchemas()
    {
        if (empty(self::$schemas)) {
            $doc = self::getOpenAPI();
            if (empty($doc['components']) || empty($doc['components']['schemas'])) {
                return $doc;
            }
            self::$schemas = $doc['components']['schemas'];
        }
    }

    public static function hasSchema($schema)
    {
        self::loadSchemas();
        if (empty(self::$schemas[$schema])) {
            return false;
        }
        return true;
    }

    public static function getViewSchemaProperties($schema)
    {
        // schema (object) -> properties -> items (array) -> items (object) -> properties
        return self::$schemas[$schema]['properties']['items']['items']['properties'];
    }

    public static function getDisplaySchemaProperties($schema)
    {
        // schema (object) -> properties
        return self::$schemas[$schema]['properties'];
    }

    public static function getCreateSchemaProperties($schema)
    {
        // schema (object) -> properties
        return self::$schemas[$schema]['properties'];
    }

    public static function getUpdateSchemaProperties($schema)
    {
        // schema (object) -> properties
        return self::$schemas[$schema]['properties'];
    }

    /**
     * Register REST API routes (in FastRoute format)
     */
    public static function registerRoutes($r)
    {
        $r->get('/objects', ['DataObjectRESTHandler', 'getObjects']);
        $r->get('/objects/{object}', ['DataObjectRESTHandler', 'getObjectList']);
        $r->get('/objects/{object}/{itemid}', ['DataObjectRESTHandler', 'getObjectItem']);
        $r->post('/objects/{object}', ['DataObjectRESTHandler', 'createObjectItem']);
        $r->put('/objects/{object}/{itemid}', ['DataObjectRESTHandler', 'updateObjectItem']);
        $r->delete('/objects/{object}/{itemid}', ['DataObjectRESTHandler', 'deleteObjectItem']);
        //$r->patch('/objects/{object}', ['DataObjectRESTHandler', 'patchObjectDefinition']);
    }

    /**
     * Send Content-Type and JSON result to the browser
     */
    public static function output($result, $status = 200)
    {
        //http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
