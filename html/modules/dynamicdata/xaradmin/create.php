<?php

/**
 * This is a standard function that is called with the results of the
 * form supplied by xarModFunc('dynamicdata','admin','new') to create a new item
 * @param 'name' the name of the item to be created
 * @param 'number' the number of the item to be created
 */
function dynamicdata_admin_create($args)
{

    extract($args);

    if (!xarVarFetch('objectid',    'id',      $objectid,   null,   XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('modid',       'id',      $modid,      xarModGetIDFromName('dynamicdata'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype',    'int',     $itemtype,   0,      XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemid',      'id',      $itemid,     0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('preview',     'id',      $preview,    0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url',  'str:1',   $return_url,
                     xarModURL('dynamicdata', 'admin', 'view',
                               array('itemid' => $myobject->objectid)), XARVAR_NOT_REQUIRED)) return;



    if (!xarSecConfirmAuthKey()) return;

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

    xarResponseRedirect($return_url);

    // Return
    return true;
}

?>
