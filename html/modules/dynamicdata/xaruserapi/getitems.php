<?php
/**
 * Get all dynamic data fields for a list of items
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * get all dynamic data fields for a list of items
 * (identified by module + item type or table, and item ids or other search criteria)
 *
 * @author the DynamicData module development team
 * @param string $args['module'] module name of the item fields to get, or
 * @param int $args['modid'] module id of the item fields to get +
 * @param int $args['itemtype'] item type of the item fields to get, or
 * @param $args['table'] database table to turn into an object
 * @param array $args['itemids'] array of item ids to return
 * @param array $args['fieldlist'] array of field labels to retrieve (default is all)
 * @param $args['status'] limit to property fields of a certain status (e.g. active)
 * @param $args['join'] join a module table to the dynamic object (if it extends the table)
 * @param $args['table'] make some database table available via DD (without pre-defined object)
 * @param int $args['catid'] select in some category
 * @param string $args['sort'] sort field(s)
 * @param int $args['numitems'] number of items to retrieve
 * @param int $args['startnum'] start number
 * @param $args['where'] WHERE clause to be used as part of the selection
 * @param bool $args['getobject'] flag indicating if you want to get the whole object back
 * @return array of (itemid => array of (name => value)), or false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function &dynamicdata_userapi_getitems($args)
{
    extract($args);
    $nullreturn = null;
    if (empty($modid) && empty($moduleid)) {
        if (empty($module)) {
            $modname = xarModGetName();
        } else {
            $modname = $module;
        }
        if (is_numeric($modname)) {
            $modid = $modname;
        } else {
            $modid = xarModGetIDFromName($modname);
        }
    } elseif (empty($modid)) {
        $modid = $moduleid;
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
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'user', 'getitems', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:All")) return $nullreturn;

    if (empty($itemids)) {
        $itemids = array();
    } elseif (!is_array($itemids)) {
        $itemids = explode(',',$itemids);
    }

    foreach ($itemids as $itemid) {
        if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:$itemid")) return $nullreturn;
    }

    // check the optional field list
    if (empty($fieldlist)) {
        $fieldlist = null;
    } elseif (is_string($fieldlist)) {
        $fieldlist = explode(',',$fieldlist);
    }

    // limit to property fields of a certain status (e.g. active)
    if (!isset($status)) {
        $status = null;
    }

    if (empty($startnum) || !is_numeric($startnum)) {
        $startnum = 1;
    }
    if (empty($numitems) || !is_numeric($numitems)) {
        $numitems = 0;
    }

    if (empty($sort)) {
        $sort = null;
    }
    if (empty($where)) {
        $where = null;
    }
    if (empty($groupby)) {
        $groupby = null;
    }

    // join a module table to a dynamic object
    if (empty($join)) {
        $join = '';
    }
    // make some database table available via DD
    if (empty($table)) {
        $table = '';
    }
    // select in some category
    if (empty($catid)) {
        $catid = '';
    }

    $object = & Dynamic_Object_Master::getObjectList(array('moduleid'  => $modid,
                                           'itemtype'  => $itemtype,
                                           'itemids' => $itemids,
                                           'sort' => $sort,
                                           'numitems' => $numitems,
                                           'startnum' => $startnum,
                                           'where' => $where,
                                           'fieldlist' => $fieldlist,
                                           'join' => $join,
                                           'table' => $table,
                                           'catid' => $catid,
                                           'groupby' => $groupby,
                                           'status' => $status));
    if (!isset($object) || empty($object->objectid)) return $nullreturn;

    if (!isset($object)) return $nullreturn;
    // $items[$itemid]['fields'][$name]['value'] --> $items[$itemid][$name] now

    if (!empty($getobject)) {
        $object->getItems();
        return $object;
    } else {
        $result = $object->getItems();
        return $result;
    }
}

?>
