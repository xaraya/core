<?php
/**
 * Utility Class to manage Dynamic Data Stores
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Utility Class to manage Dynamic Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 * @todo this factory should go into core once we use datastores in more broad ways.
 * @todo the classnames could use a bit of a clean up (shorter, lowercasing)
 */
class Dynamic_DataStore_Master
{
    /**
     * Class method to get a new dynamic data store (of the right type)
     */
    static function &getDataStore($name = '_dynamic_data_', $type = 'data')
    {
        switch ($type)
        {
            case 'table':
                sys::import('datastores.sql.flattable');
                $datastore = new Dynamic_FlatTable_DataStore($name);
                break;
            case 'data':
                sys::import('datastores.sql.variabletable');
                $datastore = new Dynamic_VariableTable_DataStore($name);
                break;
            case 'hook':
                sys::import('datastores.hook');
                $datastore = new Dynamic_Hook_DataStore($name);
                break;
            case 'function':
                sys::import('datastores.function');
                $datastore = new Dynamic_Function_DataStore($name);
                break;
            case 'uservars':
                sys::import('datastores.usersettings');
                // TODO: integrate user variable handling with DD
                $datastore = new Dynamic_UserSettings_DataStore($name);
                break;
            case 'modulevars':
                sys::import('datastores.modulevariables');
                // TODO: integrate module variable handling with DD
                $datastore = new Dynamic_ModuleVariables_DataStore($name);
                break;

                // TODO: other data stores
            case 'ldap':
                sys::import('datastores.ldap');
                $datastore = new Dynamic_LDAP_DataStore($name);
                break;
            case 'xml':
                sys::import('datastores.file.xml');
                $datastore = new Dynamic_XMLFile_DataStore($name);
                break;
            case 'csv':
                sys::import('datastores.file.csv');
                $datastore = new Dynamic_CSVFile_DataStore($name);
                break;
            case 'dummy':
            default:
                sys::import('datastores.dummy');
                $datastore = new Dynamic_Dummy_DataStore($name);
                break;
        }
        return $datastore;
    }

    function getDataStores()
    {
    }

    /**
     * Get possible data sources (// TODO: for a module ?)
     *
     * @param $args['table'] optional extra table whose fields you want to add as potential data source
     */
    static function &getDataSources($args = array())
    {
        $sources = array();

        // default data source is dynamic data
        $sources[] = 'dynamic_data';

        // module variables
        $sources[] = 'module variables';

        // user settings (= user variables per module)
        $sources[] = 'user settings';

        // session variables // TODO: perhaps someday, if this makes sense
        //$sources[] = 'session variables';

        // TODO: re-evaluate this once we're further along
        // hook modules manage their own data
        $sources[] = 'hook module';

        // user functions manage their own data
        $sources[] = 'user function';

        // no local storage
        $sources[] = 'dummy';

        // try to get the meta table definition
        if (!empty($args['table'])) {
            try {
                $meta = xarModAPIFunc('dynamicdata','util','getmeta',$args);
            } catch ( NotFoundExceptions $e ) {
                // No worries
            }
            if (!empty($meta) && !empty($meta[$args['table']])) {
                foreach ($meta[$args['table']] as $column) {
                    if (!empty($column['source'])) {
                        $sources[] = $column['source'];
                    }
                }
            }
        }

        $dbconn =& xarDBGetConn();
        $dbInfo = $dbconn->getDatabaseInfo();
        $dbTables = $dbInfo->getTables();
        foreach($dbTables as $tblInfo)
        {
            $tblColumns = $tblInfo->getColumns();
            foreach($tblColumns as $colInfo)
            {
                $sources[] = $tblInfo->getName().".".$colInfo->getName();
            }
        }
        return $sources;
    }
}

sys::import('datastores.interface');
/**
 * Base class for Dynamic Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_DataStore implements IDataStore
{
    public $name   = '';     // some static name, or the table name, or the moduleid + itemtype, or ...
    public $type;
    public $fields = array();   // array of $name => reference to property in Dynamic_Object*
    public $primary= null;

    public $sort   = array();
    public $where  = array();
    public $groupby= array();
    public $join   = array();

    public $_itemids;  // reference to itemids in Dynamic_Object_List TODO: investigate public scope

    public $cache = 0;

    function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the field name used to identify this property (by default, the property name itself)
     */
    function getFieldName($property)
    {
        return $property->name;
    }

    /**
     * Add a field to get/set in this data store, and its corresponding property
     */
    function addField($property)
    {
        $name = $this->getFieldName(&$property);
        if (!isset($name)) return;

        $this->fields[$name] = &$property; // use reference to original property

        if (!isset($this->primary) && $property->type == 21) { // Item ID
            $this->setPrimary($property);
        }
    }

    /**
     * Set the primary key for this data store (only 1 allowed for now)
     */
    function setPrimary(&$property)
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

        $this->primary = $name;
    }

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

    /**
     * Add a sort criteria for this data store (for getItems)
     */
    function addSort(&$property, $sortorder = 'ASC')
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

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

    /**
     * Add a where clause for this data store (for getItems)
     */
    function addWhere(&$property, $clause, $join, $pre = '', $post = '')
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

        $this->where[] = array('field'  => $name,
                               'clause' => $clause,
                               'join'   => $join,
                               'pre'    => $pre,
                               'post'   => $post);
    }

    /**
     * Remove all where criteria for this data store (for getItems)
     */
    function cleanWhere()
    {
        $this->where = array();
    }

    /**
     * Add a group by field for this data store (for getItems)
     */
    function addGroupBy(&$property)
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

        $this->groupby[] = $name;
    }

    /**
     * Remove all group by fields for this data store (for getItems)
     */
    function cleanGroupBy()
    {
        $this->groupby = array();
    }

    /**
     * Join another database table to this data store (unfinished)
     */
    function addJoin($table, $key, $fields, $where = array(), $andor = 'and', $more = '', $sort = array())
    {
        if (!isset($this->extra)) {
            $this->extra = array();
        }
        $fieldlist = array();
        foreach (array_keys($fields) as $field) {
            $source = $fields[$field]->source;
            // save the source for the query fieldlist
            $fieldlist[] = $source;
            // save the source => property pairs for returning the values
            $this->extra[$source] = & $fields[$field]; // use reference to original property
        }
        $whereclause = '';
        if (is_array($where) && count($where) > 0) {
            foreach ($where as $part) {
// TODO: support pre- and post-parts here too ? (cfr. bug 3090)
                $whereclause .= $part['join'] . ' ' . $part['property']->source . ' ' . $part['clause'] . ' ';
            }
        } elseif (is_string($where)) {
            $whereclause = $where;
        }
        $this->join[] = array('table' => $table,
                              'key' => $key,
                              'fields' => $fieldlist,
                              'where' => $whereclause,
                              'andor' => $andor,
                              'more' => $more);
    }

    /**
     * Remove all join criteria for this data store (for getItems)
     */
    function cleanJoin()
    {
        $this->join = array();
    }

}

?>
