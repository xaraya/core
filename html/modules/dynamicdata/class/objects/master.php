<?php
/**
 * The DD factory (sort of)
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 *
 * @todo make this a real factory
 * @todo scoping of variables
**/
sys::import('modules.dynamicdata.class.datastores');
sys::import('modules.dynamicdata.class.properties');

class ObjectDescriptor extends Object
{
    protected $args;

    function __construct(array $args)
    {
        $this->setArgs($args);
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function getPublicProperties(Object $object)
    {
        $o = $object->getClass();
        $objectname = $o->getName();
        $reflection = new ReflectionClass($objectname);
        $properties = array();
        foreach($reflection->getProperties() as $p) {
            $prop = new ReflectionProperty($objectname,$p->name);
            if ($prop->isPublic()) $properties[$p->name] = $prop->getValue($object);
        }
        return $properties;
    }

    public function refresh(Object $object)
    {
        $publicproperties = $this->getPublicProperties($object);
        foreach ($this->args as $key => $value) if (in_array($key,$publicproperties)) $object->$key = $value;
        else echo $key ."<br />";  // temporary for debugging
    }

    public function setArgs(array $args)
    {
        $this->args = $args;
    }
}

class DataObjectDescriptor extends ObjectDescriptor
{
    function __construct(array $args)
    {
//        var_dump($args);
        if (!isset($args['itemtype'])) $args['itemtype'] = 0;
        $args = $this->getObjectID($args);
//        echo "<br />";var_dump($args);exit;
        parent::__construct($args);
    }

    static function getModID(array $args=array())
    {
        foreach ($args as $key => &$value) {
            if (in_array($key, array('module','modid','module','moduleid'))) {
                if (empty($key)) $id = xarModGetIDFromName(xarModGetName());
                if (is_numeric((int)$key) || is_integer((int)$key)) {
                    $args['moduleid'] = $value;
                } else {
                    $info = xarMod::getInfo(xarMod::getRegID($key));
                    $args['moduleid'] = xarMod::getRegID($key); //$info['systemid']; FIXME
                }
                break;
            }
        }
        if (!isset($args['moduleid'])) {
            $info = xarMod::getInfo(182);
            $args['moduleid'] = 182; // $info['systemid'];  FIXME change id
        }
        return $args;
    }

    static function getObjectID(array $args=array())
    {
        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $q = new xarQuery('SELECT',$xartable['dynamic_objects']);
        if (isset($args['name'])) {
            $q->eq('xar_object_name',$args['name']);
        } elseif (isset($args['objectid'])) {
            $q->eq('xar_object_id',(int)$args['objectid']);
        } else {
            $args = self::getModID($args);
            $q->eq('xar_object_moduleid',(int)$args['moduleid']);
            $q->eq('xar_object_itemtype',(int)$args['itemtype']);
        }
        if (!$q->run()) return;
        $row = $q->row();
        $args['moduleid'] = isset($row['xar_object_moduleid']) ? $row['xar_object_moduleid'] : 182;  //will need to change this
        $args['itemtype'] = isset($row['xar_object_itemtype']) ? $row['xar_object_itemtype'] : 0;
        $args['objectid'] = isset($row['xar_object_id']) ? $row['xar_object_id'] : 1;
        $args['name'] = isset($row['xar_object_name']) ? $row['xar_object_name'] : 'objects';
//        $q->qecho();
        return $args;

    }
}

class DataObjectMaster extends Object
{
    public $objectid    = null;         // system id of the object in this installation
    public $name        = null;         // name of the object
    public $label       = null;         // label as shown on screen

    public $moduleid    = null;
    public $itemtype    = 0;
    public $parent      = 0;
    public $baseancestor= null;

    public $urlparam    = 'itemid';
    public $maxid       = 0;
    public $config      = '';
    public $isalias     = 0;
    public $join;
    public $table;
    public $extend      = true;

    public $properties  = array();      // list of properties for the DD object
    public $datastores  = array();      // similarly the list of datastores (arguably in the wrong place here)
    public $fieldlist   = array();
    public $status      = null;

    public $layout = 'default';         // optional layout inside the templates
    public $template = '';              // optional sub-template, e.g. user-objectview-[template].xd (defaults to the object name)
    public $tplmodule = 'dynamicdata';  // optional module where the object templates reside (defaults to 'dynamicdata')
    public $urlmodule = '';             // optional module for use in xarModURL() (defaults to the object module)
    public $viewfunc = 'view';          // optional view function for use in xarModURL() (defaults to 'view')

    public $primary = null;             // primary key is item id
    public $secondary = null;           // secondary key could be item type (e.g. for articles)
    public $filter = true;              // set this true to automatically filter by current itemtype on secondary key
    public $upload = false;             // flag indicating if this object has some property that provides file upload
    public $fieldprefix = '';           // prefix to use in field names etc.

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

    function toArray($args=array())
    {
        $properties = $this->descriptor->getPublicProperties($this);
        $args = array();
        foreach ($properties as $key => $value) if (!isset($args[$key])) $args[$key] = $value;
        //FIXME where do we need to define the modname best?
        $args['modname'] = xarModGetNameFromID($args['moduleid']); //FIXME change to systemid
        return $args;
    }

    function loader(DataObjectDescriptor $descriptor)
    {
        $this->descriptor = $descriptor;
        $this->load();

        xarMod::loadDbInfo('dynamicdata','dynamicdata');

        // Get the info on the db table if that was passed in.
        // meaning the object is based on a db table.
        if(!empty($this->table))
        {
            $meta = xarModAPIFunc(
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

        if(empty($this->name))
        {
            $info = self::getObjectInfo($this->descriptor->getArgs());
            if (!empty($info)) {
            $this->descriptor->setArgs($info);
            $this->load();
            }
        }
        // use the object name as default template override (*-*-[template].x*)
        if(empty($this->template) && !empty($this->name))
            $this->template = $this->name;

        // get the properties defined for this object
        if(count($this->properties) == 0 && isset($this->objectid)) {
            $args = $this->toArray();
            $args['objectref'] = $this;
            if(!isset($args['allprops']))   //FIXME is this needed??
                $args['allprops'] = null;

            DataPropertyMaster::getProperties($args); // we pass this object along
        }

        // Do we have a join?
        if(!empty($this->join))
        {
            $meta = xarModAPIFunc(
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

        // filter on property status if necessary
        if(isset($this->status) && count($this->fieldlist) == 0)
        {
            $this->fieldlist = array(); // why?
            foreach($this->properties as $name => $property)
                if($property->status & $this->status)
                    $this->fieldlist[] = $name;
        }
        // build the list of relevant data stores where we'll get/set our data
        if(count($this->datastores) == 0 && count($this->properties) > 0)
           $this->getDataStores();

        // add ancestors' properties to this object if required
        // the default is to add the fields
        $this->baseancestor = $this->objectid;
        if($this->extend)
            $this->addAncestors();
    }

    /**
     * Add the ancestors to this object
     * This is adding the properties and datastores of all the ancestors to this object
     */
    private function addAncestors($object=null)
    {
        // Determine how we are going to get the ancestors
        $params = array();
        if(!empty($this->objectid))
        {
            // We already have an object, so it can't be native
            $params['objectid'] = $this->objectid;
            $params['top']      = false;
        }
        else
        {
            $params['moduleid'] = $this->moduleid;
            $params['itemtype'] = $this->itemtype;
            $params['top']      = true;
        }
        // Retrieve the ancestors
        $ancestors = self::getAncestors($params);

        // If this is an extended object add the ancestor properties for display purposes
        if(!empty($ancestors))
        {
            $this->baseancestor = $ancestors[0]['objectid'];
            // If the ancestors are objects, add them in
            foreach($ancestors as $ancestor)
            {
                if($ancestor['objectid'])
                    $this->addObject($ancestor['objectid']);
            }
        }
    }

    /**
     * Add one object to another
     * This is basically adding the properties and datastores from one object to another
     *
     * @todo can we use the type hinting for the parameter?
     * @todo pass $object by ref?
     * @todo stricten the interface, either an object or an id, not both.
     */
    private function addObject($object=null)
    {
        if(is_numeric($object))
            $object =& self::getObject(
                array('objectid' => $object)
            );

        if(!is_object($object))
            throw new EmptyParameterException(array(),'Not a valid object');

        $properties = $object->getProperties();
        // mrb: why the ref?
        foreach($properties as &$newproperty)
        {
            // ignore if this property already belongs to the object
            if(isset($this->properties[$newproperty->name]))
                continue;
            $args = array(
                'name'      => $newproperty->name,
                'type'      => $newproperty->type,
                'label'     => $newproperty->label,
                'source'    => $newproperty->source,
                'datastore' => $newproperty->datastore
            );
            $this->addProperty($args);
            if(!isset($this->datastores[$newproperty->datastore]))
            {
                $newstore = $this->property2datastore($newproperty);
                $this->addDatastore($newstore[0],$newstore[1]);
            }
            $this->datastores[$newproperty->datastore]->addField($this->properties[$args['name']]);
            // Is this stuff needed?
            // $newproperty->_items =& $this->items;
            // $this->fieldlist[] = $newproperty->name;
        }
    }

    /**
     * Get the data stores where the dynamic properties of this object are kept
     */
    function &getDataStores($reset = false)
    {
        // if we already have the datastores
        if(!$reset && isset($this->datastores) && count($this->datastores) > 0)
        {
            return $this->datastores;
        }

        // if we're filtering on property status and there are no properties matching this status
        if(!$reset && !empty($this->status) && count($this->fieldlist) == 0)
        {
            return $this->datastores;
        }

        // reset field list of datastores if necessary
        if($reset && count($this->datastores) > 0)
        {
            foreach(array_keys($this->datastores) as $storename)
            {
                $this->datastores[$storename]->fields = array();
            }
        }

        // check the fieldlist for valid property names and for operations like COUNT, SUM etc.
        if(!empty($this->fieldlist) && count($this->fieldlist) > 0)
        {
            $cleanlist = array();
            foreach($this->fieldlist as $name)
            {
                if(!strstr($name,'('))
                {
//                    if(isset($this->properties[$name]))
                        $cleanlist[] = $name;
                }
                elseif(preg_match('/^(.+)\((.+)\)/',$name,$matches))
                {
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

        foreach($this->properties as $name => $property)
        {
            if(
                !empty($this->fieldlist) and          // if there is a fieldlist
                !in_array($name,$this->fieldlist) and // but the field is not in it,
                $property->type != 21                 // and we're not on an Item ID property
            )
            {
                // Skip it.
                $this->properties[$name]->datastore = '';
                continue;
            }

            list($storename, $storetype) = $this->property2datastore($property);
            if(!isset($this->datastores[$storename]))
                $this->addDataStore($storename, $storetype);

            $this->properties[$name]->datastore = $storename;

            if(empty($this->fieldlist) || in_array($name,$this->fieldlist))
                // we add this to the data store fields
                $this->datastores[$storename]->addField($this->properties[$name]); // use reference to original property
            else
                // we only pass this along as being the primary field
                $this->datastores[$storename]->setPrimary($this->properties[$name]);

            // keep track of what property holds the primary key (item id)
            if(!isset($this->primary) && $property->type == 21)
                $this->primary = $name;

            // keep track of what property holds the secondary key (item type)
            if(empty($this->secondary) && $property->type == 20 && !empty($this->filter))
                $this->secondary = $name;
        }
        return $this->datastores;
    }

    /**
     * Find the datastore name and type corresponding to the data source of a property
     *
     * @todo this belongs in the property class, not here
     */
    function property2datastore(&$property)
    {
        switch($property->source)
        {
            case 'dynamic_data':
                // Variable table storage method, aka 'usual dd'
                $storename = '_dynamic_data_';
                $storetype = 'data';
                break;
            case 'hook module':
                // data managed by a hook/utility module
                $storename = '_hooks_';
                $storetype = 'hook';
                break;
            case 'user function':
                // data managed by some user function (specified in validation for now)
                $storename = '_functions_';
                $storetype = 'function';
                break;
            case 'user settings':
                // data available in user variables
                // we'll keep a separate data store per module/itemtype here for now
                // TODO: (don't) integrate user variable handling with DD
                $storename = 'uservars_'.$this->moduleid.'_'.$this->itemtype; //FIXME change id
                $storetype = 'uservars';
                break;
            case 'module variables':
                // data available in module variables
                // we'll keep a separate data store per module/itemtype here for now
                // TODO: (don't) integrate module variable handling with DD
                $storename = 'modulevars_'.$this->moduleid.'_'.$this->itemtype; //FIXME change id
                $storetype = 'modulevars';
                break;
            case 'dummy':
                // no data storage
                $storename = '_dummy_';
                $storetype = 'dummy';
                break;
            default:
                // Nothing specific, perhaps a table?
                if(preg_match('/^(.+)\.(\w+)$/', $property->source, $matches))
                {
                    // data field coming from some static table : [database.]table.field
                    $table = $matches[1];
                    $field = $matches[2];
                    $storename = $table;
                    $storetype = 'table';
                    break;
                }
                // Must be on the todo list then.
                // TODO: extend with LDAP, file, ...
                $storename = '_todo_';
                $storetype = 'todo';
        }
        return array($storename, $storetype);
    }

    /**
     * Add a data store for this object
     *
     * @param $name the name for the data store
     * @param $type the type of data store
    **/
    function addDataStore($name = '_dynamic_data_', $type='data')
    {
        // get a new data store
        $datastore =& DataStoreFactory::getDataStore($name, $type);

        // add it to the list of data stores
        $this->datastores[$datastore->name] =& $datastore;

        // for dynamic object lists, put a reference to the $itemids array in the data store
        if(method_exists($this, 'getItems'))
            $this->datastores[$datastore->name]->_itemids =& $this->itemids;
    }

    /**
     * Get the selected dynamic properties for this object
    **/
/*    function &getProperties($args = array())
    {
        if(empty($args['fieldlist']))
            $args['fieldlist'] = $this->fieldlist;

        // return only the properties we're interested in (might be none)
        if(count($args['fieldlist']) > 0 || !empty($this->status))
        {
            $properties = array();
            foreach($args['fieldlist'] as $name)
                if(isset($this->properties[$name]))
                    $properties[$name] =& $this->properties[$name];
        }
        else
            $properties =& $this->properties;
        return $properties;
    }
*/
    protected function getProperties($args = array())
    {
        if(empty($args['fieldlist']) && empty($this->status))
        {
            if(count($this->fieldlist) > 0)
                $fieldlist = $this->fieldlist;
            else
                $fieldlist = array_keys($this->properties);
        }
        else
            $fieldlist = $args['fieldlist'];

        $properties = array();
        foreach($fieldlist as $name)
            if (isset($this->properties[$name])) $properties[$name] = $this->properties[$name];
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
        DataPropertyMaster::addProperty($args,$this);
    }

    /**
     * Class method to retrieve information about all DataObjects
     *
     * @return array of object definitions
    **/
    static function &getObjects($args=array())
    {
        extract($args);
        $nullreturn = NULL;
        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $bindvars = array();
        xarLogMessage("DB: query in getObjects");
        $query = "SELECT xar_object_id,
                         xar_object_name,
                         xar_object_label,
                         xar_object_moduleid,
                         xar_object_itemtype,
                         xar_object_parent,
                         xar_object_urlparam,
                         xar_object_maxid,
                         xar_object_config,
                         xar_object_isalias
                  FROM $dynamicobjects ";
        if(isset($moduleid))
        {
            $query .= "WHERE xar_object_moduleid = ?";
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
                $info['moduleid'], $info['itemtype'], $info['parent'],
                $info['urlparam'], $info['maxid'],    $info['config'],
                $info['isalias']
            ) = $result->fields;
            $objects[$info['objectid']] = $info;
        }
        $result->Close();
        return $objects;
    }

    /**
     * Class method to retrieve information about a Dynamic Object
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['name'] name of the object you're looking for, or
     * @param $args['moduleid'] module id of the object you're looking for +
     * @param $args['itemtype'] item type of the object you're looking for
     * @return array containing the name => value pairs for the object
     * @todo cache on id/name/modid ?
     * @todo when we had a constructor which was more passive, this could be non-static. (cheap construction is a good rule of thumb)
     * @todo no ref return?
     * @todo when we can turn this into an object method, we dont have to do db inclusion all the time.
    **/
    static function getObjectInfo(array $args)
    {
        $args = DataObjectDescriptor::getObjectID($args);
        if(!empty($args['table']))
        {
            $info = array();
            $info['objectid'] = 0;
            $info['name'] = $args['table'];
            $info['label'] = xarML('Table #(1)',$args['table']);
            $info['moduleid'] = 182;
            $info['itemtype'] = 0;
            $info['parent'] = 1;
            $info['urlparam'] = 'itemid';
            $info['maxid'] = 0;
            $info['config'] = '';
            $info['isalias'] = 0;
            return $info;
        }

        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $bindvars = array();
        xarLogMessage('DD: query in getObjectInfo');
        $query = "SELECT xar_object_id,
                         xar_object_name,
                         xar_object_label,
                         xar_object_moduleid,
                         xar_object_itemtype,
                         xar_object_parent,
                         xar_object_urlparam,
                         xar_object_maxid,
                         xar_object_config,
                         xar_object_isalias
                  FROM $dynamicobjects ";
        $query .= " WHERE xar_object_id = ? ";
        $bindvars[] = (int) $args['objectid'];
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if(!$result->first()) return;

        $info = array();
        list(
            $info['objectid'], $info['name'],     $info['label'],
            $info['moduleid'], $info['itemtype'], $info['parent'],
            $info['urlparam'], $info['maxid'],    $info['config'],
            $info['isalias']
        ) = $result->fields;
        $result->close();
        if(!empty($args['join']))
        {
            $info['label'] .= ' + ' . $args['join'];
        }
        return $info;
    }

    /**
     * Class method to retrieve a particular object definition, with sub-classing
     * (= the same as creating a new Dynamic Object with itemid = null)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['classname'] optional classname (e.g. <module>_DataObject)
     * @return object the requested object definition
     * @todo  automatic sub-classing per module (and itemtype) ?
    **/
    static function &getObject(array $args)
    {
        if(!isset($args['itemid']))
            $args['itemid'] = null;

        $classname = 'DataObject';
        if(!empty($args['classname']) && class_exists($args['classname']))
            $classname = $args['classname'];

        $descriptor = new DataObjectDescriptor($args);
        // here we can use our own classes to retrieve this
        $object = new $classname($descriptor);
        return $object;
    }

    /**
     * Class method to retrieve a particular object list definition, with sub-classing
     * (= the same as creating a new Dynamic Object List)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['classname'] optional classname (e.g. <module>_DataObject[_List])
     * @return object the requested object definition
     * @todo   automatic sub-classing per module (and itemtype) ?
     * @todo   get rid of the classname munging, use typing
    **/
    static function &getObjectList(array $args)
    {
        sys::import('modules.dynamicdata.class.objects.list');
        $classname = 'DataObjectList';
        if(!empty($args['classname']))
        {
            if(class_exists($args['classname'] . 'List'))
            {
                // this is a generic classname for the object, list and interface
                $classname = $args['classname'] . 'List';
            }
            elseif(class_exists($args['classname']))
            {
                // this is a specific classname for the list
                $classname = $args['classname'];
            }
        }
        $descriptor = new DataObjectDescriptor($args);
        // here we can use our own classes to retrieve this
        $object = new $classname($descriptor);
        return $object;
    }

    /**
     * Class method to retrieve a particular object interface definition, with sub-classing
     * (= the same as creating a new Dynamic Object Interface)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['classname'] optional classname (e.g. <module>_DataObject[_Interface])
     * @return object the requested object definition
     * @todo  get rid of the classname munging
     * @todo  automatic sub-classing per module (and itemtype) ?
    **/
    static function &getObjectInterface($args)
    {
        sys::import('modules.dynamicdata.class.interface');

        $classname = 'DataObjectInterface';
        if(!empty($args['classname']))
        {
            if(class_exists($args['classname'] . 'Interface'))
            {
                // this is a generic classname for the object, list and interface
                $classname = $args['classname'] . 'Interface';
            }
            elseif(class_exists($args['classname']))
            {
                // this is a specific classname for the interface
                $classname = $args['classname'];
            }
        }
        // here we can use our own classes to retrieve this
        $object = new $classname($args);
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
     * @param $args['classname'] optional classname (e.g. <module>_DataObject)
     * @return integer object id of the created item
    **/
    static function createObject(array $args)
    {
        // Generic getter
        $object = self::getObjectTemp($args);

        // Create specific part
        $objectid = $object->createItem($args);
        xarLogMessage("Class: " . get_class() . ". Creating an object of class " . $args['classname'] . ". Objectid: " . $objectid . ", module: " . $args['moduleid'] . ", itemtype: " . $args['itemtype']);
        unset($object);
        return $objectid;
    }

    static function updateObject(array $args)
    {
        // For updating, we need the object id
        // @todo raise exception here?
        if(empty($args['objectid']))
            return;

        // Generic getter
        $object = self::getObjectTemp($args);

        // Update specific part
        $itemid = $object->getItem(array('itemid' => $args['objectid']));
        if(empty($itemid))
            return;
        $itemid = $object->updateItem($args);
        unset($object);
        return $itemid;
    }

    static function deleteObject($args)
    {
        // For delete we need the object id.
        // @todo raise exception here?
        if(empty($args['objectid']))
            return;

        // Generic getter
        $object = self::getObjectTemp($args);
        if(empty($object))
            return;

        // Delete specific part
        $itemid = $object->getItem(
            array('itemid' => $args['objectid'])
        );
        if(empty($itemid))
            return;

        // Get an object list for the object itself, so we can delete its items
        $mylist =& self::getObjectList(
            array(
                'objectid' => $args['objectid'],
                'moduleid' => $args['moduleid'],
                'itemtype' => $args['itemtype'],
                'classname' => $args['classname'],
                'extend' => false
            )
        );
        if(empty($mylist))
            return;

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
        $result = $object->deleteItem();
        unset($object);
        return $result;
    }

    /*
        Temporary private helper method for getting an object, as the
        create/update/delete all use this. Until we figure out where the
        master class will fit in we make it private and with a weird name
        so we dont use this too much yet.
    */
    private static function &getObjectTemp(array &$args)
    {
        // Generic check
        $args['moduleid']  = isset($args['moduleid'])  ? $args['moduleid']  : null;
        $args['itemtype']  = isset($args['itemtype'])  ? $args['itemtype']  : null;
        $args['classname'] = isset($args['classname']) ? $args['classname'] : null;

        // get the Dynamic Objects item corresponding to these args
        $object = self::getObject(
            array(
                'objectid'  => 1, // the Dynamic Objects = 1
                'moduleid'  => $args['moduleid'],
                'itemtype'  => $args['itemtype'],
                'classname' => $args['classname']
            )
        );
        if(empty($object))
            return null;
        return $object;
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

        $meta = xarModAPIFunc(
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
                // (e.g. properties joined to xar_dynamic_objects -> where object_id eq objectid)
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

    /**
      * Get Object's Ancestors
      *
      * @param int    args[moduleid]
      * @param int    args[itemtype]
      * @param int    args[objectid]
      * @param bool args[top]
      * @param bool  args[base]
      */
    static function &getAncestors(array $args)
    {
        if(!xarSecurityCheck('ViewDynamicDataItems')) return;

        extract($args);

        if (!(isset($moduleid) && isset($itemtype)) && !isset($objectid)) {
            $msg = xarML('Wrong arguments to DataObjectMaster::getAncestors.');
            throw new BadParameterException(array(),$msg);
        }

        $top = isset($top) ? $top : true;
        $base = isset($base) ? $base : true;
        $ancestors = array();

        // Get the info of this object
        $xartable =& xarDBGetTables();
        if (isset($objectid)) {
            // We have an objectid - get the moduleid and itemtype
            $topobject = self::getObjectInfo(array('objectid' => $objectid));
            $moduleid = $topobject['moduleid'];
            $itemtype = $topobject['itemtype'];
        } else {
            // We have a moduleid and itemtype - get the objectid
            $topobject = self::getObjectInfo(array('moduleid' => $moduleid, 'itemtype' => $itemtype));
            if (empty($topobject)) {
                if ($base) {
                    $types = self::getModuleItemTypes(array('moduleid' => $moduleid));
                    $info = array('objectid' => 0, 'itemtype' => $itemtype, 'name' => xarModGetNameFromID($moduleid));
                    $ancestors[] = $info;
                    return $ancestors;
                }
                return $ancestors;
            }
            $objectid = $topobject['objectid'];
       }

        // Include the last descendant (this object) or not
        if ($top) {
            $ancestors[] = self::getObjectInfo(array('objectid' => $objectid));
        }

        // Get all the dynamic objects at once
        sys::import('modules.roles.class.xarQuery');
        $q = new xarQuery('SELECT',$xartable['dynamic_objects']);
        $q->addfields(array('xar_object_id AS objectid','xar_object_name AS objectname','xar_object_moduleid AS moduleid','xar_object_itemtype AS itemtype','xar_object_parent AS parent'));
        $q->eq('xar_object_moduleid',$moduleid);
        if (!$q->run()) return;

        // Put in itemtype as key for easier manipulation
        foreach($q->output() as $row) $objects
[$row['itemtype']] = array('objectid' => $row['objectid'],'objectname' => $row['objectname'], 'moduleid' => $row['moduleid'], 'itemtype' => $row['itemtype'], 'parent' => $row['parent']);

        // Cycle through each ancestor
        $parentitemtype = $topobject['parent'];
        for(;;) {
            $done = false;

            if ($parentitemtype >= 1000) {
                // This is a DD descendent object. add it to the ancestor array
                $thisobject     = $objects[$parentitemtype];
                $moduleid       = $thisobject['moduleid'];
                $objectid       = $thisobject['objectid'];
                $itemtype       = $thisobject['itemtype'];
                $name           = $thisobject['objectname'];
                $parentitemtype = $thisobject['parent'];
                $ancestors[] = array('objectid' => $objectid, 'itemtype' => $itemtype, 'name' => $name, 'moduleid' => $moduleid);
            } else {

                // This is a native itemtype. get ready to quit
                $done = true;
                $itemtype = $parentitemtype;
                if ($itemtype) {
                    if ($info=self::getObjectInfo(array('moduleid' => $moduleid, 'itemtype' => $itemtype))) {

                        // A DD wrapper object exists, add it to the ancestor array
                        if ($base) $ancestors[] = array('objectid' => $info['objectid'], 'itemtype' => $itemtype, 'name' => $info['name'], 'moduleid' => $moduleid);
                    } else {

                        // No DD wrapper object
                        // This must be a native itemtype of some module - add it to the ancestor array if requested
                        $types = self::getModuleItemTypes(array('moduleid' => $moduleid));
                        $name = strtolower($types[$itemtype]['label']);
                        if ($base) {$ancestors[] = array('objectid' => 0, 'itemtype' => $itemtype, 'name' => $name, 'moduleid' => $moduleid);}
                    }
                } else {
                    // Itemtype = 0. We're already at the bottom - do nothing
                }
            }
            if ($done) break;
        }
        $ancestors = array_reverse($ancestors, true);
        return $ancestors;

    }

    /**
     * Get the base ancestor for the object
     *
     * see getAncestors for parameters
     * @see self::getAncestors
     */
    static function &getBaseAncestor(array $args)
    {
        $ancestors = self::getAncestors($args);
        $ancestors = array_shift($ancestors);
        return $ancestors;
    }

    /**
     * Get a module's itemtypes
     *
     * @param int     args[moduleid]
     * @param string args[module]
     * @param bool   args[native]
     * @param bool   args[extensions]
     * @todo don't use args
     * @todo pick moduleid or module
     * @todo move this into a utils class?
     */
    static function getModuleItemTypes(array $args)
    {
        extract($args);
        // Argument checks
        if (empty($moduleid) && empty($module)) {
            throw new BadParameterException('moduleid or module');
        }
        if (empty($module)) {
            $info = xarModGetInfo($moduleid);
            $module = $info['name'];
        }

        $native = isset($native) ? $native : true;
        $extensions = isset($extensions) ? $extensions : true;

        $types = array();
        if ($native) {
            // Try to get the itemtypes
            try {
                // @todo create an adaptor class for procedural getitemtypes in modules
                $types = xarModAPIFunc($module,'user','getitemtypes',array());
            } catch ( FunctionNotFoundException $e) {
                // No worries
            }
        }
        if ($extensions) {
            // Get all the objects at once
            $xartable =& xarDBGetTables();
            sys::import('modules.roles.class.xarQuery');
            $q = new xarQuery('SELECT',$xartable['dynamic_objects']);
            $q->addfields(array('xar_object_id AS objectid','xar_object_label AS objectlabel','xar_object_moduleid AS moduleid','xar_object_itemtype AS itemtype','xar_object_parent AS parent'));
            $q->eq('xar_object_moduleid',$moduleid);
            if (!$q->run()) return;

            // put in itemtype as key for easier manipulation
            foreach($q->output() as $row)
                $types [$row['itemtype']] = array(
                                            'label' => $row['objectlabel'],
                                            'title' => xarML('View #(1)',$row['objectlabel']),
                                            'url' => xarModURL('dynamicdata','user','view',array('itemtype' => $row['itemtype'])));
        }

        return $types;
    }

    protected function load()
    {
        $this->descriptor->refresh($this);
    }

}
?>
