<?php

function mail_admin_createqdef($args)
{
    // Are we legitimately here
    if(!xarSecConfirmAuthKey()) return;

    // First determine whether we need to look at the name entered, or the object chosen
    if(!xarVarFetch('qdef_choose','int:1',$qdef_choose)) return;
    switch($qdef_choose) {
    case 1:  // Name entered
        $qdefNew = true;
        if(!xarVarFetch('qdef_name_enter','str:1:12',$qdefName)) return;
        break;
    case 2:  // Object chosen
        $qdefNew = false;
        if(!xarVarFetch('qdef_name_choose','id',$qdefObjectId)) return;
        // Get the name of the object from dd
        $qdefObject = xarModApiFunc('dynamicdata','user','getobject',array('objectid' => $qdefObjectId));
        if(!isset($qdefObject)) return;
        $qdefName = $qdefObject->name;
        break;
    default:
        // Wrong value, raise exception
    }
 
    if($qdefNew) {
        $xmlDef = @file_get_contents('modules/mail/xardata/qdef.xml'); // if it fails, sane check will catch it.
        // Take the xml and the objectname and try to create the object
        $qdefObjectId = xarModApiFunc('dynamicdata','util','import',array('objectname' => $qdefName, 'xml' => $xmlDef));
        if(!isset($qdefObjectId)) return;

        // The file contained itemtype -1 which needs to be corrected now.
        // We created the object successfully, register it as soon as possible (getitemtypes depends on it, for one)
        xarModSetVar('mail','queue-definition',$qdefName);
        // Get the itemtypes of the mail module
        $itemtypes = xarModApiFunc('mail','user','getitemtypes');
        // Get the max value from the keys and add one
        ksort($itemtypes); end($itemtypes);
        $newItemtype = key($itemtypes) +1;
        if($newItemtype==0) $newItemtype++; // prevent the 0 value

        $params = array('objectid' => $qdefObjectId, 'itemtype' => $newItemtype);
        $itemid = DataObjectMaster::updateObject($params);

    } else {
        // All went well, we can set the modvar now
        xarModSetVar('mail','queue-definition',$qdefName);
    }
    xarResponseRedirect(xarModUrl('mail','admin','view'));
}
?>
