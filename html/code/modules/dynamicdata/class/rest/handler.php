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
sys::import('modules.dynamicdata.class.timertrait');
sys::import('xaraya.caching.cachetrait');

/**
 * Class to handle DataObject REST API calls
 */
class DataObjectRESTHandler extends xarObject
{
    use xarTimerTrait;  // activate with self::$enableTimer = true
    use xarCacheTrait;  // activate with self::$enableCache = true

    public static $endpoint = 'rst.php/v1';
    public static $objects = [];
    public static $schemas = [];
    public static $config = [];
    public static $modules = [];
    public static $tokenExpires = 12 * 60 * 60;  // 12 hours
    public static $storageType = 'apcu';  // database or apcu
    public static $tokenStorage;
    public static $userId;
    public static $mediaType;

    public static function getOpenAPI($args = null)
    {
        $openapi = sys::varpath() . '/cache/api/openapi.json';
        if (!file_exists($openapi)) {
            sys::import('modules.dynamicdata.class.rest.builder');
            DataObjectRESTBuilder::init();
            return ['TODO' => 'generate var/cache/api/openapi.json with builder'];
        }
        $content = file_get_contents($openapi);
        $doc = json_decode($content, true);
        return $doc;
    }

    public static function getBaseURL($base = '', $path = null, $args = [])
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
        self::loadObjects();
        $result = ['items' => [], 'count' => count(self::$objects)];
        foreach (self::$objects as $itemid => $item) {
            if ($item['datastore'] !== 'dynamicdata') {
                continue;
            }
            $item['_links'] = ['self' => ['href' => self::getObjectURL($item['name'])]];
            array_push($result['items'], $item);
        }
        $result['filter'] = ['datastore,eq,dynamicdata'];
        //return array('method' => 'getObjects', 'args' => $args, 'result' => $result);
        return $result;
    }

    public static function getObjectList($args)
    {
        $object = $args['path']['object'];
        $method = 'view';
        if (!self::hasOperation($object, $method)) {
            return ['method' => 'getObjectList', 'args' => $args, 'error' => 'Unknown operation'];
        }
        $args = $args['query'] ?? [];
        $userId = 0;
        if (self::hasSecurity($object, $method)) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = self::checkUser();
            //$args['access'] = 'view';
        }
        if (self::$enableCache && !self::hasCaching($object, $method)) {
            self::$enableCache = false;
        }
        // @checkme always count here
        $args['count'] = true;
        if (empty($args['limit']) || !is_numeric($args['limit'])) {
            $args['limit'] = 100;
        }
        $fieldlist = self::getViewProperties($object, $args);
        $loader = new DataObjectLoader($object, $fieldlist);
        $loader->parseQueryArgs($args);
        $objectlist = $loader->getObjectList();
        if (self::hasSecurity($object, $method) && !$objectlist->checkAccess('view', 0, $userId)) {
            http_response_code(403);
            exit;
        }
        $params = $loader->addPagingParams();
        $items = $objectlist->getItems($params);
        //$items = $loader->query($args);
        $result = [
            'items' => [],
            'count' => $loader->count,
            'limit' => $loader->limit,
            'offset' => $loader->offset,
            'order' => $loader->order,
            'filter' => $loader->filter,
        ];
        $deferred = [];
        foreach ($fieldlist as $key) {
            if (!empty($objectlist->properties[$key]) && method_exists($objectlist->properties[$key], 'getDeferredData')) {
                array_push($deferred, $key);
                // @checkme we need to set the item values for relational objects here
                // foreach ($items as $itemid => $item) {
                //     $objectlist->properties[$key]->setItemValue($itemid, $item[$key] ?? null);
                // }
            }
        }
        $allowed = array_flip($fieldlist);
        foreach ($items as $itemid => $item) {
            // @todo filter out fieldlist in dynamic_data datastore
            $item = array_intersect_key($item, $allowed);
            foreach ($deferred as $key) {
                $data = $objectlist->properties[$key]->getDeferredData(['value' => $item[$key] ?? null, '_itemid' => $itemid]);
                if ($data['value'] && in_array(get_class($objectlist->properties[$key]), ['DeferredListProperty', 'DeferredManyProperty']) && is_array($data['value'])) {
                    $item[$key] = array_values($data['value']);
                } else {
                    $item[$key] = $data['value'];
                }
            }
            $item['_links'] = ['self' => ['href' => self::getObjectURL($object, $itemid)]];
            array_push($result['items'], $item);
        }
        //return array('method' => 'getObjectList', 'args' => $args, 'fieldlist' => $fieldlist, 'result' => $result);
        return $result;
    }

    public static function getObjectItem($args)
    {
        $object = $args['path']['object'];
        $itemid = intval($args['path']['itemid']);
        $method = 'display';
        if (!self::hasOperation($object, $method)) {
            return ['method' => 'getObjectItem', 'args' => $args, 'error' => 'Unknown operation'];
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        $args = $args['query'] ?? [];
        $userId = 0;
        if (self::hasSecurity($object, $method)) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = self::checkUser();
            //$args['access'] = 'display';
        }
        if (self::$enableCache && !self::hasCaching($object, $method)) {
            self::$enableCache = false;
        }
        $fieldlist = self::getDisplayProperties($object, $args);
        $params = ['name' => $object, 'itemid' => $itemid, 'fieldlist' => $fieldlist];
        $objectitem = DataObjectMaster::getObject($params);
        if (self::hasSecurity($object, $method) && !$objectitem->checkAccess('display', $itemid, $userId)) {
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
        $item = $objectitem->getFieldValues([], 1);
        $allowed = array_flip($fieldlist);
        // @todo filter out fieldlist in dynamic_data datastore
        $item = array_intersect_key($item, $allowed);
        foreach ($fieldlist as $key) {
            if (!empty($objectitem->properties[$key]) && method_exists($objectitem->properties[$key], 'getDeferredData')) {
                // @checkme take value and itemid directly from the property here, to set deferred data if needed
                $data = $objectitem->properties[$key]->getDeferredData();
                if ($data['value'] && in_array(get_class($objectitem->properties[$key]), ['DeferredListProperty', 'DeferredManyProperty'])) {
                    $item[$key] = array_values($data['value']);
                } else {
                    $item[$key] = $data['value'];
                }
            }
        }
        //$item['_links'] = array('self' => array('href' => self::getObjectURL($object, $itemid)));
        //return array('method' => 'getObjectItem', 'args' => $args, 'fieldlist' => $fieldlist, 'result' => $item);
        return $item;
    }

    public static function createObjectItem($args)
    {
        $object = $args['path']['object'];
        $method = 'create';
        if (!self::hasOperation($object, $method)) {
            return ['method' => 'createObjectItem', 'args' => $args, 'error' => 'Unknown operation'];
        }
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        $userId = self::checkUser();
        $fieldlist = self::getCreateProperties($object);
        // @todo sanity check on input based on properties
        if (empty($args['input'])) {
            throw new Exception('Unknown input ' . $object);
        }
        if (!empty($args['input']['id'])) {
            unset($args['input']['id']);
        }
        $params = ['name' => $object];
        $objectitem = DataObjectMaster::getObject($params);
        if (!$objectitem->checkAccess('create', 0, $userId)) {
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
        $object = $args['path']['object'];
        $itemid = intval($args['path']['itemid']);
        $method = 'update';
        if (!self::hasOperation($object, $method)) {
            return ['method' => 'updateObjectItem', 'args' => $args, 'error' => 'Unknown operation'];
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        $userId = self::checkUser();
        $fieldlist = self::getUpdateProperties($object);
        // @todo sanity check on input based on properties
        if (empty($args['input'])) {
            throw new Exception('Unknown input ' . $object);
        }
        if (!empty($args['input']['id']) && $itemid != $args['input']['id']) {
            throw new Exception('Unknown id ' . $object);
        }
        $params = ['name' => $object, 'itemid' => $itemid];
        $objectitem = DataObjectMaster::getObject($params);
        if (!$objectitem->checkAccess('update', $itemid, $userId)) {
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
        $object = $args['path']['object'];
        $itemid = intval($args['path']['itemid']);
        $method = 'delete';
        if (!self::hasOperation($object, $method)) {
            return ['method' => 'deleteObjectItem', 'args' => $args, 'error' => 'Unknown operation'];
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        $userId = self::checkUser();
        $params = ['name' => $object, 'itemid' => $itemid];
        $objectitem = DataObjectMaster::getObject($params);
        if (!$objectitem->checkAccess('delete', $itemid, $userId)) {
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
        if (!empty(self::$config)) {
            return;
        }
        self::$config = [];
        $configFile = sys::varpath() . '/cache/api/restapi_config.json';
        if (file_exists($configFile)) {
            $contents = file_get_contents($configFile);
            self::$config = json_decode($contents, true);
        }
        if (!empty(self::$config['storage'])) {
            self::$storageType = self::$config['storage'];
        }
        if (!empty(self::$config['expires'])) {
            self::$tokenExpires = intval(self::$config['expires']);
        }
        // use xarTimerTrait
        if (isset(self::$config['timer'])) {
            self::$enableTimer = !empty(self::$config['timer']) ? true : false;
        }
        // use xarCacheTrait
        if (isset(self::$config['cache'])) {
            self::$enableCache = !empty(self::$config['cache']) ? true : false;
        }
        if (self::$enableCache) {
            $cacheScope = 'RestAPI.Operation';
            self::setCacheScope($cacheScope);
        }
        self::setTimer('config');
        // @deprecated for existing _config files before rebuild
        if (!empty(self::$config['objects'])) {
            self::loadObjects(self::$config);
        }
        if (!empty(self::$config['modules'])) {
            self::loadModules(self::$config);
        }
    }

    public static function loadObjects($config = [])
    {
        if (!empty(self::$objects)) {
            return;
        }
        $configFile = sys::varpath() . '/cache/api/restapi_objects.json';
        if (empty($config) && file_exists($configFile)) {
            $contents = file_get_contents($configFile);
            $config = json_decode($contents, true);
        }
        $fieldlist = ['objectid', 'name', 'label', 'module_id', 'itemtype', 'datastore', 'properties'];
        $allowed = array_flip($fieldlist);
        if (!empty($config['objects'])) {
            self::$config['objects'] = $config['objects'];
            self::$objects = [];
            foreach (self::$config['objects'] as $name => $item) {
                $item = array_intersect_key($item, $allowed);
                self::$objects[$name] = $item;
            }
        } else {
            $object = 'objects';
            $params = ['name' => $object, 'fieldlist' => $fieldlist];
            $objectlist = DataObjectMaster::getObjectList($params);
            self::$objects = $objectlist->getItems();
            self::$config['objects'] = [];
            foreach (self::$objects as $itemid => $item) {
                if ($item['datastore'] !== 'dynamicdata') {
                    continue;
                }
                $item = array_intersect_key($item, $allowed);
                self::$config['objects'][$item['name']] = $item;
            }
        }
        self::setTimer('objects');
    }

    public static function loadModules($config = [])
    {
        if (!empty(self::$modules)) {
            return;
        }
        $configFile = sys::varpath() . '/cache/api/restapi_modules.json';
        if (empty($config) && file_exists($configFile)) {
            $contents = file_get_contents($configFile);
            $config = json_decode($contents, true);
        }
        if (!empty($config['modules'])) {
            self::$config['modules'] = $config['modules'];
            self::$modules = self::$config['modules'];
        } else {
            $modulelist = ['dynamicdata'];
            self::$modules = [];
            xarMod::init();
            foreach ($modulelist as $module) {
                self::$modules[$module] = [
                    'module' => $module,
                    'apilist' => xarMod::apiFunc($module, 'rest', 'getlist'),
                ];
            }
            self::$config['modules'] = self::$modules;
        }
        self::setTimer('modules');
    }

    public static function hasObject($object)
    {
        self::loadObjects();
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
        }
    }

    public static function hasSecurity($object, $method)
    {
        $operation = self::getOperation($object, $method);
        return !empty($operation['security']) ? true : false;
    }

    public static function hasCaching($object, $method)
    {
        $operation = self::getOperation($object, $method);
        return !empty($operation['caching']) ? true : false;
    }

    public static function getProperties($object, $method)
    {
        $operation = self::getOperation($object, $method);
        return $operation['properties'];
    }

    public static function getViewProperties($object, $args = null)
    {
        // schema (object) -> properties -> items (array) -> items (object) -> properties
        //return self::$schemas[$schema]['properties']['items']['items']['properties'];
        $properties = self::getProperties($object, 'view');
        return self::expandProperties($object, $properties, $args);
    }

    public static function getDisplayProperties($object, $args = null)
    {
        // schema (object) -> properties
        //return self::$schemas[$schema]['properties'];
        $properties = self::getProperties($object, 'display');
        return self::expandProperties($object, $properties, $args);
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

    public static function expandProperties($object, $fieldlist, $args = null)
    {
        if (empty($args) || empty($args['expand'])) {
            return $fieldlist;
        }
        $expand = $args['expand'];
        if (!is_array($expand)) {
            // Clean up arrays by removing false values (= empty, false, null, 0)
            $expand = array_filter(explode(',', $expand));
        }
        $allowed = array_keys(self::$config['objects'][$object]['properties']);
        foreach ($expand as $key) {
            // @todo support multi-level expand fields e.g. objects/api_people?expand=vehicles.manufacturer
            $field = explode('.', $key)[0];
            if (!in_array($field, $fieldlist) && in_array($field, $allowed)) {
                $fieldlist[] = $field;
            }
        }
        return $fieldlist;
    }

    /**
     * Return the current user or exit with 401 status code
     */
    public static function whoami($args = null)
    {
        $userId = self::checkUser();
        //return array('id' => xarUser::getVar('id'), 'name' => xarUser::getVar('name'));
        $role = xarRoles::getRole($userId);
        $user = $role->getFieldValues();
        return ['id' => $user['id'], 'name' => $user['name']];
    }

    /**
     * Verify that the cookie corresponds to an authorized user (with minimal core load) or exit with 401 status code
     */
    private static function checkUser()
    {
        $userInfo = self::checkToken();
        if (!empty($userInfo)) {
            $userInfo = @json_decode($userInfo, true);
            if (empty($userInfo['userId']) || empty($userInfo['created']) || ($userInfo['created'] < (time() - self::$tokenExpires))) {
                http_response_code(401);
                //header('WWW-Authenticate: Bearer realm="Xaraya Site Login"');
                header('WWW-Authenticate: Token realm="Xaraya Site Login", created=');
                exit;
            }
            return $userInfo['userId'];
        }
        $userId = self::checkCookie();
        if (empty($userId)) {
            http_response_code(401);
            header('WWW-Authenticate: Cookie realm="Xaraya Site Login", cookie-name=XARAYASID');
            exit;
        }
        return $userId;
    }

    /**
     * Verify that the cookie corresponds to an authorized user (with minimal core load) using xarSession
     */
    private static function checkCookie()
    {
        $cookie = !empty($_COOKIE['XARAYASID']) ? $_COOKIE['XARAYASID'] : '';
        if (empty($cookie)) {
            return;
        }
        // @checkme see graphql whoami query in dummytype.php
        xarSession::init();
        //xarUser::init();
        if (!xarUser::isLoggedIn()) {
            return;
        }
        return xarSession::getVar('role_id');
    }

    /**
     * Verify that the token corresponds to an authorized user (with minimal core load) using xarCache_Storage
     */
    private static function checkToken()
    {
        $token = !empty($_SERVER['HTTP_X_AUTH_TOKEN']) ? $_SERVER['HTTP_X_AUTH_TOKEN'] : '';
        if (empty($token) || !(self::getTokenStorage()->isCached($token))) {
            return;
        }
        return self::getTokenStorage()->getCached($token);
    }

    public static function postToken($args)
    {
        // this contains any POSTed args from rst.php
        if (empty($args['input'])) {
            $args['input'] = [];
        }
        $uname = $args['input']['uname'];
        $pass = $args['input']['pass'];
        if (empty($uname) || empty($pass)) {
            http_response_code(401);
            //header('WWW-Authenticate: Bearer realm="Xaraya Site Login", access=');
            header('WWW-Authenticate: Token realm="Xaraya Site Login", uname=, pass=');
            exit;
        }
        $access = $args['input']['access'];
        if (empty($access) || !in_array($access, ['display', 'update', 'create', 'delete', 'admin'])) {
            http_response_code(401);
            //header('WWW-Authenticate: Bearer realm="Xaraya Site Login", access=');
            header('WWW-Authenticate: Token realm="Xaraya Site Login", access=');
            exit;
        }
        //xarSession::init();
        xarMod::init();
        xarUser::init();
        // @checkme unset xarSession role_id if needed, otherwise xarUser::logIn will hit xarUser::isLoggedIn first!?
        // @checkme or call authsystem directly if we don't want/need to support any other authentication modules
        $userId = xarMod::apiFunc('authsystem', 'user', 'authenticate_user', $args['input']);
        if (empty($userId) || $userId == xarUser::AUTH_FAILED) {
            http_response_code(401);
            //header('WWW-Authenticate: Bearer realm="Xaraya Site Login"');
            header('WWW-Authenticate: Token realm="Xaraya Site Login"');
            exit;
        }
        if (function_exists('random_bytes')) {
            $token = bin2hex(random_bytes(32));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $token = bin2hex(openssl_random_pseudo_bytes(32));
        } else {
            return ['method' => 'postToken', 'error' => 'no decent token generator'];
        }
        // @checkme clean up cachestorage occasionally based on size
        self::getTokenStorage()->sizeLimitReached();
        self::getTokenStorage()->setCached($token, json_encode(['userId' => $userId, 'access' => $access, 'created' => time()]));
        $expiration = date('c', time() + self::$tokenExpires);
        return ['access_token' => $token, 'expiration' => $expiration];
    }

    public static function deleteToken($args = null)
    {
        $userId = self::checkUser();
        $token = !empty($_SERVER['HTTP_X_AUTH_TOKEN']) ? $_SERVER['HTTP_X_AUTH_TOKEN'] : '';
        if (empty($token) || !(self::getTokenStorage()->isCached($token))) {
            return false;
        }
        self::getTokenStorage()->delCached($token);
        return true;
    }

    public static function getTokenStorage()
    {
        if (!isset(self::$tokenStorage)) {
            self::loadConfig();
            // @checkme access cachestorage directly here
            self::$tokenStorage = xarCache::getStorage([
                'storage' => self::$storageType,
                'type' => 'token',
                'expire' => self::$tokenExpires,
                'sizelimit' => 2000000,
            ]);
        }
        return self::$tokenStorage;
    }

    public static function getModuleURL($module = null, $api = null, $args = [])
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
        self::loadModules();
        $result = ['items' => [], 'count' => count(self::$modules)];
        foreach (self::$modules as $itemid => $item) {
            $item['apilist'] = array_keys($item['apilist']);
            $item['_links'] = ['self' => ['href' => self::getModuleURL($item['module'])]];
            array_push($result['items'], $item);
        }
        return $result;
    }

    public static function getModuleApis($args)
    {
        $module = $args['path']['module'];
        if (!self::hasModule($module)) {
            return ['method' => 'getModuleApis', 'args' => $args, 'error' => 'Unknown module'];
        }
        $result = ['module' => $module, 'apilist' => [], 'count' => 0];
        $apilist = self::getModuleApiList($module);
        foreach ($apilist as $api => $item) {
            if (isset($item['enabled']) && empty($item['enabled'])) {
                continue;
            }
            $item['name'] = $api;
            $item['path'] = self::getModuleURL($module, $item['path']);
            $result['apilist'][] = $item;
        }
        $result['count'] = count($result['apilist']);
        return $result;
    }

    public static function getModuleCall($args)
    {
        $module = $args['path']['module'];
        $path = $args['path']['path'];
        // @checkme support optional part(s) after path, either with {path}[/{more}] or with {path:.+}
        $more = $args['path']['more'] ?? '';
        $func = self::getModuleApiFunc($module, $path, 'get', $more);
        if (empty($func)) {
            return ['method' => 'getModuleCall', 'args' => $args, 'error' => 'Unknown module api'];
        }
        $args = $args['query'] ?? [];
        if (!empty($func['security'])) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = self::checkUser();
            // @checkme assume we have a security mask here
            if (is_string($func['security'])) {
                $role = xarRoles::getRole($userId);
                $rolename = $role->getName();
                $pass = xarSecurity::check($func['security'], 0, 'All', 'All', $func['module'], $rolename);
            // @todo verify access for user based on what?
            } else {
                $pass = true;
            }
            if (!$pass) {
                http_response_code(403);
                exit;
            }
        }
        if (self::$enableCache && empty($func['caching'])) {
            self::$enableCache = false;
        }
        // @checkme how to save this in case of caching?
        if (!empty($func['mediatype'])) {
            self::$mediaType = $func['mediatype'];
        }
        xarMod::init();
        xarUser::init();
        // @checkme pass all query args from handler here?
        if (!empty($func['args'])) {
            if (!empty($more)) {
                // @checkme path params overwrite query params - but what about default args?
                $args = array_merge($args, $func['args']);
            } else {
                // @checkme query params overwrite default args
                $args = array_merge($func['args'], $args);
            }
        }
        return xarMod::apiFunc($func['module'], $func['type'], $func['name'], $args);
    }

    public static function postModuleCall($args)
    {
        $module = $args['path']['module'];
        $path = $args['path']['path'];
        // @checkme support optional part(s) after path, either with {path}[/{more}] or with {path:.+}
        $more = $args['path']['more'] ?? '';
        $func = self::getModuleApiFunc($module, $path, 'post', $more);
        if (empty($func)) {
            return ['method' => 'postModuleCall', 'args' => $args, 'error' => 'Unknown module api'];
        }
        // this contains any POSTed args from rst.php
        if (empty($args['input'])) {
            $args['input'] = [];
        }
        if (!empty($func['security'])) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = self::checkUser();
            // @checkme assume we have a security mask here
            if (is_string($func['security'])) {
                $role = xarRoles::getRole($userId);
                $rolename = $role->getName();
                $pass = xarSecurity::check($func['security'], 0, 'All', 'All', $func['module'], $rolename);
            // @todo verify access for user based on what?
            } else {
                $pass = true;
            }
            if (!$pass) {
                http_response_code(403);
                exit;
            }
        }
        if (!empty($func['mediatype'])) {
            self::$mediaType = $func['mediatype'];
        }
        xarMod::init();
        xarUser::init();
        // @checkme handle POSTed args by passing $args['input'] only in handler?
        if (!empty($more) && !empty($func['args'])) {
            $args['input'] = array_merge($args['input'], $func['args']);
        }
        return xarMod::apiFunc($func['module'], $func['type'], $func['name'], $args['input']);
    }

    public static function putModuleCall($args)
    {
        $module = $args['path']['module'];
        $path = $args['path']['path'];
        // @checkme support optional part(s) after path, either with {path}[/{more}] or with {path:.+}
        $more = $args['path']['more'] ?? '';
        $func = self::getModuleApiFunc($module, $path, 'put', $more);
        if (empty($func)) {
            return ['method' => 'putModuleCall', 'args' => $args, 'error' => 'Unknown module api'];
        }
        throw new Exception('Unsupported method PUT for module api');
    }

    public static function deleteModuleCall($args)
    {
        $module = $args['path']['module'];
        $path = $args['path']['path'];
        // @checkme support optional part(s) after path, either with {path}[/{more}] or with {path:.+}
        $more = $args['path']['more'] ?? '';
        $func = self::getModuleApiFunc($module, $path, 'delete', $more);
        if (empty($func)) {
            return ['method' => 'deleteModuleCall', 'args' => $args, 'error' => 'Unknown module api'];
        }
        throw new Exception('Unsupported method DELETE for module api');
    }

    public static function hasModule($module)
    {
        self::loadModules();
        if (empty(self::$config) || empty(self::$config['modules']) || empty(self::$config['modules'][$module])) {
            return false;
        }
        return true;
    }

    public static function getModuleApiList($module)
    {
        if (!self::hasModule($module)) {
            return;
        }
        return self::$modules[$module]['apilist'];
    }

    public static function getModuleApiFunc($module, $path, $method = 'get', $more = null)
    {
        if (!self::hasModule($module)) {
            return;
        }
        $apilist = self::getModuleApiList($module);
        if (!empty($more)) {
            // @checkme sort by decreasing path length
            uasort($apilist, function ($a, $b) {
                $lena = strlen($a['path']);
                $lenb = strlen($b['path']);
                if ($lena == $lenb) {
                    return 0;
                }
                return ($lena < $lenb) ? 1 : -1;
            });
        }
        foreach ($apilist as $api => $item) {
            if (isset($item['enabled']) && empty($item['enabled'])) {
                continue;
            }
            if (empty($more) && $item['path'] == $path && $item['method'] == $method) {
                $item['module'] ??= $module;
                $item['type'] ??= 'rest';
                $item['name'] ??= $api;
                // @checkme allow default args to start with
                $item['args'] ??= [];
                $item['caching'] ??= ($method == 'get') ? true : false;
                return $item;
            }
            // @checkme support optional part(s) after path, either with {path}[/{more}] or with {path:.+}
            if (!empty($more) && strncmp($item['path'], $path . '/', strlen($path) + 1) === 0 && $item['method'] == $method) {
                // @checkme assuming only more path parameter(s) in module paths for now... {type}/{key}/{code}
                $more_params = explode('/', substr($item['path'], strlen($path) + 1));
                $more_values = explode('/', $more);
                if (count($more_values) != count($more_params)) {
                    continue;
                }
                $item['module'] ??= $module;
                $item['type'] ??= 'rest';
                $item['name'] ??= $api;
                // @checkme allow default args to start with
                $item['args'] ??= [];
                $item['caching'] ??= ($method == 'get') ? true : false;
                $i = 0;
                foreach ($more_params as $path_param) {
                    if (empty($path_param)) {
                        continue;
                    }
                    if (substr($path_param, 0, 1) !== '{' && substr($path_param, -1) !== '}') {
                        // @checkme how do we keep track of fixed parts of the path here?
                        continue;
                    }
                    if (substr($path_param, 0, 1) !== '{' || substr($path_param, -1) !== '}') {
                        throw new Exception('Invalid path parameter in ' . $item['path']);
                    }
                    $path_param = substr($path_param, 1, -1);
                    // @checkme path params overwrite default args
                    $item['args'][$path_param] = $more_values[$i];
                    $i += 1;
                }
                return $item;
            }
        }
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
        $r->post('/token', ['DataObjectRESTHandler', 'postToken']);
        $r->delete('/token', ['DataObjectRESTHandler', 'deleteToken']);
        $r->get('/modules', ['DataObjectRESTHandler', 'getModules']);
        $r->get('/modules/{module}', ['DataObjectRESTHandler', 'getModuleApis']);
        // @checkme support optional part(s) after path, either with {path}[/{more}] or with {path:.+}
        $r->get('/modules/{module}/{path}[/{more:.+}]', ['DataObjectRESTHandler', 'getModuleCall']);
        $r->post('/modules/{module}/{path}[/{more:.+}]', ['DataObjectRESTHandler', 'postModuleCall']);
        $r->put('/modules/{module}/{path}[/{more:.+}]', ['DataObjectRESTHandler', 'putModuleCall']);
        $r->delete('/modules/{module}/{path}[/{more:.+}]', ['DataObjectRESTHandler', 'deleteModuleCall']);
    }

    public static function getQueryId($method, $vars)
    {
        $queryId = $method;
        if (!empty($vars['path'])) {
            if (!empty($vars['path']['object'])) {
                $queryId .= '-' . $vars['path']['object'];
                if (!empty($vars['path']['itemid'])) {
                    $queryId .= '-' . $vars['path']['itemid'];
                }
            }
            if (!empty($vars['path']['module'])) {
                $queryId .= '-' . $vars['path']['module'];
                if (!empty($vars['path']['path'])) {
                    $queryId .= '-' . $vars['path']['path'];
                }
            }
        }
        $queryId .= '-' . md5(json_encode($vars));
        return $queryId;
    }

    /**
     * Handle request and get result
     */
    public static function getResult($handler, $vars)
    {
        // initialize caching - delay until we need results
        xarCache::init();
        self::loadConfig();
        $tryCachedResult = false;
        if (is_array($handler) && $handler[0] === "DataObjectRESTHandler" && substr($handler[1], 0, 3) === "get") {
            $tryCachedResult = true;
        }
        if ($tryCachedResult && self::$enableCache) {
            $queryId = self::getQueryId($handler[1], $vars);
            $cacheKey = self::getCacheKey($queryId);
            // @checkme we need to initialize the database here too if variable caching uses database instead of apcu
            if (!empty($cacheKey) && self::isCached($cacheKey)) {
                $result = self::getCached($cacheKey);
                if (is_array($result)) {
                    // $result['x-cached'] = true;
                    $result['x-cached'] = self::keyCached($cacheKey);
                } else {
                    $keyInfo = self::keyCached($cacheKey);
                    if (!empty($keyInfo) && is_array($keyInfo)) {
                        header('X-Cache-Key: ' . $keyInfo['key']);
                        header('X-Cache-Code: ' . $keyInfo['code']);
                        header('X-Cache-Time: ' . $keyInfo['time']);
                        if (isset($keyInf['hits'])) {
                            header('X-Cache-Hits: ' . $keyInfo['hits']);
                        }
                    }
                    // header('X-Cache-Hit: true');
                }
                self::setTimer('cached');
                return $result;
            }
        }
        // initialize database - delay until caching fails
        xarDatabase::init();
        // initialize modules
        //xarMod::init();
        // initialize users
        //xarUser::init();
        self::setTimer('handle');
        $result = call_user_func($handler, $vars);
        // if (is_array($result)) {
        //     $result['x-debug'] = ['handler' => $handler, 'vars' => $vars];
        // }
        if ($tryCachedResult && self::$enableCache && self::hasCacheKey()) {
            $cacheKey = self::getCacheKey();
            self::setCached($cacheKey, $result);
        }
        self::setTimer('result');
        return $result;
    }

    /**
     * Send Content-Type and JSON result to the browser
     */
    public static function output($result, $status = 200)
    {
        if (is_array($result) && self::$enableTimer) {
            $result['x-times'] = self::getTimers();
        }
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Origin: *');
        }
        //http_response_code($status);
        if (is_string($result)) {
            if (!empty(self::$mediaType)) {
                header('Content-Type: ' . self::$mediaType . '; charset=utf-8');
            } elseif (substr($result, 0, 5) === '<?xml') {
                header('Content-Type: application/xml; charset=utf-8');
            } else {
                header('Content-Type: text/html; charset=utf-8');
            }
            echo $result;
            return;
        }
        header('Content-Type: application/json; charset=utf-8');
        //echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PARTIAL_OUTPUT_ON_ERROR);
        try {
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo '{"JSON Exception": ' . json_encode($e->getMessage()) . '}';
        }
    }

    /**
     * Send CORS options to the browser in preflight checks
     */
    public static function sendCORSOptions()
    {
        // See https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
        http_response_code(204);
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: X-Auth-Token, Content-Type');
        // header('Access-Control-Allow-Credentials: true');
        exit(0);
    }
}
