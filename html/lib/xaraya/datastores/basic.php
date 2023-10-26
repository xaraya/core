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

namespace Xaraya\DataObject\DataStores;

use DataObject;
use DataObjectList;
use DataProperty;
use DataPropertyMaster;
use sys;

sys::import('xaraya.datastores.factory');
sys::import('xaraya.datastores.interface');

/**
 * Base class for Dynamic Data Stores
 */
class BasicDataStore extends DDObject implements IBasicDataStore
{
    /** @var array<string, mixed> */
    public $fields = [];   // array of $name => reference to property in DataObject*
    /** @var array<mixed> */
    public $_itemids;           // reference to itemids in DataObjectList TODO: investigate public scope
    /** @var DataObject|DataObjectList */
    public $object;             // reference to DataObject or DataObjectList TODO: investigate public scope

    /** @var mixed */
    public $cache = 0;          // @deprecated not actually used in datastores

    /** @var mixed */
    public $type;

    /**
     * Add a field to get/set in this data store, and its corresponding property
     * @param DataProperty $property
     * @return void
     */
    public function addField(DataProperty &$property)
    {
        $name = $this->getFieldName($property);
        $this->fields[$name] = &$property; // use reference to original property
    }

    /**
     * Remove all group by fields for this data store (for getItems)
     * @return void
     */
    public function cleanGroupBy()
    {
        // see SQLDataStore
    }

    /**
     * Remove all where criteria for this data store (for getItems)
     * @return void
     */
    public function cleanWhere()
    {
        // see SQLDataStore
    }

    /**
     * Remove all sorts for this data store (for getItems)
     * @return void
     */
    public function cleanSort()
    {
        // see OrderedDataStore
    }

    /**
     * Get the field name used to identify this property (by default, the property name itself)
     * @param DataProperty $property
     * @return string|null
     */
    public function getFieldName(DataProperty &$property)
    {
        return $property->name;
    }

    /**
     * Summary of getItem
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function getItem(array $args = [])
    {
        return $args['itemid'];
    }

    /**
     * Summary of createItem
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function createItem(array $args = [])
    {
        return $args['itemid'];
    }

    /**
     * Summary of updateItem
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function updateItem(array $args = [])
    {
        return $args['itemid'];
    }

    /**
     * Summary of deleteItem
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function deleteItem(array $args = [])
    {
        return $args['itemid'];
    }

    /**
     * Summary of getItems
     * @param array<string, mixed> $args
     * @return void
     */
    public function getItems(array $args = [])
    {
        // abstract?
    }

    /**
     * Summary of countItems
     * @param array<string, mixed> $args
     * @return int|null
     */
    public function countItems(array $args = [])
    {
        return null; // <-- make this numeric!!
    }
}

/**
 * Base class for Dynamic Data Stores with a concept of ordering
 */
class OrderedDataStore extends BasicDataStore implements IOrderedDataStore
{
    /** @var mixed */
    public $primary = null;

    /** @var array<mixed> */
    public $sort   = [];

    /**
     * Add a field to get/set in this data store, and its corresponding property
     * @param DataProperty $property
     * @return void
     */
    public function addField(DataProperty &$property)
    {
        parent::addField($property);
        if(!isset($this->primary) && DataPropertyMaster::isPrimaryType($property->type)) {
            // Item ID
            $this->setPrimary($property);
        }
    }

    /**
     * Set the primary key for this data store (only 1 allowed for now)
     * @param DataProperty $property
     * @return void
     */
    public function setPrimary(DataProperty &$property)
    {
        $name = $this->getFieldName($property);
        $this->primary = $name;
    }

    /**
     * Add a sort criteria for this data store (for getItems)
     * @param DataProperty $property
     * @param mixed $sortorder
     * @return void
     */
    public function addSort(DataProperty &$property, $sortorder = 'ASC')
    {
        $name = $this->getFieldName($property);
        $this->sort[] = ['field'     => $name,
                        'sortorder' => $sortorder];
    }

    /**
     * Remove all sort criteria for this data store (for getItems)
     * @return void
     */
    public function cleanSort()
    {
        $this->sort = [];
    }
}
