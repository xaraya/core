<?php

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
 * @param $args['getobject'] flag indicating if you want to get the whole object back
 * @returns array
 * @return array of (itemid => array of (name => value)), or false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function &dynamicdata_userapi_getitems($args)
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

	if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:All")) return;

    if (empty($itemids)) {
        $itemids = array();
    } elseif (!is_array($itemids)) {
        $itemids = explode(',',$itemids);
    }

    foreach ($itemids as $itemid) {
		if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:$itemid")) return;
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
        $numitems = 0;
    }

    if (empty($sort)) {
        $sort = null;
    }
    if (empty($where)) {
        $where = null;
    }

    $object = new Dynamic_Object_List(array('moduleid'  => $modid,
                                           'itemtype'  => $itemtype,
                                           'itemids' => $itemids,
                                           'sort' => $sort,
                                           'numitems' => $numitems,
                                           'startnum' => $startnum,
                                           'where' => $where,
                                           'fieldlist' => $fieldlist,
                                           'status' => $status));
    if (!isset($object)) return;
    // $items[$itemid]['fields'][$name]['value'] --> $items[$itemid][$name] now

    if (!empty($getobject)) {
        $object->getItems();
        return $object;
    } else {
        return $object->getItems();
    }
}

?>
