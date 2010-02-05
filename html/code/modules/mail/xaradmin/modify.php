<?php

function mail_admin_modify($args = array())
{
    if(!xarVarFetch('itemid','id',$itemid,0,XARVAR_NOT_REQUIRED)) return;
    return xarResponse::redirect(xarModUrl('mail','admin','view',array('itemid' => $itemid)));
}
?>
