<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * query items
 * @return array|void data for the template display
 */
function dynamicdata_admin_query(array $args = [])
{
    // Security
    if(!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    extract($args);

    if(!xarVar::fetch('query', 'str', $query, '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if(!xarVar::fetch('oldquery', 'str', $oldquery, '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if(!xarVar::fetch('newquery', 'str', $newquery, '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if(!xarVar::fetch('table', 'str', $table, '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if(!xarVar::fetch('oldtable', 'str', $oldtable, '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if(!xarVar::fetch('itemid', 'int', $itemid, 0, xarVar::NOT_REQUIRED)) {
        return;
    }
    if(!xarVar::fetch('olditemid', 'int', $olditemid, 0, xarVar::NOT_REQUIRED)) {
        return;
    }
    if(!xarVar::fetch('join', 'str', $join, '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if(!xarVar::fetch('oldjoin', 'str', $oldjoin, '', xarVar::NOT_REQUIRED)) {
        return;
    }

    if(!xarVar::fetch('field', 'isset', $field, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('where', 'isset', $where, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('value', 'isset', $value, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('sort', 'isset', $sort, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('numitems', 'isset', $numitems, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('startnum', 'isset', $startnum, null, xarVar::DONT_SET)) {
        return;
    }

    if(!xarVar::fetch('groupby', 'isset', $groupby, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('operation', 'isset', $operation, null, xarVar::DONT_SET)) {
        return;
    }

    if(!xarVar::fetch('cache', 'int', $cache, 0, xarVar::DONT_SET)) {
        return;
    }

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
        $query = xarSession::getVar('DynamicData.LastQuery');
        if (!empty($query)) {
            $newquery = $query;
            $startpager = $startnum;
            $reset = true;
        }
        // used the header sort, so we retrieve the current query from session variables
    } elseif (!empty($sort) && is_string($sort)
              && empty($itemid) && empty($table) && empty($query)) {
        $query = xarSession::getVar('DynamicData.LastQuery');
        if (!empty($query)) {
            $newquery = $query;
            $sorthead = $sort;
            $reset = true;
        }
    }

    if ($reset) {
        $field = [];
        $where = [];
        $value = [];
        $sort = [];
        $numitems = 20;
        $startnum = 1;
        $groupby = 0;
        $operation = [];
        $cache = 0;
    }

    if (!empty($query) && $query == $newquery) {
        $queryinfo = xarModVars::get('dynamicdata', 'query.'.$query);
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

    $data = [];
    $data['query'] = $query;
    $data['oldquery'] = $query;
    $querylist = xarModVars::get('dynamicdata', 'querylist');
    if (!empty($querylist)) {
        $data['queries'] = unserialize($querylist);
    } else {
        $data['queries'] = [];
    }

    $data['itemid'] = $itemid;
    $data['olditemid'] = $itemid;
    $data['objects'] = DataObjectMaster::getObjects();

    $dbconn = xarDB::getConn();
    $data['table'] = $table;
    $data['oldtable'] = $table;
    $data['tables'] = $dbconn->MetaTables();

    $data['join'] = $join;
    $data['oldjoin'] = $join;
    $data['jointables'] = '';

    if (!empty($itemid)) {
        $data['object'] = DataObjectMaster::getObjectList(['objectid' => $itemid,
                                                        'join' => $join]);
        if (isset($data['object']) && !empty($data['object']->objectid)) {
            $data['itemid'] = $data['object']->objectid;
            $data['label'] = $data['object']->label;
            if (!empty($join) || empty($data['object']->primary)) {
                // (try to) show the "static" properties, corresponding to fields in dedicated
                // tables for this module
                $static = xarMod::apiFunc(
                    'dynamicdata',
                    'util',
                    'getstatic',
                    ['module_id' => $data['object']->moduleid,
                                              'itemtype' => $data['object']->itemtype]
                );
                $data['jointables'] = [];
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
            $data['properties'] = & $data['object']->properties;
        } else {
            return;
        }
    } elseif (!empty($table)) {
        $data['object'] = DataObjectMaster::getObjectList(['table' => $table]);
        if (!isset($data['object'])) {
            return;
        }
        $data['label'] = xarML('Table #(1)', $table);
        $data['properties'] = & $data['object']->properties;
    } else {
        $data['label'] = xarML('Dynamic Objects or Database Tables');
        $data['properties'] = [];
    }

    // Allow all properties that are not disabled
    $data['field'] = [];
    if (empty($field) || count($field) == 0) {
        foreach (array_keys($data['properties']) as $name) {
            $status = $data['properties'][$name]->getDisplayStatus();
            if ($status != DataPropertyMaster::DD_DISPLAYSTATE_DISABLED) {
                $data['field'][$name] = 1;
            }
        }
    } else {
        $data['field'] = $field;
    }
    $data['where'] = $where;
    $data['value'] = $value;
    if (!empty($sorthead) && isset($data['properties'][$sorthead])) {
        $sort = [];
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

    $fieldlist = [];
    $grouped = [];
    foreach ($data['field'] as $name => $val) {
        if (empty($val)) {
            continue;
        }
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

    $dbconn = xarDB::getConn();

    // TODO: clean up passing of where clauses
    $whereclause = '';
    $and = '';
    if (!empty($where) && count($where) > 0 && !empty($value) && count($value) > 0) {
        foreach ($where as $name => $what) {
            if (empty($what)) {
                continue;
            }
            if (!isset($value[$name])) {
                continue;
            }

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
                    $list = preg_split('/\s*,\s*/', $value[$name]);
                    $newlist = [];
                    foreach ($list as $part) {
                        // try to get around problem of leading 0's
                        if (is_numeric($part) && strlen($part) == strlen((float)$part)) {
                            $newlist[] = $part;
                        } else {
                            $part = preg_replace('/^\'/', '', $part);
                            $part = preg_replace('/\'$/', '', $part);
                            $newlist[] = $dbconn->qstr($part);
                        }
                    }
                    $joined = join(', ', $newlist);
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

    $sorted = [];
    if (!empty($sort) && count($sort) > 0) {
        foreach ($sort as $name => $what) {
            if (empty($what)) {
                continue;
            }
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
    if (!empty($fieldlist) && count($fieldlist) > 0 &&
         ((!empty($itemid) && $itemid == $olditemid) ||
         (!empty($table) && $table == $oldtable))) {
        $data['object']->getItems(['fieldlist' => $fieldlist,
                                        'where' => $whereclause,
                                        'groupby' => $grouplist,
                                        'sort' => $sortlist,
                                        'cache' => $cache,
                                        'numitems' => $numitems,
                                        'startnum' => $startnum]);
        $data['mylist'] = & $data['object'];
        if (empty($newquery)) {
            $newquery = xarML('Last Query');
        }
        if (!empty($table)) {
            $data['sample'] = '&lt;xar:data-view table="' . $table . '" ';
        } else {
            $modinfo = xarMod::getInfo($data['object']->moduleid);
            $modname = $modinfo['name'];
            $data['sample'] = '&lt;xar:data-view module="' . $modname . '" itemtype="' . $data['object']->itemtype . '" ';
            if (!empty($join)) {
                $data['sample'] .= 'join="' . $join . '" ';
            }
        }
        if (!empty($fieldlist)) {
            $data['sample'] .= 'fieldlist="' . xarVar::prepForDisplay(join(',', $fieldlist)) . '" ';
        }
        if (!empty($whereclause)) {
            $data['sample'] .= 'where="' . xarVar::prepForDisplay(addslashes($whereclause)) . '" ';
        }
        if (!empty($grouplist) && count($grouplist) > 0) {
            $data['sample'] .= 'groupby="' . xarVar::prepForDisplay(join(',', $grouplist)) . '" ';
        }
        if (!empty($sortlist) && count($sortlist) > 0) {
            $data['sample'] .= 'sort="' . xarVar::prepForDisplay(join(',', $sortlist)) . '" ';
        }
        if (!empty($cache)) {
            $data['sample'] .= 'cache="' . xarVar::prepForDisplay($cache) . '" ';
        }
        $data['sample'] .= 'layout="list" ';
        $data['sample'] .= 'linkfield="N/A" ';
        $data['sample'] .= 'numitems="' . xarVar::prepForDisplay($numitems) . '" ';
        $data['sample'] .= 'startnum="' . xarVar::prepForDisplay($startnum) . '" ';
        $data['sample'] .= '/&gt;';
        xarSession::setVar('DynamicData.LastQuery', $newquery);
    } else {
        xarSession::setVar('DynamicData.LastQuery', '');
    }

    if (!empty($newquery)) {
        $data['newquery'] = $newquery;
        $queryvars = [];
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
                xarModVars::delete('dynamicdata', 'query.'.$dropquery);
            }
            xarModVars::set('dynamicdata', 'querylist', serialize($data['queries']));
        }
        xarModVars::set('dynamicdata', 'query.'.$newquery, serialize($queryvars));
        if (count($data['queries']) == 0 || !in_array($newquery, $data['queries'])) {
            array_unshift($data['queries'], $newquery);
            xarModVars::set('dynamicdata', 'querylist', serialize($data['queries']));
        }
        $data['query'] = $newquery;
        $data['oldquery'] = $newquery;
    } else {
        $data['newquery'] = '';
    }

    if (!empty($table)) {
        $data['viewlink'] = xarController::URL(
            'dynamicdata',
            'admin',
            'view',
            ['table' => $table]
        );
    } elseif (!empty($itemid) && !empty($join)) {
        $data['viewlink'] = xarController::URL(
            'dynamicdata',
            'admin',
            'view',
            ['itemid' => $itemid,
                                            'join' => $join]
        );
    } elseif (!empty($itemid)) {
        $data['viewlink'] = xarController::URL(
            'dynamicdata',
            'admin',
            'view',
            ['itemid' => $itemid]
        );
    }
    $data['numfields'] = count($data['properties']);
    if (empty($data['numfields'])) {
        $data['numfields'] = 1;
    }
    if (empty($data['label'])) {
        $data['label'] = xarML('Dynamic Objects');
    }
    $data['whereoptions'] = [
                                  ['id' => 'eq', 'name' => xarML('equal to')],
                                  ['id' => 'gt', 'name' => xarML('greater than')],
                                  ['id' => 'lt', 'name' => xarML('less than')],
                                  ['id' => 'ne', 'name' => xarML('not equal to')],
                                  ['id' => 'start', 'name' => xarML('starts with')],
                                  ['id' => 'end', 'name' => xarML('ends with')],
                                  ['id' => 'like', 'name' => xarML('contains')],
                                  ['id' => 'in', 'name' => xarML('in (...)')],
                                 ];
    $data['sortoptions'] = [
                                 ['id' => '1', 'name' => xarML('sort #(1) - up', 1)],
                                 ['id' => '-1', 'name' => xarML('sort #(1) - down', 1)],
                                 ['id' => '2', 'name' => xarML('sort #(1) - up', 2)],
                                 ['id' => '-2', 'name' => xarML('sort #(1) - down', 2)],
                                 ['id' => '3', 'name' => xarML('sort #(1) - up', 3)],
                                 ['id' => '-3', 'name' => xarML('sort #(1) - down', 3)],
                                ];
    $data['operationoptions'] = [
                                 ['id' => '1', 'name' => xarML('group by #(1)', 1)],
                                 ['id' => '2', 'name' => xarML('group by #(1)', 2)],
                                 ['id' => '3', 'name' => xarML('group by #(1)', 3)],
                                 ['id' => 'count', 'name' => xarML('count')],
                                 ['id' => 'min', 'name' => xarML('minimum')],
                                 ['id' => 'max', 'name' => xarML('maximum')],
                                 ['id' => 'avg', 'name' => xarML('average')],
                                 ['id' => 'sum', 'name' => xarML('sum')],
                                ];
    $data['submit'] = xarML('Update Query');

    // Return the template variables defined in this function
    return $data;
}
