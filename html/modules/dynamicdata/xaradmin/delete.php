<?php

/**
 * delete item
 * @param 'itemid' the id of the item to be deleted
 * @param 'confirm' confirm that this item can be deleted
 */
function dynamicdata_admin_delete($args)
{
   extract($args);
 
    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('confirm',  'isset', $confirm,   NULL, XARVAR_NOT_REQUIRED)) {return;}

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

    $myobject = new Dynamic_Object(array('moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;

    $myobject->getItem();

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
	if(!xarSecurityCheck('DeleteDynamicDataItem',1,'Item',"$modid:$itemtype:$itemid")) return;

    if (empty($confirm)) {
        $data = xarModAPIFunc('dynamicdata','admin','menu');
        $data['object'] = & $myobject;
        if ($myobject->objectid == 1) {
            $mylist = new Dynamic_Object_List(array('objectid' => $itemid));
            if (count($mylist->properties) > 0) {
                $data['related'] = xarML('Warning : there are #(1) properties and #(2) items associated with this object !', count($mylist->properties), $mylist->countItems());
            }
        }
        $data['authid'] = xarSecGenAuthKey();

        return $data;
    }

    // If we get here it means that the user has confirmed the action

    if (!xarSecConfirmAuthKey()) return;

    // special case for a dynamic object : delete its properties too // TODO: and items
// TODO: extend to any parent-child relation ?
    if ($myobject->objectid == 1) {
        $mylist = new Dynamic_Object_List(array('objectid' => $itemid));
        foreach (array_keys($mylist->properties) as $name) {
            $propid = $mylist->properties[$name]->id;
            $propid = Dynamic_Property_Master::deleteProperty(array('itemid' => $propid));
        }
    }

    $itemid = $myobject->deleteItem();

    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                  array('itemid' => $objectid)));

    // Return
    return true;

}

?>
