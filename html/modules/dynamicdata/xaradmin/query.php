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
    if(!xarVarFetch('join', 'str', $join, '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('oldjoin', 'str', $oldjoin, '', XARVAR_NOT_REQUIRED)) {return;}

    if(!xarVarFetch('field', 'isset', $field, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('where', 'isset', $where, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('value', 'isset', $value, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('sort', 'isset', $sort, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('numitems', 'isset', $numitems, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('startnum', 'isset', $startnum, NULL, XARVAR_DONT_SET)) {return;}

    $reset = false;
    // changed selected object
    if ($itemid != $olditemid) {
        $table = '';
        $oldtable = '';
        $query = '';
        $oldquery = '';
        $newquery = '';
        $join = '';
        $oldjoin = '';
        $reset = true;
    // changed selected table
    } elseif ($table != $oldtable) {
        $itemid = 0;
        $olditemid = 0;
        $query = '';
        $oldquery = '';
        $newquery = '';
        $join = '';
        $oldjoin = '';
        $reset = true;
    // changed selected query
    } elseif ($query != $oldquery) {
        $itemid = 0;
        $olditemid = 0;
        $table = '';
        $oldtable = '';
        $join = '';
        $oldjoin = '';
        $newquery = $query;
        $reset = true;
    // changed selected join table
    } elseif ($join != $oldjoin) {
        $table = '';
        $oldtable = '';
        $query = '';
        $oldquery = '';
        $newquery = '';
        $reset = true;
    // used the pager, so we retrieve the current query from session variables
    } elseif (!empty($startnum) && is_numeric($startnum)
              && empty($itemid) && empty($table) && empty($query)) {
        $query = xarSessionGetVar('DynamicData.LastQuery');
        if (!empty($query)) {
            $newquery = $query;
            $startpager = $startnum;
            $reset = true;
        }
    // used the header sort, so we retrieve the current query from session variables
    } elseif (!empty($sort) && is_string($sort)
              && empty($itemid) && empty($table) && empty($query)) {
        $query = xarSessionGetVar('DynamicData.LastQuery');
        if (!empty($query)) {
            $newquery = $query;
            $sorthead = $sort;
            $reset = true;
        }
    }

    if ($reset) {
        $field = array();
        $where = array();
        $value = array();
        $sort = array();
        $numitems = 20;
        $startnum = 1;
    }

    if (!empty($query) && $query == $newquery) {
        $queryinfo = xarModGetVar('dynamicdata','query.'.$query);
        if (!empty($queryinfo)) {
            $queryvars = unserialize($queryinfo);
            if ($reset) {
                extract($queryvars);
            } else {
                $itemid = $queryvars['itemid'];
                $olditemid = $queryvars['olditemid'];
                $table = $queryvars['table'];
                $oldtable = $queryvars['oldtable'];
                $query = $queryvars['query'];
                $oldquery = $queryvars['oldquery'];
            }
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

    $data['join'] = $join;
    $data['oldjoin'] = $join;
    $data['jointables'] = '';

    if (!empty($itemid)) {
        $data['object'] = new Dynamic_Object_List(array('objectid' => $itemid));
        if (isset($data['object']) && !empty($data['object']->objectid)) {
            $data['itemid'] = $data['object']->objectid;
            $data['label'] = $data['object']->label;
            if (empty($data['object']->primary)) {
            // (try to) show the "static" properties, corresponding to fields in dedicated
            // tables for this module
                $static = xarModAPIFunc('dynamicdata','util','getstatic',
                                        array('modid' => $data['object']->moduleid,
                                              'itemtype' => $data['object']->itemtype));
                $data['jointables'] = array();
                if (!empty($static)) {
                    $count = count($data['object']->properties);
                    foreach ($static as $name => $propinfo) {
                        if (preg_match('/^(\w+)\.(\w+)$/', $propinfo['source'], $matches)) {
                            $jointable = $matches[1];
                            $data['jointables'][$jointable] = $jointable;
                        }
                    }
                }
                if (!empty($join) && isset($data['jointables'][$join])) {
                    $meta = xarModAPIFunc('dynamicdata','util','getmeta',
                                          array('table' => $join));
                    if (!isset($meta) || !isset($meta[$join])) {
                        return xarML('Invalid table #(1)',xarVarPrepForDisplay($join));
                    }
                    $count = count($data['object']->properties);
                    foreach ($meta[$join] as $name => $propinfo) {
                        $data['object']->addProperty($propinfo);
                        $data['object']->properties[$name]->items = & $data['object']->items;
                    }
                    if (count($data['object']->properties) > $count) {
                        // put join properties in front
                        $joinprops = array_splice($data['object']->properties,$count);
                        $data['object']->properties = array_merge($joinprops,$data['object']->properties);
                    }
                }
            }
            $data['properties'] =& $data['object']->properties;
        } else {
            return;
        }
    } elseif (!empty($table)) {
        $meta = xarModAPIFunc('dynamicdata','util','getmeta',
                              array('table' => $table));
        if (!isset($meta) || !isset($meta[$table])) {
            return xarML('Invalid table #(1)',xarVarPrepForDisplay($table));
        }
        $data['object'] = new Dynamic_Object_List(array('objectid' => -1, // dummy object
                                                        'moduleid' => 182, // needed for showlist check in template
                                                        'name' => $table));
        foreach ($meta[$table] as $name => $propinfo) {
            $data['object']->addProperty($propinfo);
            $data['object']->properties[$name]->items = & $data['object']->items;
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
    if (!empty($startpager)) {
        $startnum = $startpager;
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
    $and = '';
    if (!empty($where) && count($where) > 0 && !empty($value) && count($value) > 0) {
        foreach ($where as $name => $what) {
            if (empty($what)) continue;
            if (!isset($value[$name])) continue;
            if ($what == 'like') {
                $whereclause .= $and . $name . " LIKE '%" . xarVarPrepForStore($value[$name]) . "%'";
            } elseif ($what == 'start') {
                $whereclause .= $and . $name . " LIKE '" . xarVarPrepForStore($value[$name]) . "%'";
            } elseif ($what == 'end') {
                $whereclause .= $and . $name . " LIKE '%" . xarVarPrepForStore($value[$name]) . "'";
            } elseif ($what == 'in') {
                $whereclause .= $and . $name . " IN (" . xarVarPrepForStore($value[$name]) . ")";
            } else {
                $whereclause .= $and . $name . " $what '" . xarVarPrepForStore($value[$name]) . "'";
            }
            $and = ' and ';
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

    // TODO: clean up generation of dummy object
    if ( (!empty($itemid) && $itemid == $olditemid) ||
         (!empty($table) && $table == $oldtable) ) {
        $data['object']->fieldlist = $fieldlist;
        $data['object']->startnum = $startnum;
        $data['object']->numitems = $numitems;
        // regenerate the data stores
        $data['object']->datastores = null;
        $data['object']->getDataStores();
        foreach (array_keys($data['object']->datastores) as $name) {
            $data['object']->datastores[$name]->itemids = & $data['object']->itemids;
        }
        $data['object']->getItems(array('where' => $whereclause,
                                        'sort' => $sortlist));
        $data['mylist'] =& $data['object'];
        if (empty($newquery)) {
            $newquery = xarML('Last Query');
        }
        xarSessionSetVar('DynamicData.LastQuery',$newquery);
    } else {
        xarSessionSetVar('DynamicData.LastQuery','');
    }

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
        $queryvars['join'] = $join;
        $queryvars['oldjoin'] = $oldjoin;
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

    // Return the template variables defined in this function
    return $data;
}

?>
