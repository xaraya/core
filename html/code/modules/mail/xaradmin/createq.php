<?php

function mail_admin_createq($args)
{
    // Are we allowed to be here?
    if (!xarSecurityCheck('AdminMail')) return;
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // What do we need to do
    if(!xarVarFetch('name','str:1:12',$qName)) return;

    // Do we have the master ?
    if(!$qdefInfo = xarMod::apiFunc('mail','admin','getqdef')) {
        // Redirect to the view page, which offers to create one
        xarResponse::redirect(xarModUrl('mail','admin','view'));
        return true;
    }

    // Seems ok, call the create function
    $qData = xarMod::apiFunc('mail','admin','createq',array('name' => $qName));
    if(!$qData) return; // exception
    
    // Show the status screen again, 
    xarResponse::redirect(xarModUrl('mail','admin','qstatus'));
    return true;
}
?>