<?php
/**
 * Base class for Dynamic Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
**/
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
    function getFieldName(&$property)
    {
        return $property->name;
    }

    /**
     * Add a field to get/set in this data store, and its corresponding property
     */
    function addField(&$property)
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) 
            return;

        $this->fields[$name] = &$property; // use reference to original property

        if (!isset($this->primary) && $property->type == 21) 
            // Item ID
            $this->setPrimary($property);
    }

    /**
     * Set the primary key for this data store (only 1 allowed for now)
     */
    function setPrimary(&$property)
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) 
            return;

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
        if (!isset($name)) 
            return;

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
        if (!isset($name)) 
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
    function addGroupBy(&$property)
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) 
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
    function addJoin($table, $key, $fields, $where = array(), $andor = 'and', $more = '', $sort = array())
    {
        if (!isset($this->extra)) 
            $this->extra = array();

        $fieldlist = array();
        foreach (array_keys($fields) as $field) 
        {
            $source = $fields[$field]->source;
            // save the source for the query fieldlist
            $fieldlist[] = $source;
            // save the source => property pairs for returning the values
            $this->extra[$source] = & $fields[$field]; // use reference to original property
        }
        
        $whereclause = '';
        if (is_array($where) && count($where) > 0) 
        {
            foreach ($where as $part) 
            {
                // TODO: support pre- and post-parts here too ? (cfr. bug 3090)
                $whereclause .= $part['join'] . ' ' . $part['property']->source . ' ' . $part['clause'] . ' ';
            }
        } 
        elseif (is_string($where)) 
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