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

sys::import('modules.dynamicdata.class.objects.master');
sys::import('modules.dynamicdata.class.objects.interfaces');

/**
 * DataObject Base class
 */
class DataObject extends DataObjectMaster implements iDataObject
{
    public $itemid;
    public $missingfields  = array(); // reference to fields not found by checkInput

    /**
     * Inherits from DataObjectMaster and sets the requested item id
     *
     * @param $args['itemid'] item id of the object to get
    **/
    public function __construct(DataObjectDescriptor $descriptor)
    {
        // get the object type information from our parent class
        $this->loader($descriptor);
        unset($descriptor);

        // Get a reference to each property's value and find the primarys index
        if (!empty($args['config'])) {
        }
        foreach ($this->properties as $property) {
            $this->configuration['property_' . $property->name] = array('type' => &$property->type, 'value' => &$property->value);
        }
    }

    /**
     * Retrieve the values for this item
    **/
    public function getItem(Array $args = array())
    {
        xarLog::message("DataObject::getItem: Retrieving an item of object " . $this->name, xarLog::LEVEL_INFO);

        if(!empty($args['itemid']))
        {
            if($args['itemid'] != $this->itemid) {
                // initialise the properties again and refresh the contents of the object configuration
                foreach($this->properties as $property) {
                    $property->value = $property->defaultvalue;
                    $this->configuration['property_' . $property->name] = array('type' => &$property->type, 'value' => &$property->value);
                }
                $this->dataquery->clearconditions();
            }
            $this->itemid = $args['itemid'];
        }
        if (!empty($this->primary) && !empty($this->properties[$this->primary])) {
        	$this->properties[$this->primary]->value = $this->itemid;
            $primarystore = $this->properties[$this->primary]->datastore;
        }

        /* General sequence:
         * 1. Run the datastore's getItem method
         * 2. Run the property-specific mountValue method for properties using the virtual datastore
         *
         * This may need to be adjusted in the future
         */
        $itemid = $this->datastore->getItem($args);

        if(!empty($args['fieldlist'])) $this->setFieldList($args['fieldlist']);

        // Turn the values retrieved into proper PHP values
        foreach($this->properties as $property) {
            try {
                $property->value = $property->castType($property->value);
            } catch(Exception $e) {}
        }

        foreach ($this->getFieldList() as $fieldname) {
            if (empty($this->properties[$fieldname]->source) &&
                method_exists($this->properties[$fieldname],'mountValue')) {
                $this->properties[$fieldname]->mountValue($this->itemid);
            }
        }

        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if(!empty($args['preview'])) $this->checkInput();
        return $itemid;
    }

    public function getInvalids(Array $args = array())
    {
        if (!empty($args['fields'])) $fields = $args['fields'];
        else $fields = $this->getFieldList();

        $invalids = array();
        foreach($fields as $name) {
            if (!empty($this->properties[$name]->invalid))
                $invalids[$name] = $this->properties[$name]->invalid;
        }
        xarLog::message("xarLog in getInvalids functions", xarLog::LEVEL_INFO);
        xarLog::variable("printing invalids array in log file: ", $invalids);

        return $invalids;
    }

    public function displayInvalids(Array $args = array())
    {
        $invalids = $this->getInvalids($args);
        return xarTpl::module('dynamicdata', 'user', 'displayinvalids', array('invalids' => $invalids));
    }

    public function clearInvalids()
    {
        foreach(array_keys($this->properties) as $name)
            $this->properties[$name]->invalid = '';
        return true;
    }

    /**
     * Check the different input values for this item
     */
    public function checkInput(Array $args = array(), $suppress=0, $priority='dd')
    {
        xarLog::message("DataObject::checkInput: Checking an item of object " . $this->name, xarLog::LEVEL_INFO);

        if(!empty($args['itemid']) && $args['itemid'] != $this->itemid) {
            $this->itemid = $args['itemid'];
            $this->getItem($args);
        }

        if(!empty($args['fieldprefix'])) {
            $this->fieldprefix = $args['fieldprefix'];
        // Allow 0 as a fieldprefix
        } elseif (isset($args['fieldprefix']) && $args['fieldprefix'] === '0') {
            $this->fieldprefix = $args['fieldprefix'];
        } else {
            $args['fieldprefix'] = $this->fieldprefix; 
        }

        $isvalid = true;
        if (!empty($args['fields'])) {
            $fields = $args['fields'];
        } elseif (!empty($args['fieldlist'])) {
            $fields = $args['fieldlist'];
        } else {
            $fields = !empty($this->fieldlist) ? $this->fieldlist : $this->getFieldList();
        }

        $this->missingfields = array();
        $badnames = array();
        foreach($fields as $name) {
            // Ignore disabled or ignored properties
            if(($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED)
            || ($this->properties[$name]->getInputStatus() == DataPropertyMaster::DD_INPUTSTATE_IGNORED))
                continue;

            // Give the property this object's reference so it can send back info on missing fields
            $this->properties[$name]->objectref =& $this;

            // We need to check both the given name and the dd_ name
            // checking for any transitory name given a property via $args needs to be done at the property level
            $ddname = $this->propertyprefix . $this->properties[$name]->id;
            if (!empty($args['fieldprefix']) || $args['fieldprefix'] === '0') {
                $name1 = $args['fieldprefix'] . "_" . $name;
                $name2 = $args['fieldprefix'] . "_" . $ddname;
            } else {
                $name1 = $name;
                $name2 = $ddname;
            }
            if ($priority == 'dd') {
                $temp = $name1;
                $name1 = $name2;
                $name2 = $temp;
            }
            if(isset($args[$name])) {
                // Name based check
                $passed = $this->properties[$name]->checkInput($name1,$args[$name]);
                if ($passed === null) {
                    array_pop($this->missingfields);
                    $passed = $this->properties[$name]->checkInput($name2,$args[$name]);
                }
            } elseif(isset($args[$ddname])) {
                // No name, check based on field
                $passed = $this->properties[$name]->checkInput($name1,$args[$ddname]);
                if ($passed === null) {
                    array_pop($this->missingfields);
                    $passed = $this->properties[$name]->checkInput($name2,$args[$ddname]);
                }
            } else {
                // Check without values
                $passed = $this->properties[$name]->checkInput($name1);
                if ($passed === null) {
                    array_pop($this->missingfields);
                    $passed = $this->properties[$name]->checkInput($name2);
                }
            }
            if (($passed === null) || ($passed === false)) {
                $isvalid = false;
                $badnames[] = $name;
            }
        }
        if (!empty($this->missingfields)) {
            xarLog::variable('Missing properties', $this->missingfields, xarLog::LEVEL_ERROR);
            if (!$suppress)
                throw new VariableNotFoundException(array($this->name,implode(', ',$this->missingfields)),'The following fields were not found: #(1): [#(2)]');
        }
        if (!empty($badnames)) {
            xarLog::variable('Bad properties', $badnames, xarLog::LEVEL_ERROR);
            if (xarModVars::get('dynamicdata','debugmode') && 
            in_array(xarUser::getVar('id'),xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
                echo "Bad properties: "; echo $this->name . ": " . implode(', ',$badnames);
                echo "<br />";
            }
        }
        return $isvalid;
    }

    /**
     * Check the filter input values for this item
     */
    public function checkFilterInput(Array $args = array())
    {
        $isvalid = $this->checkInput($args, 1, 'dd');
        $this->clearInvalids();
        return $isvalid;
    }

    /**
     * Show an input form for this item
     */
    public function showForm(Array $args = array())
    {
        xarLog::message("DataObject::showForm: Form for object " . $this->name, xarLog::LEVEL_INFO);

        $args = $args + $this->getPublicProperties();
        $this->setFieldPrefix($args['fieldprefix']);

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
            || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY)) continue;

            $args['properties'][$name] =& $this->properties[$name];
        }

        // pass some extra template variables for use in BL tags, API calls etc.
        //FIXME: check these
        $args['isprimary'] = !empty($this->primary);
        $args['catid'] = !empty($this->catid) ? $this->catid : null;
        $args['object'] = $this;
        return xarTpl::object($args['tplmodule'],$args['template'],'showform',$args);
    }

    /**
     * Show an output display for this item
     */
    public function showDisplay(Array $args = array())
    {
        xarLog::message("DataObject::showDisplay: Display an item of object " . $this->name, xarLog::LEVEL_INFO);

        $args = $this->toArray($args);
        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if(!empty($args['preview'])) $this->checkInput();

        if (!empty($args['fieldlist']) && !is_array($args['fieldlist'])) {
            $args['fieldlist'] = explode(',',$args['fieldlist']);
            if (!is_array($args['fieldlist'])) throw new Exception('Badly formed fieldlist attribute');
            $this->fieldlist = $args['fieldlist'];
        } elseif (empty($args['fieldlist'])) {
            $this->setFieldList();
        }

        // If a different itemid was passed, get that item before we display
        if (isset($args['itemid']) && ($args['itemid'] != $this->properties[$this->primary]->value)) $this->getItem(array('itemid' => $args['itemid']));

        // Note: you can preset the list of properties to be transformed via $this->hooktransform

        // call transform hooks for this item
        $this->callHooks('transform');

        $args['properties'] = array();
        foreach($this->fieldlist as $name) {
            if(!isset($this->properties[$name])) continue;

            if(($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED)
            || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY)
            || ($this->properties[$name]->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)) continue;

            if ($this->properties[$name]->type == 21 || !isset($this->hookvalues[$name])) {
                $args['properties'][$name] =& $this->properties[$name];
            } else {
                $args['properties'][$name] =& $this->properties[$name];
                $args['properties'][$name]->value = $this->hookvalues[$name];
            }
        }
        // clean up hookvalues
        $this->hookvalues = array();

        // pass some extra template variables for use in BL tags, API calls etc.
        //FIXME: check these
        $args['isprimary'] = !empty($this->primary);
        $args['catid'] = !empty($this->catid) ? $this->catid : null;
        $args['object'] = $this;
        return xarTpl::object($args['tplmodule'],$args['template'],'showdisplay',$args);
    }

    /**
     * Get the filter values of the object's properties
     */
    public function getFilters(Array $args = array(), $bypass = 0)
    {
        $fields = array();
        $properties = $this->getProperties($args);
        if ($bypass) {
            foreach ($properties as $property) {
                if ($property->filter == 'nofilter') continue;
                $fields[$property->name] = array('filter' => $property->filter, 'value' => $property->value);
            }
        } else {
            foreach ($properties as $property) {
                if ($property->filter == 'nofilter') continue;
                $fields[$property->name] = array('filter' => $property->filter, 'value' => $property->getValue());
            }
        }
        return $fields;
    }

    public function createItem(Array $args = array())
    {
        xarLog::message("DataObject::createItem: Creating an item of object " . $this->name, xarLog::LEVEL_INFO);

        // Sanity check: do we have a primary field?
        if (empty($this->primary)) {
            $msg = xarML('The object #(1) has no primary key', $this->name);
            die($msg);
        }
        
        //  The id of the item to be created is
        //  1. An itemid arg passed
        //  2. An id arg passed ot the primary index
        //  3. 0
        
        // Reset the itemid
        $this->itemid = null;
        
        if(count($args) > 0) {
            foreach($args as $name => $value) {
                if(isset($this->properties[$name])) {
                    $this->properties[$name]->value = $value;
                }
            }
        }
        if(isset($args['itemid'])) {
            $this->itemid = $args['itemid'];
        } elseif (!empty($this->properties[$this->primary]->value)) {
            $this->itemid = $this->properties[$this->primary]->value;
        }
        
        // Special case when we try to create a new object handled by dynamicdata
        if(
            $this->objectid == 1 &&
            $this->properties['module_id']->value == xarMod::getRegID('dynamicdata')
            //&& $this->properties['itemtype']->value < 2
        )
        {
            $this->properties['itemtype']->setValue($this->getNextItemtype($args));
        }

        /* General sequence:
         * 1. Run the property-specific createValue methods for properties using the current datastore
         * 2. Run the datastore's createItem method
         * 3. Run the property-specific createValue methods for properties using the virtual datastore
         *
         * This may need to be adjusted in the future
         */

        foreach ($this->getFieldList() as $fieldname) {
            if (!empty($this->properties[$fieldname]->source) &&
                method_exists($this->properties[$fieldname],'createvalue')) {
                $this->properties[$fieldname]->createValue($this->itemid);
            }
        }
        $this->itemid = $this->datastore->createItem();

        foreach ($this->getFieldList() as $fieldname) {
            if (empty($this->properties[$fieldname]->source) &&
                method_exists($this->properties[$fieldname],'createvalue')) {
                $this->properties[$fieldname]->createValue($this->itemid);
            }
        }
        
        // Set the value of the primary index property
        $this->properties[$this->primary]->value = $this->itemid;
        
        return $this->itemid;
    }

    public function updateItem(Array $args = array())
    {
        xarLog::message("DataObject::updateItem: Updating an item of object " . $this->name, xarLog::LEVEL_INFO);

        if(count($args) > 0) {
            if(!empty($args['itemid']))
                $this->itemid = $args['itemid'];

            foreach($args as $name => $value)
                if(isset($this->properties[$name]))
                    $this->properties[$name]->setValue($value);
        }
        if(empty($this->itemid) && !empty($this->primary)) {
            $this->itemid = $this->properties[$this->primary]->getValue();
        }

        /* General sequence:
         * 1. Run the property-specific updateValue methods for properties using the current datastore
         * 2. Run the datastore's updateItem method
         * 3. Run the property-specific updateValue methods for properties using the virtual datastore
         *
         * This may need to be adjusted in the future
         */

        foreach ($this->getFieldList() as $fieldname) {
            if (!empty($this->properties[$fieldname]->source) &&
                method_exists($this->properties[$fieldname],'updatevalue')) {
                $this->properties[$fieldname]->updateValue($this->itemid);
            }
        }
        $this->itemid = $this->datastore->updateItem();

        foreach ($this->getFieldList() as $fieldname) {
            if (empty($this->properties[$fieldname]->source) &&
                method_exists($this->properties[$fieldname],'updatevalue')) {
                $this->properties[$fieldname]->updateValue($this->itemid);
            }
        }

        // CHECKME: flush the variable cache if necessary
        if ($this->objectid == 1) {
            DataObjectMaster::flushVariableCache(array('objectid' => $this->itemid));
        }

        return $this->itemid;
    }

    public function deleteItem(Array $args = array())
    {
        xarLog::message("DataObject::deleteItem: Deleting an item of object " . $this->name, xarLog::LEVEL_INFO);

        if(!empty($args['itemid']))
            $this->itemid = $args['itemid'];

        if(empty($this->itemid))
        {
            $msg = 'Invalid item id in method #(1)() for dynamic object [#(2)] #(3)';
            $vars = array('deleteItem',$this->objectid,$this->name);
            throw new BadParameterException($vars, $msg);
        }

        // Last stand against wild hooks and other excesses
        if(($this->objectid < 3) && ($this->itemid < 3))
        {
            $msg = 'You cannot delete the DataObject or DataProperties class';
            throw new BadParameterException(null, $msg);
        }

        // delete the item in all the data stores
        $args = $this->getFieldValues();
        $args['itemid'] = $this->itemid;

        /* General sequence:
         * 1. Run the property-specific deleteValue methods for properties using the current datastore
         * 2. Run the object's deleteItem method
         * 3. Run the property-specific deleteValue methods for properties using the virtual datastore
         *
         * This may need to be adjusted in the future
         */

        foreach ($this->getFieldList() as $fieldname) {
            if (!empty($this->properties[$fieldname]->source) &&
                method_exists($this->properties[$fieldname],'deletevalue')) {
                $this->properties[$fieldname]->deleteValue($this->itemid);
            }
        }
        $this->itemid = $this->datastore->deleteItem();
        if(empty($this->itemid)) return;                    // CHECKME: Is this needed?
        
        foreach ($this->getFieldList() as $fieldname) {
            if (empty($this->properties[$fieldname]->source) &&
                method_exists($this->properties[$fieldname],'deletevalue')) {
                $this->properties[$fieldname]->deleteValue($this->itemid);
            }
        }

        // CHECKME: flush the variable cache if necessary
        if ($this->objectid == 1) {
            DataObjectMaster::flushVariableCache(array('objectid' => $this->itemid));
        }

        // call delete hooks for this item
        $this->callHooks('delete');

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
    public function getNextItemtype(Array $args = array())
    {
        if(empty($args['moduleid']))
            $args['moduleid'] = $this->moduleid;

        $dbconn = xarDB::getConn();
        xarMod::loadDbInfo('dynamicdata','dynamicdata');
        $xartable =& xarDB::getTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $query = "SELECT MAX(itemtype) FROM $dynamicobjects  WHERE module_id = ?";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array((int)$args['moduleid']));
        if(!$result->first()) return; // shouldnt we raise?
        $nexttype = $result->getInt(1);

        // Note: this is *not* reliable in "multi-creator" environments
        $nexttype++;
        return $nexttype;
    }

    /**
     * Initialize whatever this object needs from the environment
     * This operation is in general performed only once
     *
     * @param array $args
     * @return integer value of the next item type
     *
     */
    function initialize(Array $args = array())
    {
        foreach ($this->properties as $name => $property) {
            $nameparts = explode(': ', $this->properties[$name]->source);
            if (empty($nameparts[1])) throw new Exception(xarML('Incorrect module name: #(1)',$modulename));
            $test = xarModVars::get($nameparts[1],$this->properties[$name]->name);
            if ($test === null)
                xarModVars::set($nameparts[1],$this->properties[$name]->name,$this->properties[$name]->defaultvalue);
        }
        return true;
    }
}
?>