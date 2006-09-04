<?php
/**
 * Base class for Dynamic Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
**/
sys::import('modules.dynamicdata.class.datastores.master');
sys::import('datastores.interface');

class BasicDataStore extends XarayaDDObject implements IBasicDataStore
{
    protected $schemaobject;	// The object representing this datastore as codified by its schema

    public $_itemids;  // reference to itemids in Dynamic_Object_List TODO: investigate public scope

    public $cache = 0;

    public $type;

    function getItem($args = array())
    {
        return $args['itemid'];
    }

    function createItem($args = array())
    {
        return $args['itemid'];
    }

    function updateItem($args = array())
    {
        return $args['itemid'];
    }

    function deleteItem($args = array())
    {
        return $args['itemid'];
    }

    function getItems($args = array())
    {
        // abstract?
    }

    function countItems($args = array())
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
    public $fields = array();   // array of $name => reference to property in Dynamic_Object*
    public $primary= null;

    public $sort   = array();

    /**
     * Get the field name used to identify this property (by default, the property name itself)
     *
     * @todo seems odd, dunno
     * @todo type hinting
     */
    function getFieldName(&$property)
    {
        return $property->name;
    }

    /**
     * Add a field to get/set in this data store, and its corresponding property
     */
    function addField(&$property)
    {
        $name = $this->getFieldName($property);
        if(!isset($name))
            return;

        $this->fields[$name] = &$property; // use reference to original property

        if(!isset($this->primary) && $property->type == 21)
            // Item ID
            $this->setPrimary($property);
    }

    /**
     * Set the primary key for this data store (only 1 allowed for now)
     */
    function setPrimary(&$property)
    {
        $name = $this->getFieldName($property);
        if(!isset($name))
            return;

        $this->primary = $name;
    }

    /**
     * Add a sort criteria for this data store (for getItems)
     */
    function addSort(&$property, $sortorder = 'ASC')
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