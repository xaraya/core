<?php
/**
 * Include the base class
 */
sys::import('modules.dynamicdata.class.properties.base');

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author Marc Lutolf <marc@luetolf-carroll.com>
 */
 /**
  * This property models the subitems of a dataobject. It is a dataobject attached to another dataobject
  * It is used when you want to model hierarchical data
  */
class SubItemsProperty extends DataProperty
{
    public $id           = 30069;
    public $name         = 'subitems';
    public $desc         = 'SubItems';
    public $reqmodules   = array('dynamicdata');

    public $include_reference            = 1; // tells the object this property belongs to whether to add a reference of itself to me
    
    // Configuration parameters
    public $initialization_refobject     = 'objects'; // The name of the property's object (the subitems object name)
    public $initialization_minimumitems  = 1;         // What is the minimum number of subitems per parent
    public $initialization_addremove     = 2;         // Can we add or remove items

    public $titlefield   = '';
    public $where        = '';        // TODO
    public $display      = 1;         // TODO

    public $objectref         = null;
    public $subitemsobject    = null;
    public $prefixarray       = array();
    public $itemsdata         = array();                   // holds the subitem objects
    public $toupdate          = array();                   // holds the ids of items to update
    public $tocreate          = array();                   // holds the ids of items to create
    public $todelete          = array();                   // holds the ids of items to delete
    public $defaultvalues     = array();                   // holds the default values of the object's properties. allows for overrides

    /*
    *   In this property the "value" ($itemsdata) corresponds to an array of the form
    *       array($subitemkey =>
    *           array(
    *               $itemkey => array($propertyname_1 => $propertyvalue_1, ...)
    *           ....
    *           )
    *           ....
    *       )
    *   $subitemkey is the id of the subobject item with the form dd_$id
    *   $itemkey is the id of an item
    *   $propertyname_x is the name of a subobject item property
    *   $propertyvalue_x is the value of a subobject item property
    */
    
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'dynamicdata';
        $this->filepath   = 'modules/dynamicdata/xarproperties';

        $this->fieldprefix    = $this->_fieldprefix . $this->propertyprefix . $this->id;
        sys::import('modules.dynamicdata.class.objects.master');
        // FIXME: properties should not be instantiated when being registered
        // In this case refreshing the property cache causes a failure which we have to catch
        try {
            $this->subitemsobject = DataObjectMaster::getObject(array('name' => $this->initialization_refobject));
        } catch (Exception $e) {
        }
    }

	/**
	 * Get the value of subitems from a web page
	 * 
	 * @param  string name The name of the subitems
	 * @param  string value The value of the subitems
	 * @return bool   This method returns the true or false value.
	 */
    public function checkInput($name = '', $value = null)
    {
        $oldprefix = $this->objectref->getFieldPrefix();
        $newprefix = empty($oldprefix) ? $this->fieldprefix : $oldprefix . "_" . $this->fieldprefix;
        $this->prefixarray[] = $newprefix;
        // Get the list of item ids, both current and previous
        if(!xarVarFetch('subitem_ids_' . $newprefix,          'str',   $itemids,          '', XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('subitem_previous_ids_' . $newprefix, 'str',   $previous_itemids, '', XARVAR_DONT_SET)) {return;}
        $itemids = ('' == $itemids) ? array() : explode(',',$itemids);
        $previous_itemids = ('' == $previous_itemids) ? array() : explode(',',$previous_itemids);

        if (empty($this->objectref)) throw new Exception(xarML('A subitem property must be part of an object'));
        // Park the current values; they may not be the same as those in the DB
        $fieldvalues = $this->objectref->getFieldValues(array(),1);
        // Now get the values stored in the DB
        $this->objectref->getItem(array('itemid' => $this->objectref->itemid));

        // Calculate what rows require what actions
        $this->toupdate = array_intersect($itemids,$previous_itemids);
        $this->tocreate = array_diff($itemids,$previous_itemids);
        $this->todelete = array_diff($previous_itemids,$itemids);

        // Get the object we'll be working with
        $data['object'] = $this->subitemsobject;
        
        // Get this propery's name
        $name = empty($name) ? $this->propertyprefix . $this->id : $name;
        
        // First we need to check all the data on the template
        // If checkInput fails, don't bail
        $itemsdata = array();
        $isvalid = true;
        // We won't check all the items, just those that are to be created or updated
        // We don't look for deleted items. By ignoring them they disappear
        $itemids = array_merge($this->toupdate,$this->tocreate);
        foreach ($itemids as $prefix) {
            // Set the index for this item on the object
            $data['object']->setFieldPrefix($prefix . "_" . $newprefix);
            // Get the data corresponding to that index from the template
            $thisvalid = $data['object']->checkInput();
            // Update whether the data we have is valid
            $isvalid = $isvalid && $thisvalid;
            // Store each item for later processing
            // Note these are storage, not display, values
            $itemsdata[$newprefix][$prefix] = $data['object']->getFieldValues(array(),1);
        }
        // Put the aquired data into the property's itemsdata property
        $this->itemsdata = $itemsdata;
        // Bring the parked values back
        $this->objectref->setFieldValues($fieldvalues);
        // Return whether we got valid data
        return $isvalid;
    }

/*
 * Note: "create" refers to the operation being performed by the parent item.
 */
    public function createValue($itemid=0)
    {
        return $this->valueFunction($itemid, 'create');
    }

/*
 * Note: "update" refers to the operation being performed by the parent item.
 */
    public function updateValue($itemid=0)
    {
        return $this->valueFunction($itemid, 'update');
    }

/*
 *  Delete the items from the subitem's itemdata property
 */ 
    public function deleteValue($itemid=0)
    {
        foreach($this->todelete as $id)
            $this->subitemsobject->deleteItem(array('itemid' => (int)$id));
        return $itemid;
    }

/*
 * Move the items from the parent object to the subitem's itemsdata property
 */
    public function mountValue($itemid=0)
    {
        $this->_setItemsData($this->_getItemsData());
        return true;
    }
    
	/**
	 * Display subitems for input
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */
    public function showInput(Array $data = array())
    {
        if (!isset($data['name'])) $data['name'] = $this->propertyprefix . $this->id;
        if (!isset($data['label'])) $data['label'] = $this->label;

        if (!empty($data['object']))  $this->initialization_refobject = $data['object'];
        if (empty($data['addremove'])) $data['addremove'] = $this->initialization_addremove;
        if (empty($data['minimumitems'])) $data['minimumitems'] = $this->initialization_minimumitems;

        // Fallback to the module that is using this property
        if (isset($data['localmodule'])) {
            $this->localmodule = $data['localmodule'];
        } else {
            $info = xarController::$request->getInfo();
            $this->localmodule = $info[0];
            $data['localmodule'] = $this->localmodule;
        }

        // Force the fieldprefix
        $data['fieldprefix'] = $this->fieldprefix;
        $this->setPrefix($this->fieldprefix);
        
        // This will hold the item(s)
        $data['object'] = $this->subitemsobject;
        $oldprefix = $this->objectref->getFieldPrefix();
        $newprefix = empty($oldprefix) ? $this->fieldprefix : $oldprefix . "_" . $this->fieldprefix;
        $data['newprefix'] = $newprefix;

        $data['object']->setFieldPrefix($newprefix);
        
        // Get the object's default values, with overrides, and pass them to the template
        foreach ($data['object']->getProperties() as $name  => $property) 
            if (!isset($this->defaultvalues[$name])) $data['defaultfieldvalues'][$name] = $property->defaultvalue;
            else $data['defaultfieldvalues'][$name] = $this->defaultvalues[$name];
        
        $data['itemid'] = $this->_itemid;

        // Check for the items data:
        // 1. Override from the tag
        // 2. The property's itemsdata array (means checkInput ran)
        // 3. The parent object's items array (means we are getting the data from db)
        // 4. Default values passed from the property defaultvalue field
        // 5. Add object default values for any rows not yet covered
        if (empty($data['items'])) {
            // 2. Nothing passed in the tag, look for the items data if checkInput ran
            if (!empty($this->itemsdata)) {
                try {
                    // Display the items from previous rounds
                    $data['items'] = $this->itemsdata[$newprefix];   
                    unset($this->itemsdata[$newprefix]);
                } catch (Exception $e) {
                    // Display the newly added items 
                    $data['items'] = array_shift($this->itemsdata);   
                }
            } else {
                    // 3. Otherwise get the values from the parent object
                    $data['items'] = $this->_getItemsData();
            }

            // 4. If still no items and we are passing default values from the property, use them
            if (empty($data['items']) && is_array($this->defaultvalue) && !empty($this->defaultvalue)) {
                $data['items'] = $this->defaultvalue;
            }

            // 5. Add items of the object's defaultvalues if the number of items does not reach the minimum to be displayed
            if ($data['minimumitems'] > count($data['items'])) {
                $limit = $data['minimumitems'];
                $start = count($data['items']);
                for ($i=$start;$i<$limit;$i++) $data['items'][$i+1] = $data['defaultfieldvalues'];
            }
/*
            // Add default values from the property
            if (is_array($this->defaultvalue) && !empty($this->defaultvalue)) {
                // Add in default values whereever they are missing in existing items
                $i = 1;
                foreach ($data['items'] as $key => $value) {
                    if (!empty($this->defaultvalue)) {
                        $itemdefault = array_shift($this->defaultvalue);
                        $data['items'][$key] += $itemdefault;
                    }
                    $i++;
                }
                // If we have more default rows than item rows just add them at the end
                $limit = count($this->defaultvalue);
                for ($j=$i;$j<=$limit;$j++) 
                    $data['items'][] = array_shift($this->defaultvalue);
            }
*/
        }
        // Some args need to be passed on to the object
        if (isset($data['layout'])) $data['object']->layout = $data['layout'];
        if (isset($data['tplmodule'])) $data['object']->tplmodule = $data['tplmodule'];
        
        return parent::showInput($data);
    }

	/**
	 * Display subitems for output
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for output on a web page
	 */	
    public function showOutput(Array $data = array())
    {
        // If there is no override from the tag, rearrange the items
        if (empty($data['items'])) {
            $data['items'] = $this->transposeItems();
        }
        // Add the default values from the property's defaultvalue field
        if (is_array($this->defaultvalue)) $data['items'] = $data['items'] + $this->defaultvalue;
        $data['object'] = DataObjectMaster::getObjectList(array('name' => $this->subitemsobject->name));
        $data['object']->items =& $data['items'];

        // Fallback to the module that is using this property
        if (isset($data['localmodule'])) {
            $this->localmodule = $data['localmodule'];
        } else {
            $info = xarController::$request->getInfo();
            $this->localmodule = $info[0];
            $data['localmodule'] = $this->localmodule;
        }
        
        // Some args need to be passed on to the object
        if (isset($data['layout'])) $data['object']->layout = $data['layout'];
        if (isset($data['tplmodule'])) $data['object']->tplmodule = $data['tplmodule'];
        
        return parent::showOutput($data);
    }

	/**
	 * Used to show the hidden subitems
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for output on a web page
	 */	 
    public function showHidden(Array $data = array())
    {
        // Force the fieldprefix
        $data['fieldprefix'] = $this->fieldprefix;
        $this->setPrefix($this->fieldprefix);
        
        if (empty($data['addremove'])) $data['addremove'] = $this->initialization_addremove;
        if (empty($data['minimumitems'])) $data['minimumitems'] = $this->initialization_minimumitems;

        // This will hold the item(s)
        $data['object'] = $this->subitemsobject;
        $oldprefix = $this->objectref->getFieldPrefix();
        $newprefix = empty($oldprefix) ? $this->fieldprefix : $oldprefix . "_" . $this->fieldprefix;
        $data['newprefix'] = $newprefix;

        $data['object']->setFieldPrefix($newprefix);
        
        // Get the object's default values, with overrides, and pass them to the template
        foreach ($data['object']->getProperties() as $name  => $property) 
            if (!isset($this->defaultvalues[$name])) $data['defaultfieldvalues'][$name] = $property->defaultvalue;
            else $data['defaultfieldvalues'][$name] = $this->defaultvalues[$name];
        
        $data['itemid'] = $this->_itemid;

        if (empty($data['items'])) {
            // 2. Nothing passed in the tag, look for the items data if checkInput ran
            if (!empty($this->itemsdata)) {
                try {
                    // Display the items from previous rounds
                    $data['items'] = $this->itemsdata[$newprefix];   
                    unset($this->itemsdata[$newprefix]);
                } catch (Exception $e) {
                    // Display the newly added items 
                    $data['items'] = array_shift($this->itemsdata);   
                }
            } else {
                    // 3. Otherwise get the values from the parent object
                    $data['items'] = $this->_getItemsData();
            }

            // 4. If still no items and we are passing default values from the property, use them
            if (empty($data['items']) && is_array($this->defaultvalue) && !empty($this->defaultvalue)) {
                $data['items'] = $this->defaultvalue;
            }

            // 5. Add items of the object's defaultvalues if the number of items does not reach the minimum to be displayed
            if ($data['minimumitems'] > count($data['items'])) {
                $limit = $data['minimumitems'];
                $start = count($data['items']);
                for ($i=$start;$i<$limit;$i++) $data['items'][$i+1] = $data['defaultfieldvalues'];
            }
        }
        return parent::showHidden($data);
    }

/*
 * The public function just returns the contents of the itemsdata array corresponding to this property's key
 */
    public function getItemsData()
    {
        $name = $this->propertyprefix . $this->id;
        if (!isset($this->itemsdata[$name])) $this->itemsdata[$name] = array();
        return $this->itemsdata[$name];
    }

/*
 * The public function adds the key to the args and saves as the contents of the itemsdata array corresponding to this property's key
 */
    public function setItemsData($args=array())
    {
        $name = $this->propertyprefix . $this->id;
        $this->itemsdata[$name] = $args;
    }
    
/*
 * Rework the postings so that we can update where possible and not delete and recreate them all each time
 * This means addin the ID and transaction ID values of postings we will overwrite in the DB
 */
    public function loadItemsData($args=array())
    {
        $old_ids = array_keys($this->getItemsData());
        $parent_id = (int)$this->objectref->itemid;
        $available_slots = count($old_ids);

        $needed_slots = count($args);
        $replaceable_slots = min($available_slots, $needed_slots);
        // Note that subitems are numbered beginning at 1
        for($i=1;$i<=$replaceable_slots;$i++) {
            $args[$i]['id'] = (int)array_shift($old_ids);
            $args[$i]['transaction_id'] = $parent_id;
        }
        
        // The postings of the leftover old IDs need to be deleted
        $this->todelete = $old_ids;
        
        $this->setItemsData($args);//echo "<pre>";var_dump($this->todelete);exit;
    }
    
    // FIXME: _getitemsdata and _setitemsdata should operate as opposites
    private function _getItemsData($withkeys=0)
    {
        $name = $this->propertyprefix . $this->id;
        $items = $this->transposeItems();
        if (!isset($items[$name])) $items[$name] = array();
        if ($withkeys) return $items;
        return $items[$name];
    }

    private function _setItemsData($args=array(), $withnokeys=0)
    {
        if ($withnokeys) {
            $this->itemsdata = $args;
        } else {
            $name = $this->propertyprefix . $this->id;
            $this->itemsdata[$name] = $args;
        }
        return true;
    }

    private function transposeItems()
    {
        $name = $this->propertyprefix . $this->id;
        if (empty($this->objectref->items)) return array();
        $itemsarray = reset($this->objectref->items);
        $namelength = strlen($this->initialization_refobject) + 1;
        foreach ($itemsarray as $key => $value) {
            $cleankey = substr($key, $namelength);
            foreach ($value as $key1 => $value1) {
                $items[$name][$key1][$cleankey] = $value1;
            }
        }
        return $items;
    }
    
    // Method to get the names of the properties of this object and the parent object 
    // that are linked
    private function getLinks()
    {
        // Get the link properties of both the parent and the subobject for use in creates and deletes
        $objectarray = unserialize($this->objectref->objects);
        foreach ($objectarray as $value){
            $valueparts = explode('.',$value[1]);
            if ($valueparts[0] == $this->initialization_refobject) {
                $sublink = $valueparts[1];
                $keyparts = explode('.',$value[0]);
                $link = $keyparts[1];
                break;
            }
        }
        return array($sublink,$link);
    }

    // Recursively set the prefix for subitemobject properties, including those that are subitems
    private function setPrefix($prefix)
    {
        foreach (array_keys($this->subitemsobject->properties) as $name) {
            $this->subitemsobject->properties[$name]->_fieldprefix = $prefix;
            if ($this->subitemsobject->properties[$name]->type == 30069) {
                $this->subitemsobject->properties[$name]->setPrefix($prefix);
            }
        }
        return true;
    }   

/*
 * This function creates or updates subitems.
 * Note that an update of the parent update does not imply the subitems are all updated:
 * We might be creating new subitems even while updating the parent item.
 * However, when creating a new parent item, all the subitems are created, too.
 *
 */
    private function valueFunction($itemid=0, $functiontype='create')
    {
        list($sublink, $link) = $this->getLinks();
        // Create or update each item
        try {
            if (empty($newprefix)) {
                // Creation happens programmatically
                $newprefix = $this->propertyprefix . $this->id;
            } else {
                // Creation happens via UI submit, checkInput has run
                $newprefix = array_shift($this->prefixarray);
            }

            // Only do this if we actually have any items to be created/updated (might just be a delete call)
            if (isset($this->itemsdata[$newprefix])) {
                foreach ($this->itemsdata[$newprefix] as $itemdata) {
                    $primary =& $this->subitemsobject->properties[$this->subitemsobject->primary];
                    $this->subitemsobject->setFieldValues($itemdata);
                    $primary =& $this->subitemsobject->properties[$this->subitemsobject->primary];
                    
                    // If we are creating a new parent item, we need to set the primary index to 0
                    if ($functiontype == 'create') $primary->value = 0;

                    if (empty($primary->value)) {
                        // Insert the link value to the parent object
                        $this->subitemsobject->properties[$sublink]->value = $this->objectref->properties[$link]->value;
                        $itemid = $this->subitemsobject->createItem();
                    } else {
                        $itemid = $this->subitemsobject->updateItem();
                    }
                // Clear the value of the primary index in preparation for the next round
                    $primary->value = 0;
                    $this->subitemsobject->itemid = 0;
                }
            }
        } catch (Exception $e) {
            $msg = xarML('Subitem create/update failed: #(1)',$this->name);
            throw new Exception($msg);
        }
        // Delete any items that are no longer present
        $this->deleteValue($itemid);
        
        return true;
    }
}
?>