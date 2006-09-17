<?php
/**
 * Interface declarations for the datastores hierarchy
 *
 * @copyright The Digital Development Foundation, 2006
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @author Marcel van der Boom <mrb@hsdev.com>
**/

/*
    Current inheritance (leaving out Dynamic_ prefix and _Datastore suffix)

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
interface IXarayaDDObject
{
    function __construct($name);

    // @note routines for dealing with XML files
    function readSchema(array $args = array());
    function loadSchema(array $args = array());
    function toArray(SimpleXMLElement $schemaobject=null);
    function toXML(SimpleXMLElement $schemaobject=null);
}

interface IBasicDataStore
{
    // @note this looks pretty generic, but we dont know what's in $args
    function    getItem(array $args = array()); // would typ. need some sort of ID value
    function createItem(array $args = array()); // would typ. need some sort of Item object
    function updateItem(array $args = array()); // would typ. need some sort of Item object
    function deleteItem(array $args = array()); // would typ. need some sort of ID value
    function   getItems(array $args = array()); // would typ. need some sort of Criteria object
    function countItems(array $args = array()); // would typ. need some sort of Criteria object
}

interface IOrderedDataStore
{
    // @note tied to properties, as used by dd
    function getFieldName(Dynamic_Property &$property);
    function     addField(Dynamic_Property &$property);
    function   setPrimary(Dynamic_Property &$property);
    function      addSort(Dynamic_Property &$property, $sortorder = 'ASC');

    // @note tied to db table

    // @note this looks pretty generic
    function cleanSort();
}

interface ISQLDataStore
{
    // @note tied to properties, as used by dd
    function     addWhere(Dynamic_Property &$property, $clause, $join, $pre = '', $post = '');
    function   addGroupBy(Dynamic_Property &$property);
    function      addJoin($table, $key, $fields, array $where = array(), $andor = 'and', $more = '', $sort = array());

    // @note this looks pretty generic
    function cleanWhere();
    function cleanGroupBy();
    function cleanJoin();
}

/*
    Introduced by FlatTable datastore:
        function getNext($args = array())

    Introduced by VariableTable datastore:
        function getNextId($args)
*/
?>
