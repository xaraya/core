<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminGui;
use Xaraya\Modules\DynamicData\UtilApi;
use DataPropertyMaster;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata admin query function
 * @extends MethodClass<AdminGui>
 */
class QueryMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * query items
     * @return array|void data for the template display
     * @see AdminGui::query()
     */
    public function __invoke(array $args = [])
    {
        /** @var UtilApi $utilapi */
        $utilapi = $this->utilapi();
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        extract($args);

        $this->var()->check('query', $query, 'str', '');
        $this->var()->check('oldquery', $oldquery, 'str', '');
        $this->var()->check('newquery', $newquery, 'str', '');
        $this->var()->check('table', $table, 'str', '');
        $this->var()->check('oldtable', $oldtable, 'str', '');
        $this->var()->check('itemid', $itemid, 'int', 0);
        $this->var()->check('olditemid', $olditemid, 'int', 0);
        $this->var()->check('join', $join, 'str', '');
        $this->var()->check('oldjoin', $oldjoin, 'str', '');

        $this->var()->check('field', $field);
        $this->var()->check('where', $where);
        $this->var()->check('value', $value);
        $this->var()->check('sort', $sort);
        $this->var()->check('numitems', $numitems);
        $this->var()->check('startnum', $startnum);

        $this->var()->check('groupby', $groupby);
        $this->var()->check('operation', $operation);

        $this->var()->check('cache', $cache, 'int', 0);

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
            $query = $this->session()->getVar('DynamicData.LastQuery');
            if (!empty($query)) {
                $newquery = $query;
                $startpager = $startnum;
                $reset = true;
            }
            // used the header sort, so we retrieve the current query from session variables
        } elseif (!empty($sort) && is_string($sort)
                  && empty($itemid) && empty($table) && empty($query)) {
            $query = $this->session()->getVar('DynamicData.LastQuery');
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
            $queryinfo = $this->mod()->getVar('query.' . $query);
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
        $querylist = $this->mod()->getVar('querylist');
        if (!empty($querylist)) {
            $data['queries'] = unserialize($querylist);
        } else {
            $data['queries'] = [];
        }

        $data['itemid'] = $itemid;
        $data['olditemid'] = $itemid;
        $data['objects'] = $this->data()->getObjects();

        $dbconn = $this->db()->getConn();
        $data['table'] = $table;
        $data['oldtable'] = $table;
        $data['tables'] = $dbconn->MetaTables();

        $data['join'] = $join;
        $data['oldjoin'] = $join;
        $data['jointables'] = '';

        if (!empty($itemid)) {
            $data['object'] = $this->data()->getObjectList(
                ['objectid' => $itemid,
                    'join' => $join]
            );
            if (isset($data['object']) && !empty($data['object']->objectid)) {
                $data['itemid'] = $data['object']->objectid;
                $data['label'] = $data['object']->label;
                if (!empty($join) || empty($data['object']->primary)) {
                    // (try to) show the "static" properties, corresponding to fields in dedicated
                    // tables for this module
                    $static = $utilapi->getstatic(['module_id' => $data['object']->moduleid,
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
            $data['object'] = $this->data()->getObjectList(
                ['table' => $table]
            );
            if (!isset($data['object'])) {
                return;
            }
            $data['label'] = $this->ml('Table #(1)', $table);
            $data['properties'] = & $data['object']->properties;
        } else {
            $data['label'] = $this->ml('Dynamic Objects or Database Tables');
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

        $dbconn = $this->db()->getConn();

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
                switch ($what) {
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
                            if (is_numeric($part) && strlen($part) == strlen(strval((float) $part))) {
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
                        if (is_numeric($value[$name]) && strlen($value[$name]) == strlen(strval((float) $value[$name]))) {
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
            $data['object']->getItems([
                'fieldlist' => $fieldlist,
                'where' => $whereclause,
                'groupby' => $grouplist,
                'sort' => $sortlist,
                'cache' => $cache,
                'numitems' => $numitems,
                'startnum' => $startnum,
            ]);
            $data['mylist'] = & $data['object'];
            if (empty($newquery)) {
                $newquery = $this->ml('Last Query');
            }
            if (!empty($table)) {
                $data['sample'] = '&lt;xar:data-view table="' . $table . '" ';
            } else {
                $modinfo = $this->mod()->getInfo($data['object']->moduleid);
                $modname = $modinfo['name'];
                $data['sample'] = '&lt;xar:data-view module="' . $modname . '" itemtype="' . $data['object']->itemtype . '" ';
                if (!empty($join)) {
                    $data['sample'] .= 'join="' . $join . '" ';
                }
            }
            if (!empty($fieldlist)) {
                $data['sample'] .= 'fieldlist="' . $this->var()->prep(join(',', $fieldlist)) . '" ';
            }
            if (!empty($whereclause)) {
                $data['sample'] .= 'where="' . $this->var()->prep(addslashes($whereclause)) . '" ';
            }
            if (!empty($grouplist) && count($grouplist) > 0) {
                $data['sample'] .= 'groupby="' . $this->var()->prep(join(',', $grouplist)) . '" ';
            }
            if (!empty($sortlist) && count($sortlist) > 0) {
                $data['sample'] .= 'sort="' . $this->var()->prep(join(',', $sortlist)) . '" ';
            }
            if (!empty($cache)) {
                $data['sample'] .= 'cache="' . $this->var()->prep($cache) . '" ';
            }
            $data['sample'] .= 'layout="list" ';
            $data['sample'] .= 'linkfield="N/A" ';
            $data['sample'] .= 'numitems="' . $this->var()->prep($numitems) . '" ';
            $data['sample'] .= 'startnum="' . $this->var()->prep($startnum) . '" ';
            $data['sample'] .= '/&gt;';
            $this->session()->setVar('DynamicData.LastQuery', $newquery);
        } else {
            $this->session()->setVar('DynamicData.LastQuery', '');
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
                    $this->mod()->setVar('query.' . $dropquery, null);
                }
                $this->mod()->setVar('querylist', serialize($data['queries']));
            }
            $this->mod()->setVar('query.' . $newquery, serialize($queryvars));
            if (count($data['queries']) == 0 || !in_array($newquery, $data['queries'])) {
                array_unshift($data['queries'], $newquery);
                $this->mod()->setVar('querylist', serialize($data['queries']));
            }
            $data['query'] = $newquery;
            $data['oldquery'] = $newquery;
        } else {
            $data['newquery'] = '';
        }

        if (!empty($table)) {
            $data['viewlink'] = $this->mod()->getURL(
                'admin',
                'view',
                ['table' => $table]
            );
        } elseif (!empty($itemid) && !empty($join)) {
            $data['viewlink'] = $this->mod()->getURL(
                'admin',
                'view',
                ['itemid' => $itemid,
                    'join' => $join]
            );
        } elseif (!empty($itemid)) {
            $data['viewlink'] = $this->mod()->getURL(
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
            $data['label'] = $this->ml('Dynamic Objects');
        }
        $data['whereoptions'] = [
            ['id' => 'eq', 'name' => $this->ml('equal to')],
            ['id' => 'gt', 'name' => $this->ml('greater than')],
            ['id' => 'lt', 'name' => $this->ml('less than')],
            ['id' => 'ne', 'name' => $this->ml('not equal to')],
            ['id' => 'start', 'name' => $this->ml('starts with')],
            ['id' => 'end', 'name' => $this->ml('ends with')],
            ['id' => 'like', 'name' => $this->ml('contains')],
            ['id' => 'in', 'name' => $this->ml('in (...)')],
        ];
        $data['sortoptions'] = [
            ['id' => '1', 'name' => $this->ml('sort #(1) - up', 1)],
            ['id' => '-1', 'name' => $this->ml('sort #(1) - down', 1)],
            ['id' => '2', 'name' => $this->ml('sort #(1) - up', 2)],
            ['id' => '-2', 'name' => $this->ml('sort #(1) - down', 2)],
            ['id' => '3', 'name' => $this->ml('sort #(1) - up', 3)],
            ['id' => '-3', 'name' => $this->ml('sort #(1) - down', 3)],
        ];
        $data['operationoptions'] = [
            ['id' => '1', 'name' => $this->ml('group by #(1)', 1)],
            ['id' => '2', 'name' => $this->ml('group by #(1)', 2)],
            ['id' => '3', 'name' => $this->ml('group by #(1)', 3)],
            ['id' => 'count', 'name' => $this->ml('count')],
            ['id' => 'min', 'name' => $this->ml('minimum')],
            ['id' => 'max', 'name' => $this->ml('maximum')],
            ['id' => 'avg', 'name' => $this->ml('average')],
            ['id' => 'sum', 'name' => $this->ml('sum')],
        ];
        $data['submit'] = $this->ml('Update Query');

        // Return the template variables defined in this function
        return $data;
    }
}
