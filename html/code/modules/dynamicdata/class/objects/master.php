<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundationetobject
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */

/**
 * @todo How far do we want to go with DD as ORM tool + Data Mapper vs. Active Record pattern
 * http://www.terrenceryan.com/blog/post.cfm/coldfusion-9-orm-data-mapper-versus-active-record
 * http://madgeek.com/Articles/ORMapping/EN/mapping.htm
 * http://www.agiledata.org/essays/mappingObjects.html
 * http://www.doctrine-project.org/documentation/manual/2_0/en
 */

sys::import('modules.dynamicdata.class.objects.descriptor');

// FIXME: only needed for the DataPropertyMaster::DD_* constants (or explicit import) - handle differently ?
//sys::import('modules.dynamicdata.class.properties.master');

class DataObjectMaster extends Object
{
    protected $descriptor  = null;      // descriptor object of this class

    public $objectid    = null;         // system id of the object in this installation
    public $name        = null;         // name of the object
    public $label       = null;         // label as shown on screen

    public $moduleid    = null;
    public $itemtype    = 0;

    public $urlparam    = 'itemid';
    public $maxid       = 0;
    public $config      = 'a:0:{}';       // the configuration parameters for this DD object
    public $configuration;                // the exploded configuration parameters for this DD object
    public $isalias     = 0;
    public $join        = '';
    public $table       = '';
    public $extend      = true;

    public $class       = 'DataObject'; // the class name of this DD object
    public $filepath    = 'auto';       // the path to the class of this DD object (can be empty or 'auto' for DataObject)
    public $properties  = array();      // list of properties for the DD object
    public $datastores  = array();      // similarly the list of datastores (arguably in the wrong place here)
    public $fieldlist   = array();      // array of properties to be displayed
    public $fieldorder  = array();      // displayorder for the properties
    public $fieldprefix = '';           // prefix to use in field names etc.
// CHECKME: should be overridden by DataObjectList and DataObject to exclude DISPLAYONLY resp. VIEWONLY !?
    public $status      = 65;           // inital status is active and can add/modify
    public $anonymous   = 0;            // if true forces display of names of properties instead of dd_xx designations

    public $layout = 'default';         // optional layout inside the templates
    public $template = '';              // optional sub-template, e.g. user-objectview-[template].xt (defaults to the object name)
    public $tplmodule = 'dynamicdata';  // optional module where the object templates reside (defaults to 'dynamicdata')
    public $viewfunc = 'view';          // optional view function for use in xarModURL() (defaults to 'view')

    public $primary = null;             // primary key is item id
    public $secondary = null;           // secondary key could be item type (e.g. for articles)
    public $filter = false;             // set this true to automatically filter by current itemtype on secondary key
    public $upload = false;             // flag indicating if this object has some property that provides file upload

    public $visibility = 'public';      // hint to DD whether this is a private object for a particular module, a protected object
                                        // which preferably shouldn't be messed with, or a public object that any admin can modify

// TODO: validate this way of working in trickier situations
    public $hookvalues    = array();    // updated hookvalues for API actions
    public $hookoutput    = array();    // output from each hook module for GUI actions
    public $hooktransform = array();    // list of names for the properties to be transformed by the transform hook

// CHECKME: this is no longer needed
    private $hooklist     = null;       // list of hook modules (= observers) to call
    private $hookscope    = 'item';     // the hook scope for dataobject (for now)

    public $links         = null;       // links between objects

// TODO: relink objects, properties and datastores in __wakeup() methods after unserialize()

    /**
     * Default constructor to set the object variables, retrieve the dynamic properties
     * and get the corresponding data stores for those properties
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve, or
     * @param $args['table'] database table to turn into an object
     * @param $args['catid'] categories we're selecting in (if hooked)
     *
     * @param $args['fieldlist'] optional list of properties to use, or
     * @param $args['status'] optional status of the properties to use
     * @param $args['allprops'] skip disabled properties by default
     * @todo  This does too much, split it up
    **/

    function toArray(Array $args=array())
    {
        $properties = $this->getPublicProperties();
        foreach ($properties as $key => $value) if (!isset($args[$key])) $args[$key] = $value;
        //FIXME where do we need to define the modname best?
        if (!empty($args['moduleid'])) $args['modname'] = xarMod::getName($args['moduleid']); //FIXME change to systemid
        return $args;
    }

    function loader(DataObjectDescriptor $descriptor)
    {
        $descriptor->refresh($this);

        xarMod::loadDbInfo('dynamicdata','dynamicdata');

        // Get the info on the db table if that was passed in.
        // meaning the object is based on a db table.
        if(!empty($this->table))
        {
            $meta = xarMod::apiFunc(
                'dynamicdata','util','getmeta',
                array('table' => $this->table)
            );
            // we throw an exception here because we assume a table should always exist (for now)
            if(!isset($meta) || !isset($meta[$this->table]))
            {
                $msg = 'Invalid #(1) #(2) for dynamic object #(3)';
                $vars = array('table',$this->table,$this->table);
                throw new BadParameterException($vars,$msg);
            }
            // Add all the info we got from the table as properties to the object
            foreach($meta[$this->table] as $name => $propinfo)
                $this->addProperty($propinfo);
        }

        // use the object name as default template override (*-*-[template].x*)
        if(empty($this->template) && !empty($this->name))
            $this->template = $this->name;

        // get the properties defined for this object
       if(count($this->properties) == 0 && isset($this->objectid)) {
            $args = $this->toArray();
            $args['objectref'] =& $this;
            if(!isset($args['allprops']))   //FIXME is this needed??
                $args['allprops'] = null;

            sys::import('modules.dynamicdata.class.properties.master');
            DataPropertyMaster::getProperties($args); // we pass this object along
        }

        // Do we have a join?
        if(!empty($this->join))
        {
            $meta = xarMod::apiFunc(
                'dynamicdata','util','getmeta',
                array('table' => $this->join)
            );
            // we throw an exception here because we assume a table should always exist (for now)
            if(!isset($meta) || !isset($meta[$this->join]))
            {
                $msg = 'Invalid #(1) #(2) for dynamic object #(3)';
                $vars = array('join',$this->join,$this->name);
                throw new BadParameterException($vars,$msg);
            }
            $count = count($this->properties);
            foreach($meta[$this->join] as $name => $propinfo)
                $this->addProperty($propinfo);

            if(count($this->properties) > $count)
            {
                // put join properties in front
                $joinprops = array_splice($this->properties,$count);
                $this->properties = array_merge($joinprops,$this->properties);
            }
        }

        // always mark the internal DD objects as 'private' (= items 1-3 in xar_dynamic_objects, see xarinit.php)
        if (!empty($this->objectid) && $this->objectid == 1 && !empty($this->itemid) && $this->itemid <= 3) {
            $this->visibility = 'private';
/* CHECKME: issue warning for static table as well ?
        } elseif (empty($this->objectid) && !empty($this->table)) {
            $this->visibility = 'static table';
*/
        }

// CHECKME: get or set here ? get doesn't use any arguments, and set expects a different format for status
        // create the list of fields, filtering where necessary
        $this->fieldlist = $this->getFieldList($this->fieldlist,$this->status);

        // build the list of relevant data stores where we'll get/set our data
        if(count($this->datastores) == 0 && count($this->properties) > 0)
           $this->getDataStores();
    }

    public function setFieldList($fieldlist=array(),$status=array())
    {
        if (empty($fieldlist)) $fieldlist = $this->setupFieldList();
        if (!is_array($fieldlist)) {
            try {
                $fieldlist = explode(',',$fieldlist);
            } catch (Exception $e) {
                throw new Exception('Badly formed fieldlist attribute');
            }
        }
        $this->fieldlist = array();
        if (!empty($status)) {
            // Make sure we have an array
            if (!is_array($status)) $status = array($status);
            foreach($fieldlist as $field) {
                $field = trim($field);
                // Ignore those disabled AND those that don't exist
                if(isset($this->properties[$field]) && in_array($this->properties[$field]->getDisplayStatus(),$status))
                    $this->fieldlist[$this->properties[$field]->id] = $this->properties[$field]->name;
            }
        } else {
            foreach($fieldlist as $field) {
                $field = trim($field);
                // Ignore those disabled AND those that don't exist
                if(isset($this->properties[$field]) && ($this->properties[$field]->getDisplayStatus() != DataPropertyMaster::DD_DISPLAYSTATE_DISABLED))
                    $this->fieldlist[$this->properties[$field]->id] = $this->properties[$field]->name;
                }
        }
        return true;
    }

    public function getFieldList()
    {
        if (empty($this->fieldlist)) $this->fieldlist = $this->setupFieldList();
        return $this->fieldlist;
    }

    private function setupFieldList($fieldlist=array(),$status=array())
    {
        $fields = array();
        if(!empty($fieldlist)) {
            foreach($fieldlist as $field)
                // Ignore those disabled AND those that don't exist
                if(isset($this->properties[$field]) && ($this->properties[$field]->getDisplayStatus() != DataPropertyMaster::DD_DISPLAYSTATE_DISABLED))
                    $fields[$this->properties[$field]->id] = $this->properties[$field]->name;
        } else {
            if (!empty($status)) {
                // Make sure we have an array
                if (!is_array($status)) $status = array($status);
                // we have a status: filter on it
                foreach($this->properties as $property)
                    if(in_array($property->getDisplayStatus(),$status))
                        $fields[$property->id] = $property->name;
            } else {
                // no status filter: return those that are not disabled
                // CHECKME: filter out DISPLAYONLY or VIEWONLY depending on the class we're in !
                sys::import('modules.dynamicdata.class.properties.master');
                if (method_exists($this, 'getItems')) {
                    $filterstate = DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY;
                } else {
                    $filterstate = DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY;
                }
                foreach($this->properties as $property)
                    if($property->getDisplayStatus() != DataPropertyMaster::DD_DISPLAYSTATE_DISABLED &&
                       $property->getDisplayStatus() != $filterstate)
                        $fields[$property->id] = $property->name;
            }
        }
        return $fields;
    }

    /**
     * Get the data stores where the dynamic properties of this object are kept
    **/
    function &getDataStores($reset = false)
    {
        // if we already have the datastores
        if (!$reset && isset($this->datastores) && count($this->datastores) > 0) {
            return $this->datastores;
        }

// CHECKME: we have a default status that is not empty status now !
        // if we're filtering on property status and there are no properties matching this status
        if (!$reset && !empty($this->status) && count($this->fieldlist) == 0) {
            return $this->datastores;
        }

        // reset field list of datastores if necessary
        if ($reset && count($this->datastores) > 0) {
            foreach(array_keys($this->datastores) as $storename) {
                $this->datastores[$storename]->fields = array();
            }
        }

        // check the fieldlist for valid property names and for operations like COUNT, SUM etc.
        if (!empty($this->fieldlist) && count($this->fieldlist) > 0) {
            if (!is_array($this->fieldlist)) $this->setFieldList($this->fieldlist);
            $cleanlist = array();
            foreach($this->fieldlist as $name) {
                if (!strstr($name,'(')) {
//                    if(isset($this->properties[$name]))
                        $cleanlist[] = $name;
                } elseif (preg_match('/^(.+)\((.+)\)/',$name,$matches)) {
                    $operation = $matches[1];
                    $field = $matches[2];
                    if(isset($this->properties[$field]))
                    {
                        $this->properties[$field]->operation = $operation;
                        $cleanlist[] = $field;
                        $this->isgrouped = 1;
                    }
                }
            }
            $this->fieldlist = $cleanlist;
        }

        // CHECKME: filter out DISPLAYONLY or VIEWONLY depending on the class we're in here too ?
        foreach($this->properties as $name => $property) {
            if(
                (!empty($this->fieldlist) and         // if there is a fieldlist
                !in_array($name,$this->fieldlist) and // but the field is not in it,
                $property->type != 21) or             // and we're not on an Item ID property
                ($property->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED) // or the property is disabled
            )
            {
                // Skip it.
                $this->properties[$name]->datastore = '';
                continue;
            }

            list($storename, $storetype) = $property->getDataStore();
            if (!isset($this->datastores[$storename])) {
                $this->addDataStore($storename, $storetype);
            }
            $this->properties[$name]->datastore = $storename;

            if (empty($this->fieldlist) || in_array($name,$this->fieldlist)) {
                // we add this to the data store fields
                $this->datastores[$storename]->addField($this->properties[$name]); // use reference to original property
            } else {
                // we only pass this along as being the primary field
                $this->datastores[$storename]->setPrimary($this->properties[$name]);
            }
            // keep track of what property holds the primary key (item id)
            if (!isset($this->primary) && $property->type == 21) {
                $this->primary = $name;
            }
            // keep track of what property holds the secondary key (item type)
            if (empty($this->secondary) && $property->type == 20 && !empty($this->filter)) {
                $this->secondary = $name;
            }
        }
        return $this->datastores;
    }

    /**
     * Add a data store for this object
     *
     * @param $name the name for the data store
     * @param $type the type of data store
    **/
    function addDataStore($name = '_dynamic_data_', $type='data')
    {
        sys::import('modules.dynamicdata.class.datastores.master');
        // get a new data store
        $datastore = DataStoreFactory::getDataStore($name, $type);

        // add it to the list of data stores
        $this->datastores[$datastore->name] =& $datastore;

        // for dynamic object lists, put a reference to the $itemids array in the data store
        if(method_exists($this, 'getItems'))
            $this->datastores[$datastore->name]->_itemids =& $this->itemids;
    }

    /**
     * Get the selected dynamic properties for this object
    **/
    function &getProperties($args = array())
    {
        if(empty($args['fieldlist']))
        {
            if(count($this->fieldlist) > 0) {
                $fieldlist = $this->fieldlist;
            } else {
                return $this->properties;
            }
        } else {
            // Accept a list or an array
            if (!is_array($args['fieldlist'])) $args['fieldlist'] = explode(',',$args['fieldlist']);
            $fieldlist = $args['fieldlist'];
        }


        $properties = array();
        if (!empty($args['fieldprefix'])) {
            foreach($fieldlist as $name) {
                if (isset($this->properties[$name])) {
                    // Pass along a field prefix if there is one
                    $this->properties[$name]->_fieldprefix = $args['fieldprefix'];
                    $properties[$name] = &$this->properties[$name];
                    // Pass along the directive of what property name to display
                    if (isset($args['anonymous'])) $this->properties[$name]->anonymous = $args['anonymous'];
                }
            }
        } else {
            foreach($fieldlist as $name) {
                if (isset($this->properties[$name])) {
                    // Pass along a field prefix if there is one
                    $properties[$name] = &$this->properties[$name];
                    // Pass along the directive of what property name to display
                    if (isset($args['anonymous'])) $this->properties[$name]->anonymous = $args['anonymous'];
                }
            }
        }

        return $properties;
    }

    /**
     * Add a property for this object
     *
     * @param $args['name'] the name for the dynamic property (required)
     * @param $args['type'] the type of dynamic property (required)
     * @param $args['label'] the label for the dynamic property
     * @param $args['datastore'] the datastore for the dynamic property
     * @param $args['source'] the source for the dynamic property
     * @param $args['id'] the id for the dynamic property
     *
     * @todo why not keep the scope here and do this:
     *       $this->properties[$args['id']] = new Property($args); (with a reference probably)
    **/
    function addProperty($args)
    {
        // TODO: find some way to have unique IDs across all objects if necessary
        if(!isset($args['id']))
            $args['id'] = count($this->properties) + 1;
        sys::import('modules.dynamicdata.class.properties.master');
        DataPropertyMaster::addProperty($args,$this);
    }

    /**
     * Class method to retrieve information about all DataObjects
     *
     * @return array of object definitions
    **/
    static function &getObjects(Array $args=array())
    {
        extract($args);
        $dbconn = xarDB::getConn();
        xarMod::loadDbInfo('dynamicdata','dynamicdata');
        $xartable = xarDB::getTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $bindvars = array();
        xarLogMessage("DB: query in getObjects");
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
        if(isset($moduleid))
        {
            $query .= "WHERE module_id = ?";
            $bindvars[] = $moduleid;
        }
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);

        $objects = array();
        while ($result->next())
        {
            $info = array();
            // @todo this depends on fetchmode being numeric
            list(
                $info['objectid'], $info['name'],     $info['label'],
                $info['moduleid'], $info['itemtype'],
                $info['urlparam'], $info['maxid'],    $info['config'],
                $info['isalias']
            ) = $result->fields;
            $objects[$info['objectid']] = $info;
        }
//        $result->Close();
        return $objects;
    }

    /**
     * Class method to retrieve information about a Dynamic Object
     *
     * @param $args['objectid'] id of the object you're looking for, OR
     * @param $args['name'] name of the object you're looking for, OR
     * @param $args['moduleid'] module id of the object you're looking for + $args['itemtype'] item type of the object you're looking for
     * @return array containing the name => value pairs for the object
     * @todo cache on id/name/module_id ?
     * @todo when we had a constructor which was more passive, this could be non-static. (cheap construction is a good rule of thumb)
     * @todo no ref return?
     * @todo when we can turn this into an object method, we dont have to do db inclusion all the time.
    **/
    static function getObjectInfo(Array $args=array())
    {
        if(!empty($args['table']))
        {
            $info = array();
            $info['objectid'] = 0;
            $info['name'] = $args['table'];
            $info['label'] = xarML('Table #(1)',$args['table']);
            $info['moduleid'] = 182;
            $info['itemtype'] = 0;
            $info['filepath'] = 'auto';
            $info['urlparam'] = 'itemid';
            $info['maxid'] = 0;
            $info['config'] = '';
            $info['isalias'] = 0;
            return $info;
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
        if(xarCore::isCached($cacheKey,$infoid)) {
            return xarCore::getCached($cacheKey,$infoid);
        }

        $dbconn = xarDB::getConn();
        xarMod::loadDbInfo('dynamicdata','dynamicdata');
        $xartable = xarDB::getTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $bindvars = array();
        xarLogMessage('DD: query in getObjectInfo');
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
        if(!$result->first()) return;
        $info = array();
        list(
            $info['objectid'], $info['name'],     $info['label'],
            $info['moduleid'], $info['itemtype'],
            $info['class'], $info['filepath'],
            $info['urlparam'], $info['maxid'],    $info['config'],
            $info['isalias']
        ) = $result->fields;
        $result->close();
        if(!empty($args['join']))
        {
            $info['label'] .= ' + ' . $args['join'];
        }
        xarCore::setCached($cacheKey,$infoid,$info);
        return $info;
    }

    /**
     * Class method to retrieve a particular object definition, with sub-classing
     * (= the same as creating a new Dynamic Object with itemid = null)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['name'] name of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve + $args['itemtype'] item type of the object to retrieve
     * @param $args['class'] optional classname (e.g. <module>_DataObject)
     * @return object the requested object definition
     * @todo  automatic sub-classing per module (and itemtype) ?
    **/
    static function &getObject(Array $args=array())
    {
        if(!isset($args['itemid'])) $args['itemid'] = null;

// FIXME: clean up redundancy between self:getObjectInfo($args) and new DataObjectDescriptor($args)
        // Complete the info if this is a known object
        $info = self::getObjectInfo($args);

        if ($info != null) $args = array_merge($args,$info);
        else return $info;

        // TODO: Try to get the object from the cache ?
//        if (!empty($args['objectid']) && xarCore::isCached('DDObject', $args['objectid'])) {
//            // serialize is better here - shallow cloning is not enough for array of properties, datastores etc. and with deep cloning internal references are lost
//            $object = unserialize(xarCore::getCached('DDObject', $args['objectid']));
//            return $object;
//        }

        sys::import('modules.dynamicdata.class.objects.base');
        if(!empty($args['filepath']) && ($args['filepath'] != 'auto')) include_once(sys::code() . $args['filepath']);
        if (!empty($args['class'])) {
            if(!class_exists($args['class'])) {
                throw new ClassNotFoundException($args['class']);
            }
        } else {
            //CHECKME: remove this later. only here for backward compatibility
            $args['class'] = 'DataObject';
        }
        // here we can use our own classes to retrieve this
        $descriptor = new DataObjectDescriptor($args);

        $object = new $args['class']($descriptor);
        // serialize is better here - shallow cloning is not enough for array of properties, datastores etc. and with deep cloning internal references are lost
//        xarCore::setCached('DDObject', $args['objectid'], serialize($object));

        return $object;
    }

    /**
     * Class method to retrieve a particular object list definition, with sub-classing
     * (= the same as creating a new Dynamic Object List)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['name'] name of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['class'] optional classname (e.g. <module>_DataObject[_List])
     * @return object the requested object definition
     * @todo   automatic sub-classing per module (and itemtype) ?
     * @todo   get rid of the classname munging, use typing
    **/
    static function &getObjectList(Array $args=array())
    {
// FIXME: clean up redundancy between self:getObjectInfo($args) and new DataObjectDescriptor($args)
        // Complete the info if this is a known object
        $info = self::getObjectInfo($args);
        if ($info != null) $args = array_merge($args,$info);

        sys::import('modules.dynamicdata.class.objects.list');
        $class = 'DataObjectList';
        if(!empty($args['filepath']) && ($args['filepath'] != 'auto')) include_once(sys::code() . $args['filepath']);
        if(!empty($args['class']))
        {
            if(class_exists($args['class'] . 'List'))
            {
                // this is a generic classname for the object, list and interface
                $class = $args['class'] . 'List';
            }
            elseif(class_exists($args['class']))
            {
                // this is a specific classname for the list
                $class = $args['class'];
            }
        }
        $descriptor = new DataObjectDescriptor($args);

        // here we can use our own classes to retrieve this
        $object = new $class($descriptor);
        return $object;
    }

    /**
     * Class method to retrieve a particular object interface definition, with sub-classing
     * (= the same as creating a new Dynamic Object User Interface)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['name'] name of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['class'] optional classname (e.g. <module>_DataObject[_Interface])
     * @return object the requested object definition
     * @todo  get rid of the classname munging
     * @todo  automatic sub-classing per module (and itemtype) ?
    **/
    static function &getObjectInterface($args)
    {
        sys::import('modules.dynamicdata.class.userinterface');

        $class = 'DataObjectUserInterface';
        if(!empty($args['class']))
        {
            if(class_exists($args['class'] . 'UserInterface'))
            {
                // this is a generic classname for the object, list and interface
                $class = $args['class'] . 'UserInterface';
            }
            elseif(class_exists($args['class'] . 'Interface')) // deprecated
            {
                // this is a generic classname for the object, list and interface
                $class = $args['class'] . 'Interface';
            }
            elseif(class_exists($args['class']))
            {
                // this is a specific classname for the interface
                $class = $args['class'];
            }
        }
        // here we can use our own classes to retrieve this
        $object = new $class($args);
        return $object;
    }

    /**
     * Class method to create a new type of Dynamic Object
     *
     * @param $args['objectid'] id of the object you want to create (optional)
     * @param $args['name'] name of the object to create
     * @param $args['label'] label of the object to create
     * @param $args['moduleid'] module id of the object to create
     * @param $args['itemtype'] item type of the object to create
     * @param $args['urlparam'] URL parameter to use for the object items (itemid, exid, aid, ...)
     * @param $args['maxid'] for purely dynamic objects, the current max. itemid (for import only)
     * @param $args['config'] some configuration for the object (free to define and use)
     * @param $args['isalias'] flag to indicate whether the object name is used as alias for short URLs
     * @param $args['class'] optional classname (e.g. <module>_DataObject)
     * @return integer object id of the created item
    **/
    static function createObject(Array $args)
    {
        // TODO: if we extend dobject classes then probably we need to put the class name here
        $object = self::getObject(array('name' => 'objects'));

        // Create specific part
        $descriptor = new DataObjectDescriptor($args);
        $objectid = $object->createItem($descriptor->getArgs());
        $classname = get_class($object);
        xarLogMessage("Creating an object of class " . $classname . ". Objectid: " . $objectid . ", module: " . $args['moduleid'] . ", itemtype: " . $args['itemtype']);
        unset($object);
        return $objectid;
    }

    static function updateObject(Array $args)
    {
        $object = self::getObject(array('name' => 'objects'));

        // Update specific part
        $itemid = $object->getItem(array('itemid' => $args['objectid']));
        if(empty($itemid)) return;
        $itemid = $object->updateItem($args);
        unset($object);
        return $itemid;
    }

    static function deleteObject($args)
    {
        $descriptor = new DataObjectDescriptor($args);
        $args = $descriptor->getArgs();

        // Last stand against wild hooks and other excesses
        if($args['objectid'] < 3)
        {
            $msg = 'You cannot delete the DataObject or DataProperties class';
            throw new BadParameterException(null, $msg);
        }

        // Get an object list for the object itself, so we can delete its items
        $mylist =& self::getObjectList(
            array(
                'objectid' => $args['objectid'],
                'extend' => false
            )
        );
        if(empty($mylist))
            return;

        sys::import('modules.dynamicdata.class.properties.master');
        // TODO: delete all the (dynamic ?) data for this object

        // delete all the properties for this object
        foreach(array_keys($mylist->properties) as $name)
        {
            $propid = $mylist->properties[$name]->id;
            $propid = DataPropertyMaster::deleteProperty(
                array('itemid' => $propid)
            );
        }
        unset($mylist);

        // delete the Dynamic Objects item corresponding to this object
        $object = self::getObject(array('objectid' => 1));
        $itemid = $object->getItem(array('itemid' => $args['objectid']));
        if(empty($itemid))
            return;
        $result = $object->deleteItem();
        unset($object);
        return $result;
    }

    /**
     * Join another database table to this object (unfinished)
     * The difference with the 'join' argument above is that we don't create a new datastore for it here,
     * and the join is handled directly in the original datastore, i.e. more efficient querying...
     *
     * @param $args['table'] the table to join with
     * @param $args['key'] the join key for this table
     * @param $args['fields'] the fields you want from this table
     * @param $args['where'] optional where clauses for those table fields
     * @param $args['andor'] optional combination of those clauses with the ones from the object
     * @param $args['sort'] optional sort order in that table (TODO)
     *
    **/
    function joinTable($args)
    {
        if(empty($args['table']))
            return;

        $meta = xarMod::apiFunc(
            'dynamicdata','util','getmeta',
            array('table' => $args['table'])
        );

        // we throw an exception here because we assume a table should always exist (for now)
        if(!isset($meta) || !isset($meta[$args['table']]))
        {
            $msg = 'Invalid #(1) #(2) for dynamic object #(3)';
            $vars = array('join',$args['table'],$this->name);
            throw new BadParameterException($vars, $msg);
        }

        $count = count($this->properties);
        foreach($meta[$args['table']] as $name => $propinfo)
            $this->addProperty($propinfo);

        $table = $args['table'];
        $key = null;
        if(!empty($args['key']) && isset($this->properties[$args['key']]))
            $key = $this->properties[$args['key']]->source;

        $fields = array();
        if(!empty($args['fields']))
        {
            foreach($args['fields'] as $field)
            {
                if(isset($this->properties[$field]))
                {
                    $fields[$field] =& $this->properties[$field];
                    if(count($this->fieldlist) > 0 && !in_array($field,$this->fieldlist))
                        $this->fieldlist[] = $field;
                }
            }
        }

        $where = array();
        if(!empty($args['where']))
        {
            // cfr. BL compiler - adapt as needed (I don't think == and === are accepted in SQL)
            $findLogic      = array(' eq ', ' ne ', ' lt ', ' gt ', ' id ', ' nd ', ' le ', ' ge ');
            $replaceLogic   = array( ' = ', ' != ',  ' < ',  ' > ',  ' = ', ' != ', ' <= ', ' >= ');

            $args['where'] = str_replace($findLogic, $replaceLogic, $args['where']);

            $parts = preg_split('/\s+(and|or)\s+/',$args['where'],-1,PREG_SPLIT_DELIM_CAPTURE);
            $join = '';
            foreach($parts as $part)
            {
                if($part == 'and' || $part == 'or')
                {
                    $join = $part;
                    continue;
                }
                $pieces = preg_split('/\s+/',$part);
                $name = array_shift($pieces);
                // sanity check on SQL
                if(count($pieces) < 2)
                {
                    $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
                    $vars = array('query ' . $args['where'], 'DataObjectMaster', 'joinTable', 'DynamicData');
                    throw new BadParameterException($vars,$msg);
                }
                // for many-to-1 relationships where you specify the foreign key in the original table here
                // (e.g. properties joined to xar_dynamic_objects -> where id eq objectid)
                if(
                    !empty($pieces[1]) &&
                    is_string($pieces[1]) &&
                    isset($this->properties[$pieces[1]])
                )  $pieces[1] = $this->properties[$pieces[1]]->source;

                if(isset($this->properties[$name]))
                {
                    $where[] = array(
                        'property' => &$this->properties[$name],
                        'clause' => join(' ',$pieces),
                        'join' => $join
                    );
                }
            }
        }

        $andor = !empty($args['andor']) ? $args['andor'] : 'and';

        foreach(array_keys($this->datastores) as $name)
             $this->datastores[$name]->addJoin($table, $key, $fields, $where, $andor);
    }

    /* Get a module's itemtypes
     *
     * @param int     args[moduleid]
     * @param string args[module]
     * @param bool   args[native]
     * @param bool   args[extensions]
     * @todo don't use args
     * @todo pick moduleid or module
     * @todo move this into a utils class?
     */
    static function getModuleItemTypes(Array $args)
    {
        extract($args);
        // Argument checks
        if (empty($moduleid) && empty($module)) {
            throw new BadParameterException('moduleid or module');
        }
        if (empty($module)) {
            $module = xarMod::getName($moduleid);
        }

        $native = isset($native) ? $native : true;
        $extensions = isset($extensions) ? $extensions : true;

        $types = array();
        if ($native) {
            // Try to get the itemtypes
            try {
                // @todo create an adaptor class for procedural getitemtypes in modules
                $types = xarMod::apiFunc($module,'user','getitemtypes',array());
            } catch ( FunctionNotFoundException $e) {
                // No worries
            }
        }
        if ($extensions) {
            // Get all the objects at once
            // removed dependency on roles xarQuery
            xarMod::loadDbInfo('dynamicdata','dynamicdata');
            $xartable = xarDB::getTables();

            $dynamicobjects = $xartable['dynamic_objects'];

            $bindvars = array();
            $query = "SELECT id AS objectid,
                             name AS objectname,
                             label AS objectlabel,
                             module_id AS moduleid,
                             itemtype AS itemtype
                      FROM $dynamicobjects ";

            $query .= " WHERE module_id = ? ";
            $bindvars[] = (int) $moduleid;

            $dbconn = xarDB::getConn();
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);

            // put in itemtype as key for easier manipulation
            while ($result->next())
            {
                $row = $result->fields;
                $types [$row['itemtype']] = array(
                                            'label' => $row['objectlabel'],
                                            'title' => xarML('View #(1)',$row['objectlabel']),
                                            'url' => xarModURL('dynamicdata','user','view',array('itemtype' => $row['itemtype'])));
            }
        }

        return $types;
    }

    /**
     * Call $action hooks for this object (= notify observers in observer pattern)
     *
     * @param $action the hook action ('create', 'display', ...)
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
        } elseif (xarCore::isCached('DynamicData','HookAction')) {
            return;
        }

        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        $modname = xarMod::getName($this->moduleid);
        if($modname == 'articles' || $modname == 'roles') {
            return;
        }

        // CHECKME: prevent recursive hook calls in general
        xarCore::setCached('DynamicData','HookAction',$action);

        // let xarObjectHooks worry about calling the different hooks
        xarObjectHooks::callHooks($this, $action);

        // the result of API actions will be in $this->hookvalues
        // the result of GUI actions will be in $this->hookoutput

        // CHECKME: prevent recursive hook calls in general
        xarCore::delCached('DynamicData','HookAction');
    }

    /**
     * Get linked objects (see DataObjectLinks)
     *
     * @param $linktype the type of links we're looking for (default, parents, children, linkedto, linkedfrom, info, all)
     * @param $itemid (optional) for a particular itemid in ObjectList ?
     */
    public function getLinkedObjects($linktype = '', $itemid = null)
    {
        sys::import('modules.dynamicdata.class.objects.links');
        // we'll skip the 'info' here, unless explicitly asked for 'all'
        return DataObjectLinks::getLinkedObjects($this, $linktype, $itemid);
    }
}
?>
