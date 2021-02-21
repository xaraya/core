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
    //public static $security = array();
    public static $config = array();
    public static $modules = array();

    public static function getOpenAPI($args = null)
    {
        $openapi = sys::varpath() . '/cache/api/openapi.json';
        if (!file_exists($openapi)) {
            sys::import('modules.dynamicdata.class.rest.builder');
            DataObjectRESTBuilder::init();
            return array('TODO' => 'generate var/cache/api/openapi.json with builder');
        }
        $content = file_get_contents($openapi);
        $doc = json_decode($content, true);
        return $doc;
    }

    public static function getBaseURL($base = '', $path = null, $args = array())
    {
        if (empty($path)) {
            return xarServer::getBaseURL() . self::$endpoint . $base;
        }
        return xarServer::getBaseURL() . self::$endpoint . $base . '/' . $path;
    }

    public static function getObjectURL($object = null, $itemid = null)
    {
        if (empty($object)) {
            return self::getBaseURL('/objects');
        }
        if (empty($itemid)) {
            return self::getBaseURL('/objects', $object);
        }
        return self::getBaseURL('/objects', $object . '/' . $itemid);
    }

    public static function getObjects($args)
    {
        if (empty(self::$objects)) {
            self::loadConfig();
        }
        $result = array('items' => array(), 'count' => count(self::$objects));
        foreach (self::$objects as $itemid => $item) {
            if ($item['datastore'] !== 'dynamicdata') {
                continue;
            }
            $item['_links'] = array('self' => array('href' => self::getObjectURL($item['name'])));
            array_push($result['items'], $item);
        }
        $result['filter'] = 'datastore,eq,dynamicdata';
        //return array('method' => 'getObjects', 'args' => $args, 'result' => $result);
        return $result;
    }

    public static function getObjectList($args)
    {
        $object = $args['object'];
        $method = 'view';
        if (!self::hasOperation($object, $method)) {
            return array('method' => 'getObjectList', 'args' => $args, 'error' => 'Unknown operation');
        }
        if (self::hasSecurity($object, $method)) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            self::checkuser();
        }
        $fieldlist = self::getViewProperties($object);
        $limit = 100;
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
            if (!is_array($filter)) {
                $filter = array($filter);
            }
            // Clean up arrays by removing false values (= empty, false, null, 0)
            $filter = array_filter($filter);
        }
        $params = array('name' => $object, 'fieldlist' => $fieldlist);
        $objectlist = DataObjectMaster::getObjectList($params);
        if (self::hasSecurity($object, $method) && !$objectlist->checkAccess('view')) {
            http_response_code(403);
            exit;
        }
        // @todo fix setWhere() and/or dataquery to support other datastores than relational ones
        // See code/modules/dynamicdata/class/ui_handlers/search.php
        $wherestring = '';
        if (!empty($filter)) {
            $join = '';
            $mapop = array('eq' => '=', 'ne' => '!=', 'gt' => '>', 'lt' => '<', 'le' => '>=', 'ge' => '<=', 'in' => 'IN');
            foreach ($filter as $where) {
                list($field, $op, $value) = explode(',', $where . ',,');
                if (empty($field) || empty($objectlist->properties[$field]) || empty($op) || empty($mapop[$op])) {
                    continue;
                }
                $clause = '';
                if (is_numeric($value)) {
                    $clause = $mapop[$op] . " " . $value;
                } elseif (is_string($value)) {
                    if ($op !== 'in') {
                        $value = str_replace("'", "\\'", $value);
                        $clause = $mapop[$op] . " '" . $value . "'";
                    } else {
                        // keep only the third variable with the rest of the string, e.g. itemid,in,3,7,11
                        list(, , $value) = explode(',', $where, 3);
                        $value = str_replace("'", "\\'", $value);
                        $value = explode(',', $value);
                        if (count($value) > 0) {
                            if (is_numeric($value[0])) {
                                $clause = $mapop[$op] . " (" . implode(", ", $value) . ")";
                            } elseif (is_string($value[0])) {
                                $clause = $mapop[$op] . " ('" . implode("', '", $value) . "')";
                            }
                        }
                    }
                }
                if (!empty($clause)) {
                    $objectlist->addWhere($field, $clause, $join);
                    $wherestring .= $join . ' ' . $field . ' ' . trim($clause);
                    $join = 'AND';
                }
            }
        }
        if (!empty($wherestring) && is_object($objectlist->datastore) && get_class($objectlist->datastore) !== 'VariableTableDataStore') {
            $conditions = $objectlist->setWhere($wherestring);
            $objectlist->dataquery->addconditions($conditions);
        }
        $result = array('items' => array(), 'count' => $objectlist->countItems(), 'limit' => $limit, 'offset' => $offset, 'order' => $order);
        if (!empty($filter)) {
            $result['filter'] = $filter;
        }
        $params = array('numitems' => $limit);
        if (!empty($offset) && !empty($result['count'])) {
            if ($offset < $result['count']) {
                $params['startnum'] = $offset + 1;
            } else {
                throw new Exception('Invalid offset ' . $offset);
            }
        }
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
            $item['_links'] = array('self' => array('href' => self::getObjectURL($object, $itemid)));
            array_push($result['items'], $item);
        }
        //return array('method' => 'getObjectList', 'args' => $args, 'fieldlist' => $fieldlist, 'result' => $result);
        return $result;
    }

    public static function getObjectItem($args)
    {
        $object = $args['object'];
        $itemid = $args['itemid'];
        $method = 'display';
        if (!self::hasOperation($object, $method)) {
            return array('method' => 'getObjectItem', 'args' => $args, 'error' => 'Unknown operation');
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        if (self::hasSecurity($object, $method)) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            self::checkuser();
        }
        $fieldlist = self::getDisplayProperties($object);
        $params = array('name' => $object, 'itemid' => $itemid, 'fieldlist' => $fieldlist);
        $objectitem = DataObjectMaster::getObject($params);
        if (self::hasSecurity($object, $method) && !$objectitem->checkAccess('display')) {
            http_response_code(403);
            exit;
        }
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
        //$item['_links'] = array('self' => array('href' => self::getObjectURL($object, $itemid)));
        //return array('method' => 'getObjectItem', 'args' => $args, 'fieldlist' => $fieldlist, 'result' => $item);
        return $item;
    }

    public static function createObjectItem($args)
    {
        $object = $args['object'];
        $method = 'create';
        if (!self::hasOperation($object, $method)) {
            return array('method' => 'createObjectItem', 'args' => $args, 'error' => 'Unknown operation');
        }
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        //$user = self::whoami();
        self::checkuser();
        $fieldlist = self::getCreateProperties($object);
        // @todo sanity check on input based on properties
        if (empty($args['input'])) {
            throw new Exception('Unknown input ' . $object);
        }
        if (!empty($args['input']['id'])) {
            unset($args['input']['id']);
        }
        $params = array('name' => $object);
        $objectitem = DataObjectMaster::getObject($params);
        if (!$objectitem->checkAccess('create')) {
            http_response_code(403);
            exit;
        }
        $itemid = $objectitem->createItem($args['input']);
        if (empty($itemid)) {
            throw new Exception('Unknown item ' . $object);
        }
        //return array('method' => 'createObjectItem', 'args' => $args, 'properties' => $properties, 'user' => $user, 'result' => $itemid);
        return $itemid;
    }

    public static function updateObjectItem($args)
    {
        $object = $args['object'];
        $itemid = $args['itemid'];
        $method = 'update';
        if (!self::hasOperation($object, $method)) {
            return array('method' => 'updateObjectItem', 'args' => $args, 'error' => 'Unknown operation');
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        //$user = self::whoami();
        self::checkuser();
        $fieldlist = self::getUpdateProperties($object);
        // @todo sanity check on input based on properties
        if (empty($args['input'])) {
            throw new Exception('Unknown input ' . $object);
        }
        if (!empty($args['input']['id']) && $itemid != $args['input']['id']) {
            throw new Exception('Unknown id ' . $object);
        }
        $params = array('name' => $object, 'itemid' => $itemid);
        $objectitem = DataObjectMaster::getObject($params);
        if (!$objectitem->checkAccess('update')) {
            http_response_code(403);
            exit;
        }
        $itemid = $objectitem->updateItem($args['input']);
        if ($itemid != $params['itemid']) {
            throw new Exception('Unknown item ' . $object);
        }
        //return array('method' => 'updateObjectItem', 'args' => $args, 'properties' => $properties, 'user' => $user, 'result' => $itemid);
        return $itemid;
    }

    public static function deleteObjectItem($args)
    {
        $object = $args['object'];
        $itemid = $args['itemid'];
        $method = 'delete';
        if (!self::hasOperation($object, $method)) {
            return array('method' => 'deleteObjectItem', 'args' => $args, 'error' => 'Unknown operation');
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        //$user = self::whoami();
        self::checkuser();
        $params = array('name' => $object, 'itemid' => $itemid);
        $objectitem = DataObjectMaster::getObject($params);
        if (!$objectitem->checkAccess('delete')) {
            http_response_code(403);
            exit;
        }
        $itemid = $objectitem->deleteItem();
        if ($itemid != $params['itemid']) {
            throw new Exception('Unknown item ' . $object);
        }
        //return array('method' => 'deleteObjectItem', 'args' => $args, 'user' => $user, 'result' => $itemid);
        return $itemid;
    }

    public static function loadConfig()
    {
        if (empty(self::$config)) {
            self::$config = array();
            $configFile = sys::varpath() . '/cache/api/restapi_config.json';
            if (file_exists($configFile)) {
                $contents = file_get_contents($configFile);
                self::$config = json_decode($contents, true);
            }
            $fieldlist = array('objectid', 'name', 'label', 'module_id', 'itemtype', 'datastore');
            $allowed = array_flip($fieldlist);
            if (!empty(self::$config['objects'])) {
                self::$objects = array();
                foreach (self::$config['objects'] as $name => $item) {
                    $item = array_intersect_key($item, $allowed);
                    self::$objects[$name] = $item;
                }
            } else {
                $object = 'objects';
                $params = array('name' => $object, 'fieldlist' => $fieldlist);
                $objectlist = DataObjectMaster::getObjectList($params);
                self::$objects = $objectlist->getItems();
                self::$config['objects'] = array();
                foreach (self::$objects as $itemid => $item) {
                    $item = array_intersect_key($item, $allowed);
                    self::$config['objects'][$item['name']] = $item;
                }
            }
        }
    }

    public static function hasObject($object)
    {
        self::loadConfig();
        if (empty(self::$config) || empty(self::$config['objects']) || empty(self::$config['objects'][$object])) {
            return false;
        }
        return true;
    }

    public static function hasOperation($object, $method)
    {
        if (!self::hasObject($object)) {
            return false;
        }
        if (empty(self::$config['objects'][$object]['x-operations']) || empty(self::$config['objects'][$object]['x-operations'][$method])) {
            return false;
        }
        return true;
    }

    public static function getOperation($object, $method)
    {
        return self::$config['objects'][$object]['x-operations'][$method];
    }

    public static function loadSchemas()
    {
        if (empty(self::$schemas)) {
            $doc = self::getOpenAPI();
            if (empty($doc['components']) || empty($doc['components']['schemas'])) {
                return $doc;
            }
            self::$schemas = $doc['components']['schemas'];
            /**
            self::$security = array();
            self::$config = array();
            foreach ($doc['paths'] as $path => $operations) {
                foreach ($operations as $method => $operation) {
                    if (!empty($operation['security'])) {
                        self::$security[$operation['operationId']] = $operation['security'];
                    }
                    if (!empty($operation['x-xaraya-config'])) {
                        self::$config[$operation['operationId']] = $operation['x-xaraya-config'];
                    }
                }
            }
             */
        }
    }

    public static function hasSecurity($object, $method)
    {
        $operation = self::getOperation($object, $method);
        return !empty($operation['security']) ? true : false;
    }

    public static function getProperties($object, $method)
    {
        $operation = self::getOperation($object, $method);
        return $operation['properties'];
    }

    public static function getViewProperties($object)
    {
        // schema (object) -> properties -> items (array) -> items (object) -> properties
        //return self::$schemas[$schema]['properties']['items']['items']['properties'];
        return self::getProperties($object, 'view');
    }

    public static function getDisplayProperties($object)
    {
        // schema (object) -> properties
        //return self::$schemas[$schema]['properties'];
        return self::getProperties($object, 'display');
    }

    public static function getCreateProperties($object)
    {
        // schema (object) -> properties
        //return self::$schemas[$schema]['properties'];
        return self::getProperties($object, 'create');
    }

    public static function getUpdateProperties($object)
    {
        // schema (object) -> properties
        //return self::$schemas[$schema]['properties'];
        return self::getProperties($object, 'update');
    }

    /**
     * Return the current user or exit with 401 status code
     */
    public static function whoami($args = null)
    {
        self::checkuser();
        //return array('id' => xarUser::getVar('id'), 'name' => xarUser::getVar('name'));
        $role = xarRoles::current();
        $user = $role->getFieldValues();
        return array('id' => $user['id'], 'name' => $user['name']);
    }

    /**
     * Verify that the cookie corresponds to an authorized user (with minimal core load) or exit with 401 status code
     */
    private static function checkuser()
    {
        $cookie = !empty($_COOKIE['XARAYASID']) ? $_COOKIE['XARAYASID'] : '';
        if (empty($cookie)) {
            http_response_code(401);
            header('WWW-Authenticate: Cookie realm="Xaraya Site Login", cookie-name=XARAYASID');
            exit;
        }
        // @checkme see graphql whoami query in dummytype.php
        xarSession::init();
        //xarUser::init();
        if (!xarUser::isLoggedIn()) {
            http_response_code(401);
            header('WWW-Authenticate: Cookie realm="Xaraya Site Login", cookie-name=XARAYASID');
            exit;
        }
    }

    public static function getModuleURL($module = null, $api = null, $args = array())
    {
        if (empty($module)) {
            return self::getBaseURL('/modules');
        }
        if (empty($api)) {
            return self::getBaseURL('/modules', $module);
        }
        return self::getBaseURL('/modules', $module . '/' . $api);
    }

    public static function getModules($args)
    {
        if (empty(self::$modules)) {
            $modulelist = array('dynamicdata');
            self::$modules = array();
            xarMod::init();
            foreach ($modulelist as $module) {
                self::$modules[$module] = array(
                    'module' => $module,
                    'apilist' => xarMod::apiFunc($module, 'rest', 'getlist')
                );
            }
        }
        $result = array('items' => array(), 'count' => count(self::$modules));
        foreach (self::$modules as $itemid => $item) {
            $item['apilist'] = array_keys($item['apilist']);
            $item['_links'] = array('self' => array('href' => self::getModuleURL($item['module'])));
            array_push($result['items'], $item);
        }
        return $result;
    }

    public static function getModuleApis($args)
    {
        $module = $args['module'];
        $result = array('module' => $module, 'apilist' => array(), 'count' => 0);
        xarMod::init();
        // Get the list of REST API calls supported by this module (if any)
        $apilist = xarMod::apiFunc($module, 'rest', 'getlist');
        foreach ($apilist as $api => $item) {
            $item['path'] = self::getModuleURL($module, $item['path']);
            $result['apilist'][$api] = $item;
        }
        $result['count'] = count($result['apilist']);
        return $result;
    }

    public static function getModuleCall($args)
    {
        $module = $args['module'];
        $path = $args['path'];
        xarMod::init();
        // Find the REST API call corresponding to this path and method
        $apilist = xarMod::apiFunc($module, 'rest', 'getlist');
        foreach ($apilist as $api => $item) {
            if ($item['path'] == $path && $item['method'] == 'get') {
                return xarMod::apiFunc($module, 'rest', $api);
            }
        }
        $result = array('module' => $module, 'path' => $path, 'args' => $args, 'apilist' => $apilist);
        return $result;
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
        $r->get('/whoami', ['DataObjectRESTHandler', 'whoami']);
        $r->get('/modules', ['DataObjectRESTHandler', 'getModules']);
        $r->get('/modules/{module}', ['DataObjectRESTHandler', 'getModuleApis']);
        $r->get('/modules/{module}/{path}', ['DataObjectRESTHandler', 'getModuleCall']);
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
