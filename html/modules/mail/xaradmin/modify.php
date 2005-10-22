<?php

function mail_admin_modify($args = array())
{
    if(!xarVarFetch('itemid','id',$itemid,0,XARVAR_NOT_REQUIRED)) return;
    return xarResponseRedirect(xarModUrl('mail','admin','viewqdefs',array('itemid' => $itemid)));
}
?>