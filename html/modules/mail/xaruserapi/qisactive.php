<?php
/*
 * Determine wether a mail queue is active
 *
 * @param $qInfo in args as returned by getobjectinfo of dd
 * @returns bool 
 */
function mail_userapi_qisactive($args)
{
    extract($args);
    if(!isset($objectid)) return false; // we're lazy

    $params = array('objectid' => $objectid, 'fieldlist' => array('id'));
    $props = xarModApiFunc('dynamicdata','user','getprop',$params);
    if(isset($props['id'])) return true;
    return false;
}
?>