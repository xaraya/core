<?php
/**
 * DataObject
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 *
**/
sys::import('modules.dynamicdata.class.properties');
sys::import('modules.dynamicdata.class.objects.master');

class DataObject extends DataObjectMaster
{
    public $itemid = 0;

    /**
     * Inherits from DataObjectMaster and sets the requested item id
     *
     * @param $args['itemid'] item id of the object to get
    **/
    function __construct(array $args)
    {
        // get the object type information from our parent class
        parent::__construct($args);

        // set the specific item id (or 0)
        if(isset($args['itemid']))
            $this->itemid = $args['itemid'];

        // see if we can access this object, at least in overview
        if(!xarSecurityCheck(
            'ViewDynamicDataItems',1,'Item',
            $this->moduleid.':'.$this->itemtype.':'.$this->itemid)
        ) return;

        // don't retrieve the item here yet !
        //$this->getItem();
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

        $modinfo = xarModGetInfo($this->moduleid);
        foreach($this->datastores as $name => $datastore)
        {
            $itemid = $datastore->getItem(
                array(
                    'modid'    => $this->moduleid,
                    'itemtype' => $this->itemtype,
                    'itemid'   => $this->itemid,
                    'modname'  => $modinfo['name']
                )
            );
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
            // for hooks, use the values passed via $extrainfo if available
            $field = 'dd_' . $this->properties[$name]->id;
            if(isset($args[$name]))
                // Name based check
                $isvalid = $this->properties[$name]->checkInput($name,$args[$name]);
            elseif(isset($args[$field]))
                // No name, check based on field
                $isvalid = $this->properties[$name]->checkInput($field,$args[$field]);
            elseif(!empty($args['fieldprefix']))
            {
                // No field, but prefix given, use that
                // cfr. prefix layout in objects/showform template
                $field = $args['fieldprefix'] . '_' . $field;
                $isvalid = $this->properties[$name]->checkInput($field);
            }
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
        if(empty($args['layout']))      $args['layout'] = $this->layout;
        if(empty($args['template']))    $args['template'] = $this->template;
        if(empty($args['tplmodule']))   $args['tplmodule'] = $this->tplmodule;
        if(empty($args['viewfunc']))    $args['viewfunc'] = $this->viewfunc;
        if(empty($args['fieldlist']))   $args['fieldlist'] = $this->fieldlist;
        if(empty($args['fieldprefix'])) $args['fieldprefix'] = $this->fieldprefix;

        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if(!empty($args['preview']))
            $this->checkInput();

        // Set all properties based on what is passed in.
        $args['properties'] = array();
        if(count($args['fieldlist']) > 0 || !empty($this->status))
        {
            foreach($args['fieldlist'] as $name)
                if(isset($this->properties[$name]))
                    $args['properties'][$name] =& $this->properties[$name];
        }
        else
            $args['properties'] =& $this->properties;

        // pass some extra template variables for use in BL tags, API calls etc.
        $args['objectname'] = !empty($this->name) ? $this->name : null;
        $args['moduleid'] = $this->moduleid;
        $modinfo = xarModGetInfo($this->moduleid);
        $args['modname'] = $modinfo['name'];
        $args['itemtype'] = !empty($this->itemtype) ? $this->itemtype : null;
        $args['itemid'] = $this->itemid;
        $args['isprimary'] = !empty($this->primary);
        $args['catid'] = !empty($this->catid) ? $this->catid : null;

        return xarTplObject($args['tplmodule'],$args['template'],'showform',$args);
    }

    /**
     * Show an output display for this item
     */
    function showDisplay($args = array())
    {
        if(empty($args['layout']))    $args['layout'] = $this->layout;
        if(empty($args['template']))  $args['template'] = $this->template;
        if(empty($args['tplmodule'])) $args['tplmodule'] = $this->tplmodule;
        if(empty($args['viewfunc']))  $args['viewfunc'] = $this->viewfunc;
        if(empty($args['fieldlist'])) $args['fieldlist'] = $this->fieldlist;

        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if(!empty($args['preview']))
            $this->checkInput();

        if(count($args['fieldlist']) > 0 || !empty($this->status))
        {
            // Explicit fieldlist or status has value
            $args['properties'] = array();
            foreach($args['fieldlist'] as $name)
            {
                if(isset($this->properties[$name]))
                {
                    $thisprop = $this->properties[$name];
                    if(($thisprop->status & DataPropertyMaster::DD_DISPLAYMASK) != DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)
                        $args['properties'][$name] =& $this->properties[$name];
                }
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
        $args['objectname'] = !empty($this->name) ? $this->name : null;
        $args['moduleid'] = $this->moduleid;
        $modinfo = xarModGetInfo($this->moduleid);
        $args['modname'] = $modinfo['name'];
        $args['itemtype'] = !empty($this->itemtype) ? $this->itemtype : null;
        $args['itemid'] = $this->itemid;
        $args['isprimary'] = !empty($this->primary);
        $args['catid'] = !empty($this->catid) ? $this->catid : null;
        return xarTplObject($args['tplmodule'],$args['template'],'showdisplay',$args);
    }

    /**
     * Get the names and values of
     */
    function getFieldValues($args = array())
    {
        if(empty($args['fieldlist']))
        {
            if(count($this->fieldlist) > 0)
                $fieldlist = $this->fieldlist;
            else
                $fieldlist = array_keys($this->properties);
        }
        else
            $fieldlist = $args['fieldlist'];

        $fields = array();
        foreach($fieldlist as $name)
        {
            $property = $this->properties[$name];
            if(xarSecurityCheck(
                'ReadDynamicDataField',0,'Field',
                $property->name.':'.$property->type.':'.$property->id)
            )
            {
                $fields[$name] = $property->value;
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

        $modinfo = xarModGetInfo($this->moduleid);

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

                    $this->itemid = $this->datastores[$primarystore]->createItem(
                        array(
                            'objectid' => $this->objectid,
                            'modid'    => $this->moduleid,
                            'itemtype' => $this->itemtype,
                            'itemid'   => $this->itemid,
                            'modname'  => $modinfo['name']
                        )
                    );
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

            $itemid = $this->datastores[$store]->createItem(
                array(
                    'objectid' => $this->objectid,
                    'modid'    => $this->moduleid,
                    'itemtype' => $this->itemtype,
                    'itemid'   => $this->itemid,
                    'modname'  => $modinfo['name']
                )
            );
            if(empty($itemid))
                return;
        }

        xarLogMessage("Class: " . get_class() . ". Creating an item. Itemid: " . $this->itemid . ", module: " . $modinfo['name'] . ", itemtype: " . $this->itemtype);
        // call create hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
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

        $modinfo = xarModGetInfo($this->moduleid);
        // TODO: this won't work for objects with several static tables !
        // update all the data stores
        foreach(array_keys($this->datastores) as $store)
        {
            $itemid = $this->datastores[$store]->updateItem(
                array(
                    'objectid' => $this->objectid,
                    'modid'    => $this->moduleid,
                    'itemtype' => $this->itemtype,
                    'itemid'   => $this->itemid,
                    'modname'  => $modinfo['name']
                )
            );
            if(empty($itemid))
                return;
        }

        // call update hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
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

        $modinfo = xarModGetInfo($this->moduleid);
        xarLogMessage("Class: " . get_class() . ". Deleting an item. Itemid: " . $this->itemid . ", module: " . $modinfo['name'] . ", itemtype: " . $this->itemtype);

        // TODO: this won't work for objects with several static tables !
        // delete the item in all the data stores
        foreach(array_keys($this->datastores) as $store)
        {
            $itemid = $this->datastores[$store]->deleteItem(
                array(
                    'objectid' => $this->objectid,
                    'modid'    => $this->moduleid,
                    'itemtype' => $this->itemtype,
                    'itemid'   => $this->itemid,
                    'modname'  => $modinfo['name']
                )
            );
            if(empty($itemid))
                return;
        }

        // call delete hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
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

        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $query = "SELECT MAX(xar_object_itemtype) FROM $dynamicobjects  WHERE xar_object_moduleid = ?";

        $result =& $dbconn->Execute($query,array((int)$args['moduleid']));
        if($result->EOF)
            return;

        $nexttype = $result->fields[0];

        $result->Close();

        // Note: this is *not* reliable in "multi-creator" environments
        $nexttype++;
        return $nexttype;
    }
}
?>