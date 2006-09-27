<?php
/**
 * Base class for Dynamic Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
**/
sys::import('modules.dynamicdata.class.datastores.master');
sys::import('datastores.interface');

class BasicDataStore extends DDObject implements IBasicDataStore
{
    protected $schemaobject;    // The object representing this datastore as codified by its schema

    public $fields = array();   // array of $name => reference to property in Dynamic_Object*
    public $_itemids;  // reference to itemids in Dynamic_Object_List TODO: investigate public scope

    public $cache = 0;

    public $type;

    /**
     * Add a field to get/set in this data store, and its corresponding property
     */
    function addField(Dynamic_Property &$property)
    {
        $name = $this->getFieldName($property);
        if(!isset($name))
            return;

        $this->fields[$name] = &$property; // use reference to original property
    }
    
    /**
     * Get the field name used to identify this property (by default, the property name itself)
     */
    function getFieldName(Dynamic_Property &$property)
    {
        return $property->name;
    }

    function getItem(array $args = array())
    {
        return $args['itemid'];
    }

    function createItem(array $args = array())
    {
        return $args['itemid'];
    }

    function updateItem(array $args = array())
    {
        return $args['itemid'];
    }

    function deleteItem(array $args = array())
    {
        return $args['itemid'];
    }

    function getItems(array $args = array())
    {
        // abstract?
    }

    function countItems(array $args = array())
    {
        return null; // <-- make this numeric!!
    }
}

/**
 * Base class for Dynamic Data Stores with a concept of ordering
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
**/

class OrderedDataStore extends BasicDataStore implements IOrderedDataStore
{
    public $primary= null;

    public $sort   = array();

    /**
     * Add a field to get/set in this data store, and its corresponding property
     */
    function addField(Dynamic_Property &$property)
    {
        parent::addField($property);
        if(!isset($this->primary) && $property->type == 21)
            // Item ID
            $this->setPrimary($property);
    }

    /**
     * Set the primary key for this data store (only 1 allowed for now)
     */
    function setPrimary(Dynamic_Property &$property)
    {
        $name = $this->getFieldName($property);
        if(!isset($name))
            return;

        $this->primary = $name;
    }

    /**
     * Add a sort criteria for this data store (for getItems)
     */
    function addSort(Dynamic_Property &$property, $sortorder = 'ASC')
    {
        $name = $this->getFieldName($property);
        if(!isset($name))
            return;

        $this->sort[] = array('field'     => $name,
                              'sortorder' => $sortorder);
    }

    /**
     * Remove all sort criteria for this data store (for getItems)
     */
    function cleanSort()
    {
        $this->sort = array();
    }

}
?>
