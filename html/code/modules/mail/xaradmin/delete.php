<?php

function mail_admin_delete($args = array())
{
    // Are we legitimally here?
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    // Security check
    if (!xarSecurityCheck('AdminMail')) return; 
    // Required parameters
    if(!xarVarFetch('itemid','id',$itemid)) return;
    if(!xarVarFetch('objectid','id',$objectid)) return;

    $qdefObject = xarMod::apiFunc('dynamicdata','user','getobject',array('objectid' => $objectid));
    if(!$qdefObject) return;

    $result = $qdefObject->deleteItem(array('itemid' => $itemid));
    if(!$result) return;

    return xarController::redirect(xarModUrl('mail','admin','view'));
}
?>
