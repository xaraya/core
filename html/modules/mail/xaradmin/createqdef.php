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
 
    $saneDef = '1d82ec410e6a4f63254a0f16533b6f7247e7903f'; // sha1 has on std definition, update when def changes.
    if($qdefNew) {
        $xmlDef = @file_get_contents('modules/mail/xardata/qdef.xml'); // if it fails, sane check will catch it.
    } else {
        // Set the name to the default, so we can validate it against the same hash
        $qdefObject->name = 'mailqueues'; // $qdefName still holds the original
        $xmlDef = xarModApiFunc('dynamicdata','util','export', array('objectref' => $qdefObject));
    }
    if(sha1($xmlDef) != $saneDef) 
        throw new Exception('XML definition not proper');

    if($qdefNew) {
        // Take the xml and the objectname and try to create the object
        $qdefObjectId = xarModApiFunc('dynamicdata','util','import',array('objectname' => $qdefName, 'xml' => $xmlDef));
        if(!isset($qdefObjectId)) 
            throw new Exception('object creation failed');
    }
    // All went well, we can set the modvar now
    xarModSetVar('mail','queue-definition',$qdefName);
    xarResponseRedirect(xarModUrl('mail','admin','viewqueues'));
}
?>
