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
        $qdefObject = xarModApiFunc('dynamicdata','user','getobjectinfo',array('objectid' => $qdefObjectId));
        if(!isset($qdefObject)) return;
        $qdefName = $qdefObject['name'];
        break;
    default:
        // Wrong value, raise exception
    }
    xarModSetVar('mail','queue-definition',$qdefName);
 
    // k, name known now, create it if needed
    if($qdefNew) {
        $sane = '3de31ebcd985f169f4d6948340b601672e138ac8'; // let's honour monotone :-)
        $xmlDef = file_get_contents('modules/mail/xardata/qdef.xml');
        if(sha1($xmlDef) != $sane) {
            // TODO: raise 
            die('XML definition not proper');
        }
        
    }
    xarResponseRedirect(xarModUrl('mail','admin','viewqueues'));
}
?>
