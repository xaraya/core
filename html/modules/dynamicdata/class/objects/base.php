<?php
/**
 * DataObject
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 *
**/
sys::import('structures.descriptor');
sys::import('modules.dynamicdata.class.properties');
sys::import('modules.dynamicdata.class.objects.master');

class DataObject extends DataObjectMaster
{
    protected $descriptor  = null;      // descriptor object of this class

    public $itemid = 0;

    /**
     * Inherits from DataObjectMaster and sets the requested item id
     *
     * @param $args['itemid'] item id of the object to get
    **/
    function __construct(DataObjectDescriptor $descriptor)
    {
        // get the object type information from our parent class
        $this->loader($descriptor);

        // set the specific item id (or 0)
        $args = $descriptor->getArgs();
        if(isset($args['itemid']))
            $this->itemid = $args['itemid'];

        // see if we can access this object, at least in overview
        if(!xarSecurityCheck(
            'ViewDynamicDataItems',1,'Item',
            $this->moduleid.':'.$this->itemtype.':'.$this->itemid)
        ) return;
    }

    /**
     * Retrieve the values for this item
    **/
    function getItem($args = array())
    {
        if(!empty($args['itemid']))
        {
            if($args['itemid'] != $this->itemid)
                // initialise the properties again
                foreach(array_keys($this->properties) as $name)
                    $this->properties[$name]->value = $this->properties[$name]->default;

            $this->itemid = $args['itemid'];
        }
        if(empty($this->itemid))
        {
            $msg = 'Invalid item id in method #(1)() for dynamic object [#(2)] #(3)';
            $vars = array('getItem',$this->objectid,$this->name);
            throw new BadParameterException($vars,$msg);
        }

        if(!empty($this->primary) && !empty($this->properties[$this->primary]))
            $primarystore = $this->properties[$this->primary]->datastore;

//        $modinfo = xarModGetInfo($this->moduleid);
        foreach($this->datastores as $name => $datastore)
        {
            $itemid = $datastore->getItem($this->toArray());
            // only worry about finding something in primary datastore (if any)
            if(empty($itemid) && !empty($primarystore) && $primarystore == $name)
                return;
        }

        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if(!empty($args['preview']))
            $this->checkInput();
        return $this->itemid;
    }

    /**
     * Check the different input values for this item
     */
    function checkInput($args = array())
    {
        if(!empty($args['itemid']) && $args['itemid'] != $this->itemid)
        {
            $this->itemid = $args['itemid'];
            $this->getItem($args);
        }

        if(empty($args['fieldprefix']))
            $args['fieldprefix'] = $this->fieldprefix;

        $isvalid = true;
        $fields = !empty($this->fieldlist) ? $this->fieldlist : array_keys($this->properties);
        foreach($fields as $name)
        {
            $field = 'dd_' . $this->properties[$name]->id;
            if(!empty($args['fieldprefix']))
            {
                // No field, but prefix given, use that
                // cfr. prefix layout in objects/showform template
                    $field = $args['fieldprefix'] . '_' . $field;
                    $isvalid = $this->properties[$name]->checkInput($field);
                    if (!$isvalid) {
                        $field = $args['fieldprefix'] . '_' . $name;
                        $isvalid = $this->properties[$name]->checkInput($field);
                    }
            }
            // for hooks, use the values passed via $extrainfo if available
            elseif(isset($args[$name]))
                // Name based check
                $isvalid = $this->properties[$name]->checkInput($name,$args[$name]);
            elseif(isset($args[$field]))
                // No name, check based on field
                $isvalid = $this->properties[$name]->checkInput($field,$args[$field]);
            else
                // Ok, try without anything
                $isvalid = $this->properties[$name]->checkInput();
        }
        return $isvalid;
    }

    /**
     * Show an input form for this item
     */
    function showForm($args = array())
    {
        $args = $this->toArray($args);

        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if(!empty($args['preview']))
            $this->checkInput();

        // Set all properties based on what is passed in.
        $args['properties'] = $this->getProperties($args);

        // pass some extra template variables for use in BL tags, API calls etc.
        //FIXME: check these
        $args['isprimary'] = !empty($this->primary);
        $args['catid'] = !empty($this->catid) ? $this->catid : null;

        return xarTplObject($args['tplmodule'],$args['template'],'showform',$args);
    }

    /**
     * Show an output display for this item
     */
    function showDisplay($args = array())
    {
        $args = $this->toArray($args);
        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if(!empty($args['preview']))
            $this->checkInput();

        if(count($args['fieldlist']) > 0 || !empty($this->status))
        {
            $properties = $this->getProperties($args);
            $args['properties'] = array();
            foreach ($properties as $property) {
                if(($property->status & DataPropertyMaster::DD_DISPLAYMASK) != DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)
                    $args['properties'][$property->name] = $property;
            }
        }
        else
        {
            $args['properties'] =& $this->properties;
            // Do them all, except for status = DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN
            // TODO: this is exactly the same as in the display function, consolidate it.
            $totransform = array(); $totransform['transform'] = array();
            foreach($this->properties as $pname => $pobj)
            {
                // *never* transform an ID
                // TODO: there is probably lots more to skip here.
                if($pobj->type == '21')
                    continue;
                $totransform['transform'][] = $pname;
                $totransform[$pname] = $pobj->value;
            }

            // CHECKME: is $this->tplmodule safe here?
            $transformed = xarModCallHooks(
                'item','transform',$this->itemid,
                $totransform, $this->tplmodule,$this->itemtype
            );

            foreach($this->properties as $property)
            {
                if(
                    (($property->status & DataPropertyMaster::DD_DISPLAYMASK) != DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN) &&
                    ($property->type != 21) &&
                    isset($transformed[$property->name])
                )
                {
                    // sigh, 5 letters, but so many hours to discover them
                    // anyways, clone the property, so we can safely change it, PHP 5 specific!!
                    $args['properties'][$property->name] = clone $property;
                    $args['properties'][$property->name]->value = $transformed[$property->name];
                }
            }

        }

        // pass some extra template variables for use in BL tags, API calls etc.
        //FIXME: check these
        $args['isprimary'] = !empty($this->primary);
        $args['catid'] = !empty($this->catid) ? $this->catid : null;
        return xarTplObject($args['tplmodule'],$args['template'],'showdisplay',$args);
    }

    /**
     * Get the names and values of
     */
    function getFieldValues($args = array())
    {
        $fields = array();
        $properties = $this->getProperties($args);
        foreach ($properties as $property) {
            if(xarSecurityCheck(
                'ReadDynamicDataField',0,'Field',
                $property->name.':'.$property->type.':'.$property->id)
            )
            {
                $fields[$property->name] = $property->value;
            }
        }
        return $fields;
    }

    /**
     * Get the labels and values to include in some output display for this item
    **/
    function getDisplayValues($args = array())
    {
        if(empty($args['fieldlist']))
            $args['fieldlist'] = $this->fieldlist;

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

    function createItem($args = array())
    {
        if(count($args) > 0)
        {
            if(isset($args['itemid']))
                $this->itemid = $args['itemid'];

            foreach($args as $name => $value)
                if(isset($this->properties[$name]))
                    $this->properties[$name]->setValue($value);
        }

//        $modinfo = xarModGetInfo($this->moduleid);

        // special case when we try to create a new object handled by dynamicdata
        if(
            $this->objectid == 1 &&
            $this->properties['moduleid']->value == xarModGetIDFromName('dynamicdata') &&
            $this->properties['itemtype']->value < 2
        )
        {
            $this->properties['itemtype']->setValue($this->getNextItemtype($args));
        }

        // check that we have a valid item id, or that we can create one if it's set to 0
        if(empty($this->itemid))
        {
//            echo $this->baseancestor." " .$this->objectid;exit;
            if ($this->baseancestor == $this->objectid) {
                $primaryobject = $this;
            } else {
                $primaryobject = DataObjectMaster::getObject(array('objectid' => $this->baseancestor));
            }
            // no primary key identified for this object, so we're stuck
            if(!isset($primaryobject->primary))
            {
                $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
                $vars = array('primary key', 'DataObject', 'createItem', 'DynamicData');
                throw new BadParameterException($vars,$msg);
            }
            else
            {
                $value = $primaryobject->properties[$primaryobject->primary]->getValue();

                // we already have an itemid value in the properties
                if(!empty($value))
                {
                    $this->itemid = $value;
                }
                elseif(!empty($primaryobject->properties[$primaryobject->primary]->datastore))
                {
                    // we'll let the primary datastore create an itemid for us
                    $primarystore = $primaryobject->properties[$primaryobject->primary]->datastore;
                    // add the primary to the data store fields if necessary
                    if(!empty($this->fieldlist) && !in_array($primaryobject->primary,$this->fieldlist))
                        $this->datastores[$primarystore]->addField($this->properties[$this->primary]); // use reference to original property

                    $this->itemid = $this->datastores[$primarystore]->createItem($this->toArray());
                }
                else
                {
                    $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
                    $vars = array('primary key datastore', 'Dynamic Object', 'createItem', 'DynamicData');
                    throw new BadParameterException($vars,$msg);
                }
            }
        }
        if(empty($this->itemid))
            return;

        // TODO: this won't work for objects with several static tables !
        // now let's try to create items in the other data stores
        foreach(array_keys($this->datastores) as $store)
        {
            // skip the primary store
            if(isset($primarystore) && $store == $primarystore)
                continue;

            $itemid = $this->datastores[$store]->createItem($this->toArray());
            if(empty($itemid))
                return;
        }

//        xarLogMessage("Class: " . get_class() . ". Creating an item. Itemid: " . $this->itemid . ", module: " . $modinfo['name'] . ", itemtype: " . $this->itemtype);
        // call create hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
        $modinfo = xarModGetInfo($this->moduleid);
        if(
            !empty($this->primary) &&
            ($modinfo['name'] != 'articles') && ($modinfo['name'] != 'roles')
        )
        {
            $item = array();
            foreach(array_keys($this->properties) as $name)
                $item[$name] = $this->properties[$name]->value;

            $item['module'] = $modinfo['name'];
            $item['itemtype'] = $this->itemtype;
            $item['itemid'] = $this->itemid;
            xarModCallHooks('item', 'create', $this->itemid, $item, $modinfo['name']);
        }
        return $this->itemid;
    }

    function updateItem($args = array())
    {
        if(count($args) > 0)
        {
            if(!empty($args['itemid']))
                $this->itemid = $args['itemid'];

            foreach($args as $name => $value)
                if(isset($this->properties[$name]))
                    $this->properties[$name]->setValue($value);
        }

        if(empty($this->itemid))
        {
            $msg = 'Invalid item id in method #(1)() for dynamic object [#(2)] #(3)';
            $vars = array('updateItem',$this->objectid,$this->name);
            throw new BadParameterException($vars,$msg);
        }

//        $modinfo = xarModGetInfo($this->moduleid);
        // TODO: this won't work for objects with several static tables !
        // update all the data stores
        foreach(array_keys($this->datastores) as $store)
        {
            $itemid = $this->datastores[$store]->updateItem($this->toArray());
            if(empty($itemid))
                return;
        }

        // call update hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
        $modinfo = xarModGetInfo($this->moduleid);
        if(
            !empty($this->primary) &&
            ($modinfo['name'] != 'articles') && ($modinfo['name'] != 'roles')
        )
        {
            $item = array();
            foreach(array_keys($this->properties) as $name)
                $item[$name] = $this->properties[$name]->value;

            $item['module'] = $modinfo['name'];
            $item['itemtype'] = $this->itemtype;
            $item['itemid'] = $this->itemid;
            xarModCallHooks('item', 'update', $this->itemid, $item, $modinfo['name']);
        }
        return $this->itemid;
    }

    function deleteItem($args = array())
    {
        if(!empty($args['itemid']))
            $this->itemid = $args['itemid'];

        if(empty($this->itemid))
        {
            $msg = 'Invalid item id in method #(1)() for dynamic object [#(2)] #(3)';
            $vars = array('deleteItem',$this->objectid,$this->name);
            throw new BadParameterException($vars, $msg);
        }

//        $modinfo = xarModGetInfo($this->moduleid);
//        xarLogMessage("Class: " . get_class() . ". Deleting an item. Itemid: " . $this->itemid . ", module: " . $modinfo['name'] . ", itemtype: " . $this->itemtype);

        // TODO: this won't work for objects with several static tables !
        // delete the item in all the data stores
        foreach(array_keys($this->datastores) as $store)
        {
            $itemid = $this->datastores[$store]->deleteItem($this->toArray());
            if(empty($itemid))
                return;
        }

        // call delete hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
        $modinfo = xarModGetInfo($this->moduleid);
        if(
            !empty($this->primary) &&
            ($modinfo['name'] != 'articles') && ($modinfo['name'] != 'roles')
        )
        {
            $item = array();
            foreach(array_keys($this->properties) as $name)
                $item[$name] = $this->properties[$name]->value;

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
     * @return integer value of the next item type
     *
     * @todo this needs to change into something more safe.
     */
    function getNextItemtype($args = array())
    {
        if(empty($args['moduleid']))
            $args['moduleid'] = $this->moduleid;

        $dbconn = xarDBGetConn();
        $xartable = xarDBGetTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $query = "SELECT MAX(xar_object_itemtype) FROM $dynamicobjects  WHERE xar_object_moduleid = ?";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array((int)$args['moduleid']));
        if(!$result->first()) return; // shouldnt we raise?
        $nexttype = $result->getInt(1);
//        $result->close();

        // Note: this is *not* reliable in "multi-creator" environments
        $nexttype++;
        return $nexttype;
    }
}
?>
