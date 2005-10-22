<?php

function mail_user_display($args)
{
    if(!xarVarFetch('itemid','id',$itemid,0,XARVAR_NOT_REQUIRED)) return;
    xarResponseRedirect(xarModUrl('mail','admin','view',array('itemid' => $itemid)));
}
?>
