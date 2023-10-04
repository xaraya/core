<?php
/**
 * Base class for Dynamic Data Stores
 *
 * @package core\datastores
 * @subpackage datastores
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
*/
sys::import('xaraya.datastores.factory');
sys::import('xaraya.datastores.interface');

class BasicDataStore extends DDObject implements IBasicDataStore
{
    public $fields = [];   // array of $name => reference to property in DataObject*
    public $_itemids;           // reference to itemids in DataObjectList TODO: investigate public scope
    /** @var DataObject|DataObjectList $object */
    public $object;             // reference to DataObject or DataObjectList TODO: investigate public scope

    public $cache = 0;

    public $type;

    /**
     * Add a field to get/set in this data store, and its corresponding property
     */
    public function addField(DataProperty &$property)
    {
        $name = $this->getFieldName($property);
        $this->fields[$name] = &$property; // use reference to original property
    }

    /**
     * Remove all group by fields for this data store (for getItems)
     */
    public function cleanGroupBy()
    {
        if ($this instanceof DataObjectList) {
            $this->groupby = [];
        }
    }

    /**
     * Remove all where criteria for this data store (for getItems)
     */
    public function cleanWhere()
    {
        if ($this instanceof DataObjectList) {
            $this->where = [];
        }
    }

    /**
     * Remove all sorts for this data store (for getItems)
     */
    public function cleanSort()
    {
        if ($this instanceof DataObjectList) {
            $this->sort = [];
        }
    }

    /**
     * Get the field name used to identify this property (by default, the property name itself)
     */
    public function getFieldName(DataProperty &$property)
    {
        return $property->name;
    }

    public function getItem(array $args = [])
    {
        return $args['itemid'];
    }

    public function createItem(array $args = [])
    {
        return $args['itemid'];
    }

    public function updateItem(array $args = [])
    {
        return $args['itemid'];
    }

    public function deleteItem(array $args = [])
    {
        return $args['itemid'];
    }

    public function getItems(array $args = [])
    {
        // abstract?
    }

    public function countItems(array $args = [])
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
    public $primary = null;

    public $sort   = [];

    /**
     * Add a field to get/set in this data store, and its corresponding property
     */
    public function addField(DataProperty &$property)
    {
        parent::addField($property);
        if(!isset($this->primary) && $property->type == 21) {
            // Item ID
            $this->setPrimary($property);
        }
    }

    /**
     * Set the primary key for this data store (only 1 allowed for now)
     */
    public function setPrimary(DataProperty &$property)
    {
        $name = $this->getFieldName($property);
        $this->primary = $name;
    }

    /**
     * Add a sort criteria for this data store (for getItems)
     */
    public function addSort(DataProperty &$property, $sortorder = 'ASC')
    {
        $name = $this->getFieldName($property);
        $this->sort[] = ['field'     => $name,
                              'sortorder' => $sortorder];
    }

    /**
     * Remove all sort criteria for this data store (for getItems)
     */
    public function cleanSort()
    {
        $this->sort = [];
    }
}
