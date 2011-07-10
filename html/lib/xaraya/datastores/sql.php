<?php
/**
 * Base class for SQL Data Stores
 *
 * @package core
 * @subpackage datastores
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
**/

/**
 * Base class for SQL Data Stores
 *
**/
sys::import('modules.dynamicdata.class.datastores.base');

class SQLDataStore extends OrderedDataStore implements ISQLDataStore
{
    protected $db     = null;
    //protected $tables = null;

    public $where  = array();
    public $groupby= array();
    public $join   = array();

    function __construct($name=null)
    {
        parent::__construct($name);
        // lazy connection
        //$this->db     = xarDB::getConn();
        //$this->tables = xarDB::getTables(); // Is this scopy enough? i.e. would all tables be there already?
    }

    /**
     * Add a where clause for this data store (for getItems)
     */
    function addWhere(DataProperty &$property, $clause, $join, $pre = '', $post = '')
    {
        $name = $this->getFieldName($property);
        if(!isset($name))
            return;

        $this->where[] = array('field'  => $name,
                               'clause' => $clause,
                               'join'   => $join,
                               'pre'    => $pre,
                               'post'   => $post);
    }

    /**
     * Add a group by field for this data store (for getItems)
     */
    function addGroupBy(DataProperty &$property)
    {
        $name = $this->getFieldName($property);
        if(!isset($name))
            return;

        $this->groupby[] = $name;
    }

    /**
     * Join another database table to this data store (unfinished)
     */
    function addJoin($table, $key, $fields, $where = '', $andor = 'and', $more = '', $sort = array())
    {
        if(!isset($this->extra))
            $this->extra = array();

        $fieldlist = array();
        foreach(array_keys($fields) as $field)
        {
            $source = $fields[$field]->source;
            // save the source for the query fieldlist
            $fieldlist[] = $source;
            // save the source => property pairs for returning the values
            $this->extra[$source] = & $fields[$field]; // use reference to original property
        }

        $whereclause = '';
        if(is_array($where) && count($where) > 0)
        {
            foreach($where as $part)
            {
                // TODO: support pre- and post-parts here too ? (cfr. bug 3090)
                $whereclause .= $part['join'] . ' ' . $part['property']->source . ' ' . $part['clause'] . ' ';
            }
        }
        elseif(is_string($where))
            $whereclause = $where;

        $this->join[] = array(
            'table'  => $table,
            'key'    => $key,
            'fields' => $fieldlist,
            'where'  => $whereclause,
            'andor'  => $andor,
            'more'   => $more
        );
    }

    /**
     * Remove all join criteria for this data store (for getItems)
     */
    function cleanJoin()
    {
        $this->join = array();
    }

    /**
     * Database functions for lazy connection
     */
    function connect()
    {
        // Note: the only reason we keep this variable is for getLastId()
        if (empty($this->db)) {
            $this->db = xarDB::getConn();
        }
    }

    function getTable($name)
    {
        $tables = xarDB::getTables();
        if (!empty($tables[$name])) {
            return $tables[$name];
        }
    }

    function getType()
    {
        return xarDB::getType();
    }

    function prepareStatement($sql)
    {
        $this->connect();
        return $this->db->prepareStatement($sql);
    }

    function getLastId($table)
    {
        $this->connect();
        return $this->db->getLastId($table);
    }

    function getDatabaseInfo()
    {
        $this->connect();
        return $this->db->getDatabaseInfo();
    }
}

?>
