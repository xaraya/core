<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
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

class DataObjectMaster extends Object
{
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
    public $datastore   = '';             // the datastore for the DD object
    public $datasources = array();        // the db source tables of this object
    public $dataquery;                    // the initialization query of this obect
    public $isalias     = 0;

    public $class       = 'DataObject'; // the class name of this DD object
    public $filepath    = 'auto';       // the path to the class of this DD object (can be empty or 'auto' for DataObject)
    public $properties  = array();      // list of properties for the DD object
    public $fieldlist   = array();      // array of properties to be displayed
    public $fieldorder  = array();      // displayorder for the properties
    public $fieldprefix = '';           // prefix to use in field names etc.
// CHECKME: should be overridden by DataObjectList and DataObject to exclude DISPLAYONLY resp. VIEWONLY !?
    public $status      = 65;           // inital status is active and can add/modify
    public $propertyprefix   = 'dd_';   // The prefix used for automatic designations of property names and IDs in templates
    public $anonymous   = 0;            // if true forces display of names of properties instead of dd_xx designations

    public $layout = 'default';         // optional layout inside the templates
    public $template = '';              // optional sub-template, e.g. user-objectview-[template].xt (defaults to the object name)
    public $tplmodule = 'dynamicdata';  // optional module where the object templates reside (defaults to 'dynamicdata')
    public $linktype = 'user';          // optional link type for use in getActionURL() (defaults to 'user' for module URLs, 'object' for object URLs)
    public $linkfunc = 'display';       // optional link function for use in getActionURL() (defaults to 'display', unused for object URLs)
    private $cached_urls  = array();    // cached URLs for use in getActionURL()

    public $primary = null;             // primary key is item id (or objectid in the case of the objects object)
    public $secondary = null;           // secondary key could be item type (e.g. for articles)
    public $filter = false;             // set this true to automatically filter by current itemtype on secondary key
    public $upload = false;             // flag indicating if this object has some property that provides file upload
    public $propertyargs;
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

    public $isgrouped     = 0;          // indicates that we have operations (COUNT, SUM, etc.) on properties

    /**
     * Default constructor to set the object variables, retrieve the dynamic properties
     * and get the corresponding data stores for those properties
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve, or
     *
     * @param $args['fieldlist'] optional list of properties to use, or
     * @param $args['status'] optional status of the properties to use
     * @param $args['allprops'] skip disabled properties by default
     * @todo  This does too much, split it up
    **/

    public function toArray(Array $args=array())
    {
        $properties = $this->getPublicProperties();
        foreach ($properties as $key => $value) if (!isset($args[$key])) $args[$key] = $value;
        //FIXME where do we need to define the modname best?
        if (!empty($args['moduleid'])) $args['modname'] = xarMod::getName($args['moduleid']);
        return $args;
    }

    public function loader(DataObjectDescriptor $descriptor)
    {
        $this->descriptor = $descriptor;
        $descriptor->refresh($this);

        xarMod::loadDbInfo('dynamicdata','dynamicdata');

        // use the object name as default template override (*-*-[template].x*)
        if(empty($this->template) && !empty($this->name))
            $this->template = $this->name;

        // get the properties defined for this object
        sys::import('modules.dynamicdata.class.properties.master');
        foreach ($this->propertyargs as $row) DataPropertyMaster::addProperty($row, $this);
        unset($this->propertyargs);

        // Make sure we have a primary key
        foreach ($this->properties as $property)
            if ($property->type == 21) $this->primary = $property->name;

        // create the list of fields, filtering where necessary
        $this->fieldlist = $this->setupFieldList($this->fieldlist,$this->status);

        // Set the configuration parameters
        if ($descriptor->exists('config')) {
            try {
                $configargs = unserialize($descriptor->get('config'));
                foreach ($configargs as $key => $value) if (!empty($key)) $this->{$key} = $value;
                $this->configuration = $configargs;
            } catch (Exception $e) {}
        }

        // set the specific item id (or 0)
        if(isset($args['itemid'])) $this->itemid = $args['itemid'];
        
        sys::import('xaraya.structures.query');
        $this->dataquery = new Query();
        if ($descriptor->exists('datastore')) {
            $this->datastore = $descriptor->get('datastore');
            if ($this->datastore == 'relational') {
                // We start from scratch
                $this->dataquery->cleartables();
                $this->assembleQuery($this);
            }
        } else {
            $this->datastore = 'dynamicdata';
        }

        if (isset($this->configuration['where'])) {
            $conditions = $this->setWhere($this->configuration['where']);
            $this->dataquery->addconditions($conditions);
        }
        
        // always mark the internal DD objects as 'private' (= items 1-3 in xar_dynamic_objects, see xarinit.php)
        if (!empty($this->objectid) && $this->objectid == 1 && !empty($this->itemid) && $this->itemid <= 3) {
            $this->visibility = 'private';
/* CHECKME: issue warning for static table as well ?
        } elseif (empty($this->objectid) && !empty($this->table)) {
            $this->visibility = 'static table';
*/
        }
//        $this->dataquery->qecho();echo "<br/><br />";
        // build the list of relevant data stores where we'll get/set our data
        try {
            $this->getDataStore();
        } catch (Exception $e) {
            echo $e->getMessage();;
        }
           
        // Explode the configuration
        try{
            $this->configuration = unserialize($this->config);
        } catch (Exception $e) {}
        
        // Explode the access rules
        try{
            $this->access_rules = unserialize($this->access);            
        } catch (Exception $e) {}
    }

    private function propertysource($sourcestring, $object, $prefix=false)
    {
        $parts = explode('.',$sourcestring);
        if (!isset($parts[1])) throw new Exception(xarML('Bad property definition'));
        $parts[0] = trim($parts[0]);
        if ($parts[0] == 'this' || $parts[0] == $object->name) {
            if ($prefix) return $object->name . "_" . $object->properties[$parts[1]]->source;
            else return $object->properties[$parts[1]]->source;
        } else {
            $foreignobject = self::getObject(array('name' => $parts[0]));
            $foreignstore = $foreignobject->properties[$parts[1]]->source;
            $foreignparts = explode('.',$foreignstore);
            $foreignconfiguration = $foreignobject->datasources;
            if (!isset($foreignconfiguration[$foreignparts[0]])) throw new Exception(xarML('Bad foreign datasource'));
            $foreigntable = $foreignconfiguration[$foreignparts[0]];
            // Support simple array form
            if (is_array($foreigntable)) $foreigntable = current($foreigntable);
            
            // Add the foreign table to this object's query
            $object->dataquery->addtable($foreigntable,$parts[0] . "_" . $foreignparts[0]);
            return $parts[0] . "_" . $foreignstore;
        }
    }

    /**
     * Show an filter form for this item
     */
    public function showFilterForm(Array $args = array())
    {
        $args = $args + $this->getPublicProperties();
        if (isset($args['fieldprefix'])) $this->setFieldPrefix($args['fieldprefix']);

        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if(!empty($args['preview'])) $this->checkInput();

// CHECKME: this has no real purpose here anymore ???
        // Set all properties based on what is passed in.
        $properties = $this->getProperties($args);

        if (!empty($args['fieldlist']) && !is_array($args['fieldlist'])) {
            $args['fieldlist'] = explode(',',$args['fieldlist']);
            if (!is_array($args['fieldlist'])) throw new Exception('Badly formed fieldlist attribute');
        }
        
        if(count($args['fieldlist']) > 0) {
            $fields = $args['fieldlist'];
        } else {
            $fields = array_keys($this->properties);
        }

        $args['properties'] = array();
        foreach($fields as $name) {
            if(!isset($this->properties[$name])) continue;

            if(($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED)
            || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)
            || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY)) continue;

            $args['properties'][$name] =& $this->properties[$name];
        }

        // pass some extra template variables for use in BL tags, API calls etc.
        //FIXME: check these
        $args['isprimary'] = !empty($this->primary);
        $args['catid'] = !empty($this->catid) ? $this->catid : null;
        $args['object'] = $this;
        return xarTpl::object($args['tplmodule'],$args['template'],'showfilterform',$args);
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
        foreach (array_keys($this->properties) as $property)
            $this->properties[$property]->_fieldprefix = $prefix;
        return true;
    }

    public function setFieldList($fieldlist=array(),$status=array())
    {
        if (empty($fieldlist)) $fieldlist = $this->setupFieldList();
        if (!is_array($fieldlist)) {
            try {
                $fieldlist = explode(',',$fieldlist);
            } catch (Exception $e) {
                throw new Exception(xarML('Badly formed fieldlist attribute'));
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
            if (!is_array($fieldlist)) $fieldlist = explode(',', $fieldlist);
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
                // temporarily nullifying the effect of above added IF condition
                $filterstate = -1;
                foreach($this->properties as $property)
                    if($property->getDisplayStatus() != DataPropertyMaster::DD_DISPLAYSTATE_DISABLED &&
                       $property->getDisplayStatus() != $filterstate)
                        $fields[$property->id] = $property->name;
            }
        }
        return $fields;
    }

    /**
     * Set the display status of some properties
     */
    public function setDisplayStatus($fieldlist=array(), $status)
    {
        if(!empty($fieldlist)) {
            foreach($fieldlist as $field)
                // Ignore those disabled AND those that don't exist
                if(isset($this->properties[$field]))
                    $this->properties[$field]->setDisplayStatus($status);
        }
        return true;
    }

    /**
     * Get the data stores where the dynamic properties of this object are kept
    **/
    public function getDataStore($reset = false)
    {
        switch ($this->datastore) {
            case 'relational': $this->addDataStore('relational', 'relational'); break;
            case 'module_variables': 
                try {
                    $firstproperty = reset($this->properties);
                    // FIXME: this needs a better design
                    $name = trim(substr($firstproperty->source,17));
                    $this->addDataStore($name, 'modulevars'); 
                } catch (Exception $e) {
                    throw new Exception(xarML('Did not find a first property for module variable datastore'));
                }
                break;
            case 'dynamicdata': $this->addDataStore('_dynamic_data_', 'data'); break;
        }
    }

    /**
     * Add a data store for this object
     *
     * @param $name the name for the data store
     * @param $type the type of data store
    **/
    public function addDataStore($name = '_dynamic_data_', $type='data')
    {
        // get the data store
        sys::import('modules.dynamicdata.class.datastores.master');
        $this->datastore = DataStoreFactory::getDataStore($name, $type);

        // Pass along a reference to this object
        $this->datastore->object = $this;

        // for dynamic object lists, put a reference to the $itemids array in the data store
        if(method_exists($this, 'getItems'))
            $this->datastore->_itemids =& $this->itemids;
    }

    /**
     * Get the selected dynamic properties for this object
    **/
    public function &getProperties($args = array())
    {
        if(!empty($args['fieldlist'])) {
            $fields = $this->getFieldList();
            $this->setFieldList($args['fieldlist']);
        }

        $properties = array();
        foreach($this->getFieldList() as $name) {
            if (isset($this->properties[$name])) {
                // Filter for state if one is passed
                if (!empty($args['status'])) {
                    if (is_array($args['status'])) {
                        if (!in_array($this->properties[$name]->getDisplayStatus(), $args['status'])) continue;
                    } else {
                        if ($this->properties[$name]->getDisplayStatus() != $args['status']) continue;
                    }
                }
                // Pass along a field prefix if there is one
                if (!empty($args['fieldprefix'])) $this->properties[$name]->_fieldprefix = $args['fieldprefix'];
                $properties[$name] = &$this->properties[$name];
                // Pass along the directive of what property name to display
                if (isset($args['anonymous'])) $this->properties[$name]->anonymous = $args['anonymous'];
            }
        }

        if(!empty($args['fieldlist'])) $this->setFieldList($fields);
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
    public function addProperty(Array $args=array())
    {
        // TODO: find some way to have unique IDs across all objects if necessary
        if(!isset($args['id']))
            $args['id'] = count($this->properties) + 1;
        sys::import('modules.dynamicdata.class.properties.master');
        DataPropertyMaster::addProperty($args,$this);
        return true;
    }

    /**
     * Modify a property for this object
     *
     * @param $property the property or its name (required)
     * @param $args an array of parameters that re to be changed
     *
    **/
    public function modifyProperty($property, Array $args=array())
    {
        if (!is_object($property)) {
            // Check what we assume to be a name
            if (isset($this->properties[$property])) {
                $property =& $this->properties[$property];
            } else {
                $msg = xarML('Bad property name parameter for modifyProperty');
                throw new Exception($msg);
            }
        } else {
            Check if this is object is a property of this dataobject
            if (!isset($this->properties[$property->name])) {
                $msg = xarML('Bad property object parameter for modifyProperty');
                throw new Exception($msg);
            }
        }
        // Get the description of the property and add its args to those passed
        $args = $args + $property->descriptor->getArgs();
        // Remove the property we are changing;
        unset($this->properties[$property->name]);
        // Add a new property,like the old, but with the changes passed
        $this->addProperty($args);
        return true;
    }

    /**
     * Class method to retrieve information about all DataObjects
     *
     * @return array of object definitions
    **/
    public static function &getObjects(Array $args=array())
    {
        extract($args);
        $dbconn = xarDB::getConn();
        xarMod::loadDbInfo('dynamicdata','dynamicdata');
        $xartable =& xarDB::getTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $bindvars = array();
        xarLog::message("DB: query in getObjects");
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
     * @return array containing the name => value pairs for the object
     * @todo when we had a constructor which was more passive, this could be non-static. (cheap construction is a good rule of thumb)
     * @todo no ref return?
     * @todo when we can turn this into an object method, we dont have to do db inclusion all the time.
    **/
    public static function getObjectInfo(Array $args=array())
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
        if(xarCoreCache::isCached($cacheKey,$infoid)) {
            return xarCoreCache::getCached($cacheKey,$infoid);
        }
 
        $dbconn = xarDB::getConn();
        xarMod::loadDbInfo('dynamicdata','dynamicdata');
        $xartable =& xarDB::getTables();
 
        $dynamicobjects = $xartable['dynamic_objects'];
 
        $bindvars = array();
        xarLog::message('DD: query in getObjectInfo');
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
        if(!$result->first()) return;
        $info = array();
        list(
            $info['objectid'], $info['name'],     $info['label'],
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
        ) = $result->fields;
        $result->close();
 
        xarCore::setCached($cacheKey,$info['objectid'],$info);
        xarCore::setCached($cacheKey,$info['name'],$info);
        return $info;
    }
    
    private static function _getObjectInfo(Array $args=array())
    {
        if (!isset($args['objectid']) && (!isset($args['name']))) {
           throw new Exception(xarML('Cannot get object information without an objectid or a name'));
        }

        $cacheKey = 'DynamicData._ObjectInfo';
        if(isset($args['objectid']) && xarCore::isCached($cacheKey,$args['objectid'])) {
            return xarCore::getCached($cacheKey,$args['objectid']);
        }
        if(isset($args['name']) && xarCore::isCached($cacheKey,$args['name'])) {
            return xarCore::getCached($cacheKey,$args['name']);
        }

        sys::import('modules.dynamicdata.xartables');
        xarDB::importTables(dynamicdata_xartables());
        $xartable =& xarDB::getTables();
        sys::import('xaraya.structures.query');
        $q = new Query();

        $q->addtable($xartable['dynamic_objects'],'o');
        $q->addtable($xartable['dynamic_properties'],'p');
        $q->leftjoin('o.id','p.object_id');
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
            $q->eq('o.id',$args['objectid']);
        } else {
            $q->eq('o.name',$args['name']);
        }
        $q->addfield('p.id AS id');
        $q->addfield('p.name AS name');
        $q->addfield('p.label AS label');
        $q->addfield('p.type AS type');
        $q->addfield('p.defaultvalue AS defaultvalue');
        $q->addfield('p.source AS source');
        $q->addfield('p.status AS status');
        $q->addfield('p.seq AS seq');
        $q->addfield('p.configuration AS configuration');
        $q->addfield('p.object_id AS _objectid');
        $q->setorder('p.seq');
        if (!$q->run()) return false;
        $result = $q->output();
        $row = $q->row();
        if (!empty($row)) {
            xarCore::setCached($cacheKey,$row['object_id'],$result);
            xarCore::setCached($cacheKey,$row['object_name'],$result);
        }
        return $result;
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
    public static function getObject(Array $args=array())
    {
        /* with autoload and variable caching activated */
        // Identify the variable by its arguments here
        $hash = md5(serialize($args));
        // Get a cache key for this variable if it's suitable for variable caching
        $cacheKey = xarCache::getObjectKey('DataObject', $hash);
        // Check if the variable is cached
        if (!empty($cacheKey) && xarVariableCache::isCached($cacheKey)) {
            // Return the cached variable
            $object = xarVariableCache::getCached($cacheKey);
            return $object;
        }

        $info = self::_getObjectInfo($args);
        
        // If we have no such object, just return false for now
        if (empty($info)) return false;
        /*{
            if (isset($args['name'])) $identifier = xarML('the name is #(1)',$args['name']);
            if (isset($args['objectid'])) $identifier = xarML('the objectid is #(1)',$args['objectid']);
            throw new Exception(xarML('Unable to get an object where #(1)', $identifier));
        }*/
        
        $current = current($info);
        foreach ($current as $key => $value) 
            if (strpos($key, 'object_') === 0) $data[substr($key,7)] = $value;
        $data = array_merge($args,$data);
        $data['propertyargs'] =& $info;
        
        // Create the object if it was not in cache
        xarLog::message("DataObjectMaster::getObject: Getting a new object " . $data['class']);

        if(!empty($data['filepath']) && ($data['filepath'] != 'auto')) include_once(sys::code() . $data['filepath']);
        else sys::import('modules.dynamicdata.class.objects.base');
        $descriptor = new DataObjectDescriptor($data);
        $object = new $data['class']($descriptor);
        
        /* with autoload and variable caching activated */
        // Set the variable in cache
        if (!empty($cacheKey)) {
            xarVariableCache::setCached($cacheKey, $object);
        }
        return $object;
    }
    
    public static function getfObject(Array $args=array())
    {
        /* with autoload and variable caching activated */
        // Identify the variable by its arguments here
        $hash = md5(serialize($args));
        // Get a cache key for this variable if it's suitable for variable caching
        $cacheKey = xarCache::getVariableKey('DataObject', $hash);
        // Check if the variable is cached
        if (!empty($cacheKey) && xarVariableCache::isCached($cacheKey)) {
            // Return the cached variable
            $object = xarVariableCache::getCached($cacheKey);
            return $object;
        }
        if(!isset($args['itemid'])) $args['itemid'] = null;

// FIXME: clean up redundancy between self:getObjectInfo($args) and new DataObjectDescriptor($args)
        // Complete the info if this is a known object
        $info = self::_getObjectInfo($args);

        if ($info != null) $args = array_merge($args,$info);
        else return $info;

        // TODO: Try to get the object from the cache ?
//        if (!empty($args['objectid']) && xarCoreCache::isCached('DDObject', $args['objectid'])) {
//            // serialize is better here - shallow cloning is not enough for array of properties, datastores etc. and with deep cloning internal references are lost
//            $object = unserialize(xarCoreCache::getCached('DDObject', $args['objectid']));
//            return $object;
//        }

        if(!empty($args['filepath']) && ($args['filepath'] != 'auto')) include_once(sys::code() . $args['filepath']);
        else sys::import('modules.dynamicdata.class.objects.base');
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
//        xarCoreCache::setCached('DDObject', $args['objectid'], serialize($object));

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
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['name'] name of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['class'] optional classname (e.g. <module>_DataObject[_List])
     * @return object the requested object definition
     * @todo   automatic sub-classing per module (and itemtype) ?
     * @todo   get rid of the classname munging, use typing
    **/
    public static function getObjectList(Array $args=array())
    {
        /* with autoload and variable caching activated */
        // Identify the variable by its arguments here
        $hash = md5(serialize($args));
        // Get a cache key for this variable if it's suitable for variable caching
        $cacheKey = xarCache::getObjectKey('DataObjectList', $hash);
        // Check if the variable is cached
        if (!empty($cacheKey) && xarVariableCache::isCached($cacheKey)) {
            // Return the cached variable
            $object = xarVariableCache::getCached($cacheKey);
            return $object;
        }
// FIXME: clean up redundancy between self:getObjectInfo($args) and new DataObjectDescriptor($args)
        // Complete the info if this is a known object
        $info = self::_getObjectInfo($args);
        if (empty($info)) {
            if (isset($args['name'])) $identifier = xarML('the name is #(1)',$args['name']);
            if (isset($args['objectid'])) $identifier = xarML('the objectid is #(1)',$args['objectid']);
            throw new Exception(xarML('Unable to create an object where #(1)', $identifier));
        }
        $current = current($info);
        foreach ($current as $key => $value) 
            if (strpos($key, 'object_') === 0) $data[substr($key,7)] = $value;
        $data = $args + $data;        
        $data['propertyargs'] =& $info;

        sys::import('modules.dynamicdata.class.objects.list');
        $class = 'DataObjectList';
        if(!empty($data['filepath']) && ($data['filepath'] != 'auto')) include_once(sys::code() . $data['filepath']);
        if(!empty($data['class']))
        {
            if(class_exists($data['class'] . 'List'))
            {
                // this is a generic classname for the object, list and interface
                $class = $data['class'] . 'List';
            }
            elseif(class_exists($data['class']) && method_exists($data['class'],'getItems'))
            {
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
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['name'] name of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['class'] optional classname (e.g. <module>_DataObject[_Interface])
     * @return object the requested object definition
     * @todo  get rid of the classname munging
     * @todo  automatic sub-classing per module (and itemtype) ?
    **/
    public static function &getObjectInterface(Array $args=array())
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

    public static function isObject(Array $args)
    {
        $info = self::_getObjectInfo($args);
        return !empty($info);
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
    public static function createObject(Array $args=array())
    {
        // TODO: if we extend dobject classes then probably we need to put the class name here
        $object = self::getObject(array('name' => 'objects'));

        // Create specific part
        $descriptor = new DataObjectDescriptor($args);
        $objectid = $object->createItem($descriptor->getArgs());
        $classname = get_class($object);
        xarLog::message("Creating an object of class " . $classname . ". Objectid: " . $objectid . ", module: " . $args['moduleid'] . ", itemtype: " . $args['itemtype']);
        unset($object);
        return $objectid;
    }

    public static function updateObject(Array $args=array())
    {
        $object = self::getObject(array('name' => 'objects'));

        // Update specific part
        $itemid = $object->getItem(array('itemid' => $args['objectid']));
        if(empty($itemid)) return;
        $itemid = $object->updateItem($args);
        unset($object);
        return $itemid;
    }

    public static function deleteObject(Array $args=array())
    {
        $descriptor = new DataObjectDescriptor($args);
        $args = $descriptor->getArgs();

        // Last stand against wild hooks and other excesses
        if($args['objectid'] < 5)
        {
            $msg = 'You cannot delete the DynamicDat classes';
            throw new BadParameterException(null, $msg);
        }

        // Do direct queries here, for speed
        xarMod::load('dynamicdata');
        $tables =& xarDB::getTables();

        sys::import('xaraya.structures.query');
        // TODO: delete all the (dynamic ?) data for this object

        // Delete all the properties of this object
        $q = new Query('DELETE', $tables['dynamic_properties']);
        $q->eq('object_id',$args['objectid']);
        if (!$q->run()) return false;
        
        // Delete the object itself
        $q = new Query('DELETE', $tables['dynamic_objects']);
        $q->eq('id',$args['objectid']);
        if (!$q->run()) return false;

        return true;
    }

    /**
     * Get the names and values of the object's properties
     */
    public function getFieldValues(Array $args = array(), $bypass = 0)
    {
        $fields = array();
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

    public function setFieldValues(Array $args = array(), $bypass = 0)
    {
        if ($bypass) {
            foreach ($args as $key => $value)
                if (isset($this->properties[$key])) $this->properties[$key]->value = $value;
        } else {
            foreach ($args as $key => $value)
                if (isset($this->properties[$key]))  $this->properties[$key]->setValue($value);
        }
        return true;
    }

    public function clearFieldValues(Array $args = array())
    {
        $properties = $this->getProperties($args);
        foreach ($properties as $property) {
            $fields[$property->name] = $property->clearValue();
        }
        return true;
    }

    /**
     * Get the labels and values to include in some output display for this item
     */
    public function getDisplayValues(Array $args = array())
    {
        $displayvalues = array();
        $properties = $this->getProperties($args);
        foreach($properties as $property) {
            $label = xarVarPrepForDisplay($property->label);
            $displayvalues[$label] = $property->showOutput();
        }
        return $displayvalues;

        /* FIXME: the status value isn't being used correctly I think
        if(count($args['fieldlist']) > 0 || !empty($this->status))
        {
            foreach($args['fieldlist'] as $name)
                if(isset($this->properties[$name]))
                {
                    $label = xarVarPrepForDisplay($this->properties[$name]->label);
                    $displayvalues[$label] = $this->properties[$name]->showOutput();
                }
        }
        else
        {
            foreach(array_keys($this->properties) as $name)
            {
                $label = xarVarPrepForDisplay($this->properties[$name]->label);
                $displayvalues[$label] = $this->properties[$name]->showOutput();
            }
        }
        return $displayvalues;
        */
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
    public static function getModuleItemTypes(Array $args=array())
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
            xarMod::loadDbInfo('dynamicdata','dynamicdata');
            $xartable =& xarDB::getTables();

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
     * Generate URL for a specific action on an object - the format will depend on the linktype
     *
     * @access public
     * @param object object the object or object list we want to create an URL for
     * @param action string the action we want to take on this object (= method or func)
     * @param itemid mixed the specific item id or null
     * @param extra array extra arguments to pass to the URL - CHECKME: we should only need itemid here !?
     * @return string the generated URL
     */
    public function getActionURL($action = '', $itemid = null, $extra = array())
    {
        // if we have a cached URL already, use that
        if (!empty($itemid) && empty($extra) && !empty($this->cached_urls[$action])) {
            $url = str_replace('=<itemid>', '='.$itemid, $this->cached_urls[$action]);
            return $url;
        }

        // get URL for this object and action
        $url = xarObject::getActionURL($this, $action, $itemid, $extra);

        // cache the URL if the itemid is in there
        if (!empty($itemid) && empty($extra) && strpos($url, $this->urlparam . '=' . $itemid) !== false) {
            $this->cached_urls[$action] = str_replace($this->urlparam . '=' . $itemid, $this->urlparam . '=<itemid>', $url);
        }

        return $url;
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
        } elseif (xarCoreCache::isCached('DynamicData','HookAction')) {
            return;
        }

        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        $modname = xarMod::getName($this->moduleid);
        if($modname == 'articles' || $modname == 'roles') {
            return;
        }

        // CHECKME: prevent recursive hook calls in general
        xarCoreCache::setCached('DynamicData','HookAction',$action);

        // <chris> moved this from xarObjectHooks::initHookSubject()
        // This is the correct place to handle it, hooks system doesn't need to know
        // initialize hookvalues
        $this->hookvalues = array();
    
        // Note: you can preset the list of properties to be transformed via $this->hooktransform
        
        // add property values to hookvalues
        if ($action == 'transform') {
            if (!empty($this->hooktransform)) {
                $fields = $this->hooktransform;
            } else {
                $fields = array_keys($this->properties);
            }
            $this->hookvalues['transform'] = array();

            foreach($fields as $name) {
            // TODO: this is exactly the same as in the dataobject display function, consolidate it ?
                if(!isset($this->properties[$name])) continue;

                if(($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED)
                || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY)
                || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)) continue;

                // *never* transform an ID
                // TODO: there is probably lots more to skip here.
                if ($this->properties[$name]->type != 21) {
                    $this->hookvalues['transform'][] = $name;
                }
                $this->hookvalues[$name] = $this->properties[$name]->value;
            }
            $this->hooktransform = $this->hookvalues['transform'];
        } else {
            foreach(array_keys($this->properties) as $name)
                $this->hookvalues[$name] = $this->properties[$name]->value;
            $this->hooktransform = array();
        }

        // add extra info for traditional hook modules
// FIXME: THis causes problems if you have a property named "module", "itemtype" etc.
//        $this->hookvalues['module'] = xarMod::getName($this->moduleid);
//        $this->hookvalues['itemtype'] = $this->itemtype;
//        $this->hookvalues['itemid'] = $this->itemid;
        // CHECKME: is this sufficient in most cases, or do we need an explicit xarModURL() ?
        $this->hookvalues['returnurl'] = xarServer::getCurrentURL();

        // Use the standard method to call hooks 
        $hooks = xarModCallHooks('item', $action, $this->itemid, $this->hookvalues);
        // FIXME: we don't need two distinct properties to store gui and api hook responses
        // A response is a response, it's up to the caller to decide if it's appropriate
        // For now we'll populate both with the same data
        $this->hookvalues = $this->hookoutput = $hooks;       
        
        // let xarObjectHooks worry about calling the different hooks
        //xarObjectHooks::callHooks($this, $action);

        // the result of API actions will be in $this->hookvalues
        // the result of GUI actions will be in $this->hookoutput

        // CHECKME: prevent recursive hook calls in general
        xarCoreCache::delCached('DynamicData','HookAction');
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

    private function assembleQuery($object, $prefix=false, $type="SELECT")
    {
        $descriptor = $object->descriptor;
        // Set up the db tables
        if ($descriptor->exists('sources')) {
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
                        }

                        // Remove any spaces and similar chars
                        $value = trim($value);
                        $key = trim($key);

                        // Default to variable datasource if we find that anywhere
                        if ($key == 'variable') {
                            $object->datasources = array('variable' => 'variable');
                            $this->dataquery->cleartables();
                            break;
                        } else {
                            if ($type != "SELECT" && $tabletype != "internal") continue;
//                            if (is_array($value)) $value = current($value);
                            if ($prefix) $this->dataquery->addtable($value,$object->name . "_" . $key);
                            else $this->dataquery->addtable($value,$key);
                        }
                    }
                }
            } catch (Exception $e) {
                echo xarML('Found sources: ');var_dump($sources);
                throw new Exception(xarML('Error reading object sources'));
            }
        }

        // Set up the db table relations
        if ($descriptor->exists('relations')) {
            try {
                $relationargs = @unserialize($descriptor->get('relations'));
                if (is_array($relationargs)) {
                    foreach ($relationargs as $key => $value) {
                    
                        // Support simple array form
    //                    if (is_array($value)) $value = current($value);

                        // Bail if we are missing anything
                        if (count($value) < 2) continue;
                    
                        // Remove any spaces and similar chars
                        $left = trim($value[0]);
                        $right = trim($value[1]);
                    
                        // If this was just the empty first line, bail
                        if (empty($left)) continue;
                    
                        // Check if this relation includes a foreign table
                        // If it does do a left or right join, rather than an inner join
                        $join = "";
                        $fromobjectparts = explode('.',$left);
                        $fromobject = $fromobjectparts[0];
                        if (isset($object->datasources[$fromobject])) {
                            if (isset($object->datasources[$fromobject][1]) && $object->datasources[$fromobject][1] == 'internal') {
                                $join = 'join';
                            } else {
                                if ($type != "SELECT") continue;
                                $join = 'rightjoin';
                            }
                        }
                    
                        $toobjectparts = explode('.',$right);
                        $toobject = $toobjectparts[0];
                        if (isset($object->datasources[$toobject])) {
                            if (isset($object->datasources[$toobject][1]) && $object->datasources[$toobject][1] == 'internal') {
                                $join = 'join';
                            } else {
                                if ($type != "SELECT") continue;
                                $join = 'leftjoin';
                            }                        
                        }
                    
                        // If no join was defined, then this is a bad realtion: ignore
                        if (empty($join)) continue;
                    
                        // Add this relation's join to the object's dataquery
                        if ($prefix) $this->dataquery->{$join}($object->name . "_" . $left,$object->name . "_" . $right);
                        else $this->dataquery->{$join}($left,$right);
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
    //                    if (is_array($value)) $value = current($value);

                        // Bail if we are missing anything
                        if (count($value) < 2) continue;
                    
                        // Remove any spaces and similar chars
                        $left = trim($value[0]);
                        $right = trim($value[1]);

                        // If this was just the empty first line, bail
                        if (empty($left)) continue;
                        if (empty($right)) continue;
                    
                        if ((strpos($left, 'this') === false) && (strpos($right, 'this') === false)
                        && (strpos($left, $object->name) === false) && (strpos($right, $object->name) === false)
                        ) 
                            echo 'One of the links must be of a property of ' . $object->name . '<br />';
                        try {
                            $leftside = $object->propertysource($left, $object, $prefix);
                        } catch (Exception $e) {echo 'Cannot translate ' . $left . ' to a valid datasource<br />'; }
                        try {
                            $rightside = $object->propertysource($right, $object, $prefix);
                        } catch (Exception $e) {echo 'Cannot translate ' . $right . ' to a valid datasource<br />'; }
                        $this->dataquery->leftjoin($leftside,$rightside);
                    
                        // FIXME: We don't yet support a sort order for related object items, so order them by ID for now
                        $parts = explode('.',$right);
                        $table = trim($parts[0]);
                        // We should actually sort by the object's primary key, but lets forgoe that for now
    //                    $this->dataquery->setorder($table . ".id");
                    }
                }
            } catch (Exception $e) {
                if (isset($left)) echo 'Bad object relation: ' . $left . ' or ' . $right;
                else echo 'The object relation cannot be read (badly formed)';
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
     * @param action string the action we want to take on this object (= method or func)
     * @param itemid mixed the specific item id or null
     * @param roleid mixed override the current user or null // CHECKME: do we want this ?
     * @return boolean true if access
     */
    public function checkAccess($action, $itemid = null, $roleid = null)
    {
        if (empty($action)) throw new EmptyParameterException('Access method');

        // only allow direct access to tables for administrators
        if (!empty($this->table)) {
            $action = 'admin';
        }

        // default actions supported by dynamic objects
        switch($action)
        {
            case 'admin':
                // require admin access to the module here
                return xarSecurityCheck('AdminDynamicData',0);

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
            $access_rules = array();
        }

        // DD specific access scheme 
        // check if we have specific access rules for this level
        if (!empty($access_rules) && is_array($access_rules) && !empty($access_rules[$level])) {
            if (empty($roleid) && xarUserIsLoggedIn()) {
                // get the direct parents of the current user (no ancestors)
                $grouplist = xarCache::getParents();
            } elseif (!empty($roleid) && $roleid != _XAR_ID_UNREGISTERED) {
                // get the direct parents of the specified user (no ancestors)
                $grouplist = xarCache::getParents($roleid);
            } else {
                // check anonymous visitors by themselves
                $grouplist = array(_XAR_ID_UNREGISTERED);
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
            if (!empty($this->itemid)) {
                $itemid = $this->itemid;
            } else {
                $itemid = 'All';
            }
        }

        if (!empty($roleid)) {
            $role = xarRoles::get($roleid);
            $rolename = $role->getName();
            return xarSecurity::check($mask,0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid,'',$rolename);
        } else {
            return xarSecurity::check($mask,0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid);
        }
/*
        $access_method = $action . '_access';
        $access = isset($this->$access_method) ? $this->$access_method :
            array('group' => 0, 'level' => 100, 'failure' => 0);
        // Decide whether this block is displayed to the current user
        $args = array(
            'module' => $this->module,
            'component' => 'Block',
            'instance' => $this->type . ":" . $this->name . ":" . $this->bid,
            'group' => $access['group'],
            'level' => $access['level'],
        );
        if (!isset(self::$access_property)) {
            sys::import('modules.dynamicdata.class.properties.master');
            self::$access_property = DataPropertyMaster::getProperty(array('name' => 'access'));
        }
        return self::$access_property->check($args);
*/
    }

    /**
     * Translate a string containing a SQL WHERE clause into Query conditions
     *
     * @param mixed where string or array of name => value pairs
     * @return array of query conditions
     */
    public function setWhere($where, $transform=1)
    {
        // Note this helper property is only defined in this method and the methods called from here
        $this->conditions = new Query();

        if ($transform) $wherestring = $this->transformClause($where);
        else $wherestring = $where;
        
        // If the condition is empty, bail (for now)
        if (empty($wherestring)) return $this->conditions;

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
     * Transform property names to their source field names in a clause
     *
     * @param mixed where string or array of name => value pairs
     * @return string representing a SQL where clause
     */
    private function transformClause($clause)
    {
        // If the condition is empty, bail (for now)
        if (empty($clause)) return '';

        // If a string is passed, make it an array (for now)
        if (!is_array($clause)) $clause = array($clause);
        
        // If we have an array just get the first element (for now)
        if (is_array($clause)) $clause = $clause[0];

        // cfr. BL compiler - adapt as needed (I don't think == and === are accepted in SQL)
        $findLogic    = array( ' = ', ' != ',  ' < ',  ' > ', ' <= ', ' >= ');
        $replaceLogic = array(' eq ', ' ne ', ' lt ', ' gt ', ' le ', ' ge ');

        // Clean up all the operators
        $clause = str_ireplace($findLogic, $replaceLogic, $clause);

        // Replace property names with source field names
        // Note this does not preclude (if the store is a single DB table) 
        // that we have fields in the where clause with no corresponding no properties
        $findLogic    = array();
        $replaceLogic = array();
        foreach ($this->properties as $name => $property) {
            if (empty($property->source)) continue;
            $findLogic[] = '/\b' . $name . '\b/';
            $replaceLogic[] = $property->source;
        }
        $clause = preg_replace($findLogic, $replaceLogic, $clause);
        return $clause;
    }
    
    private function parseClause($clause)
    {   
        // Enclose the clause in parentheses
        $clause = '(' . $clause . ')';
        // Split the clause into its parts
        $parts = preg_split('/(\(|\)|\bor\b|\band\b)/',$clause,-1,PREG_SPLIT_DELIM_CAPTURE);
        
        $processed_parts = array();
        if (empty($parts)) return $processed_parts;
        foreach ($parts as $part) {
            $part = array_shift($parts);
            $part = trim($part);
            switch ($part) {
                case "": break;
                case "(": $processed_parts[] = array('type' => 'begin', 'value' => 1); break;
                case ")": $processed_parts[] = array('type' => 'end', 'value' => 1); break;
                case "or": $processed_parts[] = array('type' => 'operator', 'value' => $part); break;
                case "and": $processed_parts[] = array('type' => 'operator', 'value' => $part); break;
                default: $processed_parts[] = array('type' => 'operand', 'value' => $part); break;
            }
        }
        return $processed_parts;
    }
    
    private function bracketClause($parts)
    {   
        $values = array();
        $conjunctions = array();
        while (1) {
            if (empty($parts)) break;
            $part = array_shift($parts);
            switch ($part['type']) {
                case 'begin' :
                    list($parts, $subclause) = $this->bracketClause($parts);
                    $values[] = $subclause;
                break;
                case 'end' :
                    $consistent = true;
                    $this_conjunction = array_shift($conjunctions);
                    foreach ($conjunctions as $conjunction) {
                        if ($conjunction != $this_conjunction) {$consistent = false; break;}
                    }
                    if (!$consistent) throw new Exception(xarML('Inconsistent conjunctions in a clause'));
                    if ($this_conjunction == 'or') {
                        $clause = $this->conditions->qor($values);
                    } else {
                        $clause = $this->conditions->qand($values);
                    }
                    return array($parts, $clause);
                break;
                case 'operand' :
                    $values[] = $this->parseRelation($part['value']);
                break;
                case 'operator' :
                    $conjunctions[] = $part['value'];
                break;
            }
        }
    }
    
    private function parseRelation($string)
    {   
        $parts = explode(' ', $string);
        // Make sure we have enough arguments. We need to have something like "foo = 17" or "foo = 'bar'"
        if (count($parts) < 3) throw new Exception(xarML('Incorrect relation "#(1)"', $string));
        
        // Remove any parens from strings here. They will be added automatically if needed
        $parts[2] = str_replace("'", "", $parts[2]);
        
        // Construct the relation and add it to the conditions
        $func = 'p' . $parts[1];
        $relation = $this->conditions->$func($parts[0], $parts[2]);
        return $relation;
    }
}

?>