<?php

/**
 * query items
 */
function dynamicdata_admin_query($args)
{
    // Security Check
    if(!xarSecurityCheck('AdminDynamicData')) return;

    extract($args);

    if(!xarVarFetch('query', 'str', $query, '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('oldquery', 'str', $oldquery, '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('newquery', 'str', $newquery, '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('table', 'str', $table, '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('oldtable', 'str', $oldtable, '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemid', 'int', $itemid, 0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('olditemid', 'int', $olditemid, 0, XARVAR_NOT_REQUIRED)) {return;}

    if(!xarVarFetch('field', 'isset', $field, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('where', 'isset', $where, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('value', 'isset', $value, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('sort', 'isset', $sort, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('numitems', 'isset', $numitems, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('startnum', 'isset', $startnum, NULL, XARVAR_DONT_SET)) {return;}

    // changed selected object
    if ($itemid != $olditemid) {
        $table = '';
        $oldtable = '';
        $query = '';
        $oldquery = '';
        $newquery = '';
        $field = array();
        $where = array();
        $value = array();
        $sort = array();
        $numitems = 20;
        $startnum = 1;
    // changed selected table
    } elseif ($table != $oldtable) {
        $itemid = 0;
        $olditemid = 0;
        $query = '';
        $oldquery = '';
        $newquery = '';
        $field = array();
        $where = array();
        $value = array();
        $sort = array();
        $numitems = 20;
        $startnum = 1;
    // changed selected query
    } elseif ($query != $oldquery) {
        $itemid = 0;
        $olditemid = 0;
        $table = '';
        $oldtable = '';
        $newquery = $query;
        $field = array();
        $where = array();
        $value = array();
        $sort = array();
        $numitems = 20;
        $startnum = 1;
    // used the pager, so we retrieve the current query from session variables
    } elseif (!empty($startnum) && is_numeric($startnum)
              && empty($itemid) && empty($table) && empty($query)) {
        $query = xarSessionGetVar('DynamicData.LastQuery');
        if (!empty($query)) {
            $newquery = $query;
        }
    // used the header sort, so we retrieve the current query from session variables
    } elseif (!empty($sort) && is_string($sort)
              && empty($itemid) && empty($table) && empty($query)) {
        $query = xarSessionGetVar('DynamicData.LastQuery');
        if (!empty($query)) {
            $newquery = $query;
            $sorthead = $sort;
            $sort = null;
        }
    }

    if (!empty($query) && $query == $newquery) {
        $queryinfo = xarModGetVar('dynamicdata','query.'.$query);
        if (!empty($queryinfo)) {
            $queryvars = unserialize($queryinfo);
            extract($queryvars);
        }
    }

    $data = array();
    $data['query'] = $query;
    $data['oldquery'] = $query;
    $querylist = xarModGetVar('dynamicdata','querylist');
    if (!empty($querylist)) {
        $data['queries'] = unserialize($querylist);
    } else {
        $data['queries'] = array();
    }

    $data['itemid'] = $itemid;
    $data['olditemid'] = $itemid;
    $data['objects'] = xarModAPIFunc('dynamicdata','user','getobjects');

    list($dbconn) = xarDBGetConn();
    $data['table'] = $table;
    $data['oldtable'] = $table;
    $data['tables'] = $dbconn->MetaTables();

    if (!empty($itemid)) {
        $data['object'] =& xarModAPIFunc('dynamicdata','user','getobject',
                                         array('objectid' => $itemid));
        if (isset($data['object']) && !empty($data['object']->objectid)) {
            $data['itemid'] = $data['object']->objectid;
            $data['label'] = $data['object']->label;
            $data['properties'] =& $data['object']->properties;
        } else {
            return;
        }
    } elseif (!empty($table)) {
        $meta = xarModAPIFunc('dynamicdata','util','getmeta',
                              array('table' => $table));
        if (!isset($meta) || !isset($meta[$table])) {
           return xarML('Invalid table');
        }
        $data['object'] =& xarModAPIFunc('dynamicdata','user','getobject',
                                         array('objectid' => -1, // dummy object
                                               'name' => $table));
        foreach ($meta[$table] as $name => $propinfo) {
            $data['object']->addProperty($propinfo);
        }
        $data['label'] = xarML('Table #(1)',$table);
        $data['properties'] =& $data['object']->properties;
    } else {
        $data['label'] = xarML('Dynamic Objects or Database Tables');
        $data['properties'] = array();
    }

    // select properties with status 1 by default
    $data['field'] = array();
    if (empty($field) || count($field) == 0) {
        foreach (array_keys($data['properties']) as $name) {
            if ($data['properties'][$name]->status == 1) {
                $data['field'][$name] = 1;
            }
        }
    } else {
        $data['field'] = $field;
    }
    $data['where'] = $where;
    $data['value'] = $value;
    if (!empty($sorthead) && isset($data['properties'][$sorthead])) {
        $sort = array();
        $sort[$sorthead] = 1;
    }
    $data['sort'] = $sort;
    if (empty($numitems)) {
        $numitems = 20;
    }
    if (empty($startnum)) {
        $startnum = 1;
    }
    $data['numitems'] = $numitems;
    $data['startnum'] = $startnum;

    $fieldlist = array();
    foreach ($data['field'] as $name => $val) {
        if (empty($val)) continue;
        $fieldlist[] = $name;
    }

// TODO: clean up passing of where clauses
    $whereclause = '';
    $join = '';
    if (!empty($where) && count($where) > 0 && !empty($value) && count($value) > 0) {
        foreach ($where as $name => $what) {
            if (empty($what)) continue;
            if (!isset($value[$name])) continue;
            if ($what == 'like') {
                $whereclause .= $join . $name . " LIKE '%" . xarVarPrepForStore($value[$name]) . "%'";
            } elseif ($what == 'start') {
                $whereclause .= $join . $name . " LIKE '" . xarVarPrepForStore($value[$name]) . "%'";
            } elseif ($what == 'end') {
                $whereclause .= $join . $name . " LIKE '%" . xarVarPrepForStore($value[$name]) . "'";
            } elseif ($what == 'in') {
                $whereclause .= $join . $name . " IN (" . xarVarPrepForStore($value[$name]) . ")";
            } else {
                $whereclause .= $join . $name . " $what '" . xarVarPrepForStore($value[$name]) . "'";
            }
            $join = ' and ';
        }
    }

    $sorted = array();
    if (!empty($sort) && count($sort) > 0) {
        foreach ($sort as $name => $what) {
            if (empty($what)) continue;
            $id = abs($what);
            $sorted[$id] = $name;
            if ($what < 0) {
                $sorted[$id] .= ' DESC';
            } else {
                $sorted[$id] .= ' ASC';
            }
        }
    }
    if (count($sorted) > 0) {
        ksort($sorted);
        $sortlist = array_values($sorted);
    } else {
        $sortlist = null;
    }

    if (!empty($itemid) && $itemid == $olditemid) {
        $mylist = new Dynamic_Object_List(array('objectid' => $itemid,
                                                'fieldlist' => $fieldlist,
                                                'where' => $whereclause,
                                                'sort' => $sortlist,
                                                'startnum' => $startnum,
                                                'numitems' => $numitems));
        $mylist->getItems();
        $data['mylist'] = & $mylist;
        if (empty($newquery)) {
            $newquery = xarML('Last Query');
        }
        xarSessionSetVar('DynamicData.LastQuery',$newquery);
    } elseif (!empty($table) && $table == $oldtable) {
    // TODO: clean up generation of dummy object
        $mylist = new Dynamic_Object_List(array('objectid' => -1, // dummy object
                                                'moduleid' => 182, // needed for showlist check
                                                'name' => $table,
                                                'startnum' => $startnum,
                                                'numitems' => $numitems));
        foreach ($meta[$table] as $name => $propinfo) {
            $mylist->addProperty($propinfo);
            $mylist->properties[$name]->items = & $mylist->items;
        }
        $mylist->fieldlist = $fieldlist;
        $mylist->getDataStores();
        $mylist->getItems(array('where' => $whereclause,
                                'sort' => $sortlist));
        $data['mylist'] = & $mylist;
        if (empty($newquery)) {
            $newquery = xarML('Last Query');
        }
        xarSessionSetVar('DynamicData.LastQuery',$newquery);
    } else {
        xarSessionSetVar('DynamicData.LastQuery','');
    }

    $data['numfields'] = count($data['properties']);
    if (empty($data['numfields'])) {
        $data['numfields'] = 1;
    }
    if (empty($data['label'])) {
        $data['label'] = xarML('Dynamic Objects');
    }
    $data['whereoptions'] = array(
                                  array('id' => 'eq', 'name' => xarML('equals')),
                                  array('id' => 'gt', 'name' => xarML('greater than')),
                                  array('id' => 'lt', 'name' => xarML('less than')),
                                  array('id' => 'ne', 'name' => xarML('not equal to')),
                                  array('id' => 'start', 'name' => xarML('starts with')),
                                  array('id' => 'end', 'name' => xarML('ends with')),
                                  array('id' => 'like', 'name' => xarML('contains')),
                                  array('id' => 'in', 'name' => xarML('in (...)')),
                                 );
    $data['sortoptions'] = array(
                                 array('id' => '1', 'name' => xarML('sort #(1) - up', 1)),
                                 array('id' => '-1', 'name' => xarML('sort #(1) - down', 1)),
                                 array('id' => '2', 'name' => xarML('sort #(1) - up', 2)),
                                 array('id' => '-2', 'name' => xarML('sort #(1) - down', 2)),
                                 array('id' => '3', 'name' => xarML('sort #(1) - up', 3)),
                                 array('id' => '-3', 'name' => xarML('sort #(1) - down', 3)),
                                );
    $data['submit'] = xarML('Update Query');

    if (!empty($newquery)) {
        $data['newquery'] = $newquery;
        $queryvars = array();
        $queryvars['itemid'] = $itemid;
        $queryvars['olditemid'] = $olditemid;
        $queryvars['table'] = $table;
        $queryvars['oldtable'] = $oldtable;
        $queryvars['query'] = $newquery;
        $queryvars['oldquery'] = $newquery;
        $queryvars['newquery'] = $newquery;
        $queryvars['field'] = $field;
        $queryvars['where'] = $where;
        $queryvars['value'] = $value;
        $queryvars['sort'] = $sort;
        $queryvars['numitems'] = $numitems;
    // don't save the start number here
    //   $queryvars['startnum'] = $startnum;
    // TODO: clean up query cleaning
        if (count($data['queries']) >= 20) {
            $dropquery = array_pop($data['queries']);
            if (!empty($dropquery)) {
                xarModDelVar('dynamicdata','query.'.$dropquery);
            }
            xarModSetVar('dynamicdata','querylist',serialize($data['queries']));
        }
        xarModSetVar('dynamicdata','query.'.$newquery, serialize($queryvars));
        if (count($data['queries']) == 0 || !in_array($newquery,$data['queries'])) {
            array_unshift($data['queries'],$newquery);
            xarModSetVar('dynamicdata','querylist',serialize($data['queries']));
        }
        $data['query'] = $newquery;
        $data['oldquery'] = $newquery;
    } else {
        $data['newquery'] = '';
    }

    // Return the template variables defined in this function
    return $data;
}

?>
