<?php
/**
 * Base class for SQL Data Stores
 *
 * @package core\datastores
 * @subpackage datastores
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
**/

sys::import('xaraya.datastores.basic');

/**
 * Base class for SQL Data Stores
 *
**/
class SQLDataStore extends OrderedDataStore implements ISQLDataStore
{
    /** @var mixed */
    protected $db     = null;
    //protected $tables = null;

    /** @var array<mixed> */
    public $where  = [];
    /** @var array<mixed> */
    public $groupby = [];
    /** @var array<mixed> */
    public $join   = [];
    /** @var ?array<mixed> */
    public $extra;

    /**
     * Summary of __construct
     * @param mixed $name
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
        // lazy connection
        //$this->db     = xarDB::getConn();
        //$this->tables = xarDB::getTables(); // Is this scopy enough? i.e. would all tables be there already?
    }

    /**
     * Add a where clause for this data store (for getItems)
     * @param DataProperty $property
     * @param mixed $clause
     * @param mixed $join
     * @param mixed $pre
     * @param mixed $post
     * @return void
     */
    public function addWhere(DataProperty &$property, $clause, $join, $pre = '', $post = '')
    {
        $name = $this->getFieldName($property);
        if(!isset($name)) {
            return;
        }

        $this->where[] = ['field'  => $name,
                               'clause' => $clause,
                               'join'   => $join,
                               'pre'    => $pre,
                               'post'   => $post];
    }

    /**
     * Remove all where criteria for this data store (for getItems)
     * @return void
     */
    public function cleanWhere()
    {
        $this->where = [];
    }

    /**
     * Add a group by field for this data store (for getItems)
     * @param DataProperty $property
     * @return void
     */
    public function addGroupBy(DataProperty &$property)
    {
        $name = $this->getFieldName($property);
        if(!isset($name)) {
            return;
        }

        $this->groupby[] = $name;
    }

    /**
     * Remove all group by fields for this data store (for getItems)
     * @return void
     */
    public function cleanGroupBy()
    {
        $this->groupby = [];
    }

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
    public function addJoin($table, $key, $fields, $where = '', $andor = 'and', $more = '', $sort = [])
    {
        if(!isset($this->extra)) {
            $this->extra = [];
        }

        $fieldlist = [];
        foreach(array_keys($fields) as $field) {
            $source = $fields[$field]->source;
            // save the source for the query fieldlist
            $fieldlist[] = $source;
            // save the source => property pairs for returning the values
            $this->extra[$source] = & $fields[$field]; // use reference to original property
        }

        $whereclause = '';
        if(is_array($where) && count($where) > 0) {
            foreach($where as $part) {
                // TODO: support pre- and post-parts here too ? (cfr. bug 3090)
                $whereclause .= $part['join'] . ' ' . $part['property']->source . ' ' . $part['clause'] . ' ';
            }
        } elseif(is_string($where)) {
            $whereclause = $where;
        }

        $this->join[] = [
            'table'  => $table,
            'key'    => $key,
            'fields' => $fieldlist,
            'where'  => $whereclause,
            'andor'  => $andor,
            'more'   => $more,
        ];
    }

    /**
     * Remove all join criteria for this data store (for getItems)
     * @return void
     */
    public function cleanJoin()
    {
        $this->join = [];
    }

    /**
     * Database functions for lazy connection
     * @return void
     */
    public function connect()
    {
        // Note: the only reason we keep this variable is for getLastId()
        if (empty($this->db)) {
            $this->db = xarDB::getConn();
        }
    }

    /**
     * Summary of getTable
     * @param mixed $name
     * @return mixed
     */
    public function getTable($name)
    {
        $tables = xarDB::getTables();
        if (!empty($tables[$name])) {
            return $tables[$name];
        }
    }

    /**
     * Summary of getType
     * @return mixed
     */
    public function getType()
    {
        return xarDB::getType();
    }

    /**
     * Summary of prepareStatement
     * @param mixed $sql
     * @return mixed
     */
    public function prepareStatement($sql)
    {
        $this->connect();
        return $this->db->prepareStatement($sql);
    }

    /**
     * Summary of getLastId
     * @param mixed $table
     * @return mixed
     */
    public function getLastId($table)
    {
        $this->connect();
        return $this->db->getLastId($table);
    }

    /**
     * Summary of getDatabaseInfo
     * @return mixed
     */
    public function getDatabaseInfo()
    {
        $this->connect();
        return $this->db->getDatabaseInfo();
    }
}
