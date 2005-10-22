<?php
  /**
   * Return the itemtypes for the mail module
   *
   */

function mail_userapi_getitemtypes($args)
{
    $itemtypes = array();

    // The mail module defines an item as a mail message. Simply
    // put, mail can be incoming or outgoing.  To be able to hook
    // modules into a subset of messages we define the concept of
    // queues which contain sets of messages. Each queue gets assigned
    // an itemtype, so it can be extended with the functonality of
    // other modules by hooking to ALL items (regardless of queue) or
    // to a specific queue.
    //
    // Use dd to retrieve the items of the mailqueue object
    $qdefName = xarModGetVar('mail','queue-definition');
    if(!$qdefName) throw new Exception('Mail queue definition does not exist');
    $qdefObjectInfo = xarModApiFunc('dynamicdata','user','getobjectinfo',array('name' => $qdefName));
    if(!$qdefObjectInfo) return;

    $args = array( 'itemtype' => $qdefObjectInfo['itemtype'],
                   'module' => 'mail');
    $items = xarModApiFunc('dynamicdata','user','getitems',$args);
 
    foreach($items as $id => $props) {
        $itemtypes[$id] = array('label' => xarVarPrepForDisplay($props['name']),
                                'title' => xarVarPrepForDisplay($props['description']),
                                'url'   => '');
    }
    return $itemtypes;    
}
?>