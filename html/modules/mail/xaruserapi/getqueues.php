<?php

function mail_userapi_getqueues($args)
{
    // Queues are different from the itemtypes here, in the sense
    // that we want the registered queues, which may or may not be an
    // itemtypes of mail yet. In short, the items of the qDef object

    // Do we have the master ?
    if(!$qdefInfo = xarModApiFunc('mail','admin','getqdef')) {
        // Redirect to the view page, which offers to create one
        xarResponseRedirect(xarModUrl('mail','admin','view'));
        return true;
    }
    $params = array('modid' => $qdefInfo['moduleid'],'itemtype' => $qdefInfo['itemtype']);
    $queues = xarModApiFunc('dynamicdata','user','getitems',$params);

    return $queues;
}
?>