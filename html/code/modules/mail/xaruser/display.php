<?php

function mail_user_display($args)
{
    if(!xarVarFetch('itemid','int:1:',$itemid,0,XARVAR_NOT_REQUIRED)) return;
    if (empty($itemid)) return xarResponse::notFound();
    xarController::redirect(xarModUrl('mail','admin','view',array('itemid' => $itemid)));
}
?>
