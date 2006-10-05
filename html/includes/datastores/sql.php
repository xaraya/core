<?php
/**
 * Base class for SQL Data Stores
 *
 * @package dynamicdata
 * @subpackage datastores
**/

/**
 * Base class for SQL Data Stores
 *
 * @package dynamicdata
**/
sys::import('modules.dynamicdata.class.datastores.base');

class DataSQL_DataStore extends OrderedDataStore implements ISQLDataStore
{
    protected $db     = null;
    protected $tables = null;

    public $where  = array();
    public $groupby= array();
    public $join   = array();

    function __construct($name=null)
    {
        parent::__construct($name);
        $this->db     = xarDBGetConn();
        $this->tables = xarDBGetTables(); // Is this scopy enough? i.e. would all tables be there already?
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
     * Remove all where criteria for this data store (for getItems)
     */
    function cleanWhere()
    {
        $this->where = array();
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
     * Remove all group by fields for this data store (for getItems)
     */
    function cleanGroupBy()
    {
        $this->groupby = array();
    }

    /**
     * Join another database table to this data store (unfinished)
     */
    function addJoin($table, $key, $fields, array $where = array(), $andor = 'and', $more = '', $sort = array())
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
}

?>