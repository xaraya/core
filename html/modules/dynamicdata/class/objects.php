<?php
/**
 * File: $Id$
 *
 * Dynamic Object Classes
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage dynamicdata module
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
    var $name;
    var $label;
    var $moduleid = null;
    var $itemtype = null;

    var $urlparam = 'itemid';
    var $maxid = 0;
    var $config = '';
    var $isalias = 0;

    var $properties;
    var $datastores;

    var $fieldlist;
    var $status = null;

    var $layout = 'default';
    var $template = '';

    var $primary = null;

    /**
     * Default constructor to set the object variables, retrieve the dynamic properties
     * and get the corresponding data stores for those properties
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     *
     * @param $args['fieldlist'] optional list of properties to use, or
     * @param $args['status'] optional status of the properties to use
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
        if (empty($this->moduleid)) {
            $this->moduleid = xarModGetIDFromName(xarModGetName());
        } elseif (!is_numeric($this->moduleid) && is_string($this->moduleid)) {
            $this->moduleid = xarModGetIDFromName($this->moduleid);
        }
        if (empty($this->itemtype)) {
            $this->itemtype = 0;
        }
        if (empty($this->name)) {
            $this->getObjectInfo($args);
        }
        // get the properties defined for this object
        if (count($this->properties) == 0 &&
            (isset($this->objectid) || (isset($this->moduleid) && isset($this->itemtype)))
           ) {
           Dynamic_Property_Master::getProperties(array('objectid'  => $this->objectid,
                                                        'moduleid'  => $this->moduleid,
                                                        'itemtype'  => $this->itemtype),
                                                  $this); // we pass this object along
        }
        // filter on property status if necessary
        if (isset($this->status) && count($this->fieldlist) == 0) {
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
    function &getDataStores()
    {
        if (isset($this->datastores) && count($this->datastores) > 0) {
            return $this->datastores;
        }

        foreach ($this->properties as $name => $property) {
            // skip properties we're not interested in (but always include the item id field)
            if (empty($this->fieldlist) || in_array($name,$this->fieldlist) || $property->type == 21) {
            } else {
                continue;
            }

            // normal dynamic data field
            if ($property->source == 'dynamic_data') {
                $storename = '_dynamic_data_';
                $storetype = 'data';

            // data field coming from some static table
            } elseif (preg_match('/^(\w+)\.(\w+)$/', $property->source, $matches)) {
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

            // TODO: extend with LDAP, file, ...
            } else {
                $storename = '_todo_';
                $storename = 'todo';
            }

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

            // keep track of what property holds the primary key
            if (!isset($this->primary) && $property->type == 21) {
                $this->primary = $name;
            }
        }
        return $this->datastores;
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
        $datastore = Dynamic_DataStore_Master::getDataStore($name, $type);

        // add it to the list of data stores
        $this->datastores[$datastore->name] = $datastore;
    }

    /**
     * Class method to retrieve all object definitions
     *
     * @returns array
     * @return array of object definitions
     */
    function getObjects()
    {
        // here we can use our own classes to retrieve this :-)
        $objects = new Dynamic_Object_List(array('objectid' => 1));
        return $objects->getItems();
    }

    /**
     * Class method to retrieve a particular object definition
     * (= the same as creating a new Dynamic Object with itemid = null)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @returns object
     * @return the requested object definition
     */
    function getObject($args)
    {
        $args['itemid'] = null;
        // here we can use our own classes to retrieve this
        $object = new Dynamic_Object($args);
        return $object;
    }

// TODO: clean up this object mess
    function getObjectInfo($args)
    {
        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $query = "SELECT xar_object_id,
                         xar_object_name,
                         xar_object_label,
                         xar_object_moduleid,
                         xar_object_itemtype,
                         xar_object_urlparam,
                         xar_object_maxid,
                         xar_object_config,
                         xar_object_isalias
                  FROM $dynamicobjects ";
        if (isset($this->objectid)) {
            $query .= " WHERE xar_object_id = " . xarVarPrepForStore($this->objectid);
        } else {
            $query .= " WHERE xar_object_moduleid = " . xarVarPrepForStore($this->moduleid) . "
                          AND xar_object_itemtype = " . xarVarPrepForStore($this->itemtype);
        }

        $result =& $dbconn->Execute($query);

        if (!$result || $result->EOF) return;

        list($this->objectid,
             $this->name,
             $this->label,
             $this->moduleid,
             $this->itemtype,
             $this->urlparam,
             $this->maxid,
             $this->config,
             $this->isalias) = $result->fields;

        $result->Close();
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
     * @returns integer
     * @return the object id of the created item
     */
    function createObject($args)
    {
        $object = new Dynamic_Object(array('objectid' => 1));
        $objectid = $object->createItem($args);
        return $objectid;
    }

    function updateObject($args)
    {
    }

    function deleteObject($args)
    {
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

        // set the specific item id
        if (isset($args['itemid'])) {
            $this->itemid = $args['itemid'];
        }

        // see if we can access this object, at least in overview
        if (!xarSecAuthAction(0, 'DynamicData::Item', $this->moduleid.':'.$this->itemtype.':'.$this->itemid, ACCESS_OVERVIEW)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
            return;
        }
        //$this->getItem();
    }

    /**
     * Retrieve the values for this item
     */
    function getItem($itemid = null)
    {
        if (isset($itemid)) {
            $this->itemid = $itemid;
        }
        if (empty($this->itemid)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM');
            return;
        }
        $modinfo = xarModGetInfo($this->moduleid);
        foreach ($this->datastores as $name => $datastore) {
            $datastore->getItem(array('modid'    => $this->moduleid,
                                      'itemtype' => $this->itemtype,
                                      'itemid'   => $this->itemid,
                                      'modname'  => $modinfo['name']));
        }
    }

    /**
     * Check the different input values for this item
     */
    function checkInput($itemid = null)
    {
        if (isset($itemid) && $itemid != $this->itemid) {
            $this->itemid = $itemid;
            $this->getItem($itemid);
        }
        $isvalid = true;
        foreach (array_keys($this->properties) as $name) {
            if (!$this->properties[$name]->checkInput()) {
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
        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        if (count($args['fieldlist']) > 0) {
            $properties = array();
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    $properties[$name] = & $this->properties[$name];
                }
            }
        } else {
            $properties = & $this->properties;
        }
        return xarTplModule('dynamicdata','admin','objectform',
                            array('properties' => $properties,
                                  'layout'     => $args['layout']),
                            $args['template']);
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
        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        if (count($args['fieldlist']) > 0) {
            $properties = array();
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    $properties[$name] = & $this->properties[$name];
                }
            }
        } else {
            $properties = & $this->properties;
        }
        return xarTplModule('dynamicdata','user','objectdisplay',
                            array('properties' => $properties,
                                  'layout'     => $args['layout']),
                            $args['template']);
    }

    function createItem($args = array())
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
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
                    $this->itemid = $this->datastores[$primarystore]->createItem(array('objectid' => $this->objectid,
                                                                                       'modid'    => $this->moduleid,
                                                                                       'itemtype' => $this->itemtype,
                                                                                       'itemid'   => $this->itemid,
                                                                                       'modname'  => $modinfo['name']));

                } else {
                    $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                                 'primary key datastore', 'Dynamic Object', 'createItem', 'DynamicData');
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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

        return $this->itemid;
    }

    function deleteItem($args = array())
    {
        if (!empty($args['itemid'])) {
            $this->itemid = $args['itemid'];
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

        return $this->itemid;
    }

    /**
     * Get the next available item type (for objects that are assigned to the dynamicdata module)
     *
     * @param $args['moduleid'] module id for the object
     * @returns integer
     * @return value of the next item type
     */
    function getNextItemtype($args)
    {
        if (empty($args['moduleid'])) {
            $args['moduleid'] = $this->moduleid;
        }
        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $query = "SELECT MAX(xar_object_itemtype)
                    FROM $dynamicobjects
                   WHERE xar_object_moduleid = " . xarVarPrepForStore($args['moduleid']);

        $result =& $dbconn->Execute($query);
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
    var $numitems = null;
    var $startnum = null;

    var $startstore = null; // the data store we should start with (for sort)

    var $items;             // the result array of itemid => (property name => value)

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
        // initialize the items array
        $this->items = array();

        // get the object type information from our parent class
        $this->Dynamic_Object_Master($args);

        // see if we can access these objects, at least in overview
        if (!xarSecAuthAction(0, 'DynamicData::Item', $this->moduleid.':'.$this->itemtype.':', ACCESS_OVERVIEW)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
            return;
        }

        // set the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($args);

        // put a reference to the $items array in all properties
        foreach (array_keys($this->properties) as $name) {
            $this->properties[$name]->items = & $this->items;
        }

        // put a reference to the $itemids array in all data stores
        foreach (array_keys($this->datastores) as $name) {
            $this->datastores[$name]->itemids = & $this->itemids;
        }
    }

    function setArguments($args)
    {
/* done automagically by Dynamic_Object_Master
        // set the number of items to retrieve
        if (!empty($args['numitems'])) {
            $this->numitems = $args['numitems'];
        }
        // set the start number to retrieve
        if (!empty($args['startnum'])) {
            $this->startnum = $args['startnum'];
        }
*/
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

    // TODO: handle fieldlist, status and primary too -> reset data store field lists !

        // Note: they can be empty here, which means overriding any previous criteria
        if (isset($args['sort']) || isset($args['where'])) {
            foreach (array_keys($this->datastores) as $name) {
                // make sure we don't have some left-over sort criteria
                if (isset($args['sort'])) {
                    $this->datastores[$name]->cleanSort();
                }
                // make sure we don't have some left-over where clauses
                if (isset($args['where'])) {
                    $this->datastores[$name]->cleanWhere();
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
            // TODO: put sort criteria in fieldlist if necessary
                $datastore = $this->properties[$criteria]->datastore;
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
            $name = array_shift($pieces);
            // sanity check on SQL
            if (count($pieces) < 2) {
                $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                             'query ' . xarVarPrepForStore($where), 'Dynamic_Object_List', 'getWhere', 'DynamicData');
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
                return;
            }
            if (isset($this->properties[$name])) {
                // pass the where clause to the right data store
            // TODO: put where criteria in fieldlist if necessary
                $datastore = $this->properties[$name]->datastore;
                $this->datastores[$datastore]->addWhere($this->properties[$name],
                                                        join(' ',$pieces),
                                                        $join);
            }
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

        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        if (count($args['fieldlist']) > 0) {
            $args['properties'] = array();
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    $args['properties'][$name] = & $this->properties[$name];
                }
            }
        } else {
            $args['properties'] = & $this->properties;
        }

        $args['items'] = & $this->items;

        // add link to display the item
        if (empty($args['linkfunc'])) {
            $args['linkfunc'] = 'display';
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
        $args['links'] = array();

        // override for viewing dynamic objects
        if ($modname == 'dynamicdata' && $this->itemtype == 0) {
            $viewtype = 'admin';
            $viewfunc = 'view';
        } else {
            $viewtype = 'user';
            $viewfunc = 'display';
        }

        $itemtype = $this->itemtype;
        if (empty($itemtype)) {
            $itemtype = null; // don't add to URL
        }
        foreach (array_keys($this->items) as $itemid) {
    // TODO: improve this + SECURITY !!!
            $options = array();
            if (xarSecAuthAction(0, 'DynamicData::Item', $this->moduleid.':'.$this->itemtype.':'.$itemid, ACCESS_READ)) {
                $options[] = array('otitle' => xarML('View'),
                                   'olink'  => xarModURL($modname,$viewtype,$viewfunc,
                                               array($args['param'] => $itemid,
                                                     'itemtype'     => $itemtype)),
                                   'ojoin'  => '');
            }
            if (xarSecAuthAction(0, 'DynamicData::Item', $this->moduleid.':'.$this->itemtype.':'.$itemid, ACCESS_EDIT)) {
                $options[] = array('otitle' => xarML('Edit'),
                                   'olink'  => xarModURL($modname,'admin','modify',
                                               array($args['param'] => $itemid,
                                                     'itemtype'     => $itemtype)),
                                   'ojoin'  => '|');
            }
            if (xarSecAuthAction(0, 'DynamicData::Item', $this->moduleid.':'.$this->itemtype.':'.$itemid, ACCESS_DELETE)) {
                $options[] = array('otitle' => xarML('Delete'),
                                   'olink'  => xarModURL($modname,'admin','delete',
                                               array($args['param'] => $itemid,
                                                     'itemtype'     => $itemtype)),
                                   'ojoin'  => '|');
            }
            $args['links'][$itemid] = $options;
        }

        // TODO: improve this + SECURITY !!!
        if (xarSecAuthAction(0, 'DynamicData::Item', $this->moduleid.':'.$this->itemtype.':', ACCESS_ADD)) {
            $args['newlink'] = xarModURL($modname,'admin','new',
                                         array('itemtype' => $itemtype));
        } else {
            $args['newlink'] = '';
        }

        list($args['prevurl'],
             $args['nexturl']) = $this->getPager();

        return xarTplModule('dynamicdata','admin','objectlist',
                            $args,
                            $args['template']);
    }

    function showView($args = array())
    {
        if (empty($args['layout'])) {
            $args['layout'] = $this->layout;
        }
        if (empty($args['template'])) {
            $args['template'] = $this->template;
        }

        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        if (count($args['fieldlist']) > 0) {
            $args['properties'] = array();
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    $args['properties'][$name] = & $this->properties[$name];
                }
            }
        } else {
            $args['properties'] = & $this->properties;
        }

        $args['items'] = & $this->items;

        // add link to display the item
        if (empty($args['linkfunc'])) {
            $args['linkfunc'] = 'display';
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
        $itemtype = $this->itemtype;
        if (empty($itemtype)) {
            $itemtype = null; // don't add to URL
        }
        $args['links'] = array();
        foreach (array_keys($this->items) as $itemid) {
            $args['links'][$itemid]['display'] =  array('otitle' => $args['linklabel'],
                                                        'olink'  => xarModURL($modname,'user',$args['linkfunc'],
                                                                              array($args['param'] => $itemid,
                                                                                    'itemtype'     => $itemtype)),
                                                        'ojoin'  => '');
        }

        list($args['prevurl'],
             $args['nexturl']) = $this->getPager();

        return xarTplModule('dynamicdata','user','objectview',
                            $args,
                            $args['template']);
    }

    function getPager()
    {
        $prevurl = '';
        $nexturl = '';

        if (empty($this->startnum)) {
            $this->startnum = 1;
        }

    // TODO: count items before calling getItems() if we want some better pager

        if (empty($this->numitems) || ( (count($this->items) < $this->numitems) && $this->startnum == 1 )) {
            return array($prevurl,$nexturl);
        }

        // Get current URL
        $currenturl = xarServerGetCurrentURL();

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
                $nexturl = $currenturl . '&startnum=' . $next;
            }
            if ($this->startnum > 1) {
                $prev = $this->startnum - $this->numitems;
                $prevurl = $currenturl . '&startnum=' . $prev;
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
        return array($prevurl,$nexturl);
    }

}


?>
