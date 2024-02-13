<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

/**
 * Move static methods from DataObjectMaster to DataObjectFactory
 */
class DataObjectFactory extends xarObject
{
    /**
     * Class method to retrieve information about all DataObjects
     *
     * @param array<string, mixed> $args
     * @return array<mixed> of object definitions
    **/
    public static function &getObjects(array $args = [])
    {
        extract($args);
        $dbconn = xarDB::getConn();
        xarMod::loadDbInfo('dynamicdata', 'dynamicdata');
        $xartable =  xarDB::getTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $bindvars = [];
        xarLog::message("DB: query in getObjects", xarLog::LEVEL_INFO);
        $query = "SELECT id,
                         name,
                         label,
                         module_id,
                         itemtype,
                         urlparam,
                         maxid,
                         config,
                         isalias
                  FROM $dynamicobjects ";
        if(isset($moduleid)) {
            $query .= "WHERE module_id = ?";
            $bindvars[] = $moduleid;
        }
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, xarDB::FETCHMODE_NUM);

        $objects = [];
        while ($result->next()) {
            $info = [];
            // @todo this depends on fetchmode being numeric
            [
                $info['objectid'], $info['name'], $info['label'],
                $info['moduleid'], $info['itemtype'],
                $info['urlparam'], $info['maxid'], $info['config'],
                $info['isalias']
            ] = $result->fields;
            $objects[$info['objectid']] = $info;
        }
        $result->close();
        return $objects;
    }

    /**
     * Class method to retrieve information about a Dynamic Object
     *
     * @param array<string, mixed> $args
     * with
     *     $args['objectid'] id of the object you're looking for, OR
     *     $args['name'] name of the object you're looking for, OR
     * @return array<mixed>|null containing the name => value pairs for the object
     * @todo when we had a constructor which was more passive, this could be non-static. (cheap construction is a good rule of thumb)
    **/
    public static function getObjectInfo(array $args = [])
    {
        if (!isset($args['objectid']) && (!isset($args['name']))) {
            throw new Exception(xarML('Cannot get object information without an objectid or a name'));
        }

        $cacheKey = 'DynamicData.ObjectInfo';
        if (!empty($args['name'])) {
            $infoid = $args['name'];
        } elseif (!empty($args['objectid'])) {
            $infoid = (int)$args['objectid'];
        } else {
            if (empty($args['moduleid'])) {
                // try to get the current module from elsewhere
                $args = DataObjectDescriptor::getModID($args);
            }
            if (empty($args['itemtype'])) {
                // set default itemtype
                $args['itemtype'] = 0;
            }
            $infoid = $args['moduleid'].':'.$args['itemtype'];
        }
        if(xarCoreCache::isCached($cacheKey, $infoid)) {
            return xarCoreCache::getCached($cacheKey, $infoid);
        }

        $dbconn = xarDB::getConn();
        xarMod::loadDbInfo('dynamicdata', 'dynamicdata');
        $xartable =  xarDB::getTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $bindvars = [];
        xarLog::message('DD: query in getObjectInfo', xarLog::LEVEL_INFO);
        $query = "SELECT id,
                         name,
                         label,
                         module_id,
                         itemtype,
                         class,
                         filepath,
                         urlparam,
                         maxid,
                         config,
                         access,
                         datastore,
                         sources,
                         relations,
                         objects,
                         isalias
                  FROM $dynamicobjects ";
        if (!empty($args['name'])) {
            $query .= " WHERE name = ? ";
            $bindvars[] = $args['name'];
        } elseif (!empty($args['objectid'])) {
            $query .= " WHERE id = ? ";
            $bindvars[] = (int) $args['objectid'];
        } else {
            $query .= " WHERE module_id = ?
                          AND itemtype = ? ";
            $bindvars[] = (int) $args['moduleid'];
            $bindvars[] = (int) $args['itemtype'];
        }

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if(!$result->first()) {
            return null;
        }
        $info = [];
        [
            $info['objectid'], $info['name'], $info['label'],
            $info['moduleid'], $info['itemtype'],
            $info['class'], $info['filepath'],
            $info['urlparam'], $info['maxid'],
            $info['config'],
            $info['access'],
            $info['datastore'],
            $info['sources'],
            $info['relations'],
            $info['objects'],
            $info['isalias']
        ] = $result->fields;
        $result->close();

        xarCoreCache::setCached($cacheKey, $info['objectid'], $info);
        xarCoreCache::setCached($cacheKey, $info['name'], $info);
        return $info;
    }

    /**
     * Summary of _getObjectInfo
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return mixed
     */
    protected static function _getObjectInfo(array $args = [])
    {
        if (!isset($args['objectid']) && (!isset($args['name']))) {
            throw new Exception(xarML('Cannot get object information without an objectid or a name'));
        }

        $cacheKey = 'DynamicData._ObjectInfo';
        if(isset($args['objectid']) && xarCoreCache::isCached($cacheKey, $args['objectid'])) {
            return xarCoreCache::getCached($cacheKey, $args['objectid']);
        }
        if(isset($args['name']) && xarCoreCache::isCached($cacheKey, $args['name'])) {
            return xarCoreCache::getCached($cacheKey, $args['name']);
        }

        sys::import('modules.dynamicdata.xartables');
        xarDB::importTables(dynamicdata_xartables());
        $xartable =  xarDB::getTables();
        sys::import('xaraya.structures.query');
        $q = new Query();

        $q->addtable($xartable['dynamic_objects'], 'o');
        $q->addtable($xartable['dynamic_properties'], 'p');
        $q->leftjoin('o.id', 'p.object_id');
        $q->addfield('o.id AS object_id');
        $q->addfield('o.name AS object_name');
        $q->addfield('o.label AS object_label');
        $q->addfield('o.module_id AS object_module_id');
        $q->addfield('o.itemtype AS object_itemtype');
        $q->addfield('o.class AS object_class');
        $q->addfield('o.filepath AS object_filepath');
        $q->addfield('o.urlparam AS object_urlparam');
        $q->addfield('o.maxid AS object_maxid');
        $q->addfield('o.config AS object_config');
        $q->addfield('o.access AS object_access');
        $q->addfield('o.datastore AS object_datastore');
        $q->addfield('o.sources AS object_sources');
        $q->addfield('o.relations AS object_relations');
        $q->addfield('o.objects AS object_objects');
        $q->addfield('o.isalias AS object_isalias');
        if (isset($args['objectid'])) {
            $q->eq('o.id', $args['objectid']);
        } else {
            $q->eq('o.name', $args['name']);
        }
        $q->addfield('p.id AS id');
        $q->addfield('p.name AS name');
        $q->addfield('p.label AS label');
        $q->addfield('p.type AS type');
        $q->addfield('p.defaultvalue AS defaultvalue');
        $q->addfield('p.source AS source');
        $q->addfield('p.translatable AS translatable');
        $q->addfield('p.status AS status');
        $q->addfield('p.seq AS seq');
        $q->addfield('p.configuration AS configuration');
        $q->addfield('p.object_id AS _objectid');
        $q->setorder('p.seq');
        if (!$q->run()) {
            return false;
        }
        $result = $q->output();
        $row = $q->row();
        if (!empty($row)) {
            xarCoreCache::setCached($cacheKey, $row['object_id'], $result);
            xarCoreCache::setCached($cacheKey, $row['object_name'], $result);
        }
        return $result;
    }

    /**
     * Class method to flush the variable cache in all scopes for a particular object definition
     *
     * @param array<string, mixed> $args
     * with
     *     $args['objectid'] id of the object you're looking for, and/or
     *     $args['name'] name of the object you're looking for
     * @return void
    **/
    public static function flushVariableCache($args = [])
    {
        // check if variable caching is actually enabled at all...
        if (!xarCache::isVariableCacheEnabled()) {
            return;
        }
        // get the missing object information
        if (empty($args['name']) || empty($args['objectid'])) {
            $args = static::getObjectInfo($args);
        }
        // flush the variable cache in all scopes
        $scopes = ['DataObject', 'DataObjectList'];
        if (!empty($args['name'])) {
            foreach ($scopes as $scope) {
                $cacheKey = static::getVariableCacheKey($scope, ['name' => $args['name']]);
                if (!empty($cacheKey)) {
                    xarVariableCache::delCached($cacheKey);
                }
            }
        }
        if (!empty($args['objectid'])) {
            foreach ($scopes as $scope) {
                $cacheKey = static::getVariableCacheKey($scope, ['objectid' => $args['objectid']]);
                if (!empty($cacheKey)) {
                    xarVariableCache::delCached($cacheKey);
                }
            }
        }
    }

    /**
     * Class method to get the variable cache key in a certain scope for a particular object definition
     *
     * @param string $scope
     * @param array<string, mixed> $args
     * with
     *     $args['objectid'] id of the object you're looking for, or
     *     $args['name'] name of the object you're looking for
     * @return mixed cacheKey if it can be cached, or null if not
    **/
    public static function getVariableCacheKey($scope, $args = [])
    {
        // check if variable caching is actually enabled at all...
        if (!xarCache::isVariableCacheEnabled()) {
            return;
        }
        if (empty($scope)) {
            throw new Exception(xarML('Cannot get variable cache key without a scope'));
        }
        if (empty($args['objectid']) && empty($args['name'])) {
            throw new Exception(xarML('Cannot get object information without an objectid or a name'));
        }
        $name = '';
        if (!empty($args['name'])) {
            $scope .= '.ByName';
            //$cacheKey = xarCache::getVariableKey($scope, $args['name']);
            $name = $args['name'];
            unset($args['name']);
        } elseif (!empty($args['objectid'])) {
            $scope .= '.ById';
            //$cacheKey = xarCache::getVariableKey($scope, $args['objectid']);
            $name = $args['objectid'];
            unset($args['objectid']);
        }
        // Note: this is supposed to be about caching a DataObject or DataObjectList variable before we do getItem(), getItems() etc.
        // we'll set itemid back after getting the object variable from cache if necessary...
        // CHECKME: any *tricky* objects relying on itemid being set at creation time to affect properties etc. should perhaps rethink their approach :-)
        if (isset($args['itemid'])) {
            unset($args['itemid']);
        }
        if (empty($args)) {
            xarLog::message('DataObjectFactory::getVariableCacheKey: ' . $scope . '(' . $name . ')', xarLog::LEVEL_INFO);
            $cacheKey = xarCache::getVariableKey($scope, $name);
        } else {
            xarLog::message('DataObjectFactory::getVariableCacheKey: TODO ' . $scope . '(' . $name . ') with ' . json_encode($args), xarLog::LEVEL_INFO);
            // TODO: any remaining arguments should *not* affect the object creation itself if we rehydrate correctly afterwards, but we'll play it safe for now...
            //$hash = md5(serialize($args));
            //$name .= '-' . $hash;
            //$cacheKey = xarCache::getVariableKey($scope, $name);
            $cacheKey = null;
        }
        return $cacheKey;
    }

    /**
     * Class method to retrieve a particular object definition, with sub-classing
     * (= the same as creating a new Dynamic Object with itemid = null)
     *
     * @param array<string, mixed> $args
     * with
     *     $args['objectid'] id of the object you're looking for, or
     *     $args['name'] name of the object you're looking for
     *     $args['class'] optional classname (e.g. <module>_DataObject)
     * @return DataObject|null the requested object definition
    **/
    public static function getObject(array $args = [])
    {
        // Once autoload is enabled this block can be moved beyond the cache retrieval code
        if (!empty($args['table']) && empty($args['objectid']) && empty($args['name'])) {
            sys::import('modules.dynamicdata.class.objects.virtual');
            $descriptor = new TableObjectDescriptor($args);
            return new DataObject($descriptor);
        }
        $info = static::_getObjectInfo($args);
        // If we have no such object, just return null for now
        if (empty($info)) {
            return null;
        }
        $data = [];
        // The info method calls an entry for each of the object's properties. We only need one
        $current = current($info);
        foreach ($current as $key => $value) {
            if (strpos($key, 'object_') === 0) {
                $data[substr($key, 7)] = $value;
            }
        }
        $data = array_merge($args, $data);
        // Make sure the class for this object is loaded
        if(!empty($data['filepath']) && ($data['filepath'] != 'auto')) {
            include_once(sys::code() . $data['filepath']);
        } else {
            sys::import('modules.dynamicdata.class.objects.base');
        }

        /* with autoload and variable caching activated */
        // CHECKME: that actually checked if we can do output caching in object ui handlers etc.
        // Identify the variable by its arguments here
        //$hash = md5(serialize($args));
        // Get a cache key for this variable if it's suitable for variable caching
        //$cacheKey = xarCache::getObjectKey('DataObject', $hash);
        // CHECKME: this is supposed to be about caching a DataObject variable before we do getItem() etc.

        // Do we allow caching?
        if (xarCore::isLoaded(xarCore::SYSTEM_MODULES) && xarModVars::get('dynamicdata', 'caching')) {
            $cacheKey = static::getVariableCacheKey('DataObject', $args);
            // Check if the variable is cached
            if (!empty($cacheKey) && xarVariableCache::isCached($cacheKey)) {
                // Return the cached variable
                $object = xarVariableCache::getCached($cacheKey);
                if (!empty($args['itemid'])) {
                    $object->itemid = $args['itemid'];
                }
                return $object;
            }
        }

        $data['propertyargs'] = & $info;

        // Create the object if it was not in cache
        xarLog::message("DataObjectFactory::getObject: Getting a new object " . $data['class'], xarLog::LEVEL_INFO);

        // When using namespaces, 'class' must contain the fully qualified class name: __NAMESPACE__.'\MyClass'
        $descriptor = new DataObjectDescriptor($data);
        $object = new $data['class']($descriptor);

        /* with autoload and variable caching activated */
        // Set the variable in cache
        if (!empty($cacheKey)) {
            xarVariableCache::setCached($cacheKey, $object);
        }
        return $object;
    }

    /**
     * Class method to retrieve a particular object list definition, with sub-classing
     * (= the same as creating a new Dynamic Object List)
     *
     * @param array<string, mixed> $args
     * with
     *     $args['objectid'] id of the object you're looking for, or
     *     $args['name'] name of the object you're looking for
     *     $args['class'] optional classname (e.g. <module>_DataObject[_List])
     * @return DataObjectList|null the requested object definition
     * @todo   get rid of the classname munging, use typing
    **/
    public static function getObjectList(array $args = [])
    {
        // Once autoload is enabled this block can be moved beyond the cache retrieval code
        // Complete the info if this is a known object
        if (!empty($args['table']) && empty($args['objectid']) && empty($args['name'])) {
            sys::import('modules.dynamicdata.class.objects.virtual');
            $descriptor = new TableObjectDescriptor($args);
            return new DataObjectList($descriptor);
        }
        $info = static::_getObjectInfo($args);
        if (empty($info)) {
            $identifier = '';
            if (isset($args['name'])) {
                $identifier = xarML("the name is '#(1)'", $args['name']);
            }
            if (isset($args['objectid'])) {
                $identifier = xarML('the objectid is #(1)', $args['objectid']);
            }
            throw new Exception(xarML('Unable to create an object where #(1)', $identifier));
        }
        $data = [];
        // The info method calls an entry for each of the object's properties. We only need one
        $current = current($info);
        foreach ($current as $key => $value) {
            if (strpos($key, 'object_') === 0) {
                $data[substr($key, 7)] = $value;
            }
        }
        $data = $args + $data;
        // Make sure the class for this object is loaded
        sys::import('modules.dynamicdata.class.objects.list');
        $class = 'DataObjectList';
        if(!empty($data['filepath']) && ($data['filepath'] != 'auto')) {
            include_once(sys::code() . $data['filepath']);
        }

        /* with autoload and variable caching activated */
        // CHECKME: that actually checked if we can do output caching in object ui handlers etc.
        // Identify the variable by its arguments here
        //$hash = md5(serialize($args));
        // Get a cache key for this variable if it's suitable for variable caching
        //$cacheKey = xarCache::getObjectKey('DataObjectList', $hash);
        // CHECKME: this is supposed to be about caching a DataObjectList variable before we do getItems() etc.

        // Do we allow caching?
        if (xarCore::isLoaded(xarCore::SYSTEM_MODULES) && xarModVars::get('dynamicdata', 'caching')) {
            $cacheKey = static::getVariableCacheKey('DataObjectList', $args);
            // Check if the variable is cached
            if (!empty($cacheKey) && xarVariableCache::isCached($cacheKey)) {
                // Return the cached variable
                $object = xarVariableCache::getCached($cacheKey);
                return $object;
            }
        }
        // FIXME: clean up redundancy between self:getObjectInfo($args) and new DataObjectDescriptor($args)
        $data['propertyargs'] = & $info;

        // When using namespaces, 'class' must contain the fully qualified class name: __NAMESPACE__.'\MyClass'
        if(!empty($data['class'])) {
            if(class_exists($data['class'] . 'List')) {
                // this is a generic classname for the object, list and interface
                $class = $data['class'] . 'List';
            } elseif(class_exists($data['class']) && method_exists($data['class'], 'getItems')) {
                // this is a specific classname for the list
                $class = $data['class'];
            }
        }
        $descriptor = new DataObjectDescriptor($data);

        // here we can use our own classes to retrieve this
        $object = new $class($descriptor);

        /* with autoload and variable caching activated */
        // Set the variable in cache
        if (!empty($cacheKey)) {
            xarVariableCache::setCached($cacheKey, $object);
        }
        return $object;
    }

    /**
     * Class method to retrieve a particular object interface definition, with sub-classing
     * (= the same as creating a new Dynamic Object User Interface)
     *
     * @param array<string, mixed> $args
     * with
     *     $args['objectid'] id of the object you're looking for, or
     *     $args['name'] name of the object you're looking for, or
     *     $args['moduleid'] module id of the object to retrieve +
     *     $args['itemtype'] item type of the object to retrieve
     *     $args['class'] optional classname (e.g. <module>_DataObject[_Interface])
     * @return object the requested object definition
     * @todo  get rid of the classname munging
    **/
    public static function &getObjectInterface(array $args = [])
    {
        sys::import('modules.dynamicdata.class.userinterface');

        $class = 'DataObjectUserInterface';
        // When using namespaces, 'class' must contain the fully qualified class name: __NAMESPACE__.'\MyClass'
        if(!empty($args['class'])) {
            if(class_exists($args['class'] . 'UserInterface')) {
                // this is a generic classname for the object, list and interface
                $class = $args['class'] . 'UserInterface';
            } elseif(class_exists($args['class'] . 'Interface')) { // deprecated
                // this is a generic classname for the object, list and interface
                $class = $args['class'] . 'Interface';
            } elseif(class_exists($args['class'])) {
                // this is a specific classname for the interface
                $class = $args['class'];
            }
        }
        // here we can use our own classes to retrieve this
        $object = new $class($args);
        return $object;
    }

    /**
     * Summary of isObject
     * @param array<string, mixed> $args
     * @return bool
     */
    public static function isObject(array $args)
    {
        $info = static::_getObjectInfo($args);
        return !empty($info);
    }

    /**
     * Class method to create a new type of Dynamic Object
     *
     * @param array<string, mixed> $args
     * with
     *     $args['objectid'] id of the object you want to create (optional)
     *     $args['name'] name of the object to create
     *     $args['label'] label of the object to create
     *     $args['moduleid'] module id of the object to create
     *     $args['itemtype'] item type of the object to create
     *     $args['urlparam'] URL parameter to use for the object items (itemid, exid, aid, ...)
     *     $args['maxid'] for purely dynamic objects, the current max. itemid (for import only)
     *     $args['config'] some configuration for the object (free to define and use)
     *     $args['isalias'] flag to indicate whether the object name is used as alias for short URLs
     *     $args['class'] optional classname (e.g. <module>_DataObject)
     * @return integer object id of the created item
    **/
    public static function createObject(array $args = [])
    {
        // TODO: if we extend dobject classes then probably we need to put the class name here
        $object = static::getObject(['name' => 'objects']);

        // Create specific part
        $descriptor = new DataObjectDescriptor($args);
        $objectid = $object->createItem($descriptor->getArgs());
        $classname = get_class($object);
        xarLog::message("Creating an object of class " . $classname . ". Objectid: " . $objectid . ", module: " . $args['moduleid'] . ", itemtype: " . $args['itemtype'], xarLog::LEVEL_INFO);
        unset($object);
        return $objectid;
    }

    /**
     * Summary of updateObject
     * @param array<string, mixed> $args
     * @return int|mixed
     */
    public static function updateObject(array $args = [])
    {
        $object = static::getObject(['name' => 'objects']);

        // Update specific part
        $itemid = $object->getItem(['itemid' => $args['objectid']]);
        if(empty($itemid)) {
            return null;
        }
        xarLog::message("Updating an object " . $object->name . ". Objectid: " . $itemid, xarLog::LEVEL_INFO);
        $itemid = $object->updateItem($args);
        unset($object);
        return $itemid;
    }

    /**
     * Summary of deleteObject
     * @param array<string, mixed> $args
     * @throws \BadParameterException
     * @return bool
     */
    public static function deleteObject(array $args = [])
    {
        $descriptor = new DataObjectDescriptor($args);
        $args = $descriptor->getArgs();

        // Last stand against wild hooks and other excesses
        if($args['objectid'] < 5) {
            $msg = 'You cannot delete the DynamicDat classes';
            throw new BadParameterException(null, $msg);
        }

        // Do direct queries here, for speed
        xarMod::load('dynamicdata');
        $tables =  xarDB::getTables();

        sys::import('xaraya.structures.query');
        // TODO: delete all the (dynamic ?) data for this object

        xarLog::message("Deleting an object with ID " . $args['objectid'], xarLog::LEVEL_INFO);

        // Delete all the properties of this object
        $q = new Query('DELETE', $tables['dynamic_properties']);
        $q->eq('object_id', $args['objectid']);
        if (!$q->run()) {
            return false;
        }

        // Delete the object itself
        $q = new Query('DELETE', $tables['dynamic_objects']);
        $q->eq('id', $args['objectid']);
        if (!$q->run()) {
            return false;
        }

        return true;
    }

    /**
     * Get a module's itemtypes
     *
     * @uses Xaraya\DataObject\UserApi::getModuleItemTypes()
     * @param array<string, mixed> $args
     * with
     *     int    args[moduleid]
     *     bool   args[native]
     *     bool   args[extensions]
     * @deprecated 2.4.1 use Xaraya\DataObject\UserApi::getModuleItemTypes() instead
     * @return array<mixed>
     */
    public static function getModuleItemTypes(array $args = [])
    {
        sys::import('modules.dynamicdata.class.userapi');
        extract($args);
        /** @var int $moduleid */
        // Argument checks
        if (empty($moduleid)) {
            throw new BadParameterException('moduleid');
        }
        $native ??= true;
        $extensions ??= true;

        return Xaraya\DataObject\UserApi::getModuleItemTypes($moduleid, $native, $extensions);
    }
}
