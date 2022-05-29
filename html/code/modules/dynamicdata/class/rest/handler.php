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
    use xarTimerTrait;
    use xarCacheTrait;

    public static $endpoint = 'rst.php/v1';
    public static $objects = [];
    public static $schemas = [];
    public static $config = [];
    public static $modules = [];
    public static $tokenExpires = 12 * 60 * 60;  // 12 hours
    public static $storageType = 'database';  // database or apcu
    public static $tokenStorage;
    public static $userId;

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
        if (empty(self::$objects)) {
            self::loadConfig();
        }
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
        $object = $args['object'];
        $method = 'view';
        if (!self::hasOperation($object, $method)) {
            return ['method' => 'getObjectList', 'args' => $args, 'error' => 'Unknown operation'];
        }
        $userId = 0;
        if (self::hasSecurity($object, $method)) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = self::checkUser();
            //$args['access'] = 'view';
        }
        // @checkme always count here
        $args['count'] = true;
        if (empty($args['limit']) || !is_numeric($args['limit'])) {
            $args['limit'] = 100;
        }
        $fieldlist = self::getViewProperties($object);
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
            }
        }
        foreach ($items as $itemid => $item) {
            // @todo filter out fieldlist in dynamic_data datastore
            $diff = array_diff(array_keys($item), $fieldlist);
            foreach ($diff as $key) {
                unset($item[$key]);
            }
            foreach ($deferred as $key) {
                $data = $objectlist->properties[$key]->getDeferredData(['value' => $item[$key], '_itemid' => $itemid]);
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
        $object = $args['object'];
        $itemid = intval($args['itemid']);
        $method = 'display';
        if (!self::hasOperation($object, $method)) {
            return ['method' => 'getObjectItem', 'args' => $args, 'error' => 'Unknown operation'];
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        $userId = 0;
        if (self::hasSecurity($object, $method)) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = self::checkUser();
            //$args['access'] = 'display';
        }
        $fieldlist = self::getDisplayProperties($object);
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
        // @todo filter out fieldlist in dynamic_data datastore
        $diff = array_diff(array_keys($item), $fieldlist);
        foreach ($diff as $key) {
            unset($item[$key]);
        }
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
        $object = $args['object'];
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
        $object = $args['object'];
        $itemid = intval($args['itemid']);
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
        $object = $args['object'];
        $itemid = intval($args['itemid']);
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
        if (empty(self::$config)) {
            self::$config = [];
            $configFile = sys::varpath() . '/cache/api/restapi_config.json';
            if (file_exists($configFile)) {
                $contents = file_get_contents($configFile);
                self::$config = json_decode($contents, true);
            }
            $fieldlist = ['objectid', 'name', 'label', 'module_id', 'itemtype', 'datastore', 'properties'];
            $allowed = array_flip($fieldlist);
            if (!empty(self::$config['objects'])) {
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
            if (!empty(self::$config['modules'])) {
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
        if (empty(self::$modules)) {
            self::loadConfig();
        }
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
        $module = $args['module'];
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
        $module = $args['module'];
        $path = $args['path'];
        $func = self::getModuleApiFunc($module, $path, 'get');
        if (empty($func)) {
            return ['method' => 'getModuleCall', 'args' => $args, 'error' => 'Unknown module api'];
        }
        if (!empty($func['security'])) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = self::checkUser();
            // @checkme assume we have a security mask here
            if (is_string($func['security'])) {
                $role = xarRoles::getRole($userId);
                $rolename = $role->getName();
                $pass = xarSecurity::check($func['security'], 0, 'All', 'All', $module, $rolename);
            // @todo verify access for user based on what?
            } else {
                $pass = true;
            }
            if (!$pass) {
                http_response_code(403);
                exit;
            }
        }
        xarMod::init();
        $type = empty($func['type']) ? 'rest' : $func['type'];
        // @checkme pass all args from handler here?
        return xarMod::apiFunc($module, $type, $func['name'], $args);
    }

    public static function postModuleCall($args)
    {
        $module = $args['module'];
        $path = $args['path'];
        // this contains any POSTed args from rst.php
        if (empty($args['input'])) {
            $args['input'] = [];
        }
        $func = self::getModuleApiFunc($module, $path, 'post');
        if (empty($func)) {
            return ['method' => 'postModuleCall', 'args' => $args, 'error' => 'Unknown module api'];
        }
        if (!empty($func['security'])) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = self::checkUser();
            // @checkme assume we have a security mask here
            if (is_string($func['security'])) {
                $role = xarRoles::getRole($userId);
                $rolename = $role->getName();
                $pass = xarSecurity::check($func['security'], 0, 'All', 'All', $module, $rolename);
            // @todo verify access for user based on what?
            } else {
                $pass = true;
            }
            if (!$pass) {
                http_response_code(403);
                exit;
            }
        }
        xarMod::init();
        $type = empty($func['type']) ? 'rest' : $func['type'];
        // @checkme handle POSTed args by passing $args['input'] only in handler?
        return xarMod::apiFunc($module, $type, $func['name'], $args['input']);
    }

    public static function hasModule($module)
    {
        self::loadConfig();
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

    public static function getModuleApiFunc($module, $path, $method = 'get')
    {
        if (!self::hasModule($module)) {
            return;
        }
        $apilist = self::getModuleApiList($module);
        foreach ($apilist as $api => $item) {
            if (isset($item['enabled']) && empty($item['enabled'])) {
                continue;
            }
            if ($item['path'] == $path && $item['method'] == $method) {
                $item['name'] = $api;
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
        $r->get('/modules/{module}/{path}', ['DataObjectRESTHandler', 'getModuleCall']);
        $r->post('/modules/{module}/{path}', ['DataObjectRESTHandler', 'postModuleCall']);
    }

    /**
     * Handle request and get result
     */
    public static function getResult($handler, $vars)
    {
        self::setTimer('handle');
        $tryCachedResult = false;
        if (is_array($handler) && $handler[0] === "DataObjectRESTHandler" && substr($handler[1], 0 , 3) === "get") {
            self::loadConfig();
            $tryCachedResult = true;
        }
        if ($tryCachedResult && self::$enableCache) {
            $queryId = $handler[1] . '-' . md5(json_encode($vars));
            $cacheKey = self::getCacheKey($queryId);
            if (!empty($cacheKey) && self::isCached($cacheKey)) {
                $result = self::getCached($cacheKey);
                if (is_array($result)) {
                    $result['x-cached'] = true;
                }
                self::setTimer('cached');
                return $result;
            }
        }
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
        //http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    }
}
