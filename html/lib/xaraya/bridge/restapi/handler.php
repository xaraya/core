<?php

/**
 * @package core\bridge
 * @subpackage restapi
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 */

sys::import('modules.dynamicdata.class.objects.factory');
sys::import('xaraya.tools.timertrait');
sys::import('xaraya.caching.cachetrait');
sys::import('xaraya.bridge.requests.requesttrait');
sys::import('xaraya.context.context');
sys::import('modules.authsystem.class.authtoken');
use Xaraya\Caching\CacheInterface;
use Xaraya\Caching\CacheTrait;
use Xaraya\Tools\TimerInterface;
use Xaraya\Tools\TimerTrait;
use Xaraya\Bridge\Requests\CommonRequestInterface;
use Xaraya\Bridge\Requests\CommonRequestTrait;
use Xaraya\Context\ContextFactory;
use Xaraya\Context\Context;
use Xaraya\Authentication\AuthToken;

/**
 * Class to handle DataObject REST API calls
 * @uses \sys::autoload()
 */
class DataObjectRESTHandler extends xarObject implements CommonRequestInterface, CacheInterface, TimerInterface
{
    use CommonRequestTrait;
    use TimerTrait;  // activate with self::enableTimer(true)
    use CacheTrait;  // activate with self::enableCache(true)

    public static string $endpoint = 'rst.php/v1';
    /** @var array<string, mixed> */
    public static $objects = [];
    /** @var array<string, mixed> */
    public static $schemas = [];
    /** @var array<string, mixed> */
    public static $config = [];
    /** @var array<string, mixed> */
    public static $modules = [];

    /**
     * Summary of getOpenAPI
     * @param array<string, mixed> $vars
     * @param mixed $context
     * @return mixed
     */
    public function getOpenAPI($vars = [], $context = null)
    {
        $openapi = sys::varpath() . '/cache/api/openapi.json';
        if (!file_exists($openapi)) {
            xarDatabase::init();
            sys::import('xaraya.bridge.restapi.builder');
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
    public function getBaseURL($base = '', $path = null, $args = [])
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
    public function getObjectURL($object = null, $itemid = null)
    {
        if (empty($object)) {
            return $this->getBaseURL('/objects');
        }
        if (empty($itemid)) {
            return $this->getBaseURL('/objects', $object);
        }
        return $this->getBaseURL('/objects', $object . '/' . $itemid);
    }

    /**
     * Summary of getObjects
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function getObjects($args)
    {
        $this->loadObjects();
        $result = ['items' => [], 'count' => count(self::$objects)];
        foreach (self::$objects as $itemid => $item) {
            if ($item['datastore'] !== 'dynamicdata') {
                continue;
            }
            $item['_links'] = ['self' => ['href' => $this->getObjectURL($item['name'])]];
            array_push($result['items'], $item);
        }
        $result['filter'] = ['datastore,eq,dynamicdata'];
        //return array('method' => 'getObjects', 'args' => $args, 'result' => $result);
        return $result;
    }

    /**
     * Summary of getObjectList
     * @param array<string, mixed> $args
     * @param Context<string, mixed> $context
     * @throws \ForbiddenOperationException
     * @return array<string, mixed>
     */
    public function getObjectList($args, $context)
    {
        $object = $args['path']['object'];
        $method = 'view';
        if (!$this->hasOperation($object, $method)) {
            return ['method' => 'getObjectList', 'args' => $args, 'error' => 'Unknown operation'];
        }
        $userId = 0;
        if ($this->hasSecurity($object, $method)) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = $this->checkUser($context);
            //$args['access'] = 'view';
        }
        if (!$this->hasCaching($object, $method)) {
            self::enableCache(false);
        }
        $args = $args['query'] ?? [];
        // @checkme always count here
        $args['count'] = true;
        if (empty($args['limit']) || !is_numeric($args['limit'])) {
            $args['limit'] = 100;
        }
        $fieldlist = $this->getViewProperties($object, $args);
        $loader = new DataObjectLoader($object, $fieldlist);
        // set context if available in handler
        $loader->setContext($context);
        $loader->parseQueryArgs($args);
        $objectlist = $loader->getObjectList();
        if ($this->hasSecurity($object, $method) && !$objectlist->checkAccess('view', 0, $userId)) {
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
                if ($data['value'] && in_array($objectlist->properties[$key]::class, ['DeferredListProperty', 'DeferredManyProperty']) && is_array($data['value'])) {
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
            $item['_links'] = ['self' => ['href' => $this->getObjectURL($object, $itemid)]];
            array_push($result['items'], $item);
        }
        //return array('method' => 'getObjectList', 'args' => $args, 'fieldlist' => $fieldlist, 'result' => $result);
        return $result;
    }

    /**
     * Summary of getObjectItem
     * @param array<string, mixed> $args
     * @param Context<string, mixed> $context
     * @throws \Exception
     * @throws \ForbiddenOperationException
     * @return array<string, mixed>
     */
    public function getObjectItem($args, $context)
    {
        $object = $args['path']['object'];
        $itemid = $this->checkItemId($object, $args['path']['itemid']);
        $method = 'display';
        if (!$this->hasOperation($object, $method)) {
            return ['method' => 'getObjectItem', 'args' => $args, 'error' => 'Unknown operation'];
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        $userId = 0;
        if ($this->hasSecurity($object, $method)) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = $this->checkUser($context);
            //$args['access'] = 'display';
        }
        if (!$this->hasCaching($object, $method)) {
            self::enableCache(false);
        }
        $args = $args['query'] ?? [];
        $fieldlist = $this->getDisplayProperties($object, $args);
        $params = ['name' => $object, 'itemid' => $itemid, 'fieldlist' => $fieldlist];
        // set context if available in handler
        $objectitem = DataObjectFactory::getObject($params, $context);
        if (empty($objectitem)) {
            throw new BadParameterException('object');
        }
        if ($this->hasSecurity($object, $method) && !$objectitem->checkAccess('display', $itemid, $userId)) {
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
                if ($data['value'] && in_array($objectitem->properties[$key]::class, ['DeferredListProperty', 'DeferredManyProperty'])) {
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
        //$item['_links'] = array('self' => array('href' => $this->getObjectURL($object, $itemid)));
        //return array('method' => 'getObjectItem', 'args' => $args, 'fieldlist' => $fieldlist, 'result' => $item);
        return $item;
    }

    /**
     * Summary of checkItemId
     * @param string $object
     * @param mixed $itemid
     * @return mixed
     */
    private function checkItemId($object, $itemid)
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
     * @param Context<string, mixed> $context
     * @throws \Exception
     * @throws \ForbiddenOperationException
     * @return array<mixed>|int|mixed
     */
    public function createObjectItem($args, $context)
    {
        $object = $args['path']['object'];
        $method = 'create';
        if (!$this->hasOperation($object, $method)) {
            return ['method' => 'createObjectItem', 'args' => $args, 'error' => 'Unknown operation'];
        }
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        $userId = $this->checkUser($context);
        $fieldlist = $this->getCreateProperties($object);
        // @todo sanity check on input based on properties
        if (empty($args['input'])) {
            throw new Exception('Unknown input ' . $object);
        }
        if (!empty($args['input']['id'])) {
            unset($args['input']['id']);
        }
        $params = ['name' => $object];
        // set context if available in handler
        $objectitem = DataObjectFactory::getObject($params, $context);
        if (empty($objectitem)) {
            throw new BadParameterException('object');
        }
        if (!$objectitem->checkAccess('create', 0, $userId)) {
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
     * @param Context<string, mixed> $context
     * @throws \Exception
     * @throws \ForbiddenOperationException
     * @return array<string, mixed>|int|mixed
     */
    public function updateObjectItem($args, $context)
    {
        $object = $args['path']['object'];
        $itemid = $this->checkItemId($object, $args['path']['itemid']);
        $method = 'update';
        if (!$this->hasOperation($object, $method)) {
            return ['method' => 'updateObjectItem', 'args' => $args, 'error' => 'Unknown operation'];
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        $userId = $this->checkUser($context);
        $fieldlist = $this->getUpdateProperties($object);
        // @todo sanity check on input based on properties
        if (empty($args['input'])) {
            throw new Exception('Unknown input ' . $object);
        }
        if (!empty($args['input']['id']) && $itemid != $args['input']['id']) {
            throw new Exception('Unknown id ' . $object);
        }
        $params = ['name' => $object, 'itemid' => $itemid];
        // set context if available in handler
        $objectitem = DataObjectFactory::getObject($params, $context);
        if (empty($objectitem)) {
            throw new BadParameterException('object');
        }
        if (!$objectitem->checkAccess('update', $itemid, $userId)) {
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
     * @param Context<string, mixed> $context
     * @throws \Exception
     * @throws \ForbiddenOperationException
     * @return array<string, mixed>|int|mixed
     */
    public function deleteObjectItem($args, $context)
    {
        $object = $args['path']['object'];
        $itemid = $this->checkItemId($object, $args['path']['itemid']);
        $method = 'delete';
        if (!$this->hasOperation($object, $method)) {
            return ['method' => 'deleteObjectItem', 'args' => $args, 'error' => 'Unknown operation'];
        }
        if (empty($itemid)) {
            throw new Exception('Unknown id ' . $object);
        }
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        $userId = $this->checkUser($context);
        $params = ['name' => $object, 'itemid' => $itemid];
        // set context if available in handler
        $objectitem = DataObjectFactory::getObject($params, $context);
        if (empty($objectitem)) {
            throw new BadParameterException('object');
        }
        if (!$objectitem->checkAccess('delete', $itemid, $userId)) {
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
    public function loadConfig()
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
        /**
        if (!empty(self::$config['storage'])) {
            AuthToken::$storageType = self::$config['storage'];
        }
        if (!empty(self::$config['expires'])) {
            AuthToken::$tokenExpires = intval(self::$config['expires']);
        }
         */
        // use xarTimerTrait
        if (isset(self::$config['timer'])) {
            self::enableTimer(!empty(self::$config['timer']) ? true : false);
        }
        // use xarCacheTrait
        if (isset(self::$config['cache'])) {
            self::enableCache(!empty(self::$config['cache']) ? true : false);
        }
        if (self::enableCache()) {
            $cacheScope = 'RestAPI.Operation';
            $this->setCacheScope($cacheScope);
        }
        $this->setTimer('config');
        // @deprecated for existing _config files before rebuild
        if (!empty(self::$config['objects'])) {
            $this->loadObjects(self::$config);
        }
        if (!empty(self::$config['modules'])) {
            $this->loadModules(self::$config);
        }
    }

    /**
     * Summary of loadObjects
     * @param array<string, mixed> $config
     * @return void
     */
    public function loadObjects($config = [])
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
        $this->setTimer('objects');
    }

    /**
     * Summary of loadModules
     * @param array<string, mixed> $config
     * @return void
     */
    public function loadModules($config = [])
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
        $this->setTimer('modules');
    }

    /**
     * Summary of hasObject
     * @param string $object
     * @return bool
     */
    public function hasObject($object)
    {
        $this->loadObjects();
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
    public function hasOperation($object, $method)
    {
        if (!$this->hasObject($object)) {
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
    public function getOperation($object, $method)
    {
        return self::$config['objects'][$object]['x-operations'][$method];
    }

    /**
     * Summary of loadSchemas
     * @return mixed
     */
    public function loadSchemas()
    {
        if (empty(self::$schemas)) {
            $doc = $this->getOpenAPI();
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
    public function hasSecurity($object, $method)
    {
        $operation = $this->getOperation($object, $method);
        return !empty($operation['security']) ? true : false;
    }

    /**
     * Summary of hasCaching
     * @param string $object
     * @param string $method
     * @return bool
     */
    public function hasCaching($object, $method)
    {
        $operation = $this->getOperation($object, $method);
        return !empty($operation['caching']) ? true : false;
    }

    /**
     * Summary of getProperties
     * @param string $object
     * @param string $method
     * @return array<string>
     */
    public function getProperties($object, $method)
    {
        $operation = $this->getOperation($object, $method);
        return $operation['properties'];
    }

    /**
     * Summary of getViewProperties
     * @param string $object
     * @param ?array<string, mixed> $args
     * @return array<string>
     */
    public function getViewProperties($object, $args = null)
    {
        // schema (object) -> properties -> items (array) -> items (object) -> properties
        //return self::$schemas[$schema]['properties']['items']['items']['properties'];
        $properties = $this->getProperties($object, 'view');
        return $this->expandProperties($object, $properties, $args);
    }

    /**
     * Summary of getDisplayProperties
     * @param string $object
     * @param ?array<string, mixed> $args
     * @return array<string>
     */
    public function getDisplayProperties($object, $args = null)
    {
        // schema (object) -> properties
        //return self::$schemas[$schema]['properties'];
        $properties = $this->getProperties($object, 'display');
        return $this->expandProperties($object, $properties, $args);
    }

    /**
     * Summary of getCreateProperties
     * @param string $object
     * @return array<string>
     */
    public function getCreateProperties($object)
    {
        // schema (object) -> properties
        //return self::$schemas[$schema]['properties'];
        return $this->getProperties($object, 'create');
    }

    /**
     * Summary of getUpdateProperties
     * @param string $object
     * @return array<string>
     */
    public function getUpdateProperties($object)
    {
        // schema (object) -> properties
        //return self::$schemas[$schema]['properties'];
        return $this->getProperties($object, 'update');
    }

    /**
     * Summary of expandProperties
     * @param string $object
     * @param array<string> $fieldlist
     * @param ?array<string, mixed> $args
     * @return array<string>
     */
    public function expandProperties($object, $fieldlist, $args = null)
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
     * @param Context<string, mixed> $context
     * @uses xarMod::init()
     * @uses xarUser::init()
     * @return array<string, mixed>
     */
    public function whoami($args, $context)
    {
        $userId = $this->checkUser($context);
        //return array('id' => xarUser::getVar('id'), 'name' => xarUser::getVar('name'));
        xarMod::init();
        xarUser::init();
        $role = xarRoles::getRole($userId);
        $user = $role->getFieldValues();
        return ['id' => $user['id'], 'name' => $user['name']];
    }

    /**
     * Return the current context or exit with 401 status code
     * @param array<string, mixed> $args
     * @param Context<string, mixed> $context
     * @return array<string, mixed>
     */
    public function getContext($args, $context)
    {
        $userId = $context->getUserId();
        // return restricted version for non-site admin
        if (empty($userId) || $userId != xarModVars::get('roles', 'admin')) {
            return ['userId' => $userId, 'error' => 'Restricted to site admin'];
        }
        return $context->getArrayCopy();
    }

    /**
     * Verify that the token or cookie corresponds to an authorized user (with minimal core load) or exit with 401 status code
     * @param Context<string, mixed> $context
     * @throws \UnauthorizedOperationException
     * @return int
     */
    private function checkUser($context)
    {
        $userId = $context->getUserId();
        // return the userId if we have one
        if (!empty($userId)) {
            return $userId;
        }
        // check if we can still send headers
        if (headers_sent()) {
            throw new UnauthorizedOperationException();
        }
        // check if we had an auth token before
        $token = AuthToken::getAuthToken($context);
        if (!empty($token)) {
            //header('WWW-Authenticate: Bearer realm="Xaraya Site Login"');
            header('WWW-Authenticate: Token realm="Xaraya Site Login", created=');
        } else {
            header('WWW-Authenticate: Cookie realm="Xaraya Site Login", cookie-name=XARAYASID');
        }
        throw new UnauthorizedOperationException();
    }

    /**
     * Summary of postToken
     * @param array<string, mixed> $args
     * @param Context<string, mixed> $context
     * @uses xarMod::init()
     * @uses xarUser::init()
     * @uses xarMod::apiFunc()
     * @throws \UnauthorizedOperationException
     * @return array<string, mixed>
     */
    public function postToken($args, $context)
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
        if (empty($access) || !in_array($access, AuthToken::ACCESS_LEVELS)) {
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
        $userId = xarMod::apiFunc('authsystem', 'user', 'authenticate_user', $args['input'], $context);
        if (empty($userId) || $userId == xarUser::AUTH_FAILED) {
            if (!headers_sent()) {
                //header('WWW-Authenticate: Bearer realm="Xaraya Site Login"');
                header('WWW-Authenticate: Token realm="Xaraya Site Login"');
            }
            throw new UnauthorizedOperationException();
        }
        $userInfo = ['userId' => $userId, 'access' => $access, 'created' => time()];
        $token = AuthToken::createToken($userInfo);
        $expiration = date('c', time() + AuthToken::$tokenExpires);
        return ['access_token' => $token, 'expiration' => $expiration, 'role_id' => $userId];
    }

    /**
     * Summary of deleteToken
     * @param array<string, mixed> $args
     * @param Context<string, mixed> $context
     * @return bool
     */
    public function deleteToken($args, $context)
    {
        $args['request'] ??= null;
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        $userId = $this->checkUser($context);
        // check if we had an auth token before
        $token = AuthToken::getAuthToken($context);
        if (empty($token)) {
            return false;
        }
        AuthToken::deleteToken($token);
        return true;
    }

    /**
     * Summary of getModuleURL
     * @param ?string $module
     * @param ?string $api
     * @param array<string, mixed> $args
     * @return string
     */
    public function getModuleURL($module = null, $api = null, $args = [])
    {
        if (empty($module)) {
            return $this->getBaseURL('/modules');
        }
        if (empty($api)) {
            return $this->getBaseURL('/modules', $module);
        }
        return $this->getBaseURL('/modules', $module . '/' . $api);
    }

    /**
     * Summary of getModules
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function getModules($args)
    {
        $this->loadModules();
        $result = ['items' => [], 'count' => count(self::$modules)];
        foreach (self::$modules as $itemid => $item) {
            $item['apilist'] = array_keys($item['apilist']);
            $item['_links'] = ['self' => ['href' => $this->getModuleURL($item['module'])]];
            array_push($result['items'], $item);
        }
        return $result;
    }

    /**
     * Summary of getModuleApis
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function getModuleApis($args)
    {
        $module = $args['path']['module'];
        if (!$this->hasModule($module)) {
            return ['method' => 'getModuleApis', 'args' => $args, 'error' => 'Unknown module'];
        }
        $result = ['module' => $module, 'apilist' => [], 'count' => 0];
        $apilist = $this->getModuleApiList($module);
        foreach ($apilist as $api => $item) {
            if (isset($item['enabled']) && empty($item['enabled'])) {
                continue;
            }
            $item['name'] = $api;
            $item['path'] = $this->getModuleURL($module, $item['path']);
            $result['apilist'][] = $item;
        }
        $result['count'] = count($result['apilist']);
        return $result;
    }

    /**
     * Summary of getModuleCall
     * @param array<string, mixed> $args
     * @param Context<string, mixed> $context
     * @uses xarMod::init()
     * @uses xarUser::init()
     * @uses xarMod::apiFunc()
     * @throws \ForbiddenOperationException
     * @return mixed
     */
    public function getModuleCall($args, $context)
    {
        $module = $args['path']['module'];
        $path = $args['path']['path'];
        // @checkme support optional part(s) after path, either with {path}[/{more}] or with {path:.+}
        $more = $args['path']['more'] ?? '';
        $func = $this->getModuleApiFunc($module, $path, 'get', $more);
        if (empty($func)) {
            return ['method' => 'getModuleCall', 'args' => $args, 'error' => 'Unknown module api'];
        }
        xarMod::init();
        xarUser::init();
        if (!empty($func['security'])) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = $this->checkUser($context);
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
        if (empty($func['caching'])) {
            self::enableCache(false);
        }
        // @checkme how to save this in case of caching?
        if (!empty($func['mediatype'])) {
            $context['mediatype'] = $func['mediatype'];
            if (!empty($context['request'])) {
                $context['request'] = ($context['request'])->withAttribute('mediaType', $func['mediatype']);
            }
        }
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
        return xarMod::apiFunc($func['module'], $func['type'], $func['name'], $params, $context);
    }

    /**
     * Summary of postModuleCall
     * @param array<string, mixed> $args
     * @param Context<string, mixed> $context
     * @uses xarMod::init()
     * @uses xarUser::init()
     * @uses xarMod::apiFunc()
     * @throws \ForbiddenOperationException
     * @return mixed
     */
    public function postModuleCall($args, $context)
    {
        $module = $args['path']['module'];
        $path = $args['path']['path'];
        // @checkme support optional part(s) after path, either with {path}[/{more}] or with {path:.+}
        $more = $args['path']['more'] ?? '';
        $func = $this->getModuleApiFunc($module, $path, 'post', $more);
        if (empty($func)) {
            return ['method' => 'postModuleCall', 'args' => $args, 'error' => 'Unknown module api'];
        }
        // this contains any POSTed args from rst.php
        if (empty($args['input'])) {
            $args['input'] = [];
        }
        xarMod::init();
        xarUser::init();
        if (!empty($func['security'])) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = $this->checkUser($context);
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
            $context['mediatype'] = $func['mediatype'];
            if (!empty($context['request'])) {
                $context['request'] = ($context['request'])->withAttribute('mediaType', $func['mediatype']);
            }
        }
        // @checkme handle POSTed args by passing $args['input'] only in handler?
        $params = $args['input'] ?? [];
        if (!empty($more) && !empty($func['args'])) {
            $params = array_merge($params, $func['args']);
        }
        return xarMod::apiFunc($func['module'], $func['type'], $func['name'], $params, $context);
    }

    /**
     * Summary of putModuleCall
     * @param array<string, mixed> $args
     * @param Context<string, mixed> $context
     * @throws \Exception
     * @return mixed
     */
    public function putModuleCall($args, $context)
    {
        $module = $args['path']['module'];
        $path = $args['path']['path'];
        // @checkme support optional part(s) after path, either with {path}[/{more}] or with {path:.+}
        $more = $args['path']['more'] ?? '';
        $func = $this->getModuleApiFunc($module, $path, 'put', $more);
        if (empty($func)) {
            return ['method' => 'putModuleCall', 'args' => $args, 'error' => 'Unknown module api'];
        }
        throw new Exception('Unsupported method PUT for module api');
    }

    /**
     * Summary of deleteModuleCall
     * @param array<string, mixed> $args
     * @param Context<string, mixed> $context
     * @throws \Exception
     * @return mixed
     */
    public function deleteModuleCall($args, $context)
    {
        $module = $args['path']['module'];
        $path = $args['path']['path'];
        // @checkme support optional part(s) after path, either with {path}[/{more}] or with {path:.+}
        $more = $args['path']['more'] ?? '';
        $func = $this->getModuleApiFunc($module, $path, 'delete', $more);
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
    public function hasModule($module)
    {
        $this->loadModules();
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
    public function getModuleApiList($module)
    {
        if (!$this->hasModule($module)) {
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
    public function getModuleApiFunc($module, $path, $method = 'get', $more = null)
    {
        if (!$this->hasModule($module)) {
            return null;
        }
        $apilist = $this->getModuleApiList($module);
        if (!empty($more)) {
            // @checkme sort by decreasing path length
            uasort($apilist, function ($a, $b) {
                $lena = strlen($a['path']);
                $lenb = strlen($b['path']);
                return $lenb <=> $lena;
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
                    if (!str_starts_with($path_param, '{') && !str_ends_with($path_param, '}')) {
                        // @checkme how do we keep track of fixed parts of the path here?
                        continue;
                    }
                    if (!str_starts_with($path_param, '{') || !str_ends_with($path_param, '}')) {
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
     * Get REST API routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param mixed $restHandler
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes($pathPrefix = '/v1', $namePrefix = 'restapi-', $restHandler = null)
    {
        // @todo move away from static methods for context
        $restHandler ??= static::class;
        return DataObjectRESTRoutes::getRoutes($pathPrefix, $namePrefix, $restHandler);
    }

    /**
     * Summary of callHandler - different processing for REST API - see rst.php
     * @param mixed $handler
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return mixed
     */
    public function callHandler($handler, $vars, &$request = null)
    {
        if (empty($vars)) {
            $vars = [];
        }
        $params = [];
        $params['path'] = $vars;
        $params['query'] = $this->getQueryParams($request);
        // handle php://input for POST etc.
        try {
            $params['input'] = $this->getJsonBody($request);
        } catch (JsonException $e) {
            $result = ["JSON Input Exception" => $e->getMessage()];
            return [$result, null];
        }
        // $this->setTimer('parse');
        [$result, $context] = $this->getResult($handler, $params, $request);
        /**
        if ($handler[1] === 'getOpenAPI') {
            header('Access-Control-Allow-Origin: *');
            // @checkme set server url to current path here
            //$result['servers'][0]['url'] = $this->getBaseURL();
            $result['servers'][0]['url'] = xarServer::getProtocol() . '://' . xarServer::getHost() . self::$endpoint;
        }
         */
        return [$result, $context];
    }

    /**
     * Summary of getQueryId
     * @param string $method
     * @param array<string, mixed> $vars
     * @return string
     */
    public function getQueryId($method, $vars)
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
        // @checkme do we want to make this user-dependent?
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
    public function getResult($handler, $params, &$request = null)
    {
        // initialize caching - delay until we need results
        xarCache::init();
        $this->loadConfig();
        $tryCachedResult = false;
        if (is_array($handler) && is_string($handler[0]) && $handler[0] === "DataObjectRESTHandler" && str_starts_with($handler[1], "get")) {
            $tryCachedResult = true;
        }
        if ($tryCachedResult && self::enableCache()) {
            $queryId = $this->getQueryId($handler[1], $params);
            $cacheKey = $this->getCacheKey($queryId);
            // @checkme we need to initialize the database here too if variable caching uses database instead of apcu
            if (!empty($cacheKey) && $this->isCached($cacheKey)) {
                $result = $this->getCached($cacheKey);
                if (is_array($result)) {
                    // $result['x-cached'] = true;
                    $result['x-cached'] = $this->keyCached($cacheKey);
                } else {
                    $keyInfo = $this->keyCached($cacheKey);
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
                $this->setTimer('cached');
                return $result;
            }
        }
        // initialize database - delay until caching fails
        xarDatabase::init();
        // initialize modules
        //xarMod::init();
        // initialize users
        //xarUser::init();
        $this->setTimer('handle');
        // define context of the request - see GraphQL
        $context = ContextFactory::fromRequest($request, __METHOD__);
        $context['mediatype'] = '';
        // @todo do we need to clone here and set the context in the call handler instance?
        if (is_object($handler[0])) {
            $routeInstance = $handler[0];
            $routeMethod = $handler[1];
            $callInstance = clone $routeInstance;
            $callInstance->setContext($context);
            $handler = [$callInstance, $routeMethod];
        }
        try {
            $result = call_user_func($handler, $params, $context);
        } catch (UnauthorizedOperationException) {
            $this->setTimer('unauthorized');
            throw new UnauthorizedOperationException();
        } catch (ForbiddenOperationException) {
            $this->setTimer('forbidden');
            throw new ForbiddenOperationException();
            //} catch (Throwable $e) {
            //    $this->setTimer('exception');
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
        if ($tryCachedResult && $this->hasCacheKey()) {
            $cacheKey = $this->getCacheKey();
            $this->setCached($cacheKey, $result);
        }
        $this->setTimer('result');
        return [$result, $context];
    }

    /**
     * Send Content-Type and JSON result to the browser
     * @param mixed $result
     * @param mixed $status
     * @param mixed $context
     * @return void
     */
    public function output($result, $status = 200, $context = null)
    {
        if (!isset($result) && php_sapi_name() !== 'cli') {
            return;
        }
        if (is_array($result) && self::enableTimer()) {
            $result['x-times'] = $this->getTimers();
        }
        if (!headers_sent() && $status !== 200) {
            http_response_code($status);
        }
        if (!empty(xarServer::getVar('HTTP_ORIGIN'))) {
            header('Access-Control-Allow-Origin: *');
        }
        if (is_string($result)) {
            if (!empty($context) && !empty($context['mediatype'])) {
                header('Content-Type: ' . $context['mediatype'] . '; charset=utf-8');
            } elseif (str_starts_with($result, '<?xml')) {
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
     * @param mixed $context
     * @return void
     */
    public static function sendCORSOptions($vars = [], $context = null)
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
