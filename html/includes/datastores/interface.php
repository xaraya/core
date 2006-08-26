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
    
    Dynamic_DataStore
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
interface IDataStore 
{
    // Introduced by Dynamic_DataStore
    function __construct($name);
    
    // @note this looks pretty generic, but we dont know what's in $args
    function    getItem($args = array()); // would typ. need some sort of ID value
    function createItem($args = array()); // would typ. need some sort of Item object
    function updateItem($args = array()); // would typ. need some sort of Item object
    function deleteItem($args = array()); // would typ. need some sort of ID value
    function   getItems($args = array()); // would typ. need some sort of Criteria object
    function countItems($args = array()); // would typ. need some sort of Criteria object

    // @note tied to properties, as used by dd
    function getFieldName(&$property);
    function     addField(&$property);
    function   setPrimary(&$property);
    function      addSort(&$property, $sortorder = 'ASC');
    function     addWhere(&$property, $clause, $join, $pre = '', $post = '');
    function   addGroupBy(&$property);
    
    // @note tied to db table
    function addJoin($table, $key, $fields, $where = array(), $andor = 'and', $more = '', $sort = array());
    
    // @note this looks pretty generic
    function cleanSort();
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
