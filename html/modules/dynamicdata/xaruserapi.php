<?php
/**
 * File: $Id$
 *
 * Dynamic Data User API
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/

// ----------------------------------------------------------------------
// Generic item get() APIs
// ----------------------------------------------------------------------

/**
 * get all data fields (dynamic or static) for an item
 * (identified by module + item type + item id)
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields to get, or
 * @param $args['modid'] module id of the item fields to get
 * @param $args['itemtype'] item type of the item fields to get
 * @param $args['itemid'] item id of the item fields to get
 * @param $args['fieldlist'] array of field labels to retrieve (default is all)
 * @param $args['status'] limit to property fields of a certain status (e.g. active)
 * @param $args['static'] include the static properties (= module tables) too (default no)
 * @returns array
 * @return array of fields, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getitem($args)
{
    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    $modinfo = xarModGetInfo($modid);

    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid) || empty($modinfo['name'])) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getall', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_OVERVIEW)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    // check the optional field list
    if (empty($fieldlist)) {
        $fieldlist = null;
    }

    // limit to property fields of a certain status (e.g. active)
    if (!isset($status)) {
        $status = null;
    }

    // include the static properties (= module tables) too ?
    if (empty($static)) {
        $static = false;
    }

    // get all properties for this module / itemtype,
    // or only the properties mentioned in $fieldlist (in the right order, PHP willing)
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype,
                                 'fieldlist' => $fieldlist,
                                 'status' => $status,
                                 'static' => $static));
    if (empty($fields) || count($fields) == 0) {
        return array();
    }

    // different processing depending on the data source
    list($dynprops,$tables,$hooks,$functions,$itemidname) =
                          xarModAPIFunc('dynamicdata','user','splitfields',
                                                          // pass by reference
                                        array('fields' => &$fields));

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // retrieve properties for this item from the dynamic_data table
    if (count($dynprops) > 0) {
        $dynamicdata = $xartable['dynamic_data'];
        $dynamicprop = $xartable['dynamic_properties'];

        $propids = array_keys($dynprops);

        $query = "SELECT xar_dd_propid,
                         xar_dd_value
                    FROM $dynamicdata
                   WHERE xar_dd_propid IN (" . join(', ',$propids) . ")
                     AND xar_dd_itemid = " . xarVarPrepForStore($itemid);
      // we don't really need the LEFT JOIN here, since we know the propid's & labels already
      //       LEFT JOIN $dynamicprop
      //              ON xar_dd_propid = xar_prop_id
        $result = $dbconn->Execute($query);

        if (!isset($result)) return;

        while (!$result->EOF) {
            list($propid, $value) = $result->fields;
            $name = $dynprops[$propid];
            if (isset($value)) {
                $fields[$name]['value'] = $value;
            }
            $result->MoveNext();
        }

        $result->Close();
    }

    $systemPrefix = xarDBGetSystemTablePrefix();
    $metaTable = $systemPrefix . '_tables';

    // retrieve properties for this item from some known table field
// TODO: create UNION (or equivalent) to retrieve all relevant table fields at once
    foreach ($tables as $table => $fieldlist) {
        // look for the item id field
        if (!empty($itemidname) && preg_match('/^(\w+)\.(\w+)$/', $fields[$itemidname]['source'], $matches)
            && $table == $matches[1] && isset($tables[$table][$matches[2]])) {
            $field = $matches[2];
        } else {
            // For now, we look for a primary key (or increment, perhaps ?),
            // and hope it corresponds to the item id :-)
        // TODO: improve this once we can define better relationships
            $query = "SELECT xar_field, xar_type
                        FROM $metaTable
                       WHERE xar_primary_key = 1
                         AND xar_table='" . xarVarPrepForStore($table) . "'";

            $result = $dbconn->Execute($query);

            if (!isset($result)) return;

            if ($result->EOF) {
                continue;
            }
            list($field, $type) = $result->fields;
            $result->Close();
        }

        // can't really do much without the item id field at the moment
        if (empty($field)) {
            continue;
        }
        $query = "SELECT $field, " . join(', ', array_keys($fieldlist)) . "
                    FROM $table
                   WHERE $field = " . xarVarPrepForStore($itemid);

        $result = $dbconn->Execute($query);

        if (!isset($result)) return;

        if ($result->EOF) {
            continue;
        }
        $values = $result->fields;
        $result->Close();

        $newitemid = array_shift($values);
        // oops, something went seriously wrong here...
        if (empty($itemid) || $newitemid != $itemid || count($values) != count($fieldlist)) {
            continue;
        }

        foreach ($fieldlist as $field => $name) {
            $fields[$name]['value'] = array_shift($values);
        }
    }

    // retrieve properties for this item via a hook module
    foreach ($hooks as $hook => $name) {
        if (xarModIsAvailable($hook) && xarModAPILoad($hook,'user')) {
        // TODO: find some more consistent way to do this !
            $fields[$name]['value'] = xarModAPIFunc($hook,'user','get',
                                                  array('modname' => $modinfo['name'],
                                                        'modid' => $modid,
                                                        'itemtype' => $itemtype,
                                                        'itemid' => $itemid,
                                                        'objectid' => $itemid));
        }
    }

    // retrieve properties for this item via some user function
    foreach ($functions as $function => $name) {
        // split into module, type and function
// TODO: improve this ?
        list($fmod,$ftype,$ffunc) = explode('_',$function);
        // see if the module is available
        if (!xarModIsAvailable($fmod)) {
            continue;
        }
        // see if we're dealing with an API function or a GUI one
        if (preg_match('/api$/',$ftype)) {
            $ftype = preg_replace('/api$/','',$ftype);
            // try to load the module API
            if (!xarModAPILoad($fmod,$ftype)) {
                continue;
            }
            // try to invoke the function with some common parameters
        // TODO: standardize this, or allow the admin to specify the arguments
            $value = xarModAPIFunc($fmod,$ftype,$ffunc,
                                   array('modname' => $modinfo['name'],
                                         'modid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid' => $itemid,
                                         'objectid' => $itemid));
            // see if we got something interesting in return
            if (isset($value)) {
                $fields[$name]['value'] = $value;
            }
        } else {
            // try to load the module GUI
            if (!xarModLoad($fmod,$ftype)) {
                continue;
            }
            // try to invoke the function with some common parameters
        // TODO: standardize this, or allow the admin to specify the arguments
            $value = xarModFunc($fmod,$ftype,$ffunc,
                                array('modname' => $modinfo['name'],
                                      'modid' => $modid,
                                      'itemtype' => $itemtype,
                                      'itemid' => $itemid,
                                      'objectid' => $itemid));
            // see if we got something interesting in return
            if (isset($value)) {
                $fields[$name]['value'] = $value;
            }
        }
    }

// TODO: retrieve from other data sources as well

    foreach ($fields as $name => $field) {
        if (xarSecAuthAction(0, 'DynamicData::Field', $field['name'].':'.$field['type'].':'.$field['id'], ACCESS_READ)) {
            if (!isset($field['value'])) {
                $fields[$name]['value'] = $fields[$name]['default'];
            }
        } else {
            unset($fields[$name]);
        }
    }

    return $fields;
}

/*
 * This function is being phased out...
 */
function dynamicdata_userapi_getall($args)
{
    return dynamicdata_userapi_getitem($args);
}

/**
 * get all dynamic data fields for a list of items
 * (identified by module + item type, and item ids or other search criteria)
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields to get, or
 * @param $args['modid'] module id of the item fields to get
 * @param $args['itemtype'] item type of the item fields to get
 * @param $args['itemids'] array of item ids to return
 * @param $args['fieldlist'] array of field labels to retrieve (default is all)
 * @param $args['status'] limit to property fields of a certain status (e.g. active)
 * @param $args['static'] include the static properties (= module tables) too (default no)
 * @param $args['sort'] sort field(s)
 * @param $args['numitems'] number of items to retrieve
 * @param $args['startnum'] start number
 * @param $args['where'] WHERE clause to be used as part of the selection
 * @returns array
 * @return array of fields, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getitems($args)
{
    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    $modinfo = xarModGetInfo($modid);

    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid) || empty($modinfo['name'])) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getitems', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:", ACCESS_OVERVIEW)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    if (empty($itemids)) {
        $itemids = array();
    } elseif (!is_array($itemids)) {
        $itemids = explode(',',$itemids);
    }

    foreach ($itemids as $itemid) {
        if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_OVERVIEW)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
            return;
        }
    }

    // check the optional field list
    if (empty($fieldlist)) {
        $fieldlist = null;
    }

    // limit to property fields of a certain status (e.g. active)
    if (!isset($status)) {
        $status = null;
    }

    // include the static properties (= module tables) too ?
    if (empty($static)) {
        $static = false;
    }

    if (empty($startnum) || !is_numeric($startnum)) {
        $startnum = 1;
    }
    if (empty($numitems) || !is_numeric($numitems)) {
        $numitems = -1;
    }

    if (empty($sort)) {
        $sort = array();
    } elseif (!is_array($sort)) {
        $sort = explode(',',$sort);
    }

    // add sort fields to field list if necessary
    if (isset($fieldlist) && count($fieldlist) > 0 && count($sort) > 0) {
        foreach ($sort as $criteria) {
            // split off trailing ASC or DESC
            if (preg_match('/^(.+)\s+(ASC|DESC)\s*$/',$criteria,$matches)) {
                $criteria = trim($matches[1]);
                $sortorder = $matches[2];
            } else {
                $sortorder = '';
            }
            if (!in_array($criteria,$fieldlist)) {
            // TODO: how to ignore those in display/view/form/list afterwards ?
                $fieldlist[] = $criteria;
            }
        }
    }

    // get all properties for this module / itemtype,
    // or only the properties mentioned in $fieldlist (in the right order, PHP willing)
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype,
                                 'fieldlist' => $fieldlist,
                                 'status' => $status,
                                 'static' => $static));
    if (empty($fields) || count($fields) == 0) {
        return array();
    }

    // different processing depending on the data source
    list($dynprops,$tables,$hooks,$functions,$itemidname) =
                          xarModAPIFunc('dynamicdata','user','splitfields',
                                                          // pass by reference
                                        array('fields' => &$fields));

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $items = array();

    // pre-fill the items if we can
    if (count($itemids) > 0) {
        foreach ($itemids as $itemid) {
            $items[$itemid] = array('itemid' => $itemid,
                                    'fields' => $fields);
        }
    }

    // analyse sort criteria
    $dynpropsort = array();
    $tablesort = array();
    $hooksort = array();
    $functionsort = array();
    $startsort = '';
    foreach ($sort as $criteria) {
        // split off trailing ASC or DESC
        if (preg_match('/^(.+)\s+(ASC|DESC)\s*$/',$criteria,$matches)) {
            $criteria = trim($matches[1]);
            $sortorder = $matches[2];
        } else {
            $sortorder = '';
        }
        if (!isset($fields[$criteria])) {
            continue;
        }
        $field = $fields[$criteria];
        // normal dynamic data field
        if ($field['source'] == 'dynamic_data') {
            // we still use the property ids here, because they're faster/more consistent
            $dynpropsort[] = array('field' => $field['id'],
                                   'sortorder' => $sortorder);
            if (empty($startsort)) {
                $startsort = 'ids';
            }

        // data field coming from some static table
        } elseif (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
            $table = $matches[1];
            $fieldname = $matches[2];
            $tablesort[$table][] =  array('field' => $fieldname,
                                          'sortorder' => $sortorder);
            if (empty($startsort)) {
                $startsort = 'tables';
            }

        // data managed by a hook/utility module
        } elseif ($field['source'] == 'hook module') {
            // check if this is a known module, based on the name of the property type
            $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');
            if (!empty($proptypes[$field['type']]['name'])) {
                $hooksort[$proptypes[$field['type']]['name']] = $sortorder;
                if (empty($startsort)) {
                    $startsort = 'hooks';
                }
            }

        // data managed by some user function (specified in validation for now)
        } elseif ($field['source'] == 'user function') {
            $functions[$field['validation']] = $sortorder;
            if (empty($startsort)) {
                $startsort = 'functions';
            }

        } else {
    // TODO: sort by other data sources than (known) tables as well
        }
    }

// TODO: expand on this some (long rainy) day
    // analyse where clauses
    $dynpropwhere = array();
    $tablewhere = array();
    $hookwhere = array();
    $functionwhere = array();
    $startwhere = '';
    if (!empty($where)) {
        // cfr. BL compiler - adapt as needed (I don't think == and === are accepted in SQL)
        $findLogic      = array(' eq ', ' neq ', ' lt ', ' gt ', ' id ', ' nid ', ' lte ', ' gte ');
        $replaceLogic   = array(' = ', ' != ',  ' < ',  ' > ', ' = ', ' != ', ' <= ', ' >= ');
        $where = str_replace($findLogic, $replaceLogic, $where);

    // TODO: reject multi-source WHERE clauses :-)
        $parts = preg_split('/\s+(and|or)\s+/',$where,-1,PREG_SPLIT_DELIM_CAPTURE);
        $join = '';
        foreach ($parts as $part) {
            if ($part == 'and' || $part == 'or') {
                $join = $part;
                continue;
            }
            $pieces = preg_split('/\s+/',$part);
            $name = array_shift($pieces);
            if (!isset($fields[$name])) {
                // discard for now
                continue;
            }
            // sanity check on SQL
            if (count($pieces) < 2) {
                $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                             'query ' . xarVarPrepForStore($where), 'user', 'getitems', 'DynamicData');
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
                return;
            }

            $field = $fields[$name];
            // normal dynamic data field
            if ($field['source'] == 'dynamic_data') {
                // we still use the property ids here, because they're faster/more consistent
                $dynpropwhere[] = array('field' => $field['id'],
                                        'clause' => join(' ',$pieces),
                                        'join' => $join);
                if (empty($startwhere)) {
                    $startwhere = 'ids';
                }

            // data field coming from another table
            } elseif (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
                $table = $matches[1];
                $fieldname = $matches[2];
                $tablewhere[$table][] =  array('field' => $fieldname,
                                               'clause' => join(' ',$pieces),
                                               'join' => $join);
                if (empty($startwhere)) {
                    $startwhere = 'tables';
                }
/*
            // data managed by a hook/utility module
            } elseif ($field['source'] == 'hook module') {
                // check if this is a known module, based on the name of the property type
                $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');
                if (!empty($proptypes[$field['type']]['name'])) {
                    $hooksort[$proptypes[$field['type']]['name']] = join(' ',$pieces);
                    if (empty($startsort)) {
                        $startsort = 'hooks';
                    }
                }

            // data managed by some user function (specified in validation for now)
            } elseif ($field['source'] == 'user function') {
                $functions[$field['validation']] = join(' ',$pieces);
                if (empty($startwhere)) {
                    $startwhere = 'functions';
                }
*/
            } else {
        // TODO: sort by other data sources than (known) tables as well
            }

        }
    }

// TODO: determine the order of retrieval depending on the $startsort or $startwhere

    // see if we need/want to save the item ids for the next data sources
    if (count($itemids) == 0) {
        $saveids = 1;
    } else {
        $saveids = 0;
    }

    $systemPrefix = xarDBGetSystemTablePrefix();
    $metaTable = $systemPrefix . '_tables';

    // retrieve properties from some known table field
// TODO: create UNION (or equivalent) to retrieve all relevant table fields at once
    foreach ($tables as $table => $fieldlist) {
        // look for the item id field
        if (!empty($itemidname) && preg_match('/^(\w+)\.(\w+)$/', $fields[$itemidname]['source'], $matches)
            && $table == $matches[1] && isset($tables[$table][$matches[2]])) {
            $field = $matches[2];
        } else {
            // For now, we look for a primary key (or increment, perhaps ?),
            // and hope it corresponds to the item id :-)
        // TODO: improve this once we can define better relationships
            $query = "SELECT xar_field, xar_type
                        FROM $metaTable
                       WHERE xar_primary_key = 1
                         AND xar_table='" . xarVarPrepForStore($table) . "'";

            $result = $dbconn->Execute($query);

            if (!isset($result)) return;

            if ($result->EOF) {
                continue;
            }
            list($field, $type) = $result->fields;
            $result->Close();
        }

        // can't really do much without the item id field at the moment
        if (empty($field)) {
            continue;
        }

        $query = "SELECT $field, " . join(', ', array_keys($fieldlist)) . "
                    FROM $table ";
        if (count($itemids) > 1) {
            $query .= " WHERE $field IN (" . join(', ',$itemids) . ") ";
        } elseif (count($itemids) == 1) {
            $query .= " WHERE $field = " . xarVarPrepForStore($itemids[0]) . " ";
        } elseif (isset($tablewhere[$table]) && count($tablewhere[$table]) > 0) {
            $query .= " WHERE ";
            foreach ($tablewhere[$table] as $whereitem) {
                $query .= $whereitem['join'] . ' ' . $whereitem['field'] . ' ' . $whereitem['clause'] . ' ';
            }
        }

        // TODO: GROUP BY, LEFT JOIN, ... ? -> cfr. relationships

        if (isset($tablesort[$table]) && count($tablesort[$table]) > 0) {
            $query .= " ORDER BY ";
            $join = '';
            foreach ($tablesort[$table] as $sortitem) {
                $query .= $join . $sortitem['field'] . ' ' . $sortitem['sortorder'];
                $join = ', ';
            }
        } else {
            $query .= " ORDER BY $field";
        }
        if ($numitems > 0) {
            $result = $dbconn->SelectLimit($query, $numitems, $startnum-1);
        } else {
            $result = $dbconn->Execute($query);
        }
        if (!isset($result)) return;

        while (!$result->EOF) {
            $values = $result->fields;
            $itemid = array_shift($values);
            // oops, something went seriously wrong here...
            if (empty($itemid) || count($values) != count($fieldlist)) {
                continue;
            }
            // add this itemid to the list
            if ($saveids) {
                $itemids[] = $itemid;
            }
            // pre-fill the item if necessary
            if (!isset($items[$itemid]) || !isset($items[$itemid]['fields'])) {
                $items[$itemid] = array('itemid' => $itemid,
                                        'fields' => $fields);
            }
            foreach ($fieldlist as $field => $name) {
                $items[$itemid]['fields'][$name]['value'] = array_shift($values);
            }

            $result->MoveNext();
        }
        $result->Close();
    }

    if (count($itemids) == 0) {
        $saveids = 1;
    } else {
        $saveids = 0;
    }

    // retrieve properties from the dynamic_data table
    if (count($dynprops) > 0) {
        $dynamicdata = $xartable['dynamic_data'];
        $dynamicprop = $xartable['dynamic_properties'];

        $propids = array_keys($dynprops);

        // easy case where we already know the items we want
        if (count($itemids) > 0) {
            $query = "SELECT xar_dd_itemid,
                             xar_dd_propid,
                             xar_dd_value
                        FROM $dynamicdata
                       WHERE xar_dd_propid IN (" . join(', ',$propids) . ") ";
            // we don't really need the LEFT JOIN here, since we know the propid's & labels already
            //       LEFT JOIN $dynamicprop
            //              ON xar_dd_propid = xar_prop_id

            if (count($itemids) > 1) {
                $query .= " AND xar_dd_itemid IN (" . join(', ',$itemids) . ") ";
            } elseif (count($itemids) == 1) {
                $query .= " AND xar_dd_itemid = " . xarVarPrepForStore($itemids[0]) . " ";
            } else {
            // TODO: add other criteria (probably needs to be done after the tables, if any)
            }

            $result = $dbconn->Execute($query);

            if (!isset($result)) return;

            while (!$result->EOF) {
                list($itemid,$propid, $value) = $result->fields;
                if (isset($value)) {
                    // pre-fill the item if necessary
                    if (!isset($items[$itemid]) || !isset($items[$itemid]['fields'])) {
                        $items[$itemid] = array('itemid' => $itemid,
                                                'fields' => $fields);
                    }
                    $name = $dynprops[$propid];
                    $items[$itemid]['fields'][$name]['value'] = $value;
                }
                $result->MoveNext();
            }

            $result->Close();

        // more difficult case where we need to create a pivot table, basically
        } elseif ($numitems > 0 || count($dynpropsort) > 0 || count($dynpropwhere) > 0) {

            $query = "SELECT xar_dd_itemid ";
            foreach ($dynprops as $propid => $name) {
                $query .= ", MAX(CASE WHEN xar_dd_propid = $propid THEN xar_dd_value ELSE '' END) AS 'dd_$propid' \n";
            }
            $query .= " FROM $dynamicdata
                       WHERE xar_dd_propid IN (" . join(', ',$propids) . ") 
                    GROUP BY xar_dd_itemid ";
            // we don't really need the LEFT JOIN here, since we know the propid's & labels already
            //       LEFT JOIN $dynamicprop
            //              ON xar_dd_propid = xar_prop_id

            if (isset($dynpropwhere) && count($dynpropwhere) > 0) {
                $query .= " HAVING ";
                foreach ($dynpropwhere as $whereitem) {
                    $query .= $whereitem['join'] . ' dd_' . $whereitem['field'] . ' ' . $whereitem['clause'] . ' ';
                }
            }

            if (isset($dynpropsort) && count($dynpropsort) > 0) {
                $query .= " ORDER BY ";
                $join = '';
                foreach ($dynpropsort as $sortitem) {
                    $query .= $join . 'dd_' . $sortitem['field'] . ' ' . $sortitem['sortorder'];
                    $join = ', ';
                }
            }

            if ($numitems > 0) {
                $result = $dbconn->SelectLimit($query, $numitems, $startnum-1);
            } else {
                $result = $dbconn->Execute($query);
            }

            if (!isset($result)) return;

            while (!$result->EOF) {
                $values = $result->fields;
                $itemid = array_shift($values);
                // oops, something went seriously wrong here...
                if (empty($itemid) || count($values) != count($dynprops)) {
                    continue;
                }
                // add this itemid to the list
                if ($saveids) {
                    $itemids[] = $itemid;
                }
                // pre-fill the item if necessary
                if (!isset($items[$itemid]) || !isset($items[$itemid]['fields'])) {
                    $items[$itemid] = array('itemid' => $itemid,
                                            'fields' => $fields);
                }
                foreach ($dynprops as $propid => $name) {
                    $items[$itemid]['fields'][$name]['value'] = array_shift($values);
                }
                $result->MoveNext();
            }

            $result->Close();

        // here we grab everyting ?
        } else {
            $query = "SELECT xar_dd_itemid,
                             xar_dd_propid,
                             xar_dd_value
                        FROM $dynamicdata
                       WHERE xar_dd_propid IN (" . join(', ',$propids) . ") ";
            // we don't really need the LEFT JOIN here, since we know the propid's & labels already
            //       LEFT JOIN $dynamicprop
            //              ON xar_dd_propid = xar_prop_id

            $result = $dbconn->Execute($query);

            if (!isset($result)) return;

            while (!$result->EOF) {
                list($itemid,$propid, $value) = $result->fields;
                if (isset($value)) {
                    // pre-fill the item if necessary
                    if (!isset($items[$itemid]) || !isset($items[$itemid]['fields'])) {
                        $items[$itemid] = array('itemid' => $itemid,
                                                'fields' => $fields);
                    }
                    $name = $dynprops[$propid];
                    $items[$itemid]['fields'][$name]['value'] = $value;
                }
                $result->MoveNext();
            }

            $result->Close();
        }
    }

    // retrieve properties via a hook module
    foreach ($hooks as $hook => $name) {
        if (xarModIsAvailable($hook) && xarModAPILoad($hook,'user')) {
        // TODO: use some getall() function !!
            foreach ($itemids as $itemid) {
            // TODO: find some more consistent way to do this !
                $items[$itemid]['fields'][$name]['value'] = xarModAPIFunc($hook,'user','get',
                                                                           array('modname' => $modinfo['name'],
                                                                                 'modid' => $modid,
                                                                                 'itemtype' => $itemtype,
                                                                                 'itemid' => $itemid,
                                                                                 'objectid' => $itemid));
            }
        }
    }

    // retrieve properties via some user function
    foreach ($functions as $function => $name) {
        // split into module, type and function
// TODO: improve this ?
        list($fmod,$ftype,$ffunc) = explode('_',$function);
        // see if the module is available
        if (!xarModIsAvailable($fmod)) {
            continue;
        }
        // see if we're dealing with an API function or a GUI one
        if (preg_match('/api$/',$ftype)) {
            $ftype = preg_replace('/api$/','',$ftype);
            // try to load the module API
            if (!xarModAPILoad($fmod,$ftype)) {
                continue;
            }
        // TODO: use some getall() function !!
            foreach ($itemids as $itemid) {
                // try to invoke the function with some common parameters
            // TODO: standardize this, or allow the admin to specify the arguments
                $value = xarModAPIFunc($fmod,$ftype,$ffunc,
                                       array('modname' => $modinfo['name'],
                                             'modid' => $modid,
                                             'itemtype' => $itemtype,
                                             'itemid' => $itemid,
                                             'objectid' => $itemid));
                // see if we got something interesting in return
                if (isset($value)) {
                    $items[$itemid]['fields'][$name]['value'] = $value;
                }
            }
        } else {
            // try to load the module GUI
            if (!xarModLoad($fmod,$ftype)) {
                continue;
            }
        // TODO: use some getall() function !!
            foreach ($itemids as $itemid) {
                // try to invoke the function with some common parameters
            // TODO: standardize this, or allow the admin to specify the arguments
                $value = xarModFunc($fmod,$ftype,$ffunc,
                                    array('modname' => $modinfo['name'],
                                          'modid' => $modid,
                                          'itemtype' => $itemtype,
                                          'itemid' => $itemid,
                                          'objectid' => $itemid));
                // see if we got something interesting in return
                if (isset($value)) {
                    $items[$itemid]['fields'][$name]['value'] = $value;
                }
            }
        }
    }

// TODO: retrieve from other data sources as well

// TODO: fix defaults ? (cfr. getall)

    return $items;
}

/**
 * get a specific item field
// TODO: update this with all the new stuff
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item field to get, or
 * @param $args['modid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @param $args['itemid'] item id of the item field to get
 * @param $args['prop_id'] property id of the field to get, or
 * @param $args['name'] name of the field to get
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getfield($args)
{
    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if ((!isset($name) && !isset($prop_id)) ||
        (isset($name) && !is_string($name)) ||
        (isset($prop_id) && !is_numeric($prop_id))) {
        $invalid[] = 'field name or property id';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'get', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicdata = $xartable['dynamic_data'];
    $dynamicprop = $xartable['dynamic_properties'];

// TODO: retrieve from other data sources as well

    $sql = "SELECT xar_prop_name,
                   xar_prop_type,
                   xar_prop_id,
                   xar_prop_default,
                   xar_dd_value
            FROM $dynamicdata, $dynamicprop
            WHERE xar_prop_id = xar_dd_propid
              AND xar_prop_moduleid = " . xarVarPrepForStore($modid);
    if (!empty($itemtype)) {
        $sql .= " AND xar_prop_itemtype = " . xarVarPrepForStore($itemtype);
    }
    $sql .= " AND xar_dd_itemid = " . xarVarPrepForStore($itemid);
    if (!empty($prop_id)) {
        $sql .= " AND xar_prop_id = " . xarVarPrepForStore($prop_id);
    } else {
        $sql .= " AND xar_prop_name = '" . xarVarPrepForStore($name) . "'";
    }

    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    if ($result->EOF) {
        $result->Close();
        return;
    }
    list($name, $type, $id, $default, $value) = $result->fields;
    $result->Close();

    if (!xarSecAuthAction(0, 'DynamicData::Field', "$name:$type:$id", ACCESS_READ)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }
    if (!isset($value)) {
        $value = $default;
    }

    return $value;
}

/*
 * This function is going to be phased out...
 */
function dynamicdata_userapi_get($args)
{
    return dynamicdata_userapi_getfield($args);
}


/**
 * split the fields array into parts for dynamic data, static tables, hooks, functions, ...
 *
 * @param &$fields fields array (pass by reference here !)
 * @returns array
 * @return array of $dynprops, $tables, $hooks, $functions arrays
 * @raise BAD_PARAM
 */
function dynamicdata_userapi_splitfields($args)
{
// don't use extract here - we get the fields by reference
//    extract($args);

    // pass by reference
    $fields = &$args['fields'];

    $dynprops = array();
    $tables = array();
    $hooks = array();
    $functions = array();
    // name of the item id field
    $itemidname = '';
    foreach ($fields as $name => $field) {
        if (empty($itemidname) && $field['type'] == 21) { // Item ID
            $itemidname = $name;
        }

        // normal dynamic data field
        if ($field['source'] == 'dynamic_data') {
            // we still use the property ids here, because they're faster/more consistent
            $dynprops[$field['id']] = $name;

        // data field coming from another table
        } elseif (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
            $table = $matches[1];
            $fieldname = $matches[2];
            $tables[$table][$fieldname] = $name;

        // data managed by a hook/utility module
        } elseif ($field['source'] == 'hook module') {
            // check if this is a known module, based on the name of the property type
            $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');
            if (!empty($proptypes[$field['type']]['name'])) {
                $hooks[$proptypes[$field['type']]['name']] = $name;
            }

        // data managed by some user function (specified in validation for now)
        } elseif ($field['source'] == 'user function') {
            $functions[$field['validation']] = $name;

        } else {
    // TODO: retrieve from other data sources than (known) tables as well
        }
    }

    return array($dynprops,$tables,$hooks,$functions,$itemidname);
}

// ----------------------------------------------------------------------
// get*() properties, data sources, static fields, relationships, ...
// ----------------------------------------------------------------------

/**
 * get field properties for a specific module + item type
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] object id of the properties to get
 * @param $args['module'] module name of the item fields, or
 * @param $args['modid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @param $args['fieldlist'] array of field labels to retrieve (default is all)
 * @param $args['status'] limit to property fields of a certain status (e.g. active)
 * @param $args['static'] include the static properties (= module tables) too (default no)
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getprop($args)
{
    static $propertybag = array();

    extract($args);

    if (!empty($objectid)) {
        $object = xarModAPIFunc('dynamicdata','user','getobject',
                                array('objectid' => $objectid));
        if (!empty($object)) {
            $modid = $object['moduleid']['value'];
            $itemtype = $object['itemtype']['value'];
        }
    }

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    // check the optional field list
    if (empty($fieldlist)) {
        $fieldlist = null;
    }

    // limit to property fields of a certain status (e.g. active)
    if (!isset($status)) {
        $status = null;
    }

    // include the static properties (= module tables) too ?
    if (empty($static)) {
        $static = false;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getprop', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (empty($static) && isset($propertybag["$modid:$itemtype"])) {
        if (!empty($fieldlist)) {
            $myfields = array();
            foreach ($fieldlist as $name) {
                if (isset($propertybag["$modid:$itemtype"][$name])) {
                    $myfields[$name] = $propertybag["$modid:$itemtype"][$name];
                }
            }
            return $myfields;
        } elseif (isset($status)) {
            $myfields = array();
            foreach ($propertybag["$modid:$itemtype"] as $name => $field) {
                if ($field['status'] == $status) {
                    $myfields[$name] = $propertybag["$modid:$itemtype"][$name];
                }
            }
            return $myfields;
        } else {
            return $propertybag["$modid:$itemtype"];
        }
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "SELECT xar_prop_name,
                   xar_prop_label,
                   xar_prop_type,
                   xar_prop_id,
                   xar_prop_default,
                   xar_prop_source,
                   xar_prop_status,
                   xar_prop_order,
                   xar_prop_validation
            FROM $dynamicprop
            WHERE xar_prop_moduleid = " . xarVarPrepForStore($modid) . "
              AND xar_prop_itemtype = " . xarVarPrepForStore($itemtype) . "
            ORDER BY xar_prop_id ASC";

    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    $fields = array();

    while (!$result->EOF) {
        list($name, $label, $type, $id, $default, $source, $fieldstatus, $order, $validation) = $result->fields;
        if (xarSecAuthAction(0, 'DynamicData::Field', "$name:$type:$id", ACCESS_READ)) {
            $fields[$name] = array('name' => $name,
                                   'label' => $label,
                                   'type' => $type,
                                   'id' => $id,
                                   'default' => $default,
                                   'source' => $source,
                                   'status' => $fieldstatus,
                                   'order' => $order,
                                   'validation' => $validation);
        }
        $result->MoveNext();
    }

    $result->Close();

    if (!empty($static)) {
        // get the list of static properties for this module
        $staticlist = xarModAPIFunc('dynamicdata','user','getstatic',
                                    array('modid' => $modid,
                                          'itemtype' => $itemtype));
// TODO: watch out for conflicting property ids ?
        $fields = array_merge($staticlist,$fields);
    }

    if (empty($static)) {
        $propertybag["$modid:$itemtype"] = $fields;
    }
    if (!empty($fieldlist)) {
        $myfields = array();
        // this should return the fields in the right order, normally
        foreach ($fieldlist as $name) {
            if (isset($fields[$name])) {
                $myfields[$name] = $fields[$name];
            }
        }
        return $myfields;
    } elseif (isset($status)) {
        $myfields = array();
        foreach ($fields as $name => $field) {
            if ($field['status'] == $status) {
                $myfields[$name] = $field;
            }
        }
        return $myfields;
    } else {
        return $fields;
    }
}

/**
 * get the list of defined dynamic objects
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array of object definitions
 * @raise DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getobjects($args)
{
    $objects = xarModAPIFunc('dynamicdata','user','getitems',
                             array('module' => 'dynamicdata',
                                   'itemtype' => 0));
                             //      'fieldlist' => array('id,name')));
    return $objects;
}

/**
 * get information about a defined dynamic object
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] id of the object you're looking for, or
 * @param $args['moduleid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @returns array
 * @return array of object definitions
 * @raise DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getobject($args)
{
    extract($args);
    if (!empty($objectid)) {
        $object = xarModAPIFunc('dynamicdata','user','getitem',
                                array('module' => 'dynamicdata',
                                      'itemtype' => 0,
                                      'itemid' => $objectid));
        if (!isset($object)) return;
        return $object;
    }

    if (empty($moduleid) && !empty($modid)) {
        $moduleid = $modid;
    }
    $objects = xarModAPIFunc('dynamicdata','user','getitems',
                             array('module' => 'dynamicdata',
                                   'itemtype' => 0,
                                   'where' => "moduleid eq $moduleid and itemtype eq $itemtype"));
    if (!isset($objects)) return;
    foreach ($objects as $object) {
        if (isset($object['fields']['id']['value'])) {
            return $object['fields'];
        }
    }
    return;
}

/**
 * get the list of modules + itemtypes for which dynamic properties are defined
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array of modid + itemtype + number of properties
 * @raise DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getmodules($args)
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicprop = $xartable['dynamic_properties'];

    $query = "SELECT xar_prop_moduleid,
                     xar_prop_itemtype,
                     COUNT(xar_prop_id)
              FROM $dynamicprop
              GROUP BY xar_prop_moduleid, xar_prop_itemtype
              ORDER BY xar_prop_moduleid ASC, xar_prop_itemtype ASC";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $modules = array();

    while (!$result->EOF) {
        list($modid, $itemtype, $count) = $result->fields;
        if (xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:", ACCESS_OVERVIEW)) {
            $modules[] = array('modid' => $modid,
                               'itemtype' => $itemtype,
                               'numitems' => $count);
        }
        $result->MoveNext();
    }

    $result->Close();

    return $modules;
}

/**
 * get possible data sources (// TODO: for a module ?)
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields, or (// TODO: for a module ?)
 * @param $args['modid'] module id of the item field to get (// TODO: for a module ?)
 * @param $args['itemtype'] item type of the item field to get (// TODO: for a module ?)
 * @returns mixed
 * @return list of possible data sources, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getsources($args)
{
    extract($args);

/*
    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getprop', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($propertybag["$modid:$itemtype"])) {
        return $propertybag["$modid:$itemtype"];
    }
*/

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $systemPrefix = xarDBGetSystemTablePrefix();
    $metaTable = $systemPrefix . '_tables';

// TODO: remove Xaraya system tables from the list of available sources ?
    $query = "SELECT xar_table,
                     xar_field,
                     xar_type,
                     xar_size
              FROM $metaTable
              ORDER BY xar_table ASC, xar_field ASC";

    $result = $dbconn->Execute($query);

    if (!isset($result)) return;

    $sources = array();

    // default data source is dynamic data
    $sources[] = 'dynamic_data';

// TODO: re-evaluate this once we're further along
    // hook modules manage their own data
    $sources[] = 'hook module';

    // hook modules manage their own data
    $sources[] = 'user function';

    // add the list of table + field
    while (!$result->EOF) {
        list($table, $field, $type, $size) = $result->fields;
    // TODO: what kind of security checks do we want/need here ?
        //if (xarSecAuthAction(0, 'DynamicData::Field', "$name:$type:$id", ACCESS_READ)) {
        //}
        $sources[] = "$table.$field";
        $result->MoveNext();
    }

    $result->Close();

    return $sources;
}

/**
 * (try to) get the "static" properties, corresponding to fields in dedicated
 * tables for this module + item type
// TODO: allow modules to specify their own properties
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of table you're looking for, or
 * @param $args['modid'] module id of table you're looking for
 * @param $args['itemtype'] item type of table you're looking for
 * @param $args['table']  table name of table you're looking for (better)
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getstatic($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    $modinfo = xarModGetInfo($modid);
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid) || empty($modinfo['name'])) {
        $invalid[] = 'module id ' . xarVarPrepForDisplay($modid);
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getstatic', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    if (empty($table)) {
        $table = '';
    }
    if (isset($propertybag["$modid:$itemtype:$table"])) {
        return $propertybag["$modid:$itemtype:$table"];
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

// TODO: support site tables as well
    $systemPrefix = xarDBGetSystemTablePrefix();
    $metaTable = $systemPrefix . '_tables';

    if ($modinfo['name'] == 'dynamicdata') {
        // let's cheat a little for DD, because otherwise it won't find any tables :)
        if ($itemtype == 0) {
            $modinfo['name'] = 'dynamic_objects';
        } elseif ($itemtype == 1) {
            $modinfo['name'] = 'dynamic_properties';
        } elseif ($itemtype == 2) {
            $modinfo['name'] = 'dynamic_data';
        }
    }

    $query = "SELECT xar_tableid,
                     xar_table,
                     xar_field,
                     xar_type,
                     xar_size,
                     xar_default,
                     xar_increment,
                     xar_primary_key
              FROM $metaTable ";

    // it's easy if the table name is known
    if (!empty($table)) {
        $query .= " WHERE xar_table = '" . xarVarPrepForStore($table) . "'";

    // otherwise try to get any table that starts with prefix_modulename
    } else {
        $query .= " WHERE xar_table LIKE '" . xarVarPrepForStore($systemPrefix)
                                   . '_' . xarVarPrepForStore($modinfo['name']) . '%' . "' ";
    }
    $query .= " ORDER BY xar_tableid ASC";

    $result =& $dbconn->Execute($query);

    if (!isset($result)) return;

    $static = array();

    // add the list of table + field
    $order = 1;
    while (!$result->EOF) {
        list($id,$table, $field, $datatype, $size, $default,$increment,$primary_key) = $result->fields;
    // TODO: what kind of security checks do we want/need here ?
        //if (xarSecAuthAction(0, 'DynamicData::Field', "$name:$type:$id", ACCESS_READ)) {
        //}

        // assign some default label for now, by removing everything except the last part (xar_..._)
// TODO: let modules define this
        $name = preg_replace('/^.+_/','',$field);
        $label = ucfirst($name);
        if (isset($static[$name])) {
            $i = 1;
            while (isset($static[$name . '_' . $i])) {
                $i++;
            }
            $name = $name . '_' . $i;
            $label = $label . '_' . $i;
        }

        // (try to) assign some default property type for now
        // = obviously limited to basic data types in this case
        switch ($datatype) {
            case 'char':
            case 'varchar':
                $proptype = 2; // Text Box
                break;
            case 'integer':
                $proptype = 15; // Number Box
                break;
            case 'float':
                $proptype = 17; // Number Box (float)
                break;
            case 'boolean':
                $proptype = 14; // Checkbox
                break;
            case 'date':
            case 'datetime':
            case 'timestamp':
                $proptype = 8; // Calendar
                break;
            case 'text':
                $proptype = 4; // Medium Text Area
                break;
            case 'blob':       // caution, could be binary too !
                $proptype = 4; // Medium Text Area
                break;
            default:
                $proptype = 1; // Static Text
                break;
        }

        // assign some default validation for now
// TODO: improve this based on property type validations
        $validation = $datatype;
        $validation .= empty($size) ? '' : ' (' . $size . ')';

        // try to figure out if it's the item id
// TODO: let modules define this
        if (!empty($increment) || !empty($primary_key)) {
            // not allowed to modify primary key !
            $proptype = 21; // Item ID
        }

        $static[$name] = array('name' => $name,
                               'label' => $label,
                               'type' => $proptype,
                               'id' => $id,
                               'default' => $default,
                               'source' => $table . '.' . $field,
                               'status' => 1,
                               'order' => $order,
                               'validation' => $validation);
        $order++;
        $result->MoveNext();
    }

    $result->Close();

    $propertybag["$modid:$itemtype:$table"] = $static;
    return $static;
}

/**
 * (try to) get the relationships between a particular module and others (e.g. hooks)
// TODO: allow other kinds of relationships than hooks
// TODO: allow modules to specify their own relationships
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields, or
 * @param $args['modid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getrelations($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    $modinfo = xarModGetInfo($modid);
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid) || empty($modinfo['name'])) {
        $invalid[] = 'module id ' . xarVarPrepForDisplay($modid);
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getstatic', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($propertybag["$modid:$itemtype"])) {
        return $propertybag["$modid:$itemtype"];
    }

    // get the list of static properties for this module
    $static = xarModAPIFunc('dynamicdata','user','getstatic',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype));

    // get the list of hook modules that are enabled for this module
// TODO: get all hooks types, not only item display hooks
//    $hooklist = xarModGetHookList($modinfo['name'],'item','display');
    $hooklist = array_merge(xarModGetHookList($modinfo['name'],'item','display'),
                            xarModGetHookList($modinfo['name'],'item','update'));
    $modlist = array();
    foreach ($hooklist as $hook) {
        $modlist[$hook['module']] = 1;
    }

    $relations = array();
    if (count($modlist) > 0) {
        // first look for the (possible) item id field in the current module
        $itemid = '???';
        foreach ($static as $field) {
            if ($field['type'] == 21) { // Item ID
                $itemid = $field['source'];
                break;
            }
        }
        // for each enabled hook module
        foreach ($modlist as $mod => $val) {
            // get the list of static properties for this hook module
            $modstatic = xarModAPIFunc('dynamicdata','user','getstatic',
                                       array('modid' => xarModGetIDFromName($mod)));
                                       // skip this for now
                                       //      'itemtype' => $itemtype));
        // TODO: automatically find the link(s) on module, item type, item id etc.
        //       or should hook modules tell us that ?
            $links = array();
            foreach ($modstatic as $field) {

        /* for hook modules, those should define the fields *relating to* other modules (not their own item ids etc.)
                // try predefined field types first
                if ($field['type'] == 19) { // Module
                    $links[] = array('from' => $field['source'], 'to' => $modid, 'type' => 'moduleid');
                } elseif ($field['type'] == 20) { // Item Type
                    $links[] = array('from' => $field['source'], 'to' => $itemtype, 'type' => 'itemtype');
                } elseif ($field['type'] == 21) { // Item ID
                    $links[] = array('from' => $field['source'], 'to' => $itemid, 'type' => 'itemid');
        */
                // try to guess based on field names *cough*
                // link on module name/id
                if (preg_match('/_module$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $modinfo['name'], 'type' => 'modulename');
                } elseif (preg_match('/_moduleid$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $modid, 'type' => 'moduleid');
                } elseif (preg_match('/_modid$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $modid, 'type' => 'moduleid');

                // link on item type
                } elseif (preg_match('/_itemtype$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $itemtype, 'type' => 'itemtype');

                // link on item id
                } elseif (preg_match('/_itemid$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $itemid, 'type' => 'itemid');
                } elseif (preg_match('/_iid$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $itemid, 'type' => 'itemid');
                }
            }
            $relations[] = array('module' => $mod,
                                 'fields' => $modstatic,
                                 'links'  => $links);
        }
    }
    return $relations;
}

/**
 * (try to) get the "meta" properties of tables via PHP ADODB
 *
 * @author the DynamicData module development team
 * @param $args['table']  optional table you're looking for
 * @returns mixed
 * @return array of field definitions, or null on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getmeta($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($table)) {
        $table = '';
    } elseif (isset($propertybag[$table])) {
        return $propertybag[$table];
    }

    list($dbconn) = xarDBGetConn();

    if (!empty($table)) {
        $tables = array($table);
    } else {
        $tables = $dbconn->MetaTables();
    }
    if (!isset($tables)) {
        return;
    }

    $metadata = array();
    foreach ($tables as $table) {
        $fields = $dbconn->MetaColumns($table);
        $keys = $dbconn->MetaPrimaryKeys($table);

        $id = 1;
        $columns = array();
        foreach ($fields as $field) {
            $fieldname = $field->name;
            $datatype = $field->type;
            $size = $field->max_length;

            // assign some default label for now, by removing everything except the last part (xar_..._)
            $name = preg_replace('/^.+_/','',$fieldname);
            $label = ucfirst($name);
            if (isset($columns[$name])) {
                $i = 1;
                while (isset($columns[$name . '_' . $i])) {
                    $i++;
                }
                $name = $name . '_' . $i;
                $label = $label . '_' . $i;
            }

            // (try to) assign some default property type for now
            // = obviously limited to basic data types in this case
            $dtype = $datatype;
            // skip special definitions (unsigned etc.)
            $dtype = preg_replace('/\(.*$/','',$dtype);
            switch ($dtype) {
                case 'char':
                case 'varchar':
                    $proptype = 2; // Text Box
                    break;
                case 'int':
                case 'integer':
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                    if ($size == 1) {
                        $proptype = 14; // Checkbox
                    } else {
                        $proptype = 15; // Number Box
                    }
                    break;
                case 'float':
                case 'decimal':
                case 'double':
                    $proptype = 17; // Number Box (float)
                    break;
                case 'boolean':
                    $proptype = 14; // Checkbox
                    break;
                case 'date':
                case 'datetime':
                case 'timestamp':
                    $proptype = 8; // Calendar
                    break;
                case 'text':
                    $proptype = 4; // Medium Text Area
                    break;
                case 'longtext':
                    $proptype = 5; // Large Text Area
                    break;
                case 'blob':       // caution, could be binary too !
                    $proptype = 4; // Medium Text Area
                    break;
                case 'enum':
                    $proptype = 6; // Dropdown
                    break;
                default:
                    $proptype = 1; // Static Text
                    break;
            }

            // assign some default validation for now
            $validation = $datatype;
            $validation .= (empty($size) || $size < 0) ? '' : ' (' . $size . ')';

            // try to figure out if it's the item id
            if (!empty($keys) && in_array($fieldname,$keys)) {
                // not allowed to modify primary key !
                $proptype = 21; // Item ID
            }

            $columns[$name] = array('name' => $name,
                                   'label' => $label,
                                   'type' => $proptype,
                                   'id' => $id,
                                   'default' => '', // unknown here
                                   'source' => $table . '.' . $fieldname,
                                   'status' => 1,
                                   'order' => $id,
                                   'validation' => $validation);
            $id++;
        }
        $metadata[$table] = $columns;
    }

    $propertybag = $metadata;
    return $metadata;
}

// ----------------------------------------------------------------------
// get*() property types
// ----------------------------------------------------------------------

/**
 * get the list of defined property types from somewhere...
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array of property types
 * @raise DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getproptypes($args)
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $proptypes = array();

// TODO: replace with something else
    $proptypes[1] = array(
                          'id'         => 1,
                          'name'       => 'static',
                          'label'      => 'Static Text',
                          'format'     => '1',
                          'validation' => '',
                          // ...
                         );
    $proptypes[2] = array(
                          'id'         => 2,
                          'name'       => 'textbox',
                          'label'      => 'Text Box',
                          'format'     => '2',
                          'validation' => '',
                          // ...
                         );
    $proptypes[3] = array(
                          'id'         => 3,
                          'name'       => 'textarea_small',
                          'label'      => 'Small Text Area',
                          'format'     => '3',
                          'validation' => '',
                          // ...
                         );
    $proptypes[4] = array(
                          'id'         => 4,
                          'name'       => 'textarea_medium',
                          'label'      => 'Medium Text Area',
                          'format'     => '4',
                          'validation' => '',
                          // ...
                         );
    $proptypes[5] = array(
                          'id'         => 5,
                          'name'       => 'textarea_large',
                          'label'      => 'Large Text Area',
                          'format'     => '5',
                          'validation' => '',
                          // ...
                         );
    $proptypes[6] = array(
                          'id'         => 6,
                          'name'       => 'dropdown',
                          'label'      => 'Dropdown List',
                          'format'     => '6',
                          'validation' => '',
                          // ...
                         );
    $proptypes[7] = array(
                          'id'         => 7,
                          'name'       => 'username',
                          'label'      => 'Username',
                          'format'     => '7',
                          'validation' => '',
                          // ...
                         );
    $proptypes[8] = array(
                          'id'         => 8,
                          'name'       => 'calendar',
                          'label'      => 'Calendar',
                          'format'     => '8',
                          'validation' => '',
                          // ...
                         );
    $proptypes[9] = array(
                          'id'         => 9,
                          'name'       => 'fileupload',
                          'label'      => 'File Upload',
                          'format'     => '9',
                          'validation' => '',
                          // ...
                         );
    $proptypes[10] = array(
                          'id'         => 10,
                          'name'       => 'status',
                          'label'      => 'Status',
                          'format'     => '10',
                          'validation' => '',
                          // ...
                         );
    $proptypes[11] = array(
                          'id'         => 11,
                          'name'       => 'url',
                          'label'      => 'URL',
                          'format'     => '11',
                          'validation' => '',
                          // ...
                         );
    $proptypes[12] = array(
                          'id'         => 12,
                          'name'       => 'image',
                          'label'      => 'Image',
                          'format'     => '12',
                          'validation' => '',
                          // ...
                         );
    $proptypes[13] = array(
                          'id'         => 13,
                          'name'       => 'webpage',
                          'label'      => 'HTML Page',
                          'format'     => '13',
                          'validation' => '',
                          // ...
                         );
    $proptypes[14] = array(
                          'id'         => 14,
                          'name'       => 'checkbox',
                          'label'      => 'Checkbox',
                          'format'     => '14',
                          'validation' => '',
                          // ...
                         );
    $proptypes[15] = array(
                          'id'         => 15,
                          'name'       => 'integerbox',
                          'label'      => 'Number Box',
                          'format'     => '15',
                          'validation' => '',
                          // ...
                         );
    $proptypes[16] = array(
                          'id'         => 16,
                          'name'       => 'integerlist',
                          'label'      => 'Number List',
                          'format'     => '16',
                          'validation' => '',
                          // ...
                         );
    $proptypes[17] = array(
                          'id'         => 17,
                          'name'       => 'floatbox',
                          'label'      => 'Number Box (float)',
                          'format'     => '17',
                          'validation' => '',
                          // ...
                         );
    $proptypes[18] = array(
                          'id'         => 18,
                          'name'       => 'hidden',
                          'label'      => 'Hidden',
                          'format'     => '18',
                          'validation' => '',
                          // ...
                         );
// handy for relationships, URLs etc.
    $proptypes[19] = array(
                          'id'         => 19,
                          'name'       => 'module',
                          'label'      => 'Module',
                          'format'     => '19',
                          'validation' => '',
                          // ...
                         );
    $proptypes[20] = array(
                          'id'         => 20,
                          'name'       => 'itemtype',
                          'label'      => 'Item Type',
                          'format'     => '20',
                          'validation' => '',
                          // ...
                         );
    $proptypes[21] = array(
                          'id'         => 21,
                          'name'       => 'itemid',
                          'label'      => 'Item ID',
                          'format'     => '21',
                          'validation' => '',
                          // ...
                         );
    $proptypes[22] = array(
                          'id'         => 22,
                          'name'       => 'fieldtype',
                          'label'      => 'Field Type',
                          'format'     => '22',
                          'validation' => '',
                          // ...
                         );
    $proptypes[23] = array(
                          'id'         => 23,
                          'name'       => 'datasource',
                          'label'      => 'Data Source',
                          'format'     => '23',
                          'validation' => '',
                          // ...
                         );
    $proptypes[24] = array(
                          'id'         => 24,
                          'name'       => 'object',
                          'label'      => 'Object',
                          'format'     => '24',
                          'validation' => '',
                          // ...
                         );
    $proptypes[25] = array(
                          'id'         => 25,
                          'name'       => 'fieldstatus',
                          'label'      => 'Field Status',
                          'format'     => '25',
                          'validation' => '',
                          // ...
                         );

    // add some property types supported by utility modules
    if (xarModIsAvailable('categories') && xarModAPILoad('categories','user')) {
        $proptypes[100] = array(
                                'id'         => 100,
                                'name'       => 'categories',
                                'label'      => 'Categories',
                                'format'     => '100',
                                'validation' => '',
                                'source'     => 'hook module',
                                // ...
                              );
    }
    if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','user')) {
        $proptypes[101] = array(
                                'id'         => 101,
                                'name'       => 'hitcount',
                                'label'      => 'Hit Count',
                                'format'     => '101',
                                'validation' => '',
                                'source'     => 'hook module',
                                // ...
                               );
    }
    if (xarModIsAvailable('ratings') && xarModAPILoad('ratings','user')) {
        $proptypes[102] = array(
                                'id'         => 102,
                                'name'       => 'ratings',
                                'label'      => 'Rating',
                                'format'     => '102',
                                'validation' => '',
                                'source'     => 'hook module',
                                // ...
                               );
    }
    if (xarModIsAvailable('comments') && xarModAPILoad('comments','user')) {
        $proptypes[103] = array(
                                'id'         => 103,
                                'name'       => 'comments',
                                'label'      => 'Comments',
                                'format'     => '103',
                                'validation' => '',
                                'source'     => 'hook module',
                                // ...
                               );
    }
// trick : retrieve the number of comments via a user function here
    if (xarModIsAvailable('comments') && xarModAPILoad('comments','user')) {
        $proptypes[104] = array(
                                'id'         => 104,
                                'name'       => 'numcomments',
                                'label'      => '# of Comments',
                                'format'     => '104',
                                'validation' => 'comments_userapi_get_count',
                                'source'     => 'user function',
                                // ...
                               );
    }
// TODO: replace fileupload above with this one someday ?
/*
    if (xarModIsAvailable('uploads') && xarModAPILoad('uploads','user')) {
        $proptypes[105] = array(
                                'id'         => 105,
                                'name'       => 'uploads',
                                'label'      => 'Upload',
                                'format'     => '105',
                                'validation' => '',
                                'source'     => 'hook module',
                                // ...
                               );
    }
*/

// TODO: yes :)
/*
    $dynamicproptypes = $xartable['dynamic_property_types'];

    $query = "SELECT ...
              FROM $dynamicproptypes";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    while (!$result->EOF) {
        list(...) = $result->fields;
        if (xarSecAuthAction(0, '...', "...", ACCESS_OVERVIEW)) {
            $proptypes[] = array(...);
        }
        $result->MoveNext();
    }

    $result->Close();
*/

    return $proptypes;
}

// ----------------------------------------------------------------------
// BL user tags (output, display & view)
// ----------------------------------------------------------------------

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-output ...> form field tags
 * Format : <xar:data-output field="$field" /> with $field an array
 *                                             containing the type, label, value, ...
 *       or <xar:data-output label="thisname" type="thattype" value="$val" ... />
 * 
 * @param $args array containing the input field definition or the type, label, value, ...
 * @returns string
 * @return the PHP code needed to invoke showoutput() in the BL template
 */
function dynamicdata_userapi_handleOutputTag($args)
{
    $out = "xarModAPILoad('dynamicdata','user');
echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showoutput',\n";
    if (isset($args['definition'])) {
        $out .= '                   '.$args['definition']."\n";
        $out .= '                  );';
    } elseif (isset($args['field'])) {
        $out .= '                   '.$args['field']."\n";
        $out .= '                  );';
    } else {
        $out .= "                   array(\n";
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $out .= "                         '$key' => $val,\n";
            } else {
                $out .= "                         '$key' => '$val',\n";
            }
        }
        $out .= "                         ));";
    }
    return $out;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * show some predefined output field in a template
 * 
 * @param $args array containing the definition of the field (type, name, value, ...)
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showoutput($args)
{
    extract($args);
    if (empty($name) && !empty($label)) {
        $name = strtolower($label);
    }
    if (empty($name)) {
        return xarML('Missing \'name\' or \'label\' attribute in tag parameters or field definition');
    }
    if (!isset($type)) {
        $type = 1;
    }
    if (!isset($value)) {
        $value = '';
    }

// TODO: replace with something else
    $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');
    if (is_numeric($type)) {
        if (!empty($proptypes[$type]['name'])) {
            $typename = $proptypes[$type]['name'];
        } else {
            return xarML('Unknown property type #(1)',$type);
        }
    } else {
        $typename = $type;
    }

// TODO: what kind of security checks do we want/need here ?
    //if (xarSecAuthAction(0, 'DynamicData::Field', "$name:$type:$id", ACCESS_READ)) {
    //}

    $output = '';
    switch ($typename) {
        case 'text':
        case 'textbox':
            $output .= xarVarPrepHTMLDisplay($value);
            break;
        case 'textarea':
        case 'textarea_small':
        case 'textarea_medium':
        case 'textarea_large':
            $output .= xarVarPrepHTMLDisplay($value);
            break;
    // TEST ONLY
        case 'webpage':
            //$basedir = '/home/mikespub/www/pictures';
            $basedir = 'd:/backup/mikespub/pictures';
            $filetype = 'html?';
            if (!empty($value) &&
                preg_match('/^[a-zA-Z0-9_\/\\\:.-]+$/',$value) &&
                preg_match("/$filetype$/",$value) &&
                file_exists($value) &&
                is_file($value)) {
                $output .= join('', file($value));
            } else {
                $output .= xarVarPrepForDisplay($value);
            }
            break;
        case 'status':
            if (!isset($options) || !is_array($options)) {
                $options = array(
                                 array('id' => 0, 'name' => xarML('Submitted')),
                                 array('id' => 1, 'name' => xarML('Rejected')),
                                 array('id' => 2, 'name' => xarML('Approved')),
                                 array('id' => 3, 'name' => xarML('Front Page')),
                           );
            }
            if (empty($value)) {
                $value = 0;
            }
            // fall through to the next one
        case 'select':
        case 'dropdown':
        case 'listbox':
            if (!isset($selected)) {
                if (!empty($value)) {
                    $selected = $value;
                } else {
                    $selected = '';
                }
            }
            if (!isset($options) || !is_array($options)) {
                $options = array();
            }
        // TODO: support multiple selection
            $join = '';
            foreach ($options as $option) {
                if ($option['id'] == $selected) {
                    $output .= $join;
                    $output .= xarVarPrepForDisplay($option['name']);
                    $join = ' | ';
                }
            }
            break;
        case 'file':
        case 'fileupload':
        // TODO: link to download file ?
            break;
        case 'url':
        // TODO: use redirect function here ?
            if (!empty($value)) {
                $value = xarVarPrepForDisplay($value);
        // TODO: add alt/title here ?
                $output .= '<a href="'.$value.'">'.$value.'</a>';
            }
            break;
        case 'image':
            if (!empty($value)) {
                $value = xarVarPrepForDisplay($value);
        // TODO: add size/alt here ?
                $output .= '<img src="' . $value . '">';
            }
            break;
        case 'static':
            $output .= xarVarPrepForDisplay($value);
            break;
        case 'hidden':
            $output .= '';
            break;
        case 'username':
            if (empty($value)) {
                $value = xarUserGetVar('uid');
            }
            $user = xarUserGetVar('name', $value);
            if (empty($user)) {
                $user = xarUserGetVar('uname', $value);
            }
            if ($value > 1) {
                $output .= '<a href="'.xarModURL('users','user','display',
                                                    array('uid' => $value))
                           . '">'.xarVarPrepForDisplay($user).'</a>';
            } else {
                $output .= xarVarPrepForDisplay($user);
            }
            break;
        case 'date':
        case 'calendar':
            if (empty($value)) {
                $value = time();
            }
        // TODO: adapt to local/user time !
            $output .= strftime('%a, %d %B %Y %H:%M:%S %Z', $value);
            break;
        case 'checkbox':
        // TODO: allow different values here, and verify $checked ?
            if (empty($value)) {
                $output .= xarML('no');
            } else {
                $output .= xarML('yes');
            }
            break;
        case 'integerbox':
            $output .= xarVarPrepForDisplay($value);
            break;
        case 'integerlist':
            $output .= xarVarPrepForDisplay($value);
            break;
        case 'floatbox':
        // TODO: allow precision etc.
            $value = xarVarPrepForDisplay($value);
            if (isset($precision) && is_numeric($precision)) {
                $output .= sprintf("%.".$precision."f",$value);
            } else {
                $output .= $value;
            }
            break;
        case 'module':
        // TODO: evaluate if we want some other output here
            if (!empty($value) && is_numeric($value)) {
                $modinfo = xarModGetInfo($value);
                $value = $modinfo['displayname'];
            }
            $output .= xarVarPrepForDisplay($value);
            break;
        case 'itemtype':
        // TODO: evaluate if we want some other output here
            $output .= xarVarPrepForDisplay($value);
            break;
        case 'itemid':
        // TODO: evaluate if we want some other output here
            $output .= xarVarPrepForDisplay($value);
            break;
        case 'fieldtype':
            if (!empty($value) && !empty($proptypes[$value]['label'])) {
                $output .= $proptypes[$value]['label'];
            }
            break;
        case 'datasource':
        // TODO: evaluate if we want some other output here
            $output .= xarVarPrepForDisplay($value);
            break;
        case 'object':
        // TODO: evaluate if we want some other output here
            $objects = xarModAPIFunc('dynamicdata','user','getobjects');
            if (!empty($value) && !empty($objects[$value])) {
                $output .= $objects[$value]['fields']['name']['value'];
            } else {
                $output .= xarVarPrepForDisplay($value);
            }
            break;
        case 'fieldstatus':
            if (!isset($options) || !is_array($options)) {
                $options = array(
                                 array('id' => 0, 'name' => xarML('Disabled')),
                                 array('id' => 1, 'name' => xarML('Active')),
                                 array('id' => 2, 'name' => xarML('Display Only')),
                           );
            }
            if (!isset($value)) {
                $value = 1;
            }
            foreach ($options as $option) {
                if ($option['id'] == $value) {
                    $output .= xarVarPrepForDisplay($option['name']);
                    break;
                }
            }
            break;

    // output from some common hook/utility modules
        case 'categories':
            $output .= '// TODO: show categories for this item';
            break;
        case 'comments':
            $output .= '// TODO: show comments for this item';
            break;
        case 'numcomments':
            // via comments_userapi_get_count()
            if (empty($value)) {
                $output .= xarML('no comments');
            } elseif ($value == 1) {
                $output .= xarML('one comment');
            } else {
                $output .= xarML('#(1) comments',$value);
            }
            break;
        case 'hitcount':
// TODO: this doesn't increase the display count yet
            if (!empty($value)) {
                $output .= xarML('(#(1) Reads)', $value);
/* value retrieved in getall now
            } elseif (empty($modname) || empty($itemid)) {
                $output .= xarML('Please provide "modname" and "itemid" as parameters in the data-input tag');
            } elseif (xarModAPILoad('hitcount','user')) {
                $value = xarModAPIFunc('hitcount','user','get',
                                       array('modname' => $modname,
                                             'itemid' => $itemid));
                $output .= xarML('(#(1) Reads', $value);
*/
            } else {
                $output .= xarML('The hitcount module is currently unavailable');
            }
            break;
        case 'ratings':
            if (!empty($value)) {
                $output .= $value;
/* value retrieved in getall now
            } elseif (empty($modname) || empty($itemid)) {
                $output .= xarML('Please provide "modname" and "itemid" as parameters in the data-input tag');
            } elseif (xarModAPILoad('ratings','user')) {
                $value = xarModAPIFunc('ratings','user','get',
                                          array('modname' => $modname,
                                                'itemid' => $itemid,
                                                'objectid' => $itemid));
                $output .= $value;
*/
            } else {
                $output .= xarML('The ratings module is currently unavailable');
            }
            break;

        default:
            $output .= xarML('Unknown type #(1)',xarVarPrepForDisplay($typename));
            break;
    }
    return $output;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-display ...> display tags
 * Format : <xar:data-display module="123" itemtype="0" itemid="555" fieldlist="$fieldlist" static="yes" .../>
 *       or <xar:data-display fields="$fields" ... />
 * 
 * @param $args array containing the item that you want to display, or fields
 * @returns string
 * @return the PHP code needed to invoke showdisplay() in the BL template
 */
function dynamicdata_userapi_handleDisplayTag($args)
{
    $out = "xarModAPILoad('dynamicdata','user');
echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showdisplay',\n";
    if (isset($args['definition'])) {
        $out .= '                   '.$args['definition']."\n";
        $out .= '                  );';
    } else {
        $out .= "                   array(\n";
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $out .= "                         '$key' => $val,\n";
            } else {
                $out .= "                         '$key' => '$val',\n";
            }
        }
        $out .= "                         ));";
    }
    return $out;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * display an item in a template
 * 
 * @param $args array containing the item or fields to show
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showdisplay($args)
{
    extract($args);

    // optional layout for the template
    if (empty($layout)) {
        $layout = 'default';
    }
    // or optional template, if you want e.g. to handle individual fields
    // differently for a specific module / item type
    if (empty($template)) {
        $template = '';
    }

    // we got everything via template parameters
    if (isset($fields) && is_array($fields) && count($fields) > 0) {
        return xarTplModule('dynamicdata','user','showdisplay',
                            array('fields' => $fields,
                                  'layout' => $layout),
                            $template);
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($module)) {
        $modname = xarModGetName();
    } else {
        $modname = $module;
    }

    if (is_numeric($modname)) {
        $modid = $modname;
        $modinfo = xarModGetInfo($modid);
        $modname = $modinfo['name'];
    } else {
        $modid = xarModGetIDFromName($modname);
    }
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'user', 'showform', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (empty($itemtype) || !is_numeric($itemtype)) {
        $itemtype = null;
    }

    // try getting the item id via input variables if necessary
    if (!isset($itemid) || !is_numeric($itemid)) {
        $itemid = xarVarCleanFromInput('itemid');
    }

// TODO: what kind of security checks do we want/need here ?
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_READ)) {
        return '';
    }

    // check the optional field list
    if (!empty($fieldlist)) {
        // support comma-separated field list
        if (is_string($fieldlist)) {
            $myfieldlist = explode(',',$fieldlist);
        // and array of fields
        } elseif (is_array($fieldlist)) {
            $myfieldlist = $fieldlist;
        }
    } else {
        $myfieldlist = null;
    }

    // include the static properties (= module tables) too ?
    if (empty($static)) {
        $static = false;
    }

    if (empty($itemid)) {
        // we're probably dealing with a new item (no itemid yet), so
        // retrieve the properties only
        $fields = xarModAPIFunc('dynamicdata','user','getprop',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype,
                                      'fieldlist' => $myfieldlist,
                                      'static' => $static));
        if (!isset($fields) || $fields == false || count($fields) == 0) {
            return '';
        }

        // prefill the values with defaults (if any)
        foreach (array_keys($fields) as $label) {
            $fields[$label]['value'] = $fields[$label]['default'];
        }

    } else {
        // we're dealing with a real item, so retrieve the property values
        $fields = xarModAPIFunc('dynamicdata','user','getall',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype,
                                      'itemid' => $itemid,
                                      'fieldlist' => $myfieldlist,
                                      'static' => $static));
        if (!isset($fields) || $fields == false || count($fields) == 0) {
            return '';
        }
    }

    // if we are in preview mode, we need to check for any preview values
    $preview = xarVarCleanFromInput('preview');
    if (!empty($preview)) {
        foreach ($fields as $label => $field) {
            $value = xarVarCleanFromInput('dd_'.$field['id']);
            if (isset($value)) {
                $fields[$label]['value'] = $value;
            }
        }
    }

    return xarTplModule('dynamicdata','user','showdisplay',
                        array('fields' => $fields,
                              'layout' => $layout),
                        $template);
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-view ...> view tags
 * Format : <xar:data-view module="123" itemtype="0" itemids="$idlist" fieldlist="$fieldlist" static="yes" .../>
 *       or <xar:data-view items="$items" labels="$labels" ... />
 * 
 * @param $args array containing the items that you want to display, or fields
 * @returns string
 * @return the PHP code needed to invoke showview() in the BL template
 */
function dynamicdata_userapi_handleViewTag($args)
{
    $out = "xarModAPILoad('dynamicdata','user');
echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showview',\n";
    if (isset($args['definition'])) {
        $out .= '                   '.$args['definition']."\n";
        $out .= '                  );';
    } else {
        $out .= "                   array(\n";
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $out .= "                         '$key' => $val,\n";
            } else {
                $out .= "                         '$key' => '$val',\n";
            }
        }
        $out .= "                         ));";
    }
    return $out;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * list some items in a template
 * 
 * @param $args array containing the items or fields to show
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showview($args)
{
    extract($args);

    // optional layout for the template
    if (empty($layout)) {
        $layout = 'default';
    }
    // or optional template, if you want e.g. to handle individual fields
    // differently for a specific module / item type
    if (empty($template)) {
        $template = '';
    }

    // we got everything via template parameters
    if (isset($items) && is_array($items)) {
        return xarTplModule('dynamicdata','user','showview',
                            array('items' => $items,
                                  'labels' => $labels,
                                  'layout' => $layout),
                            $template);
    }

    if (!empty($object)) {
        if (is_numeric($object)) {
            $objectid = $object;
            $object = xarModAPIFunc('dynamicdata','user','getobject',
                                    array('objectid' => $objectid));
            if (isset($object)) {
                $modid = $object['moduleid']['value'];
                $itemtype = $object['itemtype']['value'];
                $param = $object['urlparam']['value'];
            }
        } else {
        // TODO: find object by name
        }
    }

    if (empty($modid)) {
        if (empty($module)) {
            $modname = xarModGetName();
        } else {
            $modname = $module;
        }
        if (is_numeric($modname)) {
            $modid = $modname;
            $modinfo = xarModGetInfo($modid);
            $modname = $modinfo['name'];
        } else {
            $modid = xarModGetIDFromName($modname);
        }
    } else {
            $modinfo = xarModGetInfo($modid);
            $modname = $modinfo['name'];
    }
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'user', 'showview', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (empty($itemtype) || !is_numeric($itemtype)) {
        $itemtype = null;
    }

// TODO: what kind of security checks do we want/need here ?
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:", ACCESS_OVERVIEW)) {
        return '';
    }

    // try getting the item id list via input variables if necessary
    if (!isset($itemids)) {
        $itemids = xarVarCleanFromInput('itemids');
    }

    // try getting the sort via input variables if necessary
    if (!isset($sort)) {
        $sort = xarVarCleanFromInput('sort');
    }

    // try getting the numitems via input variables if necessary
    if (!isset($numitems)) {
        $numitems = xarVarCleanFromInput('numitems');
    }

    // try getting the startnum via input variables if necessary
    if (!isset($startnum)) {
        $startnum = xarVarCleanFromInput('startnum');
    }

    // don't try getting the where clause via input variables, obviously !
    if (empty($where)) {
        $where = '';
    }

    // check the optional field list
    if (!empty($fieldlist)) {
        // support comma-separated field list
        if (is_string($fieldlist)) {
            $myfieldlist = explode(',',$fieldlist);
        // and array of fields
        } elseif (is_array($fieldlist)) {
            $myfieldlist = $fieldlist;
        }
        $status = null;
    } else {
        $myfieldlist = null;
        // get active properties only (+ not the display only ones)
        $status = 1;
    }

    // include the static properties (= module tables) too ?
    if (empty($static)) {
        $static = false;
    }

    // check the URL parameter for the item id used by the module (e.g. exid, aid, ...)
    if (empty($param)) {
        $param = '';
    }

    // retrieve the properties for this module / itemtype
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype,
                                  'fieldlist' => $myfieldlist,
                                  'status' => $status,
                                  'static' => $static));
    // create the label list + (try to) find the field containing the item id (if any)
    $labels = array();

    foreach ($fields as $name => $field) {
        $labels[$name] = array('label' => $field['label']);

        // TODO: let the module tell us at installation ? (or specify in the template)
        if (empty($param) && $field['type'] == 21) { // Item ID
            // take a wild guess at the parameter name this module expects
            if (!empty($field['source']) && preg_match('/_([^_]+)$/',$field['source'],$matches)) {
                $param = $matches[1];
            }
        }
    }
    if (empty($param)) {
        $param = 'itemid';
    }

    $items = array();
    if (empty($itemids)) {
        $itemids = array();
    } elseif (!is_array($itemids)) {
        $itemids = explode(',',$itemids);
    }

    $items = xarModAPIFunc('dynamicdata','user','getitems',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype,
                                 'itemids' => $itemids,
                                 'sort' => $sort,
                                 'numitems' => $numitems,
                                 'startnum' => $startnum,
                                 'where' => $where,
                                 'fieldlist' => $myfieldlist,
                                 'status' => $status,
                                 'static' => $static));

    if (!isset($items)) return xarML('No items found');

    $nexturl = '';
    $prevurl = '';
    if (!empty($numitems) && (count($items) == $numitems || $startnum > 1)) {
        // Get current URL
        $currenturl = xarServerGetCurrentURL();
        if (empty($startnum)) {
            $startnum = 1;
        }

// TODO: count items
        if (preg_match('/startnum=\d+/',$currenturl)) {
            if (count($items) == $numitems) {
                $next = $startnum + $numitems;
                $nexturl = preg_replace('/startnum=\d+/',"startnum=$next",$currenturl);
            }
            if ($startnum > 1) {
                $prev = $startnum - $numitems;
                $prevurl = preg_replace('/startnum=\d+/',"startnum=$prev",$currenturl);
            }
        } elseif (preg_match('/\?/',$currenturl)) {
            if (count($items) == $numitems) {
                $next = $startnum + $numitems;
                $nexturl = $currenturl . '&startnum=' . $next;
            }
            if ($startnum > 1) {
                $prev = $startnum - $numitems;
                $prevurl = $currenturl . '&startnum=' . $prev;
            }
        } else {
            if (count($items) == $numitems) {
                $next = $startnum + $numitems;
                $nexturl = $currenturl . '?startnum=' . $next;
            }
            if ($startnum > 1) {
                $prev = $startnum - $numitems;
                $prevurl = $currenturl . '?startnum=' . $prev;
            }
        }

/*
        $count = xarModAPIFunc('dynamicdata','user','countitems',
                               array('modid' => $modid,
                                     'itemtype' => $itemtype,
                                     'itemids' => $itemids,
                                     'sort' => $sort,
                                     'numitems' => $numitems,
                                     'startnum' => $startnum,
                                     'where' => $where,
                                     'fieldlist' => $myfieldlist,
                                     'static' => $static));
*/
    }

    // add link to display the item
    if (empty($linkfunc)) {
        $linkfunc = 'display';
    }
    if (empty($linklabel)) {
        $linklabel = xarML('Display');
    }
    if (!empty($linkfield) && isset($fields[$linkfield])) {
        foreach ($items as $itemid => $item) {
            $items[$itemid]['fields'][$linkfield]['flink'] = xarModURL($modname,'user',$linkfunc,
                                                                       array($param => $itemid,
                                                                             'itemtype' => $itemtype));
        }
    } else {
        foreach ($items as $itemid => $item) {
        // TODO: improve this + security
            $options = array();
            $options['display'] = array('otitle' => $linklabel,
                                        'olink'  => xarModURL($modname,'user',$linkfunc,
                                                              array($param => $itemid,
                                                                   'itemtype' => $itemtype)),
                                        'ojoin'  => '');
            $items[$itemid]['options'] = $options;
        }
    }

    return xarTplModule('dynamicdata','user','showview',
                        array('items' => $items,
                              'labels' => $labels,
                              'nexturl' => $nexturl,
                              'prevurl' => $prevurl,
                              'layout' => $layout),
                        $template);
}


// ----------------------------------------------------------------------
// TODO: search API, some generic queries for statistics, etc.
//

/**
 * utility function pass individual menu items to the main menu
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function dynamicdata_userapi_getmenulinks()
{
    $menulinks = array();

    if (xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_OVERVIEW)) {

        // get items from the objects table
        $objects = xarModAPIFunc('dynamicdata','user','getobjects');
        if (!isset($objects)) {
            return $menulinks;
        }
        $mymodid = xarModGetIDFromName('dynamicdata');
        foreach ($objects as $object) {
            $itemid = $object['fields']['id']['value'];
            // skip the internal objects
            if ($itemid < 3) continue;
            $modid = $object['fields']['moduleid']['value'];
            if ($modid == $mymodid) {
                $modid = null;
            }
            $itemtype = $object['fields']['itemtype']['value'];
            if ($itemtype == 0) {
                $itemtype = null;
            }
            $label = $object['fields']['label']['value'];
            $menulinks[] = Array('url'   => xarModURL('dynamicdata','user','view',
                                                      array('modid' => $modid,
                                                            'itemtype' => $itemtype)),
                                 'title' => xarML('View #(1)', $label),
                                 'label' => $label);
        }
    }

    return $menulinks;
}

/**
 * utility function to count the number of items held by this module
 *
 * @author the DynamicData module development team
 * @returns integer
 * @return number of items held by this module
 * @raise DATABASE_ERROR
 */
function dynamicdata_userapi_countitems()
{
    // Get database setup - note that both xarDBGetConn() and xarDBGetTables()
    // return arrays but we handle them differently.  For xarDBGetConn() we
    // currently just want the first item, which is the official database
    // handle.  For xarDBGetTables() we want to keep the entire tables array
    // together for easy reference later on
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // It's good practice to name the table and column definitions you are
    // getting - $table and $column don't cut it in more complex modules
    $exampletable = $xartable['example'];

    // Get item - the formatting here is not mandatory, but it does make the
    // SQL statement relatively easy to read.  Also, separating out the sql
    // statement from the Execute() command allows for simpler debug operation
    // if it is ever needed
    $sql = "SELECT COUNT(1)
            FROM $exampletable";
    $result = $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an appropriate
    // error message and return
    if ($dbconn->ErrorNo() != 0) {
        // Hint : for debugging SQL queries, you can use $dbconn->ErrorMsg()
        // to retrieve the actual database error message, and use e.g. the
        // following message :
        // $msg = xarML('Database error #(1) in query #(2) for #(3) function ' .
        //             '#(4)() in module #(5)',
        //          $dbconn->ErrorMsg(), $sql, 'user', 'countitems', 'DynamicData');
        // Don't use that for release versions, though...
        /*
        $msg = xarML('Database error for #(1) function #(2)() in module #(3)',
                    'user', 'countitems', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
        */
        // This is the API compliant way to raise a db error exception
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Obtain the number of items
    list($numitems) = $result->fields;

    // All successful database queries produce a result set, and that result
    // set should be closed when it has been finished with
    $result->Close();

    // Return the number of items
    return $numitems;
}

// ----------------------------------------------------------------------
// Short URL Support
// ----------------------------------------------------------------------

/**
 * return the path for a short URL to xarModURL for this module
 * @param $args the function and arguments passed to xarModURL
 * @returns string
 * @return path to be added to index.php for a short URL, or empty if failed
 */
function dynamicdata_userapi_encode_shorturl($args)
{
    static $objectcache = array();

    if (count($objectcache) == 0) {
        $objects = xarModAPIFunc('dynamicdata','user','getobjects');
        foreach ($objects as $object) {
            $objectcache[$object['fields']['moduleid']['value'].':'.$object['fields']['itemtype']['value']] = $object['fields']['name']['value'];
        }
    }

    // Get arguments from argument array
    extract($args);

    // check if we have something to work with
    if (!isset($func)) {
        return;
    }

    // fill in default values
    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    // make sure you don't pass the following variables as arguments too

    // default path is empty -> no short URL
    $path = '';
    // if we want to add some common arguments as URL parameters below
    $join = '?';
    // we can't rely on xarModGetName() here !
    $module = 'dynamicdata';

    // specify some short URLs relevant to your module
    if ($func == 'main') {
        $path = '/' . $module . '/';
    } elseif ($func == 'view') {
        if (!empty($objectcache[$modid.':'.$itemtype])) {
            $name = $objectcache[$modid.':'.$itemtype];
            $alias = xarModGetAlias($name);
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $path = '/' . $name . '/';
            } else {
                $path = '/' . $module . '/' . $name . '/';
            }
        } else {
            // we don't know this one...
        }
    } elseif ($func == 'display' && isset($itemid)) {
        if (!empty($objectcache[$modid.':'.$itemtype])) {
            $name = $objectcache[$modid.':'.$itemtype];
            $alias = xarModGetAlias($name);
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $path = '/' . $name . '/' . $itemid;
            } else {
                $path = '/' . $module . '/' . $name . '/' . $itemid;
            }
        } else {
            // we don't know this one...
        }
    }
    // anything else does not have a short URL equivalent

// TODO: add *any* extra args we didn't use yet here
    // add some other module arguments as standard URL parameters
    if (!empty($path)) {
        // search
        if (isset($q)) {
            $path .= $join . 'q=' . urlencode($q);
            $join = '&';
        }
        // sort
        if (isset($sort)) {
            $path .= $join . 'sort=' . $sort;
            $join = '&';
        }
        // pager
        if (isset($startnum) && $startnum != 1) {
            $path .= $join . 'startnum=' . $startnum;
            $join = '&';
        }
        // multi-page articles
        if (isset($page)) {
            $path .= $join . 'page=' . $page;
            $join = '&';
        }
    }

    return $path;
}

/**
 * extract function and arguments from short URLs for this module, and pass
 * them back to xarGetRequestInfo()
 * @param $params array containing the elements of PATH_INFO
 * @returns array
 * @return array containing func the function to be called and args the query
 *         string arguments, or empty if it failed
 */
function dynamicdata_userapi_decode_shorturl($params)
{
    static $objectcache = array();

    if (count($objectcache) == 0) {
        $objects = xarModAPIFunc('dynamicdata','user','getobjects');
        foreach ($objects as $object) {
            $objectcache[$object['fields']['name']['value']] = array('modid'    => $object['fields']['moduleid']['value'],
                                                                     'itemtype' => $object['fields']['itemtype']['value']);
        }
    }

    $args = array();

    $module = 'dynamicdata';

    // Check if we're dealing with an alias here
    if ($params[0] != $module) {
        $alias = xarModGetAlias($params[0]);
        // yup, looks like it
        if ($module == $alias) {
            if (isset($objectcache[$params[0]])) {
                $args['modid'] = $objectcache[$params[0]]['modid'];
                $args['itemtype'] = $objectcache[$params[0]]['itemtype'];
            } else {
                // we don't know this one...
                return;
            }
        } else {
            // we don't know this one...
            return;
        }
    }

    if (empty($params[1]) || preg_match('/^index/i',$params[1])) {
        if (count($args) > 0) {
            return array('view', $args);
        } else {
            return array('main', $args);
        }

    } elseif (preg_match('/^(\d+)/',$params[1],$matches)) {
        $itemid = $matches[1];
        $args['itemid'] = $itemid;
        return array('display', $args);

    } elseif (isset($objectcache[$params[1]])) {
        $args['modid'] = $objectcache[$params[1]]['modid'];
        $args['itemtype'] = $objectcache[$params[1]]['itemtype'];
        if (empty($params[2]) || preg_match('/^index/i',$params[2])) {
            return array('view', $args);
        } elseif (preg_match('/^(\d+)/',$params[2],$matches)) {
            $itemid = $matches[1];
            $args['itemid'] = $itemid;
            return array('display', $args);
        } else {
            // we don't know this one...
        }

    } else {
        // we don't know this one...
    }

    // default : return nothing -> no short URL

}

?>
