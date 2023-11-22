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
 * @todo How far do we want to go with DD as ORM tool + Data Mapper vs. Active Record pattern
 * http://www.terrenceryan.com/blog/post.cfm/coldfusion-9-orm-data-mapper-versus-active-record
 * http://madgeek.com/Articles/ORMapping/EN/mapping.htm
 * http://www.agiledata.org/essays/mappingObjects.html
 * http://www.doctrine-project.org/documentation/manual/2_0/en
 */

sys::import('modules.dynamicdata.class.objects.descriptor');
sys::import('modules.dynamicdata.class.objects.factory');
sys::import('xaraya.datastores.factory');
use Xaraya\DataObject\DataStores\DataStoreFactory;
use Xaraya\DataObject\DataStores\IBasicDataStore;

class DataObjectMaster extends xarObject
{
    /**
     * These constants are added for convenience. They are currently not being used
     * TODO: Remove the ones we don't need. Probably the last 3 at least
     */
    public const MODULE_ID                 = 182;
    public const ITEMTYPE_OBJECTS          = 0;
    public const ITEMTYPE_PROPERTIES       = 1;
    public const OBJECTID_OBJECTS          = 1;
    public const OBJECTID_PROPERTIES       = 2;
    public const PROPTYPE_ID_MODULE        = 19;
    public const PROPTYPE_ID_ITEMTYPE      = 20;
    public const PROPTYPE_ID_ITEMID        = 21;

    public $descriptor  = null;      // descriptor object of this class

    public $objectid    = null;         // system id of the object in this installation
    public $name        = null;         // name of the object
    public $label       = null;         // label as shown on screen

    public $moduleid    = null;
    public $itemtype    = 0;

    public $urlparam    = 'itemid';
    public $maxid       = 0;
    public $access      = 'a:0:{}';       // the access parameters for this DD object
    public $access_rules;                 // the exploded access parameters for this DD object
    public $config      = 'a:0:{}';       // the configuration parameters for this DD object
    public $configuration;                // the configuration parameters for this DD object
    /** @var string|IBasicDataStore */
    public $datastore   = '';             // the datastore for the DD object
    public $dbConnIndex = 0;              // the connection index of the database if different from Xaraya DB
    public $dbConnArgs  = null;           // the connection arguments for the database if different from Xaraya DB
    public $datasources = [];        // the db source tables of this object
    public $dataquery;                    // the initialization query of this obect
    public $sources     = 'a:0:{}';		  // the source tables of this object (relational datastore)
    public $relations   = 'a:0:{}';		  // the table relations of this object (relational datastore)
    public $objects     = 'a:0:{}';		  // the parent child object relations of this object (relational datastore)

    public $class       = 'DataObject'; // the class name of this DD object
    public $filepath    = 'auto';       // the path to the class of this DD object (can be empty or 'auto' for DataObject)
    /** @var array<string,DataProperty> $properties */
    public $properties  = [];      // list of properties for the DD object
    public $fieldlist   = [];      // array of properties to be displayed
    public $fieldsubset = [];      // subset of fields within the properties to be displayed (dot notation for mongodb etc.)
    public $fieldorder  = [];      // displayorder for the properties
    public $fieldprefix = '';           // prefix to use in field names etc.
    // CHECKME: should be overridden by DataObjectList and DataObject to exclude DISPLAYONLY resp. VIEWONLY !?
    public $status      = 65;           // inital status is active and can add/modify
    public $propertyprefix   = 'dd_';   // the prefix used for automatic designations of property names and IDs in templates
    public $anonymous   = 0;            // if true forces display of names of properties instead of dd_xx designations
    public $where       = '';           // where clause for the object dataquery

    public $layout = 'default';         // optional layout inside the templates
    public $template = '';              // optional sub-template, e.g. user-objectview-[template].xt (defaults to the object name)
    public $tplmodule = 'dynamicdata';  // optional module where the object templates reside (defaults to 'dynamicdata')
    public $linktype = 'user';          // optional link type for use in getActionURL() (defaults to 'user' for module URLs, 'object' for object URLs)
    public $linkfunc = 'display';       // optional link function for use in getActionURL() (defaults to 'display', unused for object URLs)
    private $cached_urls  = [];    // cached URLs for use in getActionURL()

    public $primary = null;             // primary key is item id (or objectid in the case of the objects object)
    public $secondary = null;           // secondary key could be item type (e.g. for articles)
    public $filter = false;             // set this true to automatically filter by current itemtype on secondary key
    public $upload = false;             // flag indicating if this object has some property that provides file upload
    public $propertyargs;
    public $visibility = 'public';      // hint to DD whether this is a private object for a particular module, a protected object
    // which preferably shouldn't be messed with, or a public object that any admin can modify

    // TODO: validate this way of working in trickier situations
    public $hookvalues    = [];    // updated hookvalues for API actions
    public $hookoutput    = [];    // output from each hook module for GUI actions
    public $hooktransform = [];    // list of names for the properties to be transformed by the transform hook

    // CHECKME: this is no longer needed
    private $hooklist     = null;       // list of hook modules (= observers) to call
    private $hookscope    = 'item';     // the hook scope for dataobject (for now)

    public $links         = null;       // links between objects

    public $isgrouped     = 0;          // indicates that we have operations (COUNT, SUM, etc.) on properties

    public $catid;
    public $table;
    private $conditions;

    /**
     * Default constructor to set the object variables, retrieve the dynamic properties
     * and get the corresponding data stores for those properties
     *
     * @param array<string, mixed> $args
     * with
     *     $args['objectid'] id of the object you're looking for, or
     *     $args['moduleid'] module id of the object to retrieve +
     *     $args['itemtype'] item type of the object to retrieve, or
     *
     *     $args['fieldlist'] optional list of properties to use, or
     *     $args['status'] optional status of the properties to use
     *     $args['allprops'] skip disabled properties by default
     * @todo  This does too much, split it up
    **/

    public function toArray(array $args = [])
    {
        $properties = $this->getPublicProperties();
        foreach ($properties as $key => $value) {
            if (!isset($args[$key])) {
                $args[$key] = $value;
            }
        }
        //FIXME where do we need to define the modname best?
        if (!empty($args['moduleid'])) {
            $args['modname'] = xarMod::getName($args['moduleid']);
        }
        return $args;
    }

    public function loader(DataObjectDescriptor $descriptor)
    {
        $this->descriptor = $descriptor;
        $descriptor->refresh($this);

        xarMod::loadDbInfo('dynamicdata', 'dynamicdata');

        // use the object name as default template override (*-*-[template].x*)
        if(empty($this->template) && !empty($this->name)) {
            $this->template = $this->name;
        }

        // get the properties defined for this object
        sys::import('modules.dynamicdata.class.properties.master');
        foreach ($this->propertyargs as $row) {
            DataPropertyMaster::addProperty($row, $this);
        }
        unset($this->propertyargs);

        // Make sure we have a primary key
        foreach ($this->properties as $property) {
            if (DataPropertyMaster::isPrimaryType($property->type)) {
                $this->primary = $property->name;
            }
        }

        // create the list of fields, filtering where necessary
        $this->fieldlist = $this->setupFieldList($this->fieldlist, $this->status);

        // Set the configuration parameters
        // CHECKME: is this needed?
        if ($descriptor->exists('config') && !empty($descriptor->get('config'))) {
            try {
                $configargs = unserialize($descriptor->get('config'));
                foreach ($configargs as $key => $value) {
                    if (!empty($key)) {
                        $this->{$key} = $value;
                    }
                }
                $this->configuration = $configargs;
            } catch (Exception $e) {
            }
        }

        sys::import('xaraya.structures.query');
        $this->dataquery = new Query();
        if ($descriptor->exists('datastore')) {
            $this->datastore = $descriptor->get('datastore');
            if ($this->datastore == 'relational' || $this->datastore == 'external') {
                // process $this->dbConnArgs first - this could change datastore to external
                $this->parseDbConnArgs();
            }
            if ($this->datastore == 'relational') {
                // We start from scratch
                if (!empty($this->dbConnIndex)) {
                    $this->checkDbConnection();
                    $this->dataquery->setDbConnIndex($this->dbConnIndex);
                }
                $this->dataquery->cleartables();
                $this->assembleQuery($this);
            }
        } else {
            $this->datastore = 'dynamicdata';
        }

        if (isset($this->configuration['where'])) {
            $conditions = $this->setWhere($this->configuration['where']);
            $this->dataquery->addconditions($conditions);
            // Having added the conditions to the dataquery, remove them from the $where property
            $this->where = '';
        }

        // Always mark the internal DD objects as 'private' (= items 1-3 in xar_dynamic_objects, see xarinit.php)
        if (!empty($this->objectid) && $this->objectid == 1 && $this instanceof DataObject && !empty($this->itemid) && $this->itemid <= 3) {
            $this->visibility = 'private';
        }

        // build the list of relevant data stores where we'll get/set our data
        try {
            $this->getDataStore();
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        // Explode the configuration
        try {
            $this->configuration = unserialize($this->config);
        } catch (Exception $e) {
        }

        // Explode the access rules
        try {
            $this->access_rules = unserialize($this->access);
        } catch (Exception $e) {
        }

        return true;
    }

    private function propertysource($sourcestring, $object, $prefix = false)
    {
        $parts = explode('.', $sourcestring);
        if (!isset($parts[1])) {
            throw new Exception(xarML('Bad property definition'));
        }
        $parts[0] = trim($parts[0]);
        if ($parts[0] == 'this' || $parts[0] == $object->name) {
            if ($prefix) {
                return $object->name . "_" . $object->properties[$parts[1]]->source;
            } else {
                return $object->properties[$parts[1]]->source;
            }
        } else {
            $foreignobject = DataObjectFactory::getObject(['name' => $parts[0]]);
            $foreignstore = $foreignobject->properties[$parts[1]]->source;
            $foreignparts = explode('.', $foreignstore);
            $foreignconfiguration = $foreignobject->datasources;
            if (!isset($foreignconfiguration[$foreignparts[0]])) {
                throw new Exception(xarML('Bad foreign datasource'));
            }
            $foreigntable = $foreignconfiguration[$foreignparts[0]];
            // Support simple array form
            if (is_array($foreigntable)) {
                $foreigntable = current($foreigntable);
            }

            // Add the foreign table to this object's query
            $object->dataquery->addtable($foreigntable, $parts[0] . "_" . $foreignparts[0]);
            return $parts[0] . "_" . $foreignstore;
        }
    }

    /**
     * Show an filter form for this object
     */
    public function showFilterForm(array $args = [])
    {
        $args = $args + $this->getPublicProperties();
        if (isset($args['fieldprefix'])) {
            $this->setFieldPrefix($args['fieldprefix']);
        }

        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if(!empty($args['preview'])) {
            $this->checkInput();
        }

        if (!empty($args['fieldlist']) && !is_array($args['fieldlist'])) {
            $args['fieldlist'] = explode(',', $args['fieldlist']);
            if (!is_array($args['fieldlist'])) {
                throw new Exception('Badly formed fieldlist attribute');
            }
        }

        if(count($args['fieldlist']) > 0) {
            $fields = $args['fieldlist'];
        } else {
            $fields = array_keys($this->properties);
        }

        $args['properties'] = [];
        foreach($fields as $name) {
            if(!isset($this->properties[$name])) {
                continue;
            }

            if(($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED)
            || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)
            || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY)) {
                continue;
            }

            $args['properties'][$name] = & $this->properties[$name];
        }

        // pass some extra template variables for use in BL tags, API calls etc.
        //FIXME: check these
        $args['isprimary'] = !empty($this->primary);
        $args['catid'] = !empty($this->catid) ? $this->catid : null;
        $args['object'] = $this;
        return xarTpl::object($args['tplmodule'], $args['template'], 'showfilterform', $args);
    }

    /**
     * Get and set for field prefixes
     */
    public function getFieldPrefix()
    {
        return $this->fieldprefix;
    }

    public function setFieldPrefix($prefix)
    {
        $this->fieldprefix = $prefix;
        foreach (array_keys($this->properties) as $property) {
            $this->properties[$property]->_fieldprefix = $prefix;
        }
        return true;
    }

    public function setFieldList($fieldlist = [], $status = [])
    {
        if (empty($fieldlist)) {
            $fieldlist = $this->setupFieldList();
        }
        if (!is_array($fieldlist)) {
            try {
                $fieldlist = explode(',', $fieldlist);
            } catch (Exception $e) {
                throw new Exception(xarML('Badly formed fieldlist attribute'));
            }
        }
        $this->fieldlist = [];
        if (!empty($status)) {
            // Make sure we have an array
            if (!is_array($status)) {
                $status = [$status];
            }
            foreach($fieldlist as $field) {
                $field = trim($field);
                // Ignore those disabled AND those that don't exist
                if(isset($this->properties[$field]) && in_array($this->properties[$field]->getDisplayStatus(), $status)) {
                    $this->fieldlist[$this->properties[$field]->id] = $this->properties[$field]->name;
                }
            }
        } else {
            foreach($fieldlist as $field) {
                $field = trim($field);
                // Ignore those disabled AND those that don't exist
                if(isset($this->properties[$field]) && ($this->properties[$field]->getDisplayStatus() != DataPropertyMaster::DD_DISPLAYSTATE_DISABLED)) {
                    $this->fieldlist[$this->properties[$field]->id] = $this->properties[$field]->name;
                }
            }
        }
        return true;
    }

    public function getFieldList($force = 0)
    {
        if ($force) {
            $this->fieldlist = $this->setupFieldList();
        } else {
            if (empty($this->fieldlist)) {
                $this->fieldlist = $this->setupFieldList();
            }
        }
        return $this->fieldlist;
    }

    private function setupFieldList($fieldlist = [], $status = [])
    {
        $fields = [];
        if(!empty($fieldlist)) {
            if (!is_array($fieldlist)) {
                $fieldlist = explode(',', $fieldlist);
            }
            // Note: we already filter out field subsets in ui_handler view and display (and elsewhere?)
            foreach($fieldlist as $field) {
                // Ignore those disabled AND those that don't exist
                if(isset($this->properties[$field]) && ($this->properties[$field]->getDisplayStatus() != DataPropertyMaster::DD_DISPLAYSTATE_DISABLED)) {
                    $fields[$this->properties[$field]->id] = $this->properties[$field]->name;
                }
            }
        } else {
            if (!empty($status)) {
                // Make sure we have an array
                if (!is_array($status)) {
                    $status = [$status];
                }
                // we have a status: filter on it
                foreach($this->properties as $property) {
                    if(in_array($property->getDisplayStatus(), $status)) {
                        $fields[$property->id] = $property->name;
                    }
                }
            } else {
                // no status filter: return those that are not disabled
                // CHECKME: filter out DISPLAYONLY or VIEWONLY depending on the class we're in !
                sys::import('modules.dynamicdata.class.properties.master');
                if (method_exists($this, 'getItems')) {
                    $not_allowed_state = DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY;
                } else {
                    $not_allowed_state = DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY;
                }
                // Filter out properties with the state chosen above, and also the disabled properties
                foreach($this->properties as $property) {
                    if($property->getDisplayStatus() != DataPropertyMaster::DD_DISPLAYSTATE_DISABLED &&
                       $property->getDisplayStatus() != $not_allowed_state) {
                        $fields[$property->id] = $property->name;
                    }
                }
            }
        }
        return $fields;
    }

    /**
     * Set the display status of some properties
     */
    public function setDisplayStatus($fieldlist = [], $status = 1)
    {
        if(!empty($fieldlist)) {
            foreach($fieldlist as $field) {
                // Ignore those disabled AND those that don't exist
                if(isset($this->properties[$field])) {
                    $this->properties[$field]->setDisplayStatus($status);
                }
            }
        }
        return true;
    }

    /**
     * Check the DB connection index or use dbConnArgs to connect - for relational datastore
     * @return void
     */
    public function checkDbConnection()
    {
        // we already have a valid db connection index (internal)
        if (empty($this->dbConnIndex) || xarDB::hasConn($this->dbConnIndex)) {
            return;
        }
        // we have no db connection arguments to use
        if (empty($this->dbConnArgs)) {
            return;
        }
        // create a new db connection and get its index
        xarDB::newConn($this->dbConnArgs);
        $this->dbConnIndex = xarDB::getConnIndex();
    }

    /**
     * Parse DB connection arguments - for relational & external datastores
     * Note: this may set the datastore to 'external' if necessary, so call it before checkDbConnection()
     * Use json-encoded ["className","methodName"] format to invoke static method (if class is loaded)
     * @return mixed
     */
    public function parseDbConnArgs()
    {
        // if we already have an external db connection, e.g. from admin meta GUI function
        if (!empty($this->dbConnIndex) && !is_numeric($this->dbConnIndex)) {
            $this->datastore = 'external';
            return $this->dbConnArgs;
        }
        if (empty($this->dbConnArgs)) {
            return null;
        }
        if (is_string($this->dbConnArgs)) {
            $this->dbConnArgs = json_decode($this->dbConnArgs, true);
        }
        // Note: this assumes the class is (auto-)loaded
        if (is_callable($this->dbConnArgs)) {
            // we pass the current object as argument here, just in case...
            $args = call_user_func($this->dbConnArgs, $this);
            $this->dbConnArgs = $args;
        } elseif (!empty($this->dbConnArgs['databaseConfig'])) {
            sys::import('modules.dynamicdata.class.utilapi');
            // get existing database config
            try {
                [$module, $dbname] = explode('.', $this->dbConnArgs['databaseConfig']);
                $args = Xaraya\DataObject\UtilApi::getDatabaseDSN($dbname, $module);
                $this->dbConnArgs = $args;
            } catch (Exception $e) {
                // allow database connection failure later on when it's actually needed
                xarLog::message("DataObjectMaster::parseDbConnArgs: Invalid dbConnArgs - unable to create new db connection", xarLog::LEVEL_WARNING);
                return null;
            }
        } elseif (array_key_exists('databaseType', $this->dbConnArgs) || array_key_exists('external', $this->dbConnArgs)) {
            $args = $this->dbConnArgs;
        } else {
            // allow database connection failure later on when it's actually needed
            xarLog::message("DataObjectMaster::checkDbConnection: Invalid dbConnArgs - unable to create new db connection", xarLog::LEVEL_WARNING);
            return null;
        }
        // if we have an external db connection argument, the datastore is external
        if (!empty($this->dbConnArgs['external'])) {
            $this->datastore = 'external';
        }
        return $args;
    }

    /**
     * Get the data stores where the dynamic properties of this object are kept
     * @param bool $reset deprecated reset is now handled in dataquery instead of datastore
    **/
    public function getDataStore($reset = false)
    {
        switch ($this->datastore) {
            case 'relational': $this->addDataStore('relational', 'relational');
                break;
            case 'module_variables':
                try {
                    $firstproperty = reset($this->properties);
                    // FIXME: this needs a better design
                    $name = trim(substr($firstproperty->source, 17));
                    $this->addDataStore($name, 'modulevars');
                } catch (Exception $e) {
                    throw new Exception(xarML('Did not find a first property for module variable datastore'));
                }
                break;
            /**
            case 'hook':
                $this->addDataStore($this->name, 'hook');
                break;
             */
            case 'none':
                $this->addDataStore($this->name, 'none');
                break;
            case 'cache':
                $storage = $this->descriptor->get('cachestorage') ?? 'apcu';
                $this->addDataStore($this->name, 'cache', $storage);
                break;
            case 'external':
                $this->addDataStore($this->name, 'external');
                break;
            case 'dynamicdata':
                $this->addDataStore('_dynamic_data_', 'data');
                break;
        }
    }

    /**
     * Add a data store for this object
     *
     * @param string $name the name for the data store
     * @param string $type the type of data store (relational, data, hook, modulevars, cache, ...)
     * @param ?string $storage storageType for the cacheStorage in CachingDatastore (for cache only)
    **/
    public function addDataStore($name = '_dynamic_data_', $type = 'data', $storage = null)
    {
        // get the data store
        $this->datastore = DataStoreFactory::getDataStore($name, $type, $storage, $this->dbConnIndex, $this->dbConnArgs);

        // Pass along a reference to this object
        $this->datastore->object = $this;

        // for dynamic object lists, put a reference to the $itemids array in the data store
        if($this instanceof DataObjectList) {
            $this->datastore->_itemids = & $this->itemids;
        }
    }

    /**
     * Get the selected dynamic properties for this object
     * @return array<string,DataProperty>
    **/
    public function &getProperties($args = [])
    {
        $fields = [];
        if(!empty($args['fieldlist'])) {
            $fields = $this->getFieldList();
            $this->setFieldList($args['fieldlist']);
        }

        $properties = [];
        foreach($this->getFieldList() as $name) {
            if (isset($this->properties[$name])) {
                // Filter for state if one is passed
                if (!empty($args['status'])) {
                    if (is_array($args['status'])) {
                        if (!in_array($this->properties[$name]->getDisplayStatus(), $args['status'])) {
                            continue;
                        }
                    } else {
                        if ($this->properties[$name]->getDisplayStatus() != $args['status']) {
                            continue;
                        }
                    }
                }
                // Pass along a field prefix if there is one
                if (!empty($args['fieldprefix'])) {
                    $this->properties[$name]->_fieldprefix = $args['fieldprefix'];
                }
                $properties[$name] = &$this->properties[$name];
                // Pass along the directive of what property name to display
                if (isset($args['anonymous'])) {
                    $this->properties[$name]->anonymous = $args['anonymous'];
                }
            }
        }

        if(!empty($args['fieldlist'])) {
            $this->setFieldList($fields);
        }
        return $properties;
    }

    /**
     * Add a property for this object
     *
     * @param array<string, mixed> $args
     * with
     *     $args['name'] the name for the dynamic property (required)
     *     $args['type'] the type of dynamic property (required)
     *     $args['label'] the label for the dynamic property
     *     $args['source'] the source for the dynamic property
     *     $args['defaultvalue'] the default value for the dynamic property
     *     $args['status'] the input and display status for the dynamic property
     *     $args['seq'] the place in sequence this dynamic property appears in
     *     $args['configuration'] the configuration (serialized array) for the dynamic property
     *     $args['id'] the id for the dynamic property
     *
     * @todo why not keep the scope here and do this:
     *       $this->properties[$args['id']] = new Property($args); (with a reference probably)
    **/
    public function addProperty(array $args = [])
    {
        // TODO: find some way to have unique IDs across all objects if necessary
        if(!isset($args['id'])) {
            $args['id'] = count($this->properties) + 1;
        }
        sys::import('modules.dynamicdata.class.properties.master');
        DataPropertyMaster::addProperty($args, $this);
        return true;
    }

    /**
     * Modify a property for this object
     *
     * @param $property the property or its name (required)
     * @param $args an array of parameters that re to be changed
     *
    **/
    public function modifyProperty($property, array $args = [])
    {
        if (!is_object($property)) {
            // Check what we assume to be a name
            if (isset($this->properties[$property])) {
                $property = & $this->properties[$property];
            } else {
                $msg = xarML('Bad property name parameter for modifyProperty');
                throw new Exception($msg);
            }
        } else {
            // Check if this object is a property of this dataobject
            if (!isset($this->properties[$property->name])) {
                $msg = xarML('Bad property object parameter for modifyProperty');
                throw new Exception($msg);
            }
        }
        // Get the description of the property and add its args to those passed
        $args = $args + $property->descriptor->getArgs();
        // Get the value
        $value = $this->properties[$property->name]->value;
        // Remove the property we are changing;
        unset($this->properties[$property->name]);
        // Add a new property, like the old, but with the changes passed
        $this->addProperty($args);
        // Add the value to the new property
        $this->properties[$property->name]->value = $value;
        return true;
    }

    /**
     * Class method to retrieve information about all DataObjects
     *
     * @return array<mixed> of object definitions
     * @deprecated 2.4.1 use DataObjectFactory::getObjects()
    **/
    public static function getObjects(array $args = [])
    {
        return DataObjectFactory::getObjects($args);
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
     * @deprecated 2.4.1 use DataObjectFactory::getObjectInfo()
    **/
    public static function getObjectInfo(array $args = [])
    {
        return DataObjectFactory::getObjectInfo($args);
    }

    /**
     * Class method to flush the variable cache in all scopes for a particular object definition
     *
     * @param array<string, mixed> $args
     * with
     *     $args['objectid'] id of the object you're looking for, and/or
     *     $args['name'] name of the object you're looking for
     * @return void
     * @deprecated 2.4.1 use DataObjectFactory::flushVariableCache()
    **/
    public static function flushVariableCache($args = [])
    {
        DataObjectFactory::flushVariableCache($args);
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
     * @deprecated 2.4.1 use DataObjectFactory::getVariableCacheKey()
    **/
    public static function getVariableCacheKey($scope, $args = [])
    {
        return DataObjectFactory::getVariableCacheKey($scope, $args);
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
     * @deprecated 2.4.1 use DataObjectFactory::getObject()
    **/
    public static function getObject(array $args = [])
    {
        return DataObjectFactory::getObject($args);
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
     * @deprecated 2.4.1 use DataObjectFactory::getObjectList()
    **/
    public static function getObjectList(array $args = [])
    {
        return DataObjectFactory::getObjectList($args);
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
     * @deprecated 2.4.1 use DataObjectFactory::getObjectInterface()
    **/
    public static function getObjectInterface(array $args = [])
    {
        return DataObjectFactory::getObjectInterface($args);
    }

    /**
     * Summary of isObject
     * @param array $args
     * @return bool
     * @deprecated 2.4.1 use DataObjectFactory::isObject()
     */
    public static function isObject(array $args)
    {
        return DataObjectFactory::isObject($args);
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
     * @deprecated 2.4.1 use DataObjectFactory::isObject()
    **/
    public static function createObject(array $args = [])
    {
        return DataObjectFactory::createObject($args);
    }

    /**
     * Summary of updateObject
     * @param array $args
     * @return mixed
     * @deprecated 2.4.1 use DataObjectFactory::isObject()
     */
    public static function updateObject(array $args = [])
    {
        return DataObjectFactory::updateObject($args);
    }

    /**
     * Summary of deleteObject
     * @param array $args
     * @throws \BadParameterException
     * @return bool
     * @deprecated 2.4.1 use DataObjectFactory::isObject()
     */
    public static function deleteObject(array $args = [])
    {
        return DataObjectFactory::deleteObject($args);
    }

    /**
     * Get the names and values of the object's properties
     */
    public function getFieldValues(array $args = [], $bypass = 0)
    {
        $fields = [];
        $properties = $this->getProperties($args);
        if ($bypass) {
            foreach ($properties as $property) {
                $fields[$property->name] = $property->value;
            }
        } else {
            foreach ($properties as $property) {
                $fields[$property->name] = $property->getValue();
            }
        }
        return $fields;
    }

    public function setFieldValues(array $args = [], $bypass = 0)
    {
        if ($bypass) {
            foreach ($args as $key => $value) {
                if (isset($this->properties[$key])) {
                    $this->properties[$key]->value = $value;
                }
            }
        } else {
            foreach ($args as $key => $value) {
                if (isset($this->properties[$key])) {
                    $this->properties[$key]->setValue($value);
                }
            }
        }
        return true;
    }

    public function clearFieldValues(array $args = [])
    {
        $properties = $this->getProperties($args);
        foreach ($properties as $property) {
            $property->clearValue();
        }
        return true;
    }

    /**
     * Get the labels and values to include in some output display for this item
     */
    public function getDisplayValues(array $args = [])
    {
        $displayvalues = [];
        $properties = $this->getProperties($args);
        foreach($properties as $property) {
            $label = xarVar::prepForDisplay($property->label);
            $displayvalues[$label] = $property->showOutput();
        }
        return $displayvalues;
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

    /**
     * Generate URL for a specific action on an object - the format will depend on the linktype
     *
     * @access public
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @param array<string, mixed> $extra extra arguments to pass to the URL - CHECKME: we should only need itemid here !?
     * @return string the generated URL
     */
    public function getActionURL($action = '', $itemid = null, $extra = [])
    {
        // if we have a cached URL already, use that
        if (!empty($itemid) && empty($extra) && !empty($this->cached_urls[$action])) {
            $url = str_replace('=<itemid>', '='.$itemid, $this->cached_urls[$action]);
            return $url;
        }

        // get URL for this object and action
        $url = xarDDObject::getActionURL($this, $action, $itemid, $extra);

        // cache the URL if the itemid is in there
        if (!empty($itemid) && empty($extra) && strpos($url, $this->urlparam . '=' . $itemid) !== false) {
            $this->cached_urls[$action] = str_replace($this->urlparam . '=' . $itemid, $this->urlparam . '=<itemid>', $url);
        }

        return $url;
    }

    /**
     * Call $action hooks for this object (= notify observers in observer pattern)
     *
     * @param string $action the hook action ('create', 'display', ...)
     */
    public function callHooks($action = '')
    {
        // if we have no action
        if (empty($action)) {
            return;
            // if we have no primary key (= itemid)
        } elseif (empty($this->primary)) {
            return;
            // if we already have some hook call in progress
        } elseif (xarCoreCache::isCached('DynamicData', 'HookAction')) {
            return;
        }

        if ($this->moduleid === 182) {
            $modname = 'dynamicdata';
        } else {
            // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
            $modname = xarMod::getName($this->moduleid);
            if($modname == 'articles' || $modname == 'roles') {
                return;
            }
        }

        // CHECKME: prevent recursive hook calls in general
        xarCoreCache::setCached('DynamicData', 'HookAction', $action);

        // <chris> moved this from xarObjectHooks::initHookSubject()
        // This is the correct place to handle it, hooks system doesn't need to know
        // initialize hookvalues
        $this->hookvalues = [];

        // Note: you can preset the list of properties to be transformed via $this->hooktransform

        // add property values to hookvalues
        if ($action == 'transform') {
            if (!empty($this->hooktransform)) {
                $fields = $this->hooktransform;
            } else {
                $fields = array_keys($this->properties);
            }
            $this->hookvalues['transform'] = [];

            foreach($fields as $name) {
                // TODO: this is exactly the same as in the dataobject display function, consolidate it ?
                if(!isset($this->properties[$name])) {
                    continue;
                }

                if(($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED)
                || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY)
                || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)) {
                    continue;
                }

                // *never* transform an ID
                // TODO: there is probably lots more to skip here.
                if (!(DataPropertyMaster::isPrimaryType($this->properties[$name]->type))) {
                    $this->hookvalues['transform'][] = $name;
                }
                $this->hookvalues[$name] = $this->properties[$name]->value;
            }
            $this->hooktransform = $this->hookvalues['transform'];
        } else {
            foreach(array_keys($this->properties) as $name) {
                $this->hookvalues[$name] = $this->properties[$name]->value;
            }
            $this->hooktransform = [];
        }

        // add extra info for traditional hook modules
        // FIXME: This causes problems if you have a property named "module", "itemtype" etc.
        //        $this->hookvalues['module'] = xarMod::getName($this->moduleid);
        //        $this->hookvalues['itemtype'] = $this->itemtype;
        //        $this->hookvalues['itemid'] = $this->itemid;
        // CHECKME: is this sufficient in most cases, or do we need an explicit xarController::URL() ?
        $this->hookvalues['returnurl'] = xarServer::getCurrentURL();

        // Use the standard method to call hooks
        if ($this instanceof DataObject) {
            $hooks = xarModHooks::call('item', $action, $this->itemid ?? null, $this->hookvalues, $modname, $this->itemtype);
        } else {
            $hooks = xarModHooks::call('item', $action, null, $this->hookvalues, $modname, $this->itemtype);
        }
        // FIXME: we don't need two distinct properties to store gui and api hook responses
        // A response is a response, it's up to the caller to decide if it's appropriate
        // For now we'll populate both with the same data
        $this->hookvalues = $this->hookoutput = $hooks;

        // let xarObjectHooks worry about calling the different hooks
        //xarObjectHooks::callHooks($this, $action);

        // the result of API actions will be in $this->hookvalues
        // the result of GUI actions will be in $this->hookoutput

        // CHECKME: prevent recursive hook calls in general
        xarCoreCache::delCached('DynamicData', 'HookAction');
    }

    /**
     * Get linked objects (see DataObjectLinks)
     *
     * @param string $linktype the type of links we're looking for (default, parents, children, linkedto, linkedfrom, info, all)
     * @param mixed $itemid (optional) for a particular itemid in ObjectList ?
     */
    public function getLinkedObjects($linktype = '', $itemid = null)
    {
        sys::import('modules.dynamicdata.class.objects.links');
        // we'll skip the 'info' here, unless explicitly asked for 'all'
        return DataObjectLinks::getLinkedObjects($this, $linktype, $itemid);
    }

    private function assembleQuery($object, $prefix = false, $type = "SELECT")
    {
        $descriptor = $object->descriptor;
        // Set up the db tables
        if ($descriptor->exists('sources')) {
            $sources = $descriptor->get('sources');
            try {
                $sources = @unserialize($descriptor->get('sources'));

                if (!empty($sources)) {
                    $object->datasources = $sources;
                    foreach ($object->datasources as $key => $table) {
                        // Support simple array form
                        if (is_array($table)) {
                            $tabletype = $table[1];
                            $value = $table[0];
                        } else {
                            $tabletype = 'internal';
                            $value = $table;
                        }

                        // Remove any spaces and similar chars
                        $value = trim($value);
                        $key = trim($key);

                        // Default to variable datasource if we find that anywhere
                        if ($key == 'variable') {
                            $object->datasources = ['variable' => 'variable'];
                            $this->dataquery->cleartables();
                            break;
                        } else {
                            if ($type != "SELECT" && $tabletype != "internal") {
                                continue;
                            }
                            //                            if (is_array($value)) $value = current($value);
                            if ($prefix) {
                                $this->dataquery->addtable($value, $object->name . "_" . $key);
                            } else {
                                $this->dataquery->addtable($value, $key);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                echo xarML('Found sources: ');
                var_dump($sources);
                echo xarML('<br/>Error reading object sources');
            }
        }

        // Set up the db table relations
        if ($descriptor->exists('relations')) {
            try {
                $relationargs = @unserialize($descriptor->get('relations'));
                if (is_array($relationargs)) {
                    foreach ($relationargs as $key => $value) {

                        // Support simple array form
                        // if (is_array($value)) $value = current($value);

                        // Bail if we are missing anything
                        if (count($value) < 2) {
                            continue;
                        }

                        // Remove any spaces and similar chars
                        $left = trim($value[0]);
                        $right = trim($value[1]);

                        // If this was just the empty first line, bail
                        if (empty($left)) {
                            continue;
                        }

                        // Check if this relation includes a foreign table
                        // If it does do a left or right join, rather than an inner join
                        $join = "";
                        $fromobjectparts = explode('.', $left);
                        $fromobject = $fromobjectparts[0];
                        if (isset($object->datasources[$fromobject])) {
                            if (isset($object->datasources[$fromobject][1]) && $object->datasources[$fromobject][1] == 'internal') {
                                $join = 'join';
                            } else {
                                if ($type != "SELECT") {
                                    continue;
                                }
                                $join = 'rightjoin';
                            }
                        }

                        $toobjectparts = explode('.', $right);
                        $toobject = $toobjectparts[0];
                        if (isset($object->datasources[$toobject])) {
                            if (isset($object->datasources[$toobject][1]) && $object->datasources[$toobject][1] == 'internal') {
                                $join = 'join';
                            } else {
                                if ($type != "SELECT") {
                                    continue;
                                }
                                $join = 'leftjoin';
                            }
                        }

                        // If no join was defined, then this is a bad realtion: ignore
                        if (empty($join)) {
                            continue;
                        }

                        // Add this relation's join to the object's dataquery
                        if ($prefix) {
                            $this->dataquery->{$join}($object->name . "_" . $left, $object->name . "_" . $right);
                        } else {
                            $this->dataquery->{$join}($left, $right);
                        }
                    }
                }
            } catch (Exception $e) {
                throw new Exception(xarML('Error reading object relations'));
            }
        }

        // Set up the relations to related objects
        if ($descriptor->exists('objects')) {
            try {
                $objectargs = @unserialize($descriptor->get('objects'));
                if (is_array($objectargs)) {
                    foreach ($objectargs as $key => $value) {

                        // Support simple array form
                        // if (is_array($value)) $value = current($value);

                        // Bail if we are missing anything
                        if (count($value) < 2) {
                            continue;
                        }

                        // Remove any spaces and similar chars
                        $left = trim($value[0]);
                        $right = trim($value[1]);

                        // If this was just the empty first line, bail
                        if (empty($left)) {
                            continue;
                        }
                        if (empty($right)) {
                            continue;
                        }

                        if ((strpos($left, 'this') === false) && (strpos($right, 'this') === false)
                        && (strpos($left, $object->name) === false) && (strpos($right, $object->name) === false)
                        ) {
                            echo 'One of the links must be of a property of ' . $object->name . '<br />';
                        }
                        try {
                            $leftside = $object->propertysource($left, $object, $prefix);
                        } catch (Exception $e) {
                            echo 'Cannot translate ' . $left . ' to a valid datasource<br />';
                        }
                        try {
                            $rightside = $object->propertysource($right, $object, $prefix);
                        } catch (Exception $e) {
                            echo 'Cannot translate ' . $right . ' to a valid datasource<br />';
                        }
                        $this->dataquery->leftjoin($leftside, $rightside);

                        // FIXME: We don't yet support a sort order for related object items, so order them by ID for now
                        $parts = explode('.', $right);
                        $table = trim($parts[0]);
                        // We should actually sort by the object's primary key, but lets forgoe that for now
                        //                    $this->dataquery->setorder($table . ".id");
                    }
                }
            } catch (Exception $e) {
                if (isset($left)) {
                    echo 'Bad object relation: ' . $left . ' or ' . $right;
                } else {
                    echo 'The object relation cannot be read (badly formed)';
                }
            }
        }

        foreach ($object->properties as $name => $property) {
            // Recursive call for subitems properties
            if ($object->properties[$name]->type == 30069 &&
                $object->properties[$name]->getDisplayStatus() != DataPropertyMaster::DD_DISPLAYSTATE_DISABLED
            ) {
                $this->assembleQuery($object->properties[$name]->subitemsobject, true);
            }
        }
    }

    /**
     * Check access for a specific action on an object // CHECKME: how about checking *before* the object is loaded ?
     *
     * @access public
     * @param string $action the action we want to take on this object (= method or func)
     * @param mixed $itemid the specific item id or null
     * @param mixed $roleid override the current user or null // CHECKME: do we want this ?
     * @return bool true if access
     */
    public function checkAccess($action, $itemid = null, $roleid = null)
    {
        if (empty($action)) {
            throw new EmptyParameterException('Access method');
        }

        // only allow direct access to tables for administrators
        if (!empty($this->table)) {
            $action = 'admin';
        }

        // default actions supported by dynamic objects
        switch($action) {
            case 'admin':
                // require admin access to the module here
                return xarSecurity::check('AdminDynamicData', 0);

            case 'config':
            case 'access':
            case 'settings':
                $level = 'config';
                $mask = 'AdminDynamicDataItem';
                $itemid = 'All';
                break;

            case 'delete':
            case 'remove':
                $level = 'delete';
                $mask = 'DeleteDynamicDataItem';
                break;

            case 'create':
            case 'new':
                $level = 'create';
                $mask = 'AddDynamicDataItem';
                break;

            case 'update':
            case 'modify':
                $level = 'update';
                $mask = 'EditDynamicDataItem';
                break;

            case 'display':
            case 'show':
                $level = 'display';
                $mask = 'ReadDynamicDataItem';
                break;

            case 'view':
            case 'list':
            case 'search':
            case 'query':
            case 'stats':
            case 'report':
            default:
                $level = 'display'; // CHECKME: no difference in access level between view and display !?
                $mask = 'ViewDynamicDataItems';
                break;
        }

        // unserialize access levels if necessary
        try {
            $access_rules = unserialize($this->access_rules['access']);
        } catch (Exception $e) {
            $access_rules = [];
        }

        // DD specific access scheme
        // check if we have specific access rules for this level
        if (!empty($access_rules) && is_array($access_rules) && !empty($access_rules[$level])) {
            $anonid = xarConfigVars::get(null, 'Site.User.AnonymousUID');
            if (empty($roleid) && !empty(xarSession::getAnonId()) && xarUser::isLoggedIn()) {
                // get the direct parents of the current user (no ancestors)
                $grouplist = xarCache::getParents();
            } elseif (!empty($roleid) && $roleid != $anonid) {
                // get the direct parents of the specified user (no ancestors)
                $grouplist = xarCache::getParents($roleid);
            } else {
                // check anonymous visitors by themselves
                $grouplist = [$anonid];
            }
            foreach ($grouplist as $groupid) {
                // list of groups that have access at this level
                if (in_array($groupid, $access_rules[$level])) {
                    // one group having access is enough here !
                    return true;
                }
            }
            // none of the groups have access at this level
            return false;
        }

        // Fall back to normal security checks

        // check if we're dealing with a specific item here
        if (empty($itemid)) {
            if ($this instanceof DataObject && !empty($this->itemid)) {
                $itemid = $this->itemid;
            } else {
                $itemid = 'All';
            }
        }

        if (!empty($roleid)) {
            $role = xarRoles::get($roleid);
            $rolename = $role->getName();
            return xarSecurity::check($mask, 0, 'Item', $this->moduleid.':'.$this->itemtype.':'.$itemid, '', $rolename);
        } else {
            return xarSecurity::check($mask, 0, 'Item', $this->moduleid.':'.$this->itemtype.':'.$itemid);
        }
    }

    /**
     * Translate a string containing a SQL WHERE clause into Query conditions
     *
     * @param mixed $where string or array of name => value pairs
     * @return mixed Query array of query conditions
     */
    public function setWhere($where, $transform = 1)
    {
        // Note this helper property is only defined in this method and the methods called from here
        $this->conditions = new Query();

        if ($transform) {
            $wherestring = $this->transformClause($where);
        } else {
            $wherestring = $where;
        }

        // If the condition is empty, bail (for now)
        if (empty($wherestring)) {
            return $this->conditions;
        }

        $parts = $this->parseClause($wherestring);

        // Turn the parts of the clause into query conditions and add them to $this->conditions
        try {
            $this->bracketClause($parts);
        } catch (Exception $e) {
            $this->conditions->clearconditions();
            echo $e->getMessage();
        }
        return $this->conditions;
    }

    /**
     * Transform property names to their source field names in a clause and replace '=' operator with 'eq' etc.
     *
     * @param mixed $clause string or array of name => value pairs
     * @return string representing a SQL where clause
     */
    private function transformClause($clause)
    {
        // If the condition is empty, bail (for now)
        if (empty($clause)) {
            return '';
        }

        // If a string is passed, make it an array (for now)
        if (!is_array($clause)) {
            $clause = [$clause];
        }

        // If we have an array just get the first element (for now)
        if (is_array($clause)) {
            $clause = $clause[0];
        }

        // cfr. BL compiler - adapt as needed (I don't think == and === are accepted in SQL)
        $findLogic    = [ ' = ', ' != ',  ' < ',  ' > ', ' <= ', ' >= '];
        $replaceLogic = [' eq ', ' ne ', ' lt ', ' gt ', ' le ', ' ge '];

        // Clean up all the operators
        $clause = str_ireplace($findLogic, $replaceLogic, $clause);

        // Replace property names with source field names
        // Note this does not preclude (if the store is a single DB table)
        // that we have fields in the where clause with no corresponding properties
        $findLogic    = [];
        $replaceLogic = [];
        foreach ($this->properties as $name => $property) {
            // If the source is empty (like virtual properties) then ignore
            if (empty($property->source)) {
                continue;
            }
            // Replace the property name unless it is in quotes (in which case it could be a value
            $findLogic[] = "/['\"]" . $name . "['\"](*SKIP)(*FAIL)|\b" . $name . "\b/";
            // Add the property's source name as its replacement
            $replaceLogic[] = $property->source;
        }
        $clause = preg_replace($findLogic, $replaceLogic, $clause);
        return $clause;
    }

    /**
     * Divide the clause into an array of parts
     *
     * @param string $clause representing a SQL where clause
     * @return array<mixed> of operators and operands
     */
    private function parseClause($clause)
    {
        // Enclose the clause in parentheses
        $clause = '(' . $clause . ')';
        // Split the clause into its parts
        $parts = preg_split('/(\(|\)|\bor\b|\band\b)/i', $clause, -1, PREG_SPLIT_DELIM_CAPTURE);

        $processed_parts = [];
        if (empty($parts)) {
            return $processed_parts;
        }
        foreach ($parts as $part) {
            $part = array_shift($parts);
            $part = trim($part);
            switch ($part) {
                case "": break;
                case "(": $processed_parts[] = ['type' => 'begin', 'value' => 1];
                    break;
                case ")": $processed_parts[] = ['type' => 'end', 'value' => 1];
                    break;
                case "or": $processed_parts[] = ['type' => 'operator', 'value' => strtoupper($part)];
                    break;
                case "OR": $processed_parts[] = ['type' => 'operator', 'value' => $part];
                    break;
                case "and": $processed_parts[] = ['type' => 'operator', 'value' => strtoupper($part)];
                    break;
                case "AND": $processed_parts[] = ['type' => 'operator', 'value' => $part];
                    break;
                default: $processed_parts[] = ['type' => 'operand', 'value' => $part];
                    break;
            }
        }
        return $processed_parts;
    }

    private function bracketClause($parts)
    {
        $values = [];
        $conjunctions = [];
        while (1) {
            if (empty($parts)) {
                break;
            }
            $part = array_shift($parts);
            switch ($part['type']) {
                case 'begin':
                    [$parts, $subclause] = $this->bracketClause($parts);
                    $values[] = $subclause;
                    break;
                case 'end':
                    $consistent = true;
                    $this_conjunction = array_shift($conjunctions);
                    foreach ($conjunctions as $conjunction) {
                        if ($conjunction != $this_conjunction) {
                            $consistent = false;
                            break;
                        }
                    }
                    if (!$consistent) {
                        throw new Exception(xarML('Inconsistent conjunctions in a clause'));
                    }
                    if ($this_conjunction == 'or') {
                        $clause = $this->conditions->qor($values);
                    } else {
                        $clause = $this->conditions->qand($values);
                    }
                    return [$parts, $clause];
                case 'operand':
                    $values[] = $this->parseRelation($part['value']);
                    break;
                case 'operator':
                    $conjunctions[] = $part['value'];
                    break;
            }
        }
    }

    /**
     * Turn an operand into a query condition and add it to the dataquery
     *
     * @param string $string representing a SQL where clause
     * @return array<mixed> of operators and operands
     */
    private function parseRelation($string)
    {
        $parts = explode(' ', $string);
        // Make sure we have enough arguments. We need to have something like "foo = 17" or "foo = 'bar'"
        if (count($parts) < 3) {
            throw new Exception(xarML('Incorrect relation "#(1)"', $string));
        }

        // Remove any parens from strings here. They will be added automatically if needed
        $parts[2] = str_replace("'", "", $parts[2]);

        // Construct the relation and add it to the conditions
        $func = 'p' . $parts[1];
        $relation = $this->conditions->$func($parts[0], $parts[2]);
        return $relation;
    }
}
