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
sys::import('modules.dynamicdata.class.objects.factory');
sys::import('xaraya.traits.timertrait');
sys::import('xaraya.traits.cachetrait');
sys::import('xaraya.bridge.requests.requesttrait');
use Xaraya\Core\Traits\CacheInterface;
use Xaraya\Core\Traits\CacheTrait;
use Xaraya\Core\Traits\TimerInterface;
use Xaraya\Core\Traits\TimerTrait;
use Xaraya\Bridge\Requests\CommonRequestInterface;
use Xaraya\Bridge\Requests\CommonRequestTrait;

/**
 * Class to handle DataObject REST API calls
 */
class DataObjectRESTHandler extends xarObject implements CommonRequestInterface, CacheInterface, TimerInterface
{
    use CommonRequestTrait;
    use TimerTrait;  // activate with self::$enableTimer = true
    use CacheTrait;  // activate with self::$enableCache = true

    public static string $endpoint = 'rst.php/v1';
    /** @var array<string, mixed> */
    public static $objects = [];
    /** @var array<string, mixed> */
    public static $schemas = [];
    /** @var array<string, mixed> */
    public static $config = [];
    /** @var array<string, mixed> */
    public static $modules = [];
    public static int $tokenExpires = 12 * 60 * 60;  // 12 hours
    public static string $storageType = 'apcu';  // database or apcu
    public static ixarCache_Storage $tokenStorage;
    public static string $mediaType;

    /**
     * Summary of getOpenAPI
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return mixed
     */
    public static function getOpenAPI($vars = [], &$request = null)
    {
        $openapi = sys::varpath() . '/cache/api/openapi.json';
        if (!file_exists($openapi)) {
            xarDatabase::init();
            sys::import('modules.dynamicdata.class.rest.builder');
            DataObjectRESTBuilder::init();
            return ['TODO' => 'generate var/cache/api/openapi.json with builder'];
        }
        $content = file_get_contents($openapi);
        $doc = json_decode($content, true);
        return $doc;
    }

    /**
     * Summary of getBaseURL
     * @param string $base
     * @param ?string $path
     * @param array<string, mixed> $args
     * @return string
     */
    public static function getBaseURL($base = '', $path = null, $args = [])
    {
        if (empty($path)) {
            return xarServer::getBaseURL() . self::$endpoint . $base;
        }
        return xarServer::getBaseURL() . self::$endpoint . $base . '/' . $path;
    }

    /**
     * Summary of getObjectURL
     * @param ?string $object
     * @param mixed $itemid
     * @return string
     */
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

    /**
     * Summary of getObjects
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
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

    /**
     * Summary of getObjectList
     * @param array<string, mixed> $args
     * @throws \ForbiddenOperationException
     * @return array<string, mixed>
     */
    public static function getObjectList($args)
    {
        $object = $args['path']['object'];
        $method = 'view';
        if (!self::hasOperation($object, $method)) {
            return ['method' => 'getObjectList', 'args' => $args, 'error' => 'Unknown operation'];
        }
        $userId = 0;
        if (self::hasSecurity($object, $method)) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = self::checkUser($args);
            //$args['access'] = 'view';
        }
        if (self::$enableCache && !self::hasCaching($object, $method)) {
            self::$enableCache = false;
        }
        $args = $args['query'] ?? [];
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
            throw new ForbiddenOperationException();
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
        $callable = [];
        foreach ($fieldlist as $key) {
            if (!empty($objectlist->properties[$key]) && method_exists($objectlist->properties[$key], 'getDeferredData')) {
                array_push($deferred, $key);
                // @checkme we need to set the item values for relational objects here
                // foreach ($items as $itemid => $item) {
                //     $objectlist->properties[$key]->setItemValue($itemid, $item[$key] ?? null);
                // }
            }
            if (!empty($objectlist->properties[$key]) && method_exists($objectlist->properties[$key], 'checkCallable')) {
                array_push($callable, $key);
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
            foreach ($callable as $key) {
                if (!empty($item[$key]) && is_callable($item[$key])) {
                    $item[$key] = call_user_func($item[$key]);
                }
            }
            $item['_links'] = ['self' => ['href' => self::getObjectURL($object, $itemid)]];
            array_push($result['items'], $item);
        }
        //return array('method' => 'getObjectList', 'args' => $args, 'fieldlist' => $fieldlist, 'result' => $result);
        return $result;
    }

    /**
     * Summary of getObjectItem
     * @param array<string, mixed> $args
     * @throws \Exception
     * @throws \ForbiddenOperationException
     * @return array<string, mixed>
     */
    public static function getObjectItem($args)
    {
        $object = $args['path']['object'];
        $itemid = self::checkItemId($object, $args['path']['itemid']);
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
            $userId = self::checkUser($args);
            //$args['access'] = 'display';
        }
        if (self::$enableCache && !self::hasCaching($object, $method)) {
            self::$enableCache = false;
        }
        $args = $args['query'] ?? [];
        $fieldlist = self::getDisplayProperties($object, $args);
        $params = ['name' => $object, 'itemid' => $itemid, 'fieldlist' => $fieldlist];
        $objectitem = DataObjectFactory::getObject($params);
        if (empty($objectitem)) {
            throw new Exception('Unknown item ' . $object);
        }
        if (self::hasSecurity($object, $method) && !$objectitem->checkAccess('display', $itemid, $userId)) {
            throw new ForbiddenOperationException();
        }
        $itemid = $objectitem->getItem();
        if ($itemid != $params['itemid']) {
            throw new Exception('Unknown itemid for ' . $object);
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
            if (!empty($objectitem->properties[$key]) && method_exists($objectitem->properties[$key], 'checkCallable')) {
                // see showOutput() in CallableProperty - we need to go through setValue() first, but we bypassed it above
                if (!empty($item[$key]) && !is_callable($item[$key])) {
                    $objectitem->properties[$key]->setValue($item[$key]);
                    $item[$key] = $objectitem->properties[$key]->getValue();
                }
            }
        }
        //$item['_links'] = array('self' => array('href' => self::getObjectURL($object, $itemid)));
        //return array('method' => 'getObjectItem', 'args' => $args, 'fieldlist' => $fieldlist, 'result' => $item);
        return $item;
    }

    /**
     * Summary of checkItemId
     * @param string $object
     * @param mixed $itemid
     * @return mixed
     */
    private static function checkItemId($object, $itemid)
    {
        // @todo use $object to validate expected format for itemid
        // @todo how to validate other documentid types like Base64 or free-form?
        // for mongodb objectid etc. (string)
        if (is_string($itemid) && strlen($itemid) == 24) {
            return $itemid;
        }
        return intval($itemid);
    }

    /**
     * Summary of createObjectItem
     * @param array<string, mixed> $args
     * @throws \Exception
     * @throws \ForbiddenOperationException
     * @return array<mixed>|int|mixed
     */
    public static function createObjectItem($args)
    {
        $object = $args['path']['object'];
        $method = 'create';
        if (!self::hasOperation($object, $method)) {
            return ['method' => 'createObjectItem', 'args' => $args, 'error' => 'Unknown operation'];
        }
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        $userId = self::checkUser($args);
        $fieldlist = self::getCreateProperties($object);
        // @todo sanity check on input based on properties
        if (empty($args['input'])) {
            throw new Exception('Unknown input ' . $object);
        }
        if (!empty($args['input']['id'])) {
            unset($args['input']['id']);
        }
        $params = ['name' => $object];
        $objectitem = DataObjectFactory::getObject($params);
        if (empty($objectitem) || !$objectitem->checkAccess('create', 0, $userId)) {
            throw new ForbiddenOperationException();
        }
        $itemid = $objectitem->createItem($args['input']);
        if (empty($itemid)) {
            throw new Exception('Unknown item ' . $object);
        }
        //return array('method' => 'createObjectItem', 'args' => $args, 'properties' => $properties, 'user' => $user, 'result' => $itemid);
        return $itemid;
    }

    /**
     * Summary of updateObjectItem
     * @param array<string, mixed> $args
     * @throws \Exception
     * @throws \ForbiddenOperationException
     * @return array<string, mixed>|int|mixed
     */
    public static function updateObjectItem($args)
    {
        $object = $args['path']['object'];
        $itemid = self::checkItemId($object, $args['path']['itemid']);
        $method = 'update';
        if (!self::hasOperation($object, $method)) {
            return ['method' => 'updateObjectItem', 'args' => $args, 'error' => 'Unknown operation'];
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        $userId = self::checkUser($args);
        $fieldlist = self::getUpdateProperties($object);
        // @todo sanity check on input based on properties
        if (empty($args['input'])) {
            throw new Exception('Unknown input ' . $object);
        }
        if (!empty($args['input']['id']) && $itemid != $args['input']['id']) {
            throw new Exception('Unknown id ' . $object);
        }
        $params = ['name' => $object, 'itemid' => $itemid];
        $objectitem = DataObjectFactory::getObject($params);
        if (empty($objectitem) || !$objectitem->checkAccess('update', $itemid, $userId)) {
            throw new ForbiddenOperationException();
        }
        $itemid = $objectitem->updateItem($args['input']);
        if ($itemid != $params['itemid']) {
            throw new Exception('Unknown item ' . $object);
        }
        //return array('method' => 'updateObjectItem', 'args' => $args, 'properties' => $properties, 'user' => $user, 'result' => $itemid);
        return $itemid;
    }

    /**
     * Summary of deleteObjectItem
     * @param array<string, mixed> $args
     * @throws \Exception
     * @throws \ForbiddenOperationException
     * @return array<string, mixed>|int|mixed
     */
    public static function deleteObjectItem($args)
    {
        $object = $args['path']['object'];
        $itemid = self::checkItemId($object, $args['path']['itemid']);
        $method = 'delete';
        if (!self::hasOperation($object, $method)) {
            return ['method' => 'deleteObjectItem', 'args' => $args, 'error' => 'Unknown operation'];
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        $userId = self::checkUser($args);
        $params = ['name' => $object, 'itemid' => $itemid];
        $objectitem = DataObjectFactory::getObject($params);
        if (empty($objectitem) || !$objectitem->checkAccess('delete', $itemid, $userId)) {
            throw new ForbiddenOperationException();
        }
        $itemid = $objectitem->deleteItem();
        if ($itemid != $params['itemid']) {
            throw new Exception('Unknown item ' . $object);
        }
        //return array('method' => 'deleteObjectItem', 'args' => $args, 'user' => $user, 'result' => $itemid);
        return $itemid;
    }

    /**
     * Summary of loadConfig
     * @return void
     */
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

    /**
     * Summary of loadObjects
     * @param array<string, mixed> $config
     * @return void
     */
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
                self::$objects[(string) $name] = $item;
            }
        } else {
            $object = 'objects';
            $params = ['name' => $object, 'fieldlist' => $fieldlist];
            $objectlist = DataObjectFactory::getObjectList($params);
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

    /**
     * Summary of loadModules
     * @param array<string, mixed> $config
     * @return void
     */
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

    /**
     * Summary of hasObject
     * @param string $object
     * @return bool
     */
    public static function hasObject($object)
    {
        self::loadObjects();
        if (empty(self::$config) || empty(self::$config['objects']) || empty(self::$config['objects'][$object])) {
            return false;
        }
        return true;
    }

    /**
     * Summary of hasOperation
     * @param string $object
     * @param string $method
     * @return bool
     */
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

    /**
     * Summary of getOperation
     * @param string $object
     * @param string $method
     * @return array<string, mixed>
     */
    public static function getOperation($object, $method)
    {
        return self::$config['objects'][$object]['x-operations'][$method];
    }

    /**
     * Summary of loadSchemas
     * @return mixed
     */
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

    /**
     * Summary of hasSecurity
     * @param string $object
     * @param string $method
     * @return bool
     */
    public static function hasSecurity($object, $method)
    {
        $operation = self::getOperation($object, $method);
        return !empty($operation['security']) ? true : false;
    }

    /**
     * Summary of hasCaching
     * @param string $object
     * @param string $method
     * @return bool
     */
    public static function hasCaching($object, $method)
    {
        $operation = self::getOperation($object, $method);
        return !empty($operation['caching']) ? true : false;
    }

    /**
     * Summary of getProperties
     * @param string $object
     * @param string $method
     * @return array<string>
     */
    public static function getProperties($object, $method)
    {
        $operation = self::getOperation($object, $method);
        return $operation['properties'];
    }

    /**
     * Summary of getViewProperties
     * @param string $object
     * @param ?array<string, mixed> $args
     * @return array<string>
     */
    public static function getViewProperties($object, $args = null)
    {
        // schema (object) -> properties -> items (array) -> items (object) -> properties
        //return self::$schemas[$schema]['properties']['items']['items']['properties'];
        $properties = self::getProperties($object, 'view');
        return self::expandProperties($object, $properties, $args);
    }

    /**
     * Summary of getDisplayProperties
     * @param string $object
     * @param ?array<string, mixed> $args
     * @return array<string>
     */
    public static function getDisplayProperties($object, $args = null)
    {
        // schema (object) -> properties
        //return self::$schemas[$schema]['properties'];
        $properties = self::getProperties($object, 'display');
        return self::expandProperties($object, $properties, $args);
    }

    /**
     * Summary of getCreateProperties
     * @param string $object
     * @return array<string>
     */
    public static function getCreateProperties($object)
    {
        // schema (object) -> properties
        //return self::$schemas[$schema]['properties'];
        return self::getProperties($object, 'create');
    }

    /**
     * Summary of getUpdateProperties
     * @param string $object
     * @return array<string>
     */
    public static function getUpdateProperties($object)
    {
        // schema (object) -> properties
        //return self::$schemas[$schema]['properties'];
        return self::getProperties($object, 'update');
    }

    /**
     * Summary of expandProperties
     * @param string $object
     * @param array<string> $fieldlist
     * @param ?array<string, mixed> $args
     * @return array<string>
     */
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
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public static function whoami($args)
    {
        $userId = self::checkUser($args);
        //return array('id' => xarUser::getVar('id'), 'name' => xarUser::getVar('name'));
        $role = xarRoles::getRole($userId);
        $user = $role->getFieldValues();
        return ['id' => $user['id'], 'name' => $user['name']];
    }

    /**
     * Verify that the cookie corresponds to an authorized user (with minimal core load) or exit with 401 status code
     * @param array<string, mixed> $context
     * @throws \UnauthorizedOperationException
     * @return int
     */
    private static function checkUser($context)
    {
        $userInfo = self::checkToken($context);
        if (!empty($userInfo)) {
            $userInfo = @json_decode($userInfo, true);
            if (empty($userInfo['userId']) || empty($userInfo['created']) || ($userInfo['created'] < (time() - self::$tokenExpires))) {
                if (!headers_sent()) {
                    //header('WWW-Authenticate: Bearer realm="Xaraya Site Login"');
                    header('WWW-Authenticate: Token realm="Xaraya Site Login", created=');
                }
                throw new UnauthorizedOperationException();
            }
            return $userInfo['userId'];
        }
        $userId = self::checkCookie($context['cookie']);
        if (empty($userId)) {
            if (!headers_sent()) {
                header('WWW-Authenticate: Cookie realm="Xaraya Site Login", cookie-name=XARAYASID');
            }
            throw new UnauthorizedOperationException();
        }
        return $userId;
    }

    /**
     * Verify that the cookie corresponds to an authorized user (with minimal core load) using xarSession
     * @param array<string, mixed> $cookieVars
     * @uses xarSession::init()
     * @uses xarUser::isLoggedIn()
     * @return mixed|void
     */
    private static function checkCookie($cookieVars)
    {
        if (empty($cookieVars) || empty($cookieVars['XARAYASID'])) {
            return;
        }
        // @checkme see graphql whoami query in dummytype.php
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            xarSession::init();
        }
        //xarUser::init();
        if (!xarUser::isLoggedIn()) {
            return;
        }
        return xarSession::getVar('role_id');
    }

    /**
     * Verify that the token corresponds to an authorized user (with minimal core load) using xarCache_Storage
     * @param array<string, mixed> $context
     * @return mixed|void
     */
    private static function checkToken($context)
    {
        $context['request'] ??= null;
        $token = static::getAuthToken($context['request']);
        if (empty($token) || !(self::getTokenStorage()->isCached($token))) {
            return;
        }
        return self::getTokenStorage()->getCached($token);
    }

    /**
     * Summary of postToken
     * @param array<string, mixed> $args
     * @throws \UnauthorizedOperationException
     * @return array<string, mixed>
     */
    public static function postToken($args)
    {
        // this contains any POSTed args from rst.php
        if (empty($args['input'])) {
            $args['input'] = [];
        }
        $uname = $args['input']['uname'];
        $pass = $args['input']['pass'];
        if (empty($uname) || empty($pass)) {
            if (!headers_sent()) {
                //header('WWW-Authenticate: Bearer realm="Xaraya Site Login", access=');
                header('WWW-Authenticate: Token realm="Xaraya Site Login", uname=, pass=');
            }
            throw new UnauthorizedOperationException();
        }
        $access = $args['input']['access'];
        if (empty($access) || !in_array($access, ['display', 'update', 'create', 'delete', 'admin'])) {
            if (!headers_sent()) {
                //header('WWW-Authenticate: Bearer realm="Xaraya Site Login", access=');
                header('WWW-Authenticate: Token realm="Xaraya Site Login", access=');
            }
            throw new UnauthorizedOperationException();
        }
        //xarSession::init();
        xarMod::init();
        xarUser::init();
        // @checkme unset xarSession role_id if needed, otherwise xarUser::logIn will hit xarUser::isLoggedIn first!?
        // @checkme or call authsystem directly if we don't want/need to support any other authentication modules
        $userId = xarMod::apiFunc('authsystem', 'user', 'authenticate_user', $args['input']);
        if (empty($userId) || $userId == xarUser::AUTH_FAILED) {
            if (!headers_sent()) {
                //header('WWW-Authenticate: Bearer realm="Xaraya Site Login"');
                header('WWW-Authenticate: Token realm="Xaraya Site Login"');
            }
            throw new UnauthorizedOperationException();
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
        return ['access_token' => $token, 'expiration' => $expiration, 'role_id' => $userId];
    }

    /**
     * Summary of deleteToken
     * @param array<string, mixed> $args
     * @return bool
     */
    public static function deleteToken($args)
    {
        $args['request'] ??= null;
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        $userId = self::checkUser($args);
        $token = static::getAuthToken($args['request']);
        if (empty($token) || !(self::getTokenStorage()->isCached($token))) {
            return false;
        }
        self::getTokenStorage()->delCached($token);
        return true;
    }

    /**
     * Summary of getTokenStorage
     * @uses xarCache::getStorage()
     * @return ixarCache_Storage
     */
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

    /**
     * Summary of getModuleURL
     * @param ?string $module
     * @param ?string $api
     * @param array<string, mixed> $args
     * @return string
     */
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

    /**
     * Summary of getModules
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
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

    /**
     * Summary of getModuleApis
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
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

    /**
     * Summary of getModuleCall
     * @param array<string, mixed> $args
     * @uses xarMod::init()
     * @uses xarUser::init()
     * @uses xarMod::apiFunc()
     * @throws \ForbiddenOperationException
     * @return mixed
     */
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
        if (!empty($func['security'])) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = self::checkUser($args);
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
                throw new ForbiddenOperationException();
            }
            // @checkme for security checks inside API functions when using auth token - see also reactphp single session
            //$_SESSION[xarSession::PREFIX . 'role_id'] = $userId;
        }
        if (self::$enableCache && empty($func['caching'])) {
            self::$enableCache = false;
        }
        // @checkme how to save this in case of caching?
        if (!empty($func['mediatype'])) {
            self::$mediaType = $func['mediatype'];
            if (!empty($args['request'])) {
                $args['request'] = ($args['request'])->withAttribute('mediaType', $func['mediatype']);
            }
        }
        xarMod::init();
        xarUser::init();
        // @checkme pass all query args from handler here?
        $params = $args['query'] ?? [];
        if (!empty($func['args'])) {
            if (!empty($more)) {
                // @checkme path params overwrite query params - but what about default args?
                $params = array_merge($params, $func['args']);
            } else {
                // @checkme query params overwrite default args
                $params = array_merge($func['args'], $params);
            }
        }
        return xarMod::apiFunc($func['module'], $func['type'], $func['name'], $params);
    }

    /**
     * Summary of postModuleCall
     * @param array<string, mixed> $args
     * @uses xarMod::init()
     * @uses xarUser::init()
     * @uses xarMod::apiFunc()
     * @throws \ForbiddenOperationException
     * @return mixed
     */
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
            $userId = self::checkUser($args);
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
                throw new ForbiddenOperationException();
            }
            // @checkme for security checks inside API functions when using auth token - see also reactphp single session
            //$_SESSION[xarSession::PREFIX . 'role_id'] = $userId;
        }
        if (!empty($func['mediatype'])) {
            self::$mediaType = $func['mediatype'];
            if (!empty($args['request'])) {
                $args['request'] = ($args['request'])->withAttribute('mediaType', $func['mediatype']);
            }
        }
        xarMod::init();
        xarUser::init();
        // @checkme handle POSTed args by passing $args['input'] only in handler?
        $params = $args['input'] ?? [];
        if (!empty($more) && !empty($func['args'])) {
            $params = array_merge($params, $func['args']);
        }
        return xarMod::apiFunc($func['module'], $func['type'], $func['name'], $params);
    }

    /**
     * Summary of putModuleCall
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return mixed
     */
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

    /**
     * Summary of deleteModuleCall
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return mixed
     */
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

    /**
     * Summary of hasModule
     * @param string $module
     * @return bool
     */
    public static function hasModule($module)
    {
        self::loadModules();
        if (empty(self::$config) || empty(self::$config['modules']) || empty(self::$config['modules'][$module])) {
            return false;
        }
        return true;
    }

    /**
     * Summary of getModuleApiList
     * @param string $module
     * @return array<string, mixed>
     */
    public static function getModuleApiList($module)
    {
        if (!self::hasModule($module)) {
            return [];
        }
        return self::$modules[$module]['apilist'];
    }

    /**
     * Summary of getModuleApiFunc
     * @param string $module
     * @param string $path
     * @param string $method
     * @param ?string $more
     * @throws \Exception
     * @return array<string, mixed>|null
     */
    public static function getModuleApiFunc($module, $path, $method = 'get', $more = null)
    {
        if (!self::hasModule($module)) {
            return null;
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
        return null;
    }

    /**
     * Register REST API routes (in FastRoute format)
     * @param FastRoute\RouteCollector $r
     * @return void
     */
    public static function registerRoutes($r)
    {
        $r->get('/objects', [static::class, 'getObjects']);
        $r->get('/objects/{object}', [static::class, 'getObjectList']);
        $r->get('/objects/{object}/{itemid}', [static::class, 'getObjectItem']);
        $r->post('/objects/{object}', [static::class, 'createObjectItem']);
        $r->put('/objects/{object}/{itemid}', [static::class, 'updateObjectItem']);
        $r->delete('/objects/{object}/{itemid}', [static::class, 'deleteObjectItem']);
        //$r->patch('/objects/{object}', [static::class, 'patchObjectDefinition']);
        $r->get('/whoami', [static::class, 'whoami']);
        $r->post('/token', [static::class, 'postToken']);
        $r->delete('/token', [static::class, 'deleteToken']);
        $r->get('/modules', [static::class, 'getModules']);
        $r->get('/modules/{module}', [static::class, 'getModuleApis']);
        // @checkme support optional part(s) after path, either with {path}[/{more}] or with {path:.+}
        $r->get('/modules/{module}/{path}[/{more:.+}]', [static::class, 'getModuleCall']);
        $r->post('/modules/{module}/{path}[/{more:.+}]', [static::class, 'postModuleCall']);
        $r->put('/modules/{module}/{path}[/{more:.+}]', [static::class, 'putModuleCall']);
        $r->delete('/modules/{module}/{path}[/{more:.+}]', [static::class, 'deleteModuleCall']);
    }

    /**
     * Summary of addRouteGroup
     * @param FastRoute\RouteCollector $r
     * @param string $prefix
     * @param callable $registerCallback
     * @return void
     */
    public static function addRouteGroup($r, $prefix, $registerCallback)
    {
        $r->addGroup($prefix, function ($r) use ($registerCallback) {
            $registerCallback($r);
        });
    }

    /**
     * Summary of callHandler - different processing for REST API - see rst.php
     * @param mixed $handler
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return mixed
     */
    public static function callHandler($handler, $vars, &$request = null)
    {
        if (empty($vars)) {
            $vars = [];
        }
        $params = [];
        $params['path'] = $vars;
        $params['query'] = static::getQueryParams($request);
        // handle php://input for POST etc.
        try {
            $params['input'] = static::getJsonBody($request);
        } catch (JsonException $e) {
            return ["JSON Input Exception" => $e->getMessage()];
        }
        $params['server'] = static::getServerParams($request);
        $params['cookie'] = static::getCookieParams($request);
        // self::setTimer('parse');
        $result = self::getResult($handler, $params, $request);
        /**
        if ($handler[1] === 'getOpenAPI') {
            header('Access-Control-Allow-Origin: *');
            // @checkme set server url to current path here
            //$result['servers'][0]['url'] = self::getBaseURL();
            $result['servers'][0]['url'] = xarServer::getProtocol() . '://' . xarServer::getHost() . self::$endpoint;
        }
         */
        return $result;
    }

    /**
     * Summary of getQueryId
     * @param string $method
     * @param array<string, mixed> $vars
     * @return string
     */
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
        unset($vars['server']);
        // @checkme do we want to make this user-dependent?
        unset($vars['cookie']);
        $queryId .= '-' . md5(json_encode($vars));
        return $queryId;
    }

    /**
     * Handle request and get result
     * @param mixed $handler
     * @param array<string, mixed> $params
     * @param mixed $request
     * @uses xarCache::init()
     * @uses xarDatabase::init()
     * @throws \UnauthorizedOperationException
     * @throws \ForbiddenOperationException
     * @return mixed
     */
    public static function getResult($handler, $params, &$request = null)
    {
        // initialize caching - delay until we need results
        xarCache::init();
        self::loadConfig();
        $tryCachedResult = false;
        if (is_array($handler) && $handler[0] === "DataObjectRESTHandler" && substr($handler[1], 0, 3) === "get") {
            $tryCachedResult = true;
        }
        if ($tryCachedResult && self::$enableCache) {
            $queryId = self::getQueryId($handler[1], $params);
            $cacheKey = self::getCacheKey($queryId);
            // @checkme we need to initialize the database here too if variable caching uses database instead of apcu
            if (!empty($cacheKey) && self::isCached($cacheKey)) {
                $result = self::getCached($cacheKey);
                if (is_array($result)) {
                    // $result['x-cached'] = true;
                    $result['x-cached'] = self::keyCached($cacheKey);
                } else {
                    $keyInfo = self::keyCached($cacheKey);
                    if (!empty($keyInfo) && is_array($keyInfo) && !headers_sent()) {
                        header('X-Cache-Key: ' . $keyInfo['key']);
                        header('X-Cache-Code: ' . $keyInfo['code']);
                        header('X-Cache-Time: ' . $keyInfo['time']);
                        if (isset($keyInfo['hits'])) {
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
        // don't use call_user_func here anymore because $request is passed by reference
        self::$mediaType = '';
        if (!empty($request)) {
            $params['request'] = &$request;
        }
        try {
            $result = call_user_func($handler, $params);
        } catch (UnauthorizedOperationException $e) {
            self::setTimer('unauthorized');
            throw new UnauthorizedOperationException();
        } catch (ForbiddenOperationException $e) {
            self::setTimer('forbidden');
            throw new ForbiddenOperationException();
            //} catch (Throwable $e) {
            //    self::setTimer('exception');
            //    $result = "Exception: " . $e->getMessage();
            //    if ($e->getPrevious() !== null) {
            //        $result .= "\nPrevious: " . $e->getPrevious()->getMessage();
            //    }
            //    $result .= "\nTrace:\n" . $e->getTraceAsString();
            //    return $result;
        }
        // if (is_array($result)) {
        //     $result['x-debug'] = ['handler' => $handler, 'params' => $params];
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
     * @param mixed $result
     * @param mixed $status
     * @return void
     */
    public static function output($result, $status = 200)
    {
        if (!isset($result) && php_sapi_name() !== 'cli') {
            return;
        }
        if (is_array($result) && self::$enableTimer) {
            $result['x-times'] = self::getTimers();
        }
        if (!headers_sent() && $status !== 200) {
            http_response_code($status);
        }
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Origin: *');
        }
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
     * @param mixed $vars
     * @param mixed $request
     * @return void
     */
    public static function sendCORSOptions($vars = [], &$request = null)
    {
        // See https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
        http_response_code(204);
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        // @checkme X-Apollo-Tracing is used in the GraphQL Playground
        header('Access-Control-Allow-Headers: X-Auth-Token, Content-Type, X-Apollo-Tracing');
        // header('Access-Control-Allow-Credentials: true');
    }
}
