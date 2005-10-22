<?php

function mail_user_displayqdef($args)
{
    if(!xarVarFetch('itemid','id',$itemid,0,XARVAR_NOT_REQUIRED)) return;
    xarResponseRedirect(xarModUrl('mail','admin','viewqdefs',array('itemid' => $itemid)));
}
?>
