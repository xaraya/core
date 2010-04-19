<?php

function mail_admin_delete($args = array())
{
    // Are we legitimally here?
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    // Security check
    if (!xarSecurityCheck('ManageMail')) return; 
    // Required parameters
    if(!xarVarFetch('itemid','int:1:',$itemid, 0, XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('objectid','int:1:',$objectid, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($itemid) || empty($objectid)) return xarResponse::notFound();

    $qdefObject = xarMod::apiFunc('dynamicdata','user','getobject',array('objectid' => $objectid));
    if(!$qdefObject) return;

    $result = $qdefObject->deleteItem(array('itemid' => $itemid));
    if(!$result) return;

    return xarResponse::redirect(xarModUrl('mail','admin','view'));
}
?>