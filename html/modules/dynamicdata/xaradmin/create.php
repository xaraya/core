<?php

/**
 * This is a standard function that is called with the results of the
 * form supplied by xarModFunc('dynamicdata','admin','new') to create a new item
 * @param 'name' the name of the item to be created
 * @param 'number' the number of the item to be created
 */
function dynamicdata_admin_create($args)
{
    list($objectid,
         $modid,
         $itemtype,
         $itemid,
         $return_url,
         $preview) = xarVarCleanFromInput('objectid',
                                          'modid',
                                          'itemtype',
                                          'itemid',
                                          'return_url',
                                          'preview');
    extract($args);

    if (!xarSecConfirmAuthKey()) return;

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (empty($itemid)) {
        $itemid = 0;
    }
    if (empty($preview)) {
        $preview = 0;
    }

    $myobject = new Dynamic_Object(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    $isvalid = $myobject->checkInput();

    if (!empty($preview) || !$isvalid) {
        $data = xarModAPIFunc('dynamicdata','admin','menu');

        $data['object'] = & $myobject;

        $data['authid'] = xarSecGenAuthKey();
        $data['preview'] = $preview;
        return xarTplModule('dynamicdata','admin','new', $data);
    }

    $itemid = $myobject->createItem();

    if (empty($itemid)) return; // throw back

    if (!empty($return_url)) {
        xarResponseRedirect($return_url);
    } else {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('itemid' => $myobject->objectid)));
    }

    // Return
    return true;
}

?>
