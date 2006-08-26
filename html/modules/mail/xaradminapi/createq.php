<?php


function mail_adminapi_createq($args)
{
    // Security Check
    if (!xarSecurityCheck('AdminMail')) return;

    extract($args);

    // Create a new queue storage object from the xml definition
    $xmlDef = file_get_contents('modules/mail/xardata/qdatadef.xml');
    $qdataObjectId = xarModApiFunc('dynamicdata','util','import',array('objectname' => 'q_'.$name, 'xml' => $xmlDef));
    if(!isset($qdataObjectId)) return;

    // Get the itemtypes of the mail module
    $itemtypes = xarModApiFunc('mail','user','getitemtypes');
    // Get the max value from the keys and add one
    ksort($itemtypes); end($itemtypes);
    $newItemtype = key($itemtypes) +1;
    if($newItemtype==0) $newItemtype++; // prevent the 0 value
    // Create a new itemtype by creating a new object in dd
    $params = array('objectid' => $qdataObjectId, 'itemtype' => $newItemtype);
    $itemid = Dynamic_Object_Master::updateObject($params);
    
    // Itemtype changed, resync the props
    $params['moduleid'] = 771; // mail module
    if (!xarModAPIFunc('dynamicdata','admin','syncprops',$params)) return;

    return true;
}
?>