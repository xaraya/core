<?php

function mail_admin_modify($args = array())
{
    if(!xarVarFetch('itemid','int:1:',$itemid,0,XARVAR_NOT_REQUIRED)) return;
    if (empty($itemid)) return xarResponse::notFound();
    return xarController::redirect(xarModUrl('mail','admin','view',array('itemid' => $itemid)));
}
?>
