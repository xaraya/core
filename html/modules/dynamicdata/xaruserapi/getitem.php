<?php

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
 * @param $args['getobject'] flag indicating if you want to get the whole object back
 * @param $args['preview'] flag indicating if you're previewing an item
 * @returns array
 * @return array of (name => value), or false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function &dynamicdata_userapi_getitem($args)
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

	if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:$itemid")) return;

    // check the optional field list
    if (empty($fieldlist)) {
        $fieldlist = null;
    } elseif (is_string($fieldlist)) {
        // support comma-separated field list
        $fieldlist = explode(',',$fieldlist);
    }

    // limit to property fields of a certain status (e.g. active)
    if (!isset($status)) {
        $status = null;
    }

    // include the static properties (= module tables) too ?
    if (empty($static)) {
        $static = false;
    }

    $object = new Dynamic_Object(array('moduleid'  => $modid,
                                       'itemtype'  => $itemtype,
                                       'itemid'    => $itemid,
                                       'fieldlist' => $fieldlist,
                                       'status'    => $status));
    if (!isset($object) || empty($object->objectid)) return;
    if (!empty($itemid)) {
        $object->getItem();
    }
    if (!empty($preview)) {
        $object->checkInput();
    }

    if (!empty($getobject)) {
        return $object;
    }

    if (count($object->fieldlist) > 0) {
        $fieldlist = $object->fieldlist;
    } else {
        $fieldlist = array_keys($object->properties);
    }
    $fields = array();
    foreach ($fieldlist as $name) {
        $property = $object->properties[$name];
		if(xarSecurityCheck('ReadDynamicDataField',0,'Field',$property->name.':'.$property->type.':'.$property->id)) {
            $fields[$name] = $property->value;
        }
    }

    return $fields;
}

?>
