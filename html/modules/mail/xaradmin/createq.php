<?php

function mail_admin_createq($args)
{
    // Are we allowed to be here?
    if (!xarSecurityCheck('AdminMail')) return;
    if(!xarSecConfirmAuthKey()) return; 

    // What do we need to do
    if(!xarVarFetch('name','str:1:12',$qName)) return;

    // Do we have the master ?
    if(!$qdefInfo = xarModApiFunc('mail','admin','getqdef')) {
        // Redirect to the view page, which offers to create one
        xarResponseRedirect(xarModUrl('mail','admin','view'));
        return true;
    }

    // Seems ok, call the create function
    $qData = xarModApiFunc('mail','admin','createq',array('name' => $qName));
    if(!$qData) return; // exception
    
    // Show the status screen again, 
    xarResponseRedirect(xarModUrl('mail','admin','qstatus'));
    return true;
}
?>