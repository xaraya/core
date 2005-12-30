<?php
/**
 * Metaclass for Dynamic Objects
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
require_once 'modules/dynamicdata/class/properties.php';
require_once 'modules/dynamicdata/class/datastores.php';

/**
 * Metaclass for Dynamic Objects
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Object_Master
{
    var $objectid = null;
    var $name = null;
    var $label = null;
    var $moduleid = null;
    var $itemtype = null;
    var $parent = null;

    var $urlparam = 'itemid';
    var $maxid = 0;
    var $config = '';
    var $isalias = 0;

    var $properties;
    var $datastores;

    var $fieldlist;
    var $status = null;

    // optional layout inside the templates
    var $layout = 'default';
    // optional sub-template, e.g. user-objectview-[template].xd (defaults to the object name)
    var $template = '';
    // optional module where the object templates reside (defaults to 'dynamicdata')
    var $tplmodule = 'dynamicdata';

    // optional module for use in xarModURL() (defaults to the object module)
    var $urlmodule = '';
    // optional view function for use in xarModURL() (defaults to 'view')
    var $viewfunc = 'view';

    // primary key is item id
    var $primary = null;
    // secondary key could be item type (e.g. for articles)
    var $secondary = null;
    // set this true to automatically filter by current itemtype on secondary key
    var $filter;

    // flag indicating if this object has some property that provides file upload
    var $upload = false;

    // prefix to use in field names etc.
    var $fieldprefix = '';

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
     */
    function Dynamic_Object_Master($args)
    {
        $this->properties = array();
        $this->datastores = array();

        $this->fieldlist = array();

        // fill in the default object variables
        if (!empty($args) && is_array($args) && count($args) > 0) {
            foreach ($args as $key => $val) {
                $this->$key = $val;
            }
        }
        if (!empty($this->table)) {
            $meta = xarModAPIFunc('dynamicdata','util','getmeta',
                                  array('table' => $this->table));
            // we throw an exception here because we assume a table should always exist (for now)
            if (!isset($meta) || !isset($meta[$this->table])) {
                $msg = xarML('Invalid #(1) #(2) for dynamic object #(3)',
                             'table',$this->table,$this->table);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
                return;
            }
            foreach ($meta[$this->table] as $name => $propinfo) {
                $this->addProperty($propinfo);
            }
        }
        if (empty($this->moduleid)) {
            if (empty($this->objectid)) {
                $this->moduleid = xarModGetIDFromName(xarModGetName());
            }
        } elseif (!is_numeric($this->moduleid) && is_string($this->moduleid)) {
            $this->moduleid = xarModGetIDFromName($this->moduleid);
        }
        if (empty($this->itemtype)) {
            $this->itemtype = 0;
        }
        if (empty($this->parent)) {
            $this->parent = 1;
        }
        if (empty($this->name)) {
            $info = Dynamic_Object_Master::getObjectInfo($args);
            if (isset($info) && count($info) > 0) {
                foreach ($info as $key => $val) {
                    $this->$key = $val;
                }
            }
        }
        // use the object name as default template override (*-*-[template].x*)
        if (empty($this->template) && !empty($this->name)) {
            $this->template = $this->name;
        }
        // get the properties defined for this object
        if (count($this->properties) == 0 &&
            (isset($this->objectid) || (isset($this->moduleid) && isset($this->itemtype)))
           ) {
           if (!isset($args['allprops'])) {
               $args['allprops'] = null;
           }
           Dynamic_Property_Master::getProperties(array('objectid'  => $this->objectid,
                                                        'moduleid'  => $this->moduleid,
                                                        'itemtype'  => $this->itemtype,
                                                        'allprops'  => $args['allprops'],
                                                        'objectref' => & $this)); // we pass this object along
        }
        if (!empty($this->join)) {
            $meta = xarModAPIFunc('dynamicdata','util','getmeta',
                                  array('table' => $this->join));
            // we throw an exception here because we assume a table should always exist (for now)
            if (!isset($meta) || !isset($meta[$this->join])) {
                $msg = xarML('Invalid #(1) #(2) for dynamic object #(3)',
                             'join',$this->join,$this->name);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
                return;
            }
            $count = count($this->properties);
            foreach ($meta[$this->join] as $name => $propinfo) {
                $this->addProperty($propinfo);
            }
            if (count($this->properties) > $count) {
                // put join properties in front
                $joinprops = array_splice($this->properties,$count);
                $this->properties = array_merge($joinprops,$this->properties);
            }
        }
        // filter on property status if necessary
        if (isset($this->status) && count($this->fieldlist) == 0) {
            $this->fieldlist = array();
            foreach ($this->properties as $name => $property) {
                if ($property->status == $this->status) {
                    $this->fieldlist[] = $name;
                }
            }
        }
        // build the list of relevant data stores where we'll get/set our data
        if (count($this->datastores) == 0 &&
            count($this->properties) > 0) {
           $this->getDataStores();
        }
    }

    /**
     * Get the data stores where the dynamic properties of this object are kept
     */
    function &getDataStores($reset = false)
    {
        // if we already have the datastores
        if (!$reset && isset($this->datastores) && count($this->datastores) > 0) {
            return $this->datastores;
        }

        // if we're filtering on property status and there are no properties matching this status
        if (!$reset && !empty($this->status) && count($this->fieldlist) == 0) {
            return $this->datastores;
        }

        // reset field list of datastores if necessary
        if ($reset && count($this->datastores) > 0) {
            foreach (array_keys($this->datastores) as $storename) {
                $this->datastores[$storename]->fields = array();
            }
        }

        // check the fieldlist for valid property names and for operations like COUNT, SUM etc.
        if (!empty($this->fieldlist) && count($this->fieldlist) > 0) {
            $cleanlist = array();
            foreach ($this->fieldlist as $name) {
                if (!strstr($name,'(')) {
                    if (isset($this->properties[$name])) {
                        $cleanlist[] = $name;
                    }
                } elseif (preg_match('/^(.+)\((.+)\)/',$name,$matches)) {
                    $operation = $matches[1];
                    $field = $matches[2];
                    if (isset($this->properties[$field])) {
                        $this->properties[$field]->operation = $operation;
                        $cleanlist[] = $field;
                        $this->isgrouped = 1;
                    }
                }
            }
            $this->fieldlist = $cleanlist;
        }

        foreach ($this->properties as $name => $property) {
            // skip properties we're not interested in (but always include the item id field)
            if (empty($this->fieldlist) || in_array($name,$this->fieldlist) || $property->type == 21) {
            } else {
                $this->properties[$name]->datastore = '';
                continue;
            }

            list($storename, $storetype) = $this->property2datastore($property);
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
            if (!isset($this->secondary) && $property->type == 20 && !empty($this->filter)) {
                $this->secondary = $name;
            }
        }
        return $this->datastores;
    }

    /**
     * Find the datastore name and type corresponding to the data source of a property
     */
    function property2datastore(&$property)
    {
        // normal dynamic data field
        if ($property->source == 'dynamic_data') {
            $storename = '_dynamic_data_';
            $storetype = 'data';

        // data field coming from some static table : [database.]table.field
        } elseif (preg_match('/^(.+)\.(\w+)$/', $property->source, $matches)) {
            $table = $matches[1];
            $field = $matches[2];
            $storename = $table;
            $storetype = 'table';

        // data managed by a hook/utility module
        } elseif ($property->source == 'hook module') {
            $storename = '_hooks_';
            $storetype = 'hook';

        // data managed by some user function (specified in validation for now)
        } elseif ($property->source == 'user function') {
            $storename = '_functions_';
            $storetype = 'function';

        // data available in user variables
        } elseif ($property->source == 'user settings') {
            // we'll keep a separate data store per module/itemtype here for now
        // TODO: (don't) integrate user variable handling with DD
            $storename = 'uservars_'.$this->moduleid.'_'.$this->itemtype;
            $storetype = 'uservars';

        // data available in module variables
        } elseif ($property->source == 'module variables') {
            // we'll keep a separate data store per module/itemtype here for now
        // TODO: (don't) integrate module variable handling with DD
            $storename = 'modulevars_'.$this->moduleid.'_'.$this->itemtype;
            $storetype = 'modulevars';

        // no data storage
        } elseif ($property->source == 'dummy') {
            $storename = '_dummy_';
            $storetype = 'dummy';

        // TODO: extend with LDAP, file, ...
        } else {
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
     */
    function addDataStore($name = '_dynamic_data_', $type='data')
    {
        // get a new data store
        $datastore =& Dynamic_DataStore_Master::getDataStore($name, $type);

        // add it to the list of data stores
        $this->datastores[$datastore->name] =& $datastore;

        // for dynamic object lists, put a reference to the $itemids array in the data store
        if (method_exists($this, 'getItems')) {
            $this->datastores[$datastore->name]->_itemids =& $this->itemids;
        }
    }

    /**
     * Get the selected dynamic properties for this object
     */
    function &getProperties($args = array())
    {
        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        // return only the properties we're interested in (might be none)
        if (count($args['fieldlist']) > 0 || !empty($this->status)) {
            $properties = array();
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    $properties[$name] = & $this->properties[$name];
                }
            }
        } else {
            $properties = & $this->properties;
        }

        return $properties;
    }

    /**
     * Add a property for this object
     *
     * @param $args['name'] the name for the dynamic property (required)
     * @param $args['type'] the type of dynamic property (required)
     * @param $args['label'] the label for the dynamic property
     * @param $args['id'] the id for the dynamic property
     * ...
     */
    function addProperty($args)
    {
        // TODO: find some way to have unique IDs across all objects if necessary
        if (!isset($args['id'])) {
            $args['id'] = count($this->properties) + 1;
        }
        Dynamic_Property_Master::addProperty($args,$this);
    }

    /**
     * Class method to retrieve information about all Dynamic Objects
     *
     * @returns array
     * @return array of object definitions
     */
    function &getObjects($args=array())
    {
        extract($args);
        $nullreturn = NULL;
        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $dynamicobjects = $xartable['dynamic_objects'];

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
        if (isset($modid)) $query .= "WHERE xar_object_moduleid = " . $modid;
        $result =& $dbconn->Execute($query);

        if (!$result) return $nullreturn;

        $objects = array();
        while (!$result->EOF) {
            $info = array();
            list($info['objectid'],
                 $info['name'],
                 $info['label'],
                 $info['moduleid'],
                 $info['itemtype'],
                 $info['parent'],
                 $info['urlparam'],
                 $info['maxid'],
                 $info['config'],
                 $info['isalias']) = $result->fields;
             $objects[$info['objectid']] = $info;
             $result->MoveNext();
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
     * @returns array
     * @return array containing the name => value pairs for the object
     */
    function getObjectInfo($args)
    {
        if (!empty($args['table'])) {
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
        if (!empty($args['objectid'])) {
            $query .= " WHERE xar_object_id = ? ";
            $bindvars[] = (int) $args['objectid'];
        } elseif (!empty($args['name'])) {
            $query .= " WHERE xar_object_name = ? ";
            $bindvars[] = (string) $args['name'];
        } else {
            if (empty($args['moduleid'])) {
                $args['moduleid'] = xarModGetIDFromName(xarModGetName());
            }
            if (empty($args['itemtype'])) {
                $args['itemtype'] = 0;
            }
            $query .= " WHERE xar_object_moduleid = ?
                          AND xar_object_itemtype = ? ";
            $bindvars[] = (int) $args['moduleid'];
            $bindvars[] = (int) $args['itemtype'];
        }
        $result =& $dbconn->Execute($query,$bindvars);
        if (!$result || $result->EOF) return;

        $info = array();
        list($info['objectid'],
             $info['name'],
             $info['label'],
             $info['moduleid'],
             $info['itemtype'],
             $info['parent'],
             $info['urlparam'],
             $info['maxid'],
             $info['config'],
             $info['isalias']) = $result->fields;

        $result->Close();
        if (!empty($args['join'])) {
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
     * @param $args['classname'] optional classname (e.g. <module>_Dynamic_Object)
     * @returns object
     * @return the requested object definition
     */
    function &getObject($args)
    {
        if (!isset($args['itemid'])) $args['itemid'] = null;
        $classname = 'Dynamic_Object';
        if (!empty($args['classname']) && class_exists($args['classname'])) {
            $classname = $args['classname'];
/*
        // TODO: automatic sub-classing per module (and itemtype) ?
        } elseif (!empty($args['moduleid'])) {
            $modInfo = xarModGetInfo($args['moduleid']);
            $modName = strtolower($modInfo['name']);
            if ($modName != 'dynamicdata') {
                $classname = "{$modName}_Dynamic_Object";
                if (!class_exists($classname))
                    $classname = 'Dynamic_Object';
            }
*/
        }
        // here we can use our own classes to retrieve this
        $object = new $classname($args);
        return $object;
    }

    /**
     * Class method to retrieve a particular object list definition, with sub-classing
     * (= the same as creating a new Dynamic Object List)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['classname'] optional classname (e.g. <module>_Dynamic_Object[_List])
     * @returns object
     * @return the requested object definition
     */
    function &getObjectList($args)
    {
        $classname = 'Dynamic_Object_List';
        if (!empty($args['classname'])) {
            if (class_exists($args['classname'] . '_List')) {
                // this is a generic classname for the object, list and interface
                $classname = $args['classname'] . '_List';
            } elseif (class_exists($args['classname'])) {
                // this is a specific classname for the list
                $classname = $args['classname'];
            }
/*
        // TODO: automatic sub-classing per module (and itemtype) ?
        } elseif (!empty($args['moduleid'])) {
            $modInfo = xarModGetInfo($args['moduleid']);
            $modName = strtolower($modInfo['name']);
            if ($modName != 'dynamicdata') {
                $classname = "{$modName}_Dynamic_Object_List";
                if (!class_exists($classname))
                    $classname = 'Dynamic_Object_List';
            }
*/
        }
        // here we can use our own classes to retrieve this
        $object = new $classname($args);
        return $object;
    }

    /**
     * Class method to retrieve a particular object interface definition, with sub-classing
     * (= the same as creating a new Dynamic Object Interface)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['classname'] optional classname (e.g. <module>_Dynamic_Object[_Interface])
     * @returns object
     * @return the requested object definition
     */
    function &getObjectInterface($args)
    {
        require_once 'modules/dynamicdata/class/interface.php';

        $classname = 'Dynamic_Object_Interface';
        if (!empty($args['classname'])) {
            if (class_exists($args['classname'] . '_Interface')) {
                // this is a generic classname for the object, list and interface
                $classname = $args['classname'] . '_Interface';
            } elseif (class_exists($args['classname'])) {
                // this is a specific classname for the interface
                $classname = $args['classname'];
            }
/*
        // TODO: automatic sub-classing per module (and itemtype) ?
        } elseif (!empty($args['moduleid'])) {
            $modInfo = xarModGetInfo($args['moduleid']);
            $modName = strtolower($modInfo['name']);
            if ($modName != 'dynamicdata') {
                $classname = "{$modName}_Dynamic_Object_Interface";
                if (!class_exists($classname))
                    $classname = 'Dynamic_Object_Interface';
            }
*/
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
     * @param $args['classname'] optional classname (e.g. <module>_Dynamic_Object)
     * @returns integer
     * @return the object id of the created item
     */
    function createObject($args)
    {
        if (!isset($args['moduleid'])) {
            $args['moduleid'] = null;
        }
        if (!isset($args['itemtype'])) {
            $args['itemtype'] = null;
        }
        if (!isset($args['classname'])) {
            $args['classname'] = null;
        }
        // create the Dynamic Objects item corresponding to this object
        $object =& Dynamic_Object_Master::getObject(array('objectid' => 1, // the Dynamic Objects = 1
                                                          'moduleid' => $args['moduleid'],
                                                          'itemtype' => $args['itemtype'],
                                                          'classname' => $args['classname']));
        $objectid = $object->createItem($args);
        return $objectid;
    }

    function updateObject($args)
    {
        if (empty($args['objectid'])) {
            return;
        }
        if (!isset($args['moduleid'])) {
            $args['moduleid'] = null;
        }
        if (!isset($args['itemtype'])) {
            $args['itemtype'] = null;
        }
        if (!isset($args['classname'])) {
            $args['classname'] = null;
        }
        // update the Dynamic Objects item corresponding to this object
        $object =& Dynamic_Object_Master::getObject(array('objectid' => 1, // the Dynamic Objects = 1
                                                          'moduleid' => $args['moduleid'],
                                                          'itemtype' => $args['itemtype'],
                                                          'classname' => $args['classname']));
        $itemid = $object->getItem(array('itemid' => $args['objectid']));
        if (empty($itemid)) return;
        $itemid = $object->updateItem($args);
        return $itemid;
    }

    function deleteObject($args)
    {
        if (empty($args['objectid'])) {
            return;
        }
        if (!isset($args['moduleid'])) {
            $args['moduleid'] = null;
        }
        if (!isset($args['itemtype'])) {
            $args['itemtype'] = null;
        }
        if (!isset($args['classname'])) {
            $args['classname'] = null;
        }
        // get the Dynamic Objects item corresponding to this object
        $object =& Dynamic_Object_Master::getObject(array('objectid' => 1, // the Dynamic Objects = 1
                                                          'moduleid' => $args['moduleid'],
                                                          'itemtype' => $args['itemtype'],
                                                          'classname' => $args['classname']));
        if (empty($object)) return;

        $itemid = $object->getItem(array('itemid' => $args['objectid']));
        if (empty($itemid)) return;

        // get an object list for the object itself, so we can delete its items
        $mylist =& Dynamic_Object_Master::getObjectList(array('objectid' => $args['objectid'],
                                                              'moduleid' => $args['moduleid'],
                                                              'itemtype' => $args['itemtype'],
                                                              'classname' => $args['classname']));
        if (empty($mylist)) return;

        // TODO: delete all the (dynamic ?) data for this object

        // delete all the properties for this object
        foreach (array_keys($mylist->properties) as $name) {
            $propid = $mylist->properties[$name]->id;
            $propid = Dynamic_Property_Master::deleteProperty(array('itemid' => $propid));
        }

        // delete the Dynamic Objects item corresponding to this object
        return $object->deleteItem();
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
     * ...
     */
    function joinTable($args)
    {
        if (empty($args['table'])) return;
        $meta = xarModAPIFunc('dynamicdata','util','getmeta',
                              array('table' => $args['table']));
        // we throw an exception here because we assume a table should always exist (for now)
        if (!isset($meta) || !isset($meta[$args['table']])) {
            $msg = xarML('Invalid #(1) #(2) for dynamic object #(3)',
                         'join',$args['table'],$this->name);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
            return;
        }
        $count = count($this->properties);
        foreach ($meta[$args['table']] as $name => $propinfo) {
            $this->addProperty($propinfo);
        }
        $table = $args['table'];
        $key = null;
        if (!empty($args['key']) && isset($this->properties[$args['key']])) {
            $key = $this->properties[$args['key']]->source;
        }
        $fields = array();
        if (!empty($args['fields'])) {
            foreach ($args['fields'] as $field) {
                if (isset($this->properties[$field])) {
                    $fields[$field] =& $this->properties[$field];
                    if (count($this->fieldlist) > 0 && !in_array($field,$this->fieldlist)) {
                        $this->fieldlist[] = $field;
                    }
                }
            }
        }
        $where = array();
        if (!empty($args['where'])) {
            // cfr. BL compiler - adapt as needed (I don't think == and === are accepted in SQL)
            $findLogic      = array(' eq ', ' ne ', ' lt ', ' gt ', ' id ', ' nd ', ' le ', ' ge ');
            $replaceLogic   = array( ' = ', ' != ',  ' < ',  ' > ',  ' = ', ' != ', ' <= ', ' >= ');

            $args['where'] = str_replace($findLogic, $replaceLogic, $args['where']);

            $parts = preg_split('/\s+(and|or)\s+/',$args['where'],-1,PREG_SPLIT_DELIM_CAPTURE);
            $join = '';
            foreach ($parts as $part) {
                if ($part == 'and' || $part == 'or') {
                    $join = $part;
                    continue;
                }
                $pieces = preg_split('/\s+/',$part);
                $name = array_shift($pieces);
                // sanity check on SQL
                if (count($pieces) < 2) {
                    $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                                 'query ' . $args['where'], 'Dynamic_Object_Master', 'joinTable', 'DynamicData');
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    return;
                }
                // for many-to-1 relationships where you specify the foreign key in the original table here
                // (e.g. properties joined to xar_dynamic_objects -> where object_id eq objectid)
                if (!empty($pieces[1]) && is_string($pieces[1]) && isset($this->properties[$pieces[1]])) {
                    $pieces[1] = $this->properties[$pieces[1]]->source;
                }
                if (isset($this->properties[$name])) {
                    $where[] = array('property' => &$this->properties[$name],
                                     'clause' => join(' ',$pieces),
                                     'join' => $join);
                }
            }
        }
        if (!empty($args['andor'])) {
            $andor = $args['andor'];
        } else {
            $andor = 'and';
        }
        foreach (array_keys($this->datastores) as $name) {
             $this->datastores[$name]->addJoin($table, $key, $fields, $where, $andor);
        }
    }

}

/**
 * Dynamic Object
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Object extends Dynamic_Object_Master
{
    var $itemid = 0;

    /**
     * Inherits from Dynamic_Object_Master and sets the requested item id
     *
     * @param $args['itemid'] item id of the object to get
     */
    function Dynamic_Object($args)
    {
        // get the object type information from our parent class
        $this->Dynamic_Object_Master($args);

        // set the specific item id (or 0)
        if (isset($args['itemid'])) {
            $this->itemid = $args['itemid'];
        }

        // see if we can access this object, at least in overview
        if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',$this->moduleid.':'.$this->itemtype.':'.$this->itemid)) return;

        // don't retrieve the item here yet !
        //$this->getItem();
    }

    /**
     * Retrieve the values for this item
     */
    function getItem($args = array())
    {
        if (!empty($args['itemid'])) {
            if ($args['itemid'] != $this->itemid) {
                // initialise the properties again
                foreach (array_keys($this->properties) as $name) {
                    $this->properties[$name]->value = $this->properties[$name]->default;
                }
            }
            $this->itemid = $args['itemid'];
        }
        if (empty($this->itemid)) {
            $msg = xarML('Invalid item id in method #(1)() for dynamic object [#(2)] #(3)',
                         'getItem',$this->objectid,$this->name);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
            return;
        }
        if (!empty($this->primary) && !empty($this->properties[$this->primary])) {
            $primarystore = $this->properties[$this->primary]->datastore;
        }
        $modinfo = xarModGetInfo($this->moduleid);
        foreach ($this->datastores as $name => $datastore) {
            $itemid = $datastore->getItem(array('modid'    => $this->moduleid,
                                                'itemtype' => $this->itemtype,
                                                'itemid'   => $this->itemid,
                                                'modname'  => $modinfo['name']));
            // only worry about finding something in primary datastore (if any)
            if (empty($itemid) && !empty($primarystore) && $primarystore == $name) return;
        }
        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if (!empty($args['preview'])) {
            $this->checkInput();
        }
        return $this->itemid;
    }

    /**
     * Check the different input values for this item
     */
    function checkInput($args = array())
    {
        if (!empty($args['itemid']) && $args['itemid'] != $this->itemid) {
            $this->itemid = $args['itemid'];
            $this->getItem($args);
        }
        if (empty($args['fieldprefix'])) {
            $args['fieldprefix'] = $this->fieldprefix;
        }
        $isvalid = true;
        foreach (array_keys($this->properties) as $name) {
            // for hooks, use the values passed via $extrainfo if available
            $field = 'dd_' . $this->properties[$name]->id;
            if (isset($args[$name])) {
                if (!$this->properties[$name]->checkInput($name,$args[$name])) {
                    $isvalid = false;
                }
            } elseif (isset($args[$field])) {
                if (!$this->properties[$name]->checkInput($field,$args[$field])) {
                    $isvalid = false;
                }
            } elseif (!empty($args['fieldprefix'])) {
                // cfr. prefix layout in objects/showform template
                $field = $args['fieldprefix'] . '_' . $field;
                if (!$this->properties[$name]->checkInput($field)) {
                    $isvalid = false;
                }
            } elseif (!$this->properties[$name]->checkInput()) {
                $isvalid = false;
            }
        }
        return $isvalid;
    }

    /**
     * Show an input form for this item
     */
    function showForm($args = array())
    {
        if (empty($args['layout'])) {
            $args['layout'] = $this->layout;
        }
        if (empty($args['template'])) {
            $args['template'] = $this->template;
        }
        if (empty($args['tplmodule'])) {
            $args['tplmodule'] = $this->tplmodule;
        }
        if (empty($args['viewfunc'])) {
            $args['viewfunc'] = $this->viewfunc;
        }
        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        if (empty($args['fieldprefix'])) {
            $args['fieldprefix'] = $this->fieldprefix;
        }
        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if (!empty($args['preview'])) {
            $this->checkInput();
        }
        if (count($args['fieldlist']) > 0 || !empty($this->status)) {
            $args['properties'] = array();
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    $args['properties'][$name] = & $this->properties[$name];
                }
            }
        } else {
            $args['properties'] = & $this->properties;
        }

        // pass some extra template variables for use in BL tags, API calls etc.
        if (empty($this->name)) {
           $args['objectname'] = null;
        } else {
           $args['objectname'] = $this->name;
        }
        $args['moduleid'] = $this->moduleid;
        $modinfo = xarModGetInfo($this->moduleid);
        $args['modname'] = $modinfo['name'];
        if (empty($this->itemtype)) {
            $args['itemtype'] = null; // don't add to URL
        } else {
            $args['itemtype'] = $this->itemtype;
        }
        $args['itemid'] = $this->itemid;
        if (!empty($this->primary)) {
            $args['isprimary'] = true;
        } else {
            $args['isprimary'] = false;
        }
        if (!empty($this->catid)) {
            $args['catid'] = $this->catid;
        } else {
            $args['catid'] = null;
        }

        return xarTplObject($args['tplmodule'],$args['template'],'showform',$args);
    }

    /**
     * Show an output display for this item
     */
    function showDisplay($args = array())
    {
        if (empty($args['layout'])) {
            $args['layout'] = $this->layout;
        }
        if (empty($args['template'])) {
            $args['template'] = $this->template;
        }
        if (empty($args['tplmodule'])) {
            $args['tplmodule'] = $this->tplmodule;
        }
        if (empty($args['viewfunc'])) {
            $args['viewfunc'] = $this->viewfunc;
        }
        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if (!empty($args['preview'])) {
            $this->checkInput();
        }
        if (count($args['fieldlist']) > 0 || !empty($this->status)) {
            $args['properties'] = array();
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    $thisprop = $this->properties[$name];
                    if ($thisprop->status != 3)
                        $args['properties'][$name] = & $this->properties[$name];
                }
            }
        } else {
            foreach ($this->properties as $property) {
                if ($property->status != 3)
                    $args['properties'][$property->name] = $property;
            }
        }

        // pass some extra template variables for use in BL tags, API calls etc.
        if (empty($this->name)) {
           $args['objectname'] = null;
        } else {
           $args['objectname'] = $this->name;
        }
        $args['moduleid'] = $this->moduleid;
        $modinfo = xarModGetInfo($this->moduleid);
        $args['modname'] = $modinfo['name'];
        if (empty($this->itemtype)) {
            $args['itemtype'] = null; // don't add to URL
        } else {
            $args['itemtype'] = $this->itemtype;
        }
        $args['itemid'] = $this->itemid;
        if (!empty($this->primary)) {
            $args['isprimary'] = true;
        } else {
            $args['isprimary'] = false;
        }
        if (!empty($this->catid)) {
            $args['catid'] = $this->catid;
        } else {
            $args['catid'] = null;
        }

        return xarTplObject($args['tplmodule'],$args['template'],'showdisplay',$args);
    }

    /**
     * Get the names and values of
     */
    function getFieldValues($args = array())
    {
        if (empty($args['fieldlist'])) {

            if (count($this->fieldlist) > 0) {
                $fieldlist = $this->fieldlist;
            } else {
                $fieldlist = array_keys($this->properties);
            }
        }

        $fields = array();
        foreach ($fieldlist as $name) {
            $property = $this->properties[$name];
            if(xarSecurityCheck('ReadDynamicDataField',0,'Field',$property->name.':'.$property->type.':'.$property->id)) {
                $fields[$name] = $property->value;
            }
        }

        return $fields;
    }


    /**
     * Get the labels and values to include in some output display for this item
     */
    function getDisplayValues($args = array())
    {
        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        $displayvalues = array();
        if (count($args['fieldlist']) > 0 || !empty($this->status)) {
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    $label = xarVarPrepForDisplay($this->properties[$name]->label);
                    $displayvalues[$label] = $this->properties[$name]->showOutput();
                }
            }
        } else {
            foreach (array_keys($this->properties) as $name) {
                $label = xarVarPrepForDisplay($this->properties[$name]->label);
                $displayvalues[$label] = $this->properties[$name]->showOutput();
            }
        }
        return $displayvalues;
    }

    function createItem($args = array())
    {
        if (count($args) > 0) {
            if (isset($args['itemid'])) {
                $this->itemid = $args['itemid'];
            }
            foreach ($args as $name => $value) {
                if (isset($this->properties[$name])) {
                    $this->properties[$name]->setValue($value);
                }
            }
        }

        $modinfo = xarModGetInfo($this->moduleid);

        // special case when we try to create a new object handled by dynamicdata
        if ($this->objectid == 1 &&
            $this->properties['moduleid']->value == xarModGetIDFromName('dynamicdata') &&
            $this->properties['itemtype']->value < 2) {
            $this->properties['itemtype']->setValue($this->getNextItemtype($args));
        }

        // check that we have a valid item id, or that we can create one if it's set to 0
        if (empty($this->itemid)) {
            // no primary key identified for this object, so we're stuck
            if (!isset($this->primary)) {
                $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                             'primary key', 'Dynamic_Object', 'createItem', 'DynamicData');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
                return;

            } else {
                $value = $this->properties[$this->primary]->getValue();

                // we already have an itemid value in the properties
                if (!empty($value)) {
                    $this->itemid = $value;

                // we'll let the primary datastore create an itemid for us
                } elseif (!empty($this->properties[$this->primary]->datastore)) {
                    $primarystore = $this->properties[$this->primary]->datastore;
                    // add the primary to the data store fields if necessary
                    if (!empty($this->fieldlist) && !in_array($this->primary,$this->fieldlist)) {
                        $this->datastores[$primarystore]->addField($this->properties[$this->primary]); // use reference to original property
                    }
                    $this->itemid = $this->datastores[$primarystore]->createItem(array('objectid' => $this->objectid,
                                                                                       'modid'    => $this->moduleid,
                                                                                       'itemtype' => $this->itemtype,
                                                                                       'itemid'   => $this->itemid,
                                                                                       'modname'  => $modinfo['name']));

                } else {
                    $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                                 'primary key datastore', 'Dynamic Object', 'createItem', 'DynamicData');
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    return;
                }
            }
        }
        if (empty($this->itemid)) return;

    // TODO: this won't work for objects with several static tables !
        // now let's try to create items in the other data stores
        foreach (array_keys($this->datastores) as $store) {
            // skip the primary store
            if (isset($primarystore) && $store == $primarystore) {
                continue;
            }
            $itemid = $this->datastores[$store]->createItem(array('objectid' => $this->objectid,
                                                                  'modid'    => $this->moduleid,
                                                                  'itemtype' => $this->itemtype,
                                                                  'itemid'   => $this->itemid,
                                                                  'modname'  => $modinfo['name']));
            if (empty($itemid)) return;
        }

        // call create hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
        if (!empty($this->primary) && ($modinfo['name'] != 'articles') && ($modinfo['name'] != 'roles')) {
            $item = array();
            foreach (array_keys($this->properties) as $name) {
                $item[$name] = $this->properties[$name]->value;
            }
            $item['module'] = $modinfo['name'];
            $item['itemtype'] = $this->itemtype;
            $item['itemid'] = $this->itemid;
            xarModCallHooks('item', 'create', $this->itemid, $item, $modinfo['name']);
        }

        return $this->itemid;
    }

    function updateItem($args = array())
    {
        if (count($args) > 0) {
            if (!empty($args['itemid'])) {
                $this->itemid = $args['itemid'];
            }
            foreach ($args as $name => $value) {
                if (isset($this->properties[$name])) {
                    $this->properties[$name]->setValue($value);
                }
            }
        }

        if (empty($this->itemid)) {
            $msg = xarML('Invalid item id in method #(1)() for dynamic object [#(2)] #(3)',
                         'updateItem',$this->objectid,$this->name);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
            return;
        }

        $modinfo = xarModGetInfo($this->moduleid);
    // TODO: this won't work for objects with several static tables !
        // update all the data stores
        foreach (array_keys($this->datastores) as $store) {
            $itemid = $this->datastores[$store]->updateItem(array('objectid' => $this->objectid,
                                                                  'modid'    => $this->moduleid,
                                                                  'itemtype' => $this->itemtype,
                                                                  'itemid'   => $this->itemid,
                                                                  'modname'  => $modinfo['name']));
            if (empty($itemid)) return;
        }

        // call update hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
        if (!empty($this->primary) && ($modinfo['name'] != 'articles') && ($modinfo['name'] != 'roles')) {
            $item = array();
            foreach (array_keys($this->properties) as $name) {
                $item[$name] = $this->properties[$name]->value;
            }
            $item['module'] = $modinfo['name'];
            $item['itemtype'] = $this->itemtype;
            $item['itemid'] = $this->itemid;
            xarModCallHooks('item', 'update', $this->itemid, $item, $modinfo['name']);
        }

        return $this->itemid;
    }

    function deleteItem($args = array())
    {
        if (!empty($args['itemid'])) {
            $this->itemid = $args['itemid'];
        }

        if (empty($this->itemid)) {
            $msg = xarML('Invalid item id in method #(1)() for dynamic object [#(2)] #(3)',
                         'deleteItem',$this->objectid,$this->name);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
            return;
        }

        $modinfo = xarModGetInfo($this->moduleid);

    // TODO: this won't work for objects with several static tables !
        // delete the item in all the data stores
        foreach (array_keys($this->datastores) as $store) {
            $itemid = $this->datastores[$store]->deleteItem(array('objectid' => $this->objectid,
                                                                  'modid'    => $this->moduleid,
                                                                  'itemtype' => $this->itemtype,
                                                                  'itemid'   => $this->itemid,
                                                                  'modname'  => $modinfo['name']));
            if (empty($itemid)) return;
        }

        // call delete hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
        if (!empty($this->primary) && ($modinfo['name'] != 'articles') && ($modinfo['name'] != 'roles')) {
            $item = array();
            foreach (array_keys($this->properties) as $name) {
                $item[$name] = $this->properties[$name]->value;
            }
            $item['module'] = $modinfo['name'];
            $item['itemtype'] = $this->itemtype;
            $item['itemid'] = $this->itemid;
            xarModCallHooks('item', 'delete', $this->itemid, $item, $modinfo['name']);
        }

        return $this->itemid;
    }

    /**
     * Get the next available item type (for objects that are assigned to the dynamicdata module)
     *
     * @param $args['moduleid'] module id for the object
     * @returns integer
     * @return value of the next item type
     */
    function getNextItemtype($args = array())
    {
        if (empty($args['moduleid'])) {
            $args['moduleid'] = $this->moduleid;
        }
        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $query = "SELECT MAX(xar_object_itemtype)
                    FROM $dynamicobjects
                   WHERE xar_object_moduleid = ?";

        $result =& $dbconn->Execute($query,array((int)$args['moduleid']));
        if (!$result || $result->EOF) return;

        $nexttype = $result->fields[0];

        $result->Close();

        // Note: this is *not* reliable in "multi-creator" environments
        $nexttype++;
        return $nexttype;
    }
}

/**
 * Dynamic Object List
 * Note : for performance reasons, we won't use an array of objects here,
 *        but a single object with an array of item values
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Object_List extends Dynamic_Object_Master
{
    var $itemids;           // the list of item ids used in data stores
    var $where;
    var $sort;
    var $groupby;
    var $numitems = null;
    var $startnum = null;

    var $startstore = null; // the data store we should start with (for sort)

    var $items;             // the result array of itemid => (property name => value)

    // optional URL style for use in xarModURL() (defaults to itemtype=...&...)
    var $urlstyle = 'itemtype'; // TODO: table or object, or wrapper for all, or all in template, or...
    // optional display function for use in xarModURL() (defaults to 'display')
    var $linkfunc = 'display';

    /**
     * Inherits from Dynamic_Object_Master and sets the requested item ids, sort, where, ...
     *
     * @param $args['itemids'] array of item ids to return
     * @param $args['sort'] sort field(s)
     * @param $args['where'] WHERE clause to be used as part of the selection
     * @param $args['numitems'] number of items to retrieve
     * @param $args['startnum'] start number
     */
    function Dynamic_Object_List($args)
    {
        // initialize the list of item ids
        $this->itemids = array();
        // initialize the items array
        $this->items = array();

        // get the object type information from our parent class
        $this->Dynamic_Object_Master($args);

        // see if we can access these objects, at least in overview
        if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',$this->moduleid.':'.$this->itemtype.':All')) return;

        // set the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($args);
    }

    function setArguments($args)
    {
        // set the number of items to retrieve
        if (!empty($args['numitems'])) {
            $this->numitems = $args['numitems'];
        }
        // set the start number to retrieve
        if (!empty($args['startnum'])) {
            $this->startnum = $args['startnum'];
        }
        // set the list of requested item ids
        if (!empty($args['itemids'])) {
            if (is_numeric($args['itemids'])) {
                $this->itemids = array($args['itemids']);
            } elseif (is_string($args['itemids'])) {
                $this->itemids = explode(',',$args['itemids']);
            } elseif (is_array($args['itemids'])) {
                $this->itemids = $args['itemids'];
            }
        }
        if (!isset($this->itemids)) {
            $this->itemids = array();
        }

        // reset fieldlist and datastores if necessary
        if (isset($args['fieldlist']) && (!isset($this->fieldlist) || $args['fieldlist'] != $this->fieldlist)) {
            $this->fieldlist = $args['fieldlist'];
            $this->getDataStores(true);
        } elseif (isset($args['status']) && (!isset($this->status) || $args['status'] != $this->status)) {
            $this->status = $args['status'];
            $this->fieldlist = array();
            foreach ($this->properties as $name => $property) {
                if ($property->status == $this->status) {
                    $this->fieldlist[] = $name;
                }
            }
            $this->getDataStores(true);
        }

        // add where clause if itemtype is one of the properties (e.g. articles)
        if (isset($this->secondary) && !empty($this->itemtype) && $this->objectid > 2) {
           if (empty($args['where'])) {
               $args['where'] = $this->secondary . ' eq ' . $this->itemtype;
           } else {
               $args['where'] .= ' and ' . $this->secondary . ' eq ' . $this->itemtype;
           }
        }

        // Note: they can be empty here, which means overriding any previous criteria
        if (isset($args['sort']) || isset($args['where']) || isset($args['groupby']) || isset($args['cache'])) {
            foreach (array_keys($this->datastores) as $name) {
                // make sure we don't have some left-over sort criteria
                if (isset($args['sort'])) {
                    $this->datastores[$name]->cleanSort();
                }
                // make sure we don't have some left-over where clauses
                if (isset($args['where'])) {
                    $this->datastores[$name]->cleanWhere();
                }
                // make sure we don't have some left-over group by fields
                if (isset($args['groupby'])) {
                    $this->datastores[$name]->cleanGroupBy();
                }
                // pass the cache value to the datastores
                if (isset($args['cache'])) {
                    $this->datastores[$name]->cache = $args['cache'];
                }
            }
        }

        // set the sort criteria
        if (!empty($args['sort'])) {
            $this->setSort($args['sort']);
        }

        // set the where clauses
        if (!empty($args['where'])) {
            $this->setWhere($args['where']);
        }

        // set the group by fields
        if (!empty($args['groupby'])) {
            $this->setGroupBy($args['groupby']);
        }

        // set the categories
        if (!empty($args['catid'])) {
            $this->setCategories($args['catid']);
        }
    }

    function setSort($sort)
    {
        if (is_array($sort)) {
            $this->sort = $sort;
        } else {
            $this->sort = explode(',',$sort);
        }

        foreach ($this->sort as $criteria) {
            // split off trailing ASC or DESC
            if (preg_match('/^(.+)\s+(ASC|DESC)\s*$/',$criteria,$matches)) {
                $criteria = trim($matches[1]);
                $sortorder = $matches[2];
            } else {
                $sortorder = 'ASC';
            }
            if (isset($this->properties[$criteria])) {
                // pass the sort criteria to the right data store
                $datastore = $this->properties[$criteria]->datastore;
                // assign property to datastore if necessary
                if (empty($datastore)) {
                    list($storename, $storetype) = $this->property2datastore($this->properties[$criteria]);
                    if (!isset($this->datastores[$storename])) {
                        $this->addDataStore($storename, $storetype);
                    }
                    $this->properties[$criteria]->datastore = $storename;
                    $this->datastores[$storename]->addField($this->properties[$criteria]); // use reference to original property
                    $datastore = $storename;
                } elseif ($this->properties[$criteria]->type == 21) {
                    $this->datastores[$datastore]->addField($this->properties[$criteria]); // use reference to original property
                }
                $this->datastores[$datastore]->addSort($this->properties[$criteria],$sortorder);
                // if we're sorting on some field, we should start querying by the data store that holds it
                if (!isset($this->startstore)) {
                   $this->startstore = $datastore;
                }
            }
        }
    }

    function setWhere($where)
    {
        // find all single-quoted pieces of text with and/or and replace them first, to
        // allow where clauses like : title eq 'this and that' and body eq 'here or there'
        $idx = 0;
        $found = array();
        if (preg_match_all("/'(.*?)'/",$where,$matches)) {
            foreach ($matches[1] as $match) {
                // skip if it doesn't contain and/or
                if (!preg_match('/\s+(and|or)\s+/',$match)) {
                    continue;
                }
                $found[$idx] = $match;
                $match = preg_quote($match);

                $match = str_replace("#","\#",$match);

                $where = trim(preg_replace("#'$match'#","'~$idx~'",$where));
                $idx++;
            }
        }

        // cfr. BL compiler - adapt as needed (I don't think == and === are accepted in SQL)
        $findLogic      = array(' eq ', ' ne ', ' lt ', ' gt ', ' id ', ' nd ', ' le ', ' ge ');
        $replaceLogic   = array( ' = ', ' != ',  ' < ',  ' > ',  ' = ', ' != ', ' <= ', ' >= ');

        $where = str_replace($findLogic, $replaceLogic, $where);

    // TODO: reject multi-source WHERE clauses :-)
        $parts = preg_split('/\s+(and|or)\s+/',$where,-1,PREG_SPLIT_DELIM_CAPTURE);
        $join = '';
        foreach ($parts as $part) {
            if ($part == 'and' || $part == 'or') {
                $join = $part;
                continue;
            }
            $pieces = preg_split('/\s+/',$part);
            $pre = '';
            $post = '';
            $name = array_shift($pieces);
            if ($name == '(') {
                $pre = '(';
                $name = array_shift($pieces);
            }
            $last = count($pieces) - 1;
            if ($pieces[$last] == ')') {
                $post = ')';
                array_pop($pieces);
            }
            // sanity check on SQL
            if (count($pieces) < 2) {
                $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                             'query ' . $where, 'Dynamic_Object_List', 'getWhere', 'DynamicData');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
                return;
            }
            if (isset($this->properties[$name])) {
                // pass the where clause to the right data store
                $datastore = $this->properties[$name]->datastore;
                // assign property to datastore if necessary
                if (empty($datastore)) {
                    list($storename, $storetype) = $this->property2datastore($this->properties[$name]);
                    if (!isset($this->datastores[$storename])) {
                        $this->addDataStore($storename, $storetype);
                    }
                    $this->properties[$name]->datastore = $storename;
                    $this->datastores[$storename]->addField($this->properties[$name]); // use reference to original property
                    $datastore = $storename;
                } elseif ($this->properties[$name]->type == 21) {
                    $this->datastores[$datastore]->addField($this->properties[$name]); // use reference to original property
                }
                if (empty($idx)) {
                    $mywhere = join(' ',$pieces);
                } else {
                    $mywhere = '';
                    foreach ($pieces as $piece) {
                        // replace the pieces again if necessary
                        if (preg_match("#'~(\d+)~'#",$piece,$matches) && isset($found[$matches[1]])) {
                            $original = $found[$matches[1]];
                            $piece = preg_replace("#'~(\d+)~'#","'$original'",$piece);
                        }
                        $mywhere .= $piece . ' ';
                    }
                }
                $this->datastores[$datastore]->addWhere($this->properties[$name],
                                                        $mywhere,
                                                        $join,
                                                        $pre,
                                                        $post);
            }
        }
    }

    function setGroupBy($groupby)
    {
        if (is_array($groupby)) {
            $this->groupby = $groupby;
        } else {
            $this->groupby = explode(',',$groupby);
        }
        $this->isgrouped = 1;

        foreach ($this->groupby as $name) {
            if (isset($this->properties[$name])) {
                // pass the sort criteria to the right data store
                $datastore = $this->properties[$name]->datastore;
                // assign property to datastore if necessary
                if (empty($datastore)) {
                    list($storename, $storetype) = $this->property2datastore($this->properties[$name]);
                    if (!isset($this->datastores[$storename])) {
                        $this->addDataStore($storename, $storetype);
                    }
                    $this->properties[$name]->datastore = $storename;
                    $this->datastores[$storename]->addField($this->properties[$name]); // use reference to original property
                    $datastore = $storename;
                } elseif ($this->properties[$name]->type == 21) {
                    $this->datastores[$datastore]->addField($this->properties[$name]); // use reference to original property
                }
                $this->datastores[$datastore]->addGroupBy($this->properties[$name]);
                // if we're grouping by some field, we should start querying by the data store that holds it
                if (!isset($this->startstore)) {
                   $this->startstore = $datastore;
                }
            }
        }
    }

    function setCategories($catid)
    {
        if (!xarModIsAvailable('categories')) return;
        $categoriesdef = xarModAPIFunc('categories','user','leftjoin',
                                       array('modid' => $this->moduleid,
                                             'itemtype' => $this->itemtype,
                                             'catid' => $catid));
        foreach (array_keys($this->datastores) as $name) {
            $this->datastores[$name]->addJoin($categoriesdef['table'], $categoriesdef['field'], array(), $categoriesdef['where'], 'and', $categoriesdef['more']);
        }
    }

    function &getItems($args = array())
    {
        // initialize the items array
        $this->items = array();

        // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($args);

        if (empty($args['numitems'])) {
            $args['numitems'] = $this->numitems;
        }
        if (empty($args['startnum'])) {
            $args['startnum'] = $this->startnum;
        }

        // if we don't have a start store yet, but we do have a primary datastore, we'll start there
        if (empty($this->startstore) && !empty($this->primary)) {
            $this->startstore = $this->properties[$this->primary]->datastore;
        }

        // first get the items from the start store (if any)
        if (!empty($this->startstore)) {
            $this->datastores[$this->startstore]->getItems($args);

            // check if we found something - if not, no sense looking further
            if (count($this->itemids) == 0) {
                return $this->items;
            }
        }
        // then retrieve the other info about those items
        foreach (array_keys($this->datastores) as $name) {
            if (!empty($this->startstore) && $name == $this->startstore) {
                continue;
            }
            $this->datastores[$name]->getItems($args);
        }
        return $this->items;
    }

    /**
     * Count the number of items that match the selection criteria
     * Note : this must be called *before* getItems() if you're using numitems !
     */
    function countItems($args = array())
    {
        // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($args);

        // if we don't have a start store yet, but we do have a primary datastore, we'll count there
        if (empty($this->startstore) && !empty($this->primary)) {
            $this->startstore = $this->properties[$this->primary]->datastore;
        }

        // try to count the items in the start store (if any)
        if (!empty($this->startstore)) {
            $count = $this->datastores[$this->startstore]->countItems($args);
            return $count;

        // else if we don't have a start store, we're probably stuck, but we'll try the first one anyway :)
        } else {
        // TODO: find some better way to determine which data store to count in
            foreach (array_keys($this->datastores) as $name) {
                $count = $this->datastores[$name]->countItems($args);
                return $count;
            }
        }
    }

    function showList($args = array())
    {
        if (empty($args['layout'])) {
            $args['layout'] = $this->layout;
        }
        if (empty($args['template'])) {
            $args['template'] = $this->template;
        }
        if (empty($args['tplmodule'])) {
            $args['tplmodule'] = $this->tplmodule;
        }
        if (empty($args['viewfunc'])) {
            $args['viewfunc'] = $this->viewfunc;
        }
        if (empty($args['fieldprefix'])) {
            $args['fieldprefix'] = $this->fieldprefix;
        }

        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        if (count($args['fieldlist']) > 0 || !empty($this->status)) {
            $args['properties'] = array();
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    if ($this->properties[$name]->status != 3)
                        $args['properties'][$name] = & $this->properties[$name];
                }
            }
        } else {
            foreach ($this->properties as $property) {
                if ($property->status != 3)
                    $args['properties'][$property->name] = $property;
            }
        }

        $args['items'] = & $this->items;

        // add link to display the item
        if (empty($args['linkfunc'])) {
            $args['linkfunc'] = $this->linkfunc;
        }
        if (empty($args['linklabel'])) {
            $args['linklabel'] = xarML('Display');
        }
        if (empty($args['param'])) {
            $args['param'] = $this->urlparam;
        }
        if (empty($args['linkfield'])) {
            $args['linkfield'] = '';
        }

        $modinfo = xarModGetInfo($this->moduleid);
        $modname = $modinfo['name'];

        // override for viewing dynamic objects
        if ($modname == 'dynamicdata' && $this->itemtype == 0 && empty($this->table)) {
            $linktype = 'admin';
            $linkfunc = 'view';
            // Don't show link to view items that don't belong to the DD module
            // Set to 0 when interested in viewing them anyway...
            $dummy_mode = 1;
        } else {
            $linktype = 'user';
            $linkfunc = $args['linkfunc'];
            $dummy_mode = 0;
        }

        // pass some extra template variables for use in BL tags, API calls etc.
        $args['moduleid'] = $this->moduleid;

        $itemtype = $this->itemtype;
        if (empty($itemtype)) {
            $itemtype = null; // don't add to URL
        }
        if (empty($this->table)) {
            $table = null;
        } else {
            $table = $this->table;
        }
        if (empty($this->name)) {
           $args['objectname'] = null;
        } else {
           $args['objectname'] = $this->name;
        }
        $args['modname'] = $modname;
        $args['itemtype'] = $itemtype;
        $args['links'] = array();
        if (empty($args['urlmodule'])) {
            if (!empty($this->urlmodule)) {
                $args['urlmodule'] = $this->urlmodule;
            } else {
                $info = xarModAPIFunc('dynamicdata','user','getobjectinfo',array('moduleid' => $args['moduleid'], 'itemtype' => $args['itemtype']));
                $base = xarModAPIFunc('dynamicdata','user','getbaseancestor',array('objectid' => $info['objectid']));
                $args['urlmodule'] = $base['name'];
            }
        }
        foreach (array_keys($this->items) as $itemid) {
    // TODO: improve this + SECURITY !!!
            $options = array();
            if (!empty($this->isgrouped)) {
                $args['links'][$itemid] = $options;
                continue;
            }
            if(xarSecurityCheck('DeleteDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {
                if ($dummy_mode && $this->items[$itemid]['moduleid'] != 182) {
                    $options[] = array('otitle' => xarML('View'),
                                       'olink'  => '',
                                       'ojoin'  => '');
                } else {
                    $options[] = array('otitle' => xarML('View'),
                                       'olink'  => xarModURL($args['urlmodule'],$linktype,$linkfunc,
                                                   array('itemtype'     => $itemtype,
                                                         'table'        => $table,
                                                         $args['param'] => $itemid,
                                                         'template'     => $args['template'])),
                                       'ojoin'  => '');
                }
                $options[] = array('otitle' => xarML('Edit'),
                                   'olink'  => xarModURL($args['urlmodule'],'admin','modify',
                                               array('itemtype'     => $itemtype,
                                                     'table'        => $table,
                                                     $args['param'] => $itemid,
                                                     'template'     => $args['template'])),
                                   'ojoin'  => '|');
                $options[] = array('otitle' => xarML('Delete'),
                                   'olink'  => xarModURL($args['urlmodule'],'admin','delete',
                                               array('itemtype'     => $itemtype,
                                                     'table'        => $table,
                                                     $args['param'] => $itemid,
                                                     'template'     => $args['template'])),
                                   'ojoin'  => '|');
            } elseif(xarSecurityCheck('EditDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {
                if ($dummy_mode && $this->items[$itemid]['moduleid'] != 182) {
                    $options[] = array('otitle' => xarML('View'),
                                       'olink'  => '',
                                       'ojoin'  => '');
                } else {
                    $options[] = array('otitle' => xarML('View'),
                                       'olink'  => xarModURL($args['urlmodule'],$linktype,$linkfunc,
                                                   array('itemtype'     => $itemtype,
                                                         'table'        => $table,
                                                         $args['param'] => $itemid,
                                                         'template'     => $args['template'])),
                                       'ojoin'  => '');
                }
                $options[] = array('otitle' => xarML('Edit'),
                                   'olink'  => xarModURL($args['urlmodule'],'admin','modify',
                                               array('itemtype'     => $itemtype,
                                                     'table'        => $table,
                                                     $args['param'] => $itemid,
                                                     'template'     => $args['template'])),
                                   'ojoin'  => '|');
            } elseif(xarSecurityCheck('ReadDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {
                if ($dummy_mode && $this->items[$itemid]['moduleid'] != 182) {
                    $options[] = array('otitle' => xarML('View'),
                                       'olink'  => '',
                                       'ojoin'  => '');
                } else {
                    $options[] = array('otitle' => xarML('View'),
                                       'olink'  => xarModURL($args['urlmodule'],$linktype,$linkfunc,
                                                   array('itemtype'     => $itemtype,
                                                         'table'        => $table,
                                                         $args['param'] => $itemid,
                                                         'template'     => $args['template'])),
                                       'ojoin'  => '');
                }
            }
            $args['links'][$itemid] = $options;
        }
        if (!empty($this->isgrouped)) {
            foreach (array_keys($args['properties']) as $name) {
                if (!empty($this->properties[$name]->operation)) {
                    $this->properties[$name]->label = $this->properties[$name]->operation . '(' . $this->properties[$name]->label . ')';
                }
            }
        }
        if (!empty($this->primary)) {
            $args['isprimary'] = true;
        } else {
            $args['isprimary'] = false;
        }
        if (!empty($this->catid)) {
            $args['catid'] = $this->catid;
        } else {
            $args['catid'] = null;
        }

        if (isset($args['newlink'])) {
        // TODO: improve this + SECURITY !!!
        } elseif (xarSecurityCheck('AddDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':All')) {
            $args['newlink'] = xarModURL($args['urlmodule'],'admin','new',
                                         array('itemtype' => $itemtype,
                                               'table'    => $table));
        } else {
            $args['newlink'] = '';
        }

        if (empty($args['pagerurl'])) {
            $args['pagerurl'] = '';
        }
        list($args['prevurl'],
             $args['nexturl'],
             $args['sorturl']) = $this->getPager($args['pagerurl']);

        // Pass the objectid too, comfy for customizing the templates
        // with custom tags.
        $args['objectid'] = $this->objectid;

        return xarTplObject($args['tplmodule'],$args['template'],'showlist',$args);
    }

    function showView($args = array())
    {
        if (empty($args['layout'])) {
            $args['layout'] = $this->layout;
        }
        if (empty($args['template'])) {
            $args['template'] = $this->template;
        }
        if (empty($args['tplmodule'])) {
            $args['tplmodule'] = $this->tplmodule;
        }
        if (empty($args['viewfunc'])) {
            $args['viewfunc'] = $this->viewfunc;
        }

        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        if (count($args['fieldlist']) > 0 || !empty($this->status)) {
            $args['properties'] = array();
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    $thisprop = $this->properties[$name];
                    if ($thisprop->status != 3)
                        $args['properties'][$name] = & $this->properties[$name];
                }
            }
        } else {
            foreach ($this->properties as $property) {
                if ($property->status != 3)
                    $args['properties'][$property->name] = $property;
            }
        }

        $args['items'] = & $this->items;

        // add link to display the item
        if (empty($args['linkfunc'])) {
            $args['linkfunc'] = $this->linkfunc;
        }
        if (empty($args['linklabel'])) {
            $args['linklabel'] = xarML('Display');
        }
        if (empty($args['param'])) {
            $args['param'] = $this->urlparam;
        }
        if (empty($args['linkfield'])) {
            $args['linkfield'] = '';
        }

        // pass some extra template variables for use in BL tags, API calls etc.
        $args['moduleid'] = $this->moduleid;

        $modinfo = xarModGetInfo($this->moduleid);
        $modname = $modinfo['name'];
        $itemtype = $this->itemtype;
        if (empty($itemtype)) {
            $itemtype = null; // don't add to URL
        }
        if (empty($this->table)) {
            $table = null;
        } else {
            $table = $this->table;
        }
        if (empty($this->name)) {
           $args['objectname'] = null;
        } else {
           $args['objectname'] = $this->name;
        }
        $args['modname'] = $modname;
        $args['itemtype'] = $itemtype;
        $args['links'] = array();
        if (empty($args['urlmodule'])) {
            if (!empty($this->urlmodule)) {
                $args['urlmodule'] = $this->urlmodule;
            } else {
                $args['urlmodule'] = $modname;
            }
        }
        foreach (array_keys($this->items) as $itemid) {
            if (!empty($this->isgrouped)) {
                $args['links'][$itemid] = array();
                continue;
            }
            $args['links'][$itemid]['display'] =  array('otitle' => $args['linklabel'],
                                                        'olink'  => xarModURL($args['urlmodule'],'user',$args['linkfunc'],
                                                                              array('itemtype'     => $itemtype,
                                                                                    'table'        => $table,
                                                                                    $args['param'] => $itemid,
                                                                                    'template'     => $args['template'])),
                                                        'ojoin'  => '');
        }
        if (!empty($this->isgrouped)) {
            foreach (array_keys($args['properties']) as $name) {
                if (!empty($this->properties[$name]->operation)) {
                    $this->properties[$name]->label = $this->properties[$name]->operation . '(' . $this->properties[$name]->label . ')';
                }
            }
            $args['linkfield'] = 'N/A';
        }
        if (!empty($this->primary)) {
            $args['isprimary'] = true;
        } else {
            $args['isprimary'] = false;
        }
        if (!empty($this->catid)) {
            $args['catid'] = $this->catid;
        } else {
            $args['catid'] = null;
        }

        if (empty($args['pagerurl'])) {
            $args['pagerurl'] = '';
        }
        list($args['prevurl'],
             $args['nexturl'],
             $args['sorturl']) = $this->getPager($args['pagerurl']);

        return xarTplObject($args['tplmodule'],$args['template'],'showview',$args);
    }

    /**
     * Get the labels and values to include in some output view for these items
     */
    function &getViewValues($args = array())
    {
        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        if (count($args['fieldlist']) == 0 && empty($this->status)) {
            $args['fieldlist'] = array_keys($this->properties);
        }

        $viewvalues = array();
        foreach ($this->itemids as $itemid) {
            $viewvalues[$itemid] = array();
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    $label = xarVarPrepForDisplay($this->properties[$name]->label);
                    if (isset($this->items[$itemid][$name])) {
                        $value = $this->properties[$name]->showOutput(array('value' => $this->items[$itemid][$name]));
                    } else {
                        $value = '';
                    }
                    $viewvalues[$itemid][$label] = $value;
                }
            }
        }
        return $viewvalues;
    }

    function getPager($currenturl = '')
    {
        $prevurl = '';
        $nexturl = '';
        $sorturl = '';

        if (empty($this->startnum)) {
            $this->startnum = 1;
        }

    // TODO: count items before calling getItems() if we want some better pager

        // Get current URL (this uses &amp; by default now)
        if (empty($currenturl)) {
            $currenturl = xarServerGetCurrentURL();
        }

    // TODO: clean up generation of sort URL

        // get rid of current startnum and sort params
        $sorturl = $currenturl;
        $sorturl = preg_replace('/&amp;startnum=\d+/','',$sorturl);
        $sorturl = preg_replace('/\?startnum=\d+&amp;/','?',$sorturl);
        $sorturl = preg_replace('/\?startnum=\d+$/','',$sorturl);
        $sorturl = preg_replace('/&amp;sort=\w+/','',$sorturl);
        $sorturl = preg_replace('/\?sort=\w+&amp;/','?',$sorturl);
        $sorturl = preg_replace('/\?sort=\w+$/','',$sorturl);
        // add sort param at the end of the URL
        if (preg_match('/\?/',$sorturl)) {
            $sorturl = $sorturl . '&amp;sort';
        } else {
            $sorturl = $sorturl . '?sort';
        }

        if (empty($this->numitems) || ( (count($this->items) < $this->numitems) && $this->startnum == 1 )) {
            return array($prevurl,$nexturl,$sorturl);
        }

        if (preg_match('/startnum=\d+/',$currenturl)) {
            if (count($this->items) == $this->numitems) {
                $next = $this->startnum + $this->numitems;
                $nexturl = preg_replace('/startnum=\d+/',"startnum=$next",$currenturl);
            }
            if ($this->startnum > 1) {
                $prev = $this->startnum - $this->numitems;
                $prevurl = preg_replace('/startnum=\d+/',"startnum=$prev",$currenturl);
            }
        } elseif (preg_match('/\?/',$currenturl)) {
            if (count($this->items) == $this->numitems) {
                $next = $this->startnum + $this->numitems;
                $nexturl = $currenturl . '&amp;startnum=' . $next;
            }
            if ($this->startnum > 1) {
                $prev = $this->startnum - $this->numitems;
                $prevurl = $currenturl . '&amp;startnum=' . $prev;
            }
        } else {
            if (count($this->items) == $this->numitems) {
                $next = $this->startnum + $this->numitems;
                $nexturl = $currenturl . '?startnum=' . $next;
            }
            if ($this->startnum > 1) {
                $prev = $this->startnum - $this->numitems;
                $prevurl = $currenturl . '?startnum=' . $prev;
            }
        }
        return array($prevurl,$nexturl,$sorturl);
    }

    /**
     * Get items one at a time, instead of storing everything in $this->items
     */
    function getNext($args = array())
    {
        static $start = true;

        if ($start) {
            // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
            $this->setArguments($args);

            if (empty($args['numitems'])) {
                $args['numitems'] = $this->numitems;
            }
            if (empty($args['startnum'])) {
                $args['startnum'] = $this->startnum;
            }

            // if we don't have a start store yet, but we do have a primary datastore, we'll start there
            if (empty($this->startstore) && !empty($this->primary)) {
                $this->startstore = $this->properties[$this->primary]->datastore;
            }

            $start = false;
        }

        $itemid = null;
        // first get the items from the start store (if any)
        if (!empty($this->startstore)) {
            $itemid = $this->datastores[$this->startstore]->getNext($args);

            // check if we found something - if not, no sense looking further
            if (empty($itemid)) {
                return;
            }
        }
/* skip this for now !
        // then retrieve the other info about those items
        foreach (array_keys($this->datastores) as $name) {
            if (!empty($this->startstore) && $name == $this->startstore) {
                continue;
            }
            //$this->datastores[$name]->getItems($args);
            $args['itemid'] = $itemid;
            $this->datastores[$name]->getItem($args);
        }
*/
        return $itemid;
    }

}

?>
