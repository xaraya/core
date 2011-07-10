<?php
/**
 * @package core
 * @subpackage datastores
 * Interface declarations for the datastores hierarchy
 *
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marcel van der Boom <mrb@hsdev.com>
**/

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
**/
interface IDDObject
{
    function __construct($name=null);

    // @note routines for dealing with XML files
    function readSchema(Array $args = array());
    function loadSchema(Array $args = array());
    function toArray(SimpleXMLElement $schemaobject=null);
    function toXML(SimpleXMLElement $schemaobject=null);
}

interface IBasicDataStore
{
    // @note this looks pretty generic, but we dont know what's in $args
    function    getItem(Array $args = array()); // would typ. need some sort of ID value
    function createItem(Array $args = array()); // would typ. need some sort of Item object
    function updateItem(Array $args = array()); // would typ. need some sort of Item object
    function deleteItem(Array $args = array()); // would typ. need some sort of ID value
    function   getItems(Array $args = array()); // would typ. need some sort of Criteria object
    function countItems(Array $args = array()); // would typ. need some sort of Criteria object
}

interface IOrderedDataStore
{
    // @note tied to properties, as used by dd
    function getFieldName(DataProperty &$property);
    function     addField(DataProperty &$property);
    function   setPrimary(DataProperty &$property);
    function      addSort(DataProperty &$property, $sortorder = 'ASC');

    // @note tied to db table

    // @note this looks pretty generic
    function cleanSort();
}

interface ISQLDataStore
{
    // @note tied to properties, as used by dd
    function     addWhere(DataProperty &$property, $clause, $join, $pre = '', $post = '');
    function   addGroupBy(DataProperty &$property);
    function      addJoin($table, $key, $fields, $where = '', $andor = 'and', $more = '', $sort = array());

    // @note this looks pretty generic
    function cleanWhere();
    function cleanGroupBy();
    function cleanJoin();

    // @note database functions for lazy connection
    function getTable($name);
    function getType();
    function prepareStatement($sql);
    function getLastId($table);
    function getDatabaseInfo();
}

/*
    Introduced by FlatTable datastore:
        function getNext($args = array())

    Introduced by VariableTable datastore:
        function getNextId(Array $args=array())
*/
?>
