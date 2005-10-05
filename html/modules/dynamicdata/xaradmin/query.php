<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
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

    if(!xarVarFetch('groupby', 'isset', $groupby, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('operation', 'isset', $operation, NULL, XARVAR_DONT_SET)) {return;}

    if(!xarVarFetch('cache', 'int', $cache, 0, XARVAR_DONT_SET)) {return;}

    $reset = false;
    // changed selected object
    if ($itemid != $olditemid) {
        $table = '';
        $oldtable = '';
        $query = '';
        $oldquery = '';
        $newquery = '';
        if (!empty($oldjoin)) {
            $join = '';
        }
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
        $olditemid = 0;
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
        $groupby = 0;
        $operation = array();
        $cache = 0;
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
                $join = $queryvars['join'];
                $oldjoin = $queryvars['oldjoin'];
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

    $dbconn =& xarDBGetConn();
    $data['table'] = $table;
    $data['oldtable'] = $table;
    $data['tables'] = $dbconn->MetaTables();

    $data['join'] = $join;
    $data['oldjoin'] = $join;
    $data['jointables'] = '';

    if (!empty($itemid)) {
        $data['object'] = & Dynamic_Object_Master::getObjectList(array('objectid' => $itemid,
                                                        'join' => $join));
        if (isset($data['object']) && !empty($data['object']->objectid)) {
            $data['itemid'] = $data['object']->objectid;
            $data['label'] = $data['object']->label;
            if (!empty($join) || empty($data['object']->primary)) {
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
            }
            $data['properties'] =& $data['object']->properties;
        } else {
            return;
        }
    } elseif (!empty($table)) {
        $data['object'] = & Dynamic_Object_Master::getObjectList(array('table' => $table));
        if (!isset($data['object'])) return;
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
    $grouped = array();
    foreach ($data['field'] as $name => $val) {
        if (empty($val)) continue;
        if (empty($groupby)) {
            $fieldlist[] = $name;
        } else {
            // when grouped, any field *must* be either used in some function or grouped by
            if (empty($operation[$name])) {
                // if not, we unselect it :-)
                $data['field'][$name] = 0;
                continue;
            }
        // CHECKME: find equivalents for other databases if necessary
            switch ($operation[$name]) {
                case 1:
                case 2:
                case 3:
                    $grouped[$operation[$name]] = $name;
                    $fieldlist[] = $name;
                    break;
                case 'count':
                    $fieldlist[] = "COUNT($name)";
                    break;
                case 'min':
                    $fieldlist[] = "MIN($name)";
                    break;
                case 'max':
                    $fieldlist[] = "MAX($name)";
                    break;
                case 'avg':
                    $fieldlist[] = "AVG($name)";
                    break;
                case 'sum':
                    $fieldlist[] = "SUM($name)";
                    break;
                default:
                    break;
            }
        }
    }
    if (count($grouped) > 0) {
        ksort($grouped);
        $grouplist = array_values($grouped);
    } else {
        $grouplist = null;
    }

    $dbconn =& xarDBGetConn();

// TODO: clean up passing of where clauses
    $whereclause = '';
    $and = '';
    if (!empty($where) && count($where) > 0 && !empty($value) && count($value) > 0) {
        foreach ($where as $name => $what) {
            if (empty($what)) continue;
            if (!isset($value[$name])) continue;
            
            $whereclause .= $and . $name;
            switch($what) {
                case 'like':
                    $whereclause .=  " LIKE " . $dbconn->qstr("%" . $value[$name] . "%");
                    break;
                case 'start':
                    $whereclause .=  " LIKE " . $dbconn->qstr($value[$name] . "%");
                    break;
                case 'end':
                    $whereclause .=  " LIKE " . $dbconn->qstr("%" . $value[$name]);
                    break;
                case 'in':
                    $list = preg_split('/\s*,\s*/',$value[$name]);
                    $newlist = array();
                    foreach ($list as $part) {
                        // try to get around problem of leading 0's
                        if (is_numeric($part) && strlen($part) == strlen((float)$part)) {
                            $newlist[] = $part;
                        } else {
                            $part = preg_replace('/^\'/','',$part);
                            $part = preg_replace('/\'$/','',$part);
                            $newlist[] = $dbconn->qstr($part);
                        }
                    }
                    $joined = join(', ',$newlist);
                    $whereclause .=  " IN (" . $joined . ")";
                    break;
                default:
                    // try to get around problem of leading 0's
                    if (is_numeric($value[$name]) && strlen($value[$name]) == strlen((float)$value[$name])) {
                        $whereclause .=  " $what " . $value[$name];
                    } else {
                        $whereclause .=  " $what " . $dbconn->qstr($value[$name]);
                    }
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

    $data['groupby'] = $groupby;
    $data['operation'] = $operation;
    $data['cache'] = $cache;

// TODO: add extra support
//    if (!empty($groupby)) {
//        array_push($data['properties'], array('_extra_' => null));
//    }

    // TODO: clean up generation of dummy object
    if ( !empty($fieldlist) && count($fieldlist) > 0 &&
         ((!empty($itemid) && $itemid == $olditemid) ||
         (!empty($table) && $table == $oldtable)) ) {
        $data['object']->getItems(array('fieldlist' => $fieldlist,
                                        'where' => $whereclause,
                                        'groupby' => $grouplist,
                                        'sort' => $sortlist,
                                        'cache' => $cache,
                                        'numitems' => $numitems,
                                        'startnum' => $startnum));
        $data['mylist'] =& $data['object'];
        if (empty($newquery)) {
            $newquery = xarML('Last Query');
        }
        if (!empty($table)) {
            $data['sample'] = '&lt;xar:data-view table="' . $table . '" '; 
        } else {
            $modinfo = xarModGetInfo($data['object']->moduleid);
            $modname = $modinfo['name'];
            $data['sample'] = '&lt;xar:data-view module="' . $modname . '" itemtype="' . $data['object']->itemtype . '" ';
            if (!empty($join)) {
                $data['sample'] .= 'join="' . $join . '" ';
            }
        }
        if (!empty($fieldlist)) {
            $data['sample'] .= 'fieldlist="' . xarVarPrepForDisplay(join(',',$fieldlist)) . '" ';
        }
        if (!empty($whereclause)) {
            $data['sample'] .= 'where="' . xarVarPrepForDisplay(addslashes($whereclause)) . '" ';
        }
        if (!empty($grouplist) && count($grouplist) > 0) {
            $data['sample'] .= 'groupby="' . xarVarPrepForDisplay(join(',',$grouplist)) . '" ';
        }
        if (!empty($sortlist) && count($sortlist) > 0) {
            $data['sample'] .= 'sort="' . xarVarPrepForDisplay(join(',',$sortlist)) . '" ';
        }
        if (!empty($cache)) {
            $data['sample'] .= 'cache="' . xarVarPrepForDisplay($cache) . '" ';
        }
        $data['sample'] .= 'layout="list" ';
        $data['sample'] .= 'linkfield="N/A" ';
        $data['sample'] .= 'numitems="' . xarVarPrepForDisplay($numitems) . '" ';
        $data['sample'] .= 'startnum="' . xarVarPrepForDisplay($startnum) . '" ';
        $data['sample'] .= '/&gt;';
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
        $queryvars['groupby'] = $groupby;
        $queryvars['operation'] = $operation;
        $queryvars['cache'] = $cache;
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

    if (!empty($table)) {
        $data['viewlink'] = xarModURL('dynamicdata','admin','view',
                                      array('table' => $table));
    } elseif (!empty($itemid) && !empty($join)) {
        $data['viewlink'] = xarModURL('dynamicdata','admin','view',
                                      array('itemid' => $itemid,
                                            'join' => $join));
    } elseif (!empty($itemid)) {
        $data['viewlink'] = xarModURL('dynamicdata','admin','view',
                                      array('itemid' => $itemid));
    }
    $data['numfields'] = count($data['properties']);
    if (empty($data['numfields'])) {
        $data['numfields'] = 1;
    }
    if (empty($data['label'])) {
        $data['label'] = xarML('Dynamic Objects');
    }
    $data['whereoptions'] = array(
                                  array('id' => 'eq', 'name' => xarML('equal to')),
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
    $data['operationoptions'] = array(
                                 array('id' => '1', 'name' => xarML('group by #(1)',1)),
                                 array('id' => '2', 'name' => xarML('group by #(1)',2)),
                                 array('id' => '3', 'name' => xarML('group by #(1)',3)),
                                 array('id' => 'count', 'name' => xarML('count')),
                                 array('id' => 'min', 'name' => xarML('minimum')),
                                 array('id' => 'max', 'name' => xarML('maximum')),
                                 array('id' => 'avg', 'name' => xarML('average')),
                                 array('id' => 'sum', 'name' => xarML('sum')),
                                );
    $data['submit'] = xarML('Update Query');

    // Return the template variables defined in this function
    return $data;
}

?>
