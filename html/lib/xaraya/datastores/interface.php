<?php
/**
 * @package core\datastores
 * @subpackage datastores
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marcel van der Boom <mrb@hsdev.com>
**/

namespace Xaraya\DataObject\DataStores;

use DataProperty;
use SimpleXMLElement;
use BadParameterException;

/*
    Current inheritance (leaving out Data prefix and _Datastore suffix)

    XarayaDDObject
     |
    BasicDataStore
     |---SQL
     |    |---FlatTable
     |    |---VariableTable
     |---Hook
     |---File
     |    |---CSVFile
     |    |---XMLFile
     |---Join
     |---Dummy
     |---UserSettings
     |---Function
     |---LDAP
     |---ModuleVariables

     The current design of a datastore:
     - is tied to DD specifically, but not irrevocably so
     - is specified on a property by property basis (i.e. 1 dd object can be tied to multiple datastores)
     - as such a datastore can be viewed as a one dimensional list of items (db: table-column for example) it's *NOT* 2 dimensional.
     - ...and that is confusing, given the name DataStore.
*/

/**
 * Interfaces as observed in current code
 * @todo move xml schema elsewhere
**/
interface IDDObject
{
    /** @param ?string $name */
    public function __construct($name = null);

    // @note routines for dealing with XML files
    /**
     * Summary of readSchema
     * @param array<string, mixed> $args
     * @throws \BadParameterException
     * @return SimpleXMLElement|bool
     */
    public function readSchema(array $args = []);

    /**
     * Summary of loadSchema
     * @param array<string, mixed> $args
     * @return void
     */
    public function loadSchema(array $args = []);

    /**
     * Summary of toArray
     * @param SimpleXMLElement|null $schemaobject
     * @return array<mixed>|bool
     */
    public function toArray(SimpleXMLElement $schemaobject = null);

    /**
     * Summary of toXML
     * @param SimpleXMLElement|null $schemaobject
     * @return bool|string
     */
    public function toXML(SimpleXMLElement $schemaobject = null);
}

interface IBasicDataStore
{
    // @note tied to properties, as used by dd
    /**
     * Get the field name used to identify this property (by default, the property name itself)
     * @param DataProperty $property
     * @return string
     */
    public function getFieldName(DataProperty &$property);

    /**
     * Add a field to get/set in this data store, and its corresponding property
     * @param DataProperty $property
     * @return void
     */
    public function addField(DataProperty &$property);

    // @note this looks pretty generic, but we dont know what's in $args
    /**
     * Summary of getItem
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function getItem(array $args = []); // would typ. need some sort of ID value

    /**
     * Summary of createItem
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function createItem(array $args = []); // would typ. need some sort of Item object

    /**
     * Summary of updateItem
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function updateItem(array $args = []); // would typ. need some sort of Item object

    /**
     * Summary of deleteItem
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function deleteItem(array $args = []); // would typ. need some sort of ID value

    /**
     * Summary of getItems
     * @param array<string, mixed> $args
     * @return void
     */
    public function getItems(array $args = []); // would typ. need some sort of Criteria object

    /**
     * Summary of countItems
     * @param array<string, mixed> $args
     * @return int
     */
    public function countItems(array $args = []); // would typ. need some sort of Criteria object
}

/**
 * Summary of IOrderedDataStore
 */
interface IOrderedDataStore
{
    /**
     * Set the primary key for this data store (only 1 allowed for now)
     * @param DataProperty $property
     * @return void
     */
    public function setPrimary(DataProperty &$property);

    /**
     * Add a sort criteria for this data store (for getItems)
     * @param DataProperty $property
     * @param mixed $sortorder
     * @return void
     */
    public function addSort(DataProperty &$property, $sortorder = 'ASC');

    // @note tied to db table

    // @note this looks pretty generic
    /**
     * Remove all sort criteria for this data store (for getItems)
     * @return void
     */
    public function cleanSort();
}

interface ISQLDataStore
{
    // @note tied to properties, as used by dd
    /**
     * Add a where clause for this data store (for getItems)
     * @param DataProperty $property
     * @param mixed $clause
     * @param mixed $join
     * @param mixed $pre
     * @param mixed $post
     * @return void
     */
    public function addWhere(DataProperty &$property, $clause, $join, $pre = '', $post = '');

    /**
     * Add a group by field for this data store (for getItems)
     * @param DataProperty $property
     * @return void
     */
    public function addGroupBy(DataProperty &$property);

    /**
     * Join another database table to this data store (unfinished)
     * @param mixed $table
     * @param mixed $key
     * @param mixed $fields
     * @param mixed $where
     * @param mixed $andor
     * @param mixed $more
     * @param mixed $sort
     * @return void
     */
    public function addJoin($table, $key, $fields, $where = '', $andor = 'and', $more = '', $sort = []);

    // @note this looks pretty generic
    /**
     * Remove all where criteria for this data store (for getItems)
     * @return void
     */
    public function cleanWhere();

    /**
     * Remove all group by fields for this data store (for getItems)
     * @return void
     */
    public function cleanGroupBy();

    /**
     * Remove all join criteria for this data store (for getItems)
     * @return void
     */
    public function cleanJoin();

    // @note database functions for lazy connection
    /**
     * Summary of getTable
     * @param mixed $name
     * @return mixed
     */
    //protected function getTable($name);

    /**
     * Summary of getType
     * @return mixed
     */
    //protected function getType();

    /**
     * Summary of prepareStatement
     * @param mixed $sql
     * @return mixed
     */
    //protected function prepareStatement($sql);

    /**
     * Summary of getLastId
     * @param mixed $table
     * @return mixed
     */
    //protected function getLastId($table);

    /**
     * Summary of getDatabaseInfo
     * @return mixed
     */
    //protected function getDatabaseInfo();
}

/*
    Introduced by FlatTable datastore:
        function getNext($args = array())

    Introduced by VariableTable datastore:
        function getNextId(Array $args=array())
*/
