<?php
/**
 * Base class for Dynamic Data Stores
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
*/
sys::import('modules.dynamicdata.class.datastores.master');
sys::import('xaraya.datastores.interface');

class BasicDataStore extends DDObject implements IBasicDataStore
{
    protected $schemaobject;    // The object representing this datastore as codified by its schema

    public $fields = array();   // array of $name => reference to property in DataObject*
    public $_itemids;           // reference to itemids in DataObjectList TODO: investigate public scope

    public $cache = 0;

    public $type;

    /**
     * Add a field to get/set in this data store, and its corresponding property
     */
    function addField(DataProperty &$property)
    {
        $name = $this->getFieldName($property);
        $this->fields[$name] = &$property; // use reference to original property
    }

    /**
     * Remove all group by fields for this data store (for getItems)
     */
    function cleanGroupBy()
    {
        $this->groupby = array();
    }

    /**
     * Remove all where criteria for this data store (for getItems)
     */
    function cleanWhere()
    {
        $this->where = array();
    }

    /**
     * Remove all sorts for this data store (for getItems)
     */
    function cleanSort()
    {
        $this->sort = array();
    }

    /**
     * Get the field name used to identify this property (by default, the property name itself)
     */
    function getFieldName(DataProperty &$property)
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
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 * @author Marc Lutolf <marc@luetolf-carroll.com>
 */
class OrderedDataStore extends BasicDataStore implements IOrderedDataStore
{
    public $primary= null;

    public $sort   = array();

    /**
     * Add a field to get/set in this data store, and its corresponding property
     */
    function addField(DataProperty &$property)
    {
        parent::addField($property);
        if(!isset($this->primary) && $property->type == 21)
            // Item ID
            $this->setPrimary($property);
    }

    /**
     * Set the primary key for this data store (only 1 allowed for now)
     */
    function setPrimary(DataProperty &$property)
    {
        $name = $this->getFieldName($property);
        $this->primary = $name;
    }

    /**
     * Add a sort criteria for this data store (for getItems)
     */
    function addSort(DataProperty &$property, $sortorder = 'ASC')
    {
        $name = $this->getFieldName($property);
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