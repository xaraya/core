<?php

/**
 * modify an item
 * This is a standard function that is called whenever an administrator
 * wishes to modify a current module item
 * @param 'exid' the id of the item to be modified
 */
function dynamicdata_admin_modify($args)
{
    list($objectid,
         $modid,
         $itemtype,
         $itemid)= xarVarCleanFromInput('objectid',
                                        'modid',
                                        'itemtype',
                                        'itemid');
    extract($args);

    if (empty($itemid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'item id', 'admin', 'modify', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
	if(!xarSecurityCheck('EditDynamicDataItem',1,'Item',"$modid:$itemtype:$itemid")) return;

    $data = xarModAPIFunc('dynamicdata','admin','menu');

    $myobject = new Dynamic_Object(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    $myobject->getItem();
    $data['object'] = & $myobject;

    $data['objectid'] = $myobject->objectid;
    $data['itemid'] = $itemid;
    $data['authid'] = xarSecGenAuthKey();

    return $data;
}

?>