<?php
/**
 * Data Store is the module variables // TODO: integrate module variable handling with DD
 *
 * @package dynamicdata
 * @subpackage datastores
 */

/**
 * Class to handle module variables datastores
 *
 * @package dynamicdata
 */
sys::import('xaraya.datastores.sql.flattable');
class ModuleVariablesDataStore extends FlatTableDataStore
{
    public $modname;

    function __construct($name=null)
    {
        // invoke the default constructor from our parent class
        parent::__construct($name);

        // keep track of the concerned module for module settings
        // TODO: the concerned module is currently hiding in the third part of the data store name :)
        $namepart = explode('_',$name);
		if (empty($namepart[2])) $namepart[2] = 'dynamicdata';
		$this->modname = $namepart[2];
    }

    function getItem(Array $args = array())
    {
		$itemid = !empty($args['itemid']) ? $args['itemid'] : 0;

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
            // get the value from the module variables
            // TODO: use $field.$itemid for modules with several itemtypes ? [like articles :)]
            $namepart = explode('_',$field);
            $value = unserialize(xarModItemVars::get($this->modname,$namepart[0],$itemid));
            // set the value for this property
			$this->fields[$field]->value = $value;
        }
        return $itemid;
    }

    function createItem(Array $args = array())
    {
        // There's no difference with updateItem() here, because xarModItemVars:set() handles that
        return $this->updateItem($args);
    }

    function updateItem(Array $args = array())
    {
		$itemid = !empty($args['itemid']) ? $args['itemid'] : 0;

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->getValue();
            // skip fields where values aren't set
            if (!isset($value)) {
                continue;
            }
            $namepart = explode('_',$field);
            xarModItemVars::set($this->modname,$namepart[0],serialize($value),$itemid);
        }
        return $itemid;
    }

    function deleteItem(Array $args = array())
    {
		$itemid = !empty($args['itemid']) ? $args['itemid'] : 0;

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
			$namepart = explode('_',$field);
            xarModItemVars::delete($this->modname,$namepart[0],$itemid);
        }

        return $itemid;
    }

    function getItems(Array $args = array())
    {
        if (!empty($args['numitems'])) {
            $numitems = $args['numitems'];
        } else {
            $numitems = 0;
        }
        if (!empty($args['startnum'])) {
            $startnum = $args['startnum'];
        } else {
            $startnum = 1;
        }
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
        } elseif (isset($this->_itemids)) {
            $itemids = $this->_itemids;
        } else {
            $itemids = array();
            return true;
        }
        // check if it's set here - could be 0 (= empty) too
        if (isset($args['cache'])) {
            $this->cache = $args['cache'];
        }

        $table = $this->name;
        $itemidfield = $this->primary;

        if (empty($itemidfield)) {
            $itemidfield = $this->getPrimary();
            // can't really do much without the item id field at the moment
            if (empty($itemidfield)) {
                return;
            }
        }

        $tables = array($table);
        $more = '';

        // join with another table
        if (count($this->join) > 0) {
            $keys = array();
            $where = array();
            $andor = 'AND';
            foreach ($this->join as $info) {
                $tables[] = $info['table'];
                foreach ($info['fields'] as $field) {
                    $this->fields[$field] =& $this->extra[$field];
                }
                if (!empty($info['key'])) {
                    $keys[] = $info['key'] . ' = ' . $itemidfield;
                }
                if (!empty($info['where'])) {
                    $where[] = '(' . $info['where'] . ')';
                }
                if (!empty($info['andor'])) {
                    $andor = $info['andor'];
                }
                if (!empty($info['more'])) {
                    $more .= ' ' . $info['more'];
                }
                // TODO: sort clauses for the joined table ?
            }
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        // check if we're dealing with GROUP BY fields and/or COUNT, SUM etc. operations
        $isgrouped = 0;
        if (count($this->groupby) > 0) {
            $isgrouped = 1;
        }
        $newfields = array();
        foreach ($fieldlist as $field) {
            if (!empty($this->fields[$field]->operation)) {
                $newfields[] = $this->fields[$field]->operation . '(' . $field . ') AS ' . $this->fields[$field]->operation . '_' . $this->fields[$field]->name;
                $isgrouped = 1;
            } else {
                $newfields[] = $field;
            }
        }

        /*
        // CHECKME: test working without the item id field
        if (empty($itemidfield)) {
            $isgrouped = 1;
        }
        */
        if ($isgrouped) {
            $query = "SELECT " . join(', ', $newfields) . "
                        FROM " . join(', ', $tables) . $more . " ";
        } else {
            // Note: Oracle doesn't like having the same field in a sub-query twice,
            //       so we use an alias for the primary field here
            $query = "SELECT DISTINCT $itemidfield AS ddprimaryid, " . join(', ', $fieldlist) .
                        " FROM " . join(', ', $tables) . $more . " ";
        }

        $next = 'WHERE';
        if (count($this->join) > 0) {
            if (count($keys) > 0) {
                $query .= " $next " . join(' AND ', $keys);
                $next = 'AND';
            }
            if (count($where) > 0) {
                $query .= " $next ( " . join(' AND ', $where);
                $next = $andor;
            }
        }

        $bindvars = array();
        if (count($itemids) > 1) {
            $bindmarkers = '?' . str_repeat(',?',count($itemids)-1);
            $query .= " $next $itemidfield IN ($bindmarkers) ";
            foreach ($itemids as $itemid) {
                $bindvars[] = (int) $itemid;
            }
        } elseif (count($itemids) == 1) {
            $query .= " $next $itemidfield = ? ";
            $bindvars[] = (int)$itemids[0];
        } elseif (count($this->where) > 0) {
            $query .= " $next ";
            foreach ($this->where as $whereitem) {
                $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
            }
        }
        if (count($this->join) > 0 && count($where) > 0) {
            $query .= " ) ";
        }

        if (count($this->groupby) > 0) {
            $query .= " GROUP BY " . join(', ', $this->groupby);
        }

        if (count($this->sort) > 0) {
            $query .= " ORDER BY ";
            $join = '';
            foreach ($this->sort as $sortitem) {
                if (empty($this->fields[$sortitem['field']]->operation)) {
                    $query .= $join . $sortitem['field'] . ' ' . $sortitem['sortorder'];
                } else {
                    $query .= $join . $this->fields[$sortitem['field']]->operation . '_' . $this->fields[$sortitem['field']]->name . ' ' . $sortitem['sortorder'];
                }
                $join = ', ';
            }
        } elseif (!$isgrouped) {
            $query .= " ORDER BY ddprimaryid";
        }

        // We got the query, prepare it
        $stmt = $this->db->prepareStatement($query);
echo $query;exit;
        if ($numitems > 0) {
            $stmt->setLimit($numitems);
            $stmt->setOffset($startnum - 1);
        }
        $result = $stmt->executeQuery($bindvars);

        if (count($itemids) == 0 && !$isgrouped) {
            $saveids = 1;
        } else {
            $saveids = 0;
        }
        $itemid = 0;
        while ($result->next()) {
            $values = $result->getRow();
            if ($isgrouped) {
                $itemid++;
            } else {
                $itemid = array_shift($values);
            }
            // oops, something went seriously wrong here...
            if (empty($itemid) || count($values) != count($fieldlist)) {
                continue;
            }

            // add this itemid to the list
            if ($saveids) {
                $this->_itemids[] = $itemid;
            }

            foreach ($fieldlist as $field) {
                // add the item to the value list for this property
                $this->fields[$field]->setItemValue($itemid,array_shift($values));
            }
        }
        $result->close();
    }

    function countItems(Array $args = array())
    {
        // TODO: not supported by xarMod*Var
        return 0;
    }

}

?>
