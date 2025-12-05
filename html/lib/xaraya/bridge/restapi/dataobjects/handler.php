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

namespace Xaraya\Bridge\RestAPI;

use sys;
use DataObjectFactory;
use BadParameterException;
use ForbiddenOperationException;
use Exception;

/**
 * Class to handle DataObject REST API calls
 */
class DataObjectAPIHandler extends RestAPIHandler
{
    /** @var array<string, mixed> */
    public static $objects = [];

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
    public function getObjects($args = [])
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
     * @throws \ForbiddenOperationException
     * @return array<string, mixed>
     */
    public function getObjectList($args)
    {
        $object = $args['path']['object'];
        $method = 'view';
        if (!$this->hasOperation($object, $method)) {
            return ['method' => 'getObjectList', 'args' => $args, 'error' => 'Unknown operation'];
        }
        $userId = 0;
        if ($this->hasSecurity($object, $method)) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = $this->checkUser();
            //$args['access'] = 'view';
        }
        $context = $this->getContext();
        if (!$this->hasCaching($object, $method)) {
            // disable cache in RestAPI handler
            $context->handler->enableCache(false);
        }
        $args = $args['query'] ?? [];
        // @checkme always count here
        $args['count'] = true;
        if (empty($args['limit']) || !is_numeric($args['limit'])) {
            $args['limit'] = 100;
        }
        $fieldlist = $this->getViewProperties($object, $args);
        $xar = $this->getServicesClass();
        // set context if available in handler
        $loader = DataObjectFactory::getObjectLoader($object, $fieldlist, $context, $xar);
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
     * @throws \Exception
     * @throws \ForbiddenOperationException
     * @return array<string, mixed>
     */
    public function getObjectItem($args)
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
            $userId = $this->checkUser();
            //$args['access'] = 'display';
        }
        $context = $this->getContext();
        if (!$this->hasCaching($object, $method)) {
            // disable cache in RestAPI handler
            $context->handler->enableCache(false);
        }
        $args = $args['query'] ?? [];
        $fieldlist = $this->getDisplayProperties($object, $args);
        $params = ['name' => $object, 'itemid' => $itemid, 'fieldlist' => $fieldlist];
        $xar = $this->getServicesClass();
        // set context if available in handler
        $objectitem = DataObjectFactory::getObject($params, $context, $xar);
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
        // @checkme this throws exception for userlist property when xar::user()->init() is not called first
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
     * @throws \Exception
     * @throws \ForbiddenOperationException
     * @return array<mixed>|int|mixed
     */
    public function createObjectItem($args)
    {
        $object = $args['path']['object'];
        $method = 'create';
        if (!$this->hasOperation($object, $method)) {
            return ['method' => 'createObjectItem', 'args' => $args, 'error' => 'Unknown operation'];
        }
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        $userId = $this->checkUser();
        $fieldlist = $this->getCreateProperties($object);
        // @todo sanity check on input based on properties
        if (empty($args['input'])) {
            throw new Exception('Unknown input ' . $object);
        }
        if (!empty($args['input']['id'])) {
            unset($args['input']['id']);
        }
        $params = ['name' => $object];
        $context = $this->getContext();
        $xar = $this->getServicesClass();
        // set context if available in handler
        $objectitem = DataObjectFactory::getObject($params, $context, $xar);
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
     * @throws \Exception
     * @throws \ForbiddenOperationException
     * @return array<string, mixed>|int|mixed
     */
    public function updateObjectItem($args)
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
        $userId = $this->checkUser();
        $fieldlist = $this->getUpdateProperties($object);
        // @todo sanity check on input based on properties
        if (empty($args['input'])) {
            throw new Exception('Unknown input ' . $object);
        }
        if (!empty($args['input']['id']) && $itemid != $args['input']['id']) {
            throw new Exception('Unknown id ' . $object);
        }
        $params = ['name' => $object, 'itemid' => $itemid];
        $context = $this->getContext();
        $xar = $this->getServicesClass();
        // set context if available in handler
        $objectitem = DataObjectFactory::getObject($params, $context, $xar);
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
     * @throws \Exception
     * @throws \ForbiddenOperationException
     * @return array<string, mixed>|int|mixed
     */
    public function deleteObjectItem($args)
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
        $userId = $this->checkUser();
        $params = ['name' => $object, 'itemid' => $itemid];
        $context = $this->getContext();
        $xar = $this->getServicesClass();
        // set context if available in handler
        $objectitem = DataObjectFactory::getObject($params, $context, $xar);
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
     * Summary of loadObjects
     * @param array<string, mixed> $config
     * @return void
     */
    public function loadObjects($config = [])
    {
        if (!empty(self::$objects)) {
            return;
        }
        self::$config['objects'] = self::loadObjectConfig($config);
        // remove x-operations etc. from object config
        $fieldlist = ['objectid', 'name', 'label', 'module_id', 'itemtype', 'datastore', 'properties'];
        $allowed = array_flip($fieldlist);
        self::$objects = [];
        foreach (self::$config['objects'] as $name => $item) {
            $item = array_intersect_key($item, $allowed);
            self::$objects[(string) $name] = $item;
        }
        $context = $this->getContext();
        // set timer in RestAPI handler
        $context->handler->setTimer('objects');
    }

    /**
     * Summary of loadObjectConfig
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function loadObjectConfig($config = [])
    {
        $configFile = sys::varpath() . '/cache/api/restapi_objects.json';
        if (empty($config) && file_exists($configFile)) {
            $contents = file_get_contents($configFile);
            $config = json_decode($contents, true);
        }
        if (!empty($config['objects'])) {
            return $config['objects'];
        }
        return self::getDefaultObjects();
    }

    /**
     * Summary of getDefaultObjects
     * @return array<string, mixed>
     */
    public static function getDefaultObjects()
    {
        $fieldlist = ['objectid', 'name', 'label', 'module_id', 'itemtype', 'datastore', 'properties'];
        $allowed = array_flip($fieldlist);
        $object = 'objects';
        $params = ['name' => $object, 'fieldlist' => $fieldlist];
        $objectlist = DataObjectFactory::getObjectList($params);
        $objects = $objectlist->getItems();
        // @todo add x-operations etc. to object config
        $default = [];
        foreach ($objects as $itemid => $item) {
            if ($item['datastore'] !== 'dynamicdata') {
                continue;
            }
            $item = array_intersect_key($item, $allowed);
            $default[$item['name']] = $item;
        }
        return $default;
    }
}
