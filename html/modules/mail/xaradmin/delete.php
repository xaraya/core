<?php

function mail_admin_delete($args)
{
    // Are we legitimally here?
    if(!xarSecConfirmAuthKey()) return;
    // Security check
    if (!xarSecurityCheck('AdminMail')) return; 
    // Required parameters
    if(!xarVarFetch('itemid','id',$itemid)) return;
    if(!xarVarFetch('objectid','id',$objectid)) return;

    $qdefObject = xarModApiFunc('dynamicdata','user','getobject',array('objectid' => $objectid));
    if(!$qdefObject) return;

    $result = $qdefObject->deleteItem(array('itemid' => $itemid));
    if(!$result) return;

    return xarResponseRedirect(xarModUrl('mail','admin','viewqdefs'));
}
?>