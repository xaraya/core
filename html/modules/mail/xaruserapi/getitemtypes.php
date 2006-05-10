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
    // an itemtype (in DD), so it can be extended with the functonality of
    // other modules by hooking to ALL items (regardless of queue) or
    // to a specific queue.
    //
    // Use dd to retrieve the items of the mailqueue object
    $qdefName = xarModGetVar('mail','queue-definition');
    if(!$qdefName) {
        return $itemtypes;
        throw new Exception('Mail queue definition does not exist');
    }
    $qdefObjectInfo = xarModApiFunc('dynamicdata','user','getobjectinfo',array('name' => $qdefName));
    if(!$qdefObjectInfo) 
        return $itemtypes;

    // Shamelessly pasted from DD 
    // (we should takes this as a baseline/augmentation for getitemtypes for all mods really)

    // Get objects
    $objects = xarModAPIFunc('dynamicdata','user','getobjects');
    $modid = xarModGetIDFromName('mail');
    foreach ($objects as $id => $object) {
        // skip any object that doesn't belong to mail itself
        if ($modid != $object['moduleid']) continue;
        // Should we skip the "internal" mail objects (i.e. the queue-definition)?
        // if ($object['objectid'] == $qdefObjectInfo['objectid'] ) continue;
        $itemtypes[$object['itemtype']] = array('label' => xarVarPrepForDisplay($object['label']),
                                                'title' => xarVarPrepForDisplay(xarML('View #(1)',$object['label'])),
                                                'url'   => xarModURL('mail','user','view',array('itemtype' => $object['itemtype'])),
                                                'info'  => $object
                                               );
    }
    return $itemtypes;   
}
?>