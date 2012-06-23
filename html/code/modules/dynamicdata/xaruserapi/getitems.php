<?php
/**
 * Get all dynamic data fields for a list of items
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * get all dynamic data fields for a list of items
 * (identified by module + item type or table, and item ids or other search criteria)
 *
 * @author the DynamicData module development team
 * @param array    $args array of optional parameters<br/>
 *        string   $args['module'] module name of the item fields to get, or<br/>
 *        integer  $args['module_id'] module id of the item fields to get +<br/>
 *        integer  $args['itemtype'] item type of the item fields to get, or<br/>
 *        string   $args['table'] database table to turn into an object<br/>
 *        array    $args['itemids'] array of item ids to return<br/>
 *        array    $args['fieldlist'] array of field labels to retrieve (default is all)<br/>
 *        integer  $args['status'] limit to property fields of a certain status (e.g. active)<br/>
 *        string   $args['join'] join a module table to the dynamic object (if it extends the table)<br/>
 *        string   $args['table'] make some database table available via DD (without pre-defined object)<br/>
 *        integer  $args['catid'] select in some category<br/>
 *        string   $args['sort'] sort field(s)<br/>
 *        integer  $args['numitems'] number of items to retrieve<br/>
 *        integer  $args['startnum'] start number<br/>
 *        string   $args['where'] WHERE clause to be used as part of the selection<br/>
 *        boolean  $args['getobject'] flag indicating if you want to get the whole object back
 * @return array of (itemid => array of (name => value)), or false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function &dynamicdata_userapi_getitems(Array $args=array())
{
    extract($args);
    $nullreturn = null;
    if (empty($module_id)) {
        if (empty($module)) {
            $modname = xarModGetName();
        } else {
            $modname = $module;
        }
        if (is_numeric($modname)) {
            $module_id = $modname;
        } else {
            $module_id = xarMod::getRegID($modname);
        }
    }
    $modinfo = xarMod::getInfo($module_id);

    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($module_id) || !is_numeric($module_id) || empty($modinfo['name'])) {
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

    if (empty($itemids)) {
        $itemids = array();
    } elseif (!is_array($itemids)) {
        $itemids = explode(',',$itemids);
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

    $object = & DataObjectMaster::getObjectList(array('moduleid'  => $module_id,
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
    if (!isset($object) || (empty($object->objectid) && empty($object->table))) return $nullreturn;
    if (!$object->checkAccess('view'))
        return $nullreturn;

    if (!empty($getobject)) {
        $object->getItems();
        return $object;
    } else {
        $result = $object->getItems();
        return $result;
    }
}

?>