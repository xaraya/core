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
    // The list of mail queues is retrieved through the mail api
    // For now, we construct an in and out itemtype, so we can continue
    $itemtypes[1] = array('label' => xarVarPrepForDisplay(xarML('Outgoing mail queue')),
                          'title' => xarVarPrepForDisplay(xarML('Manage outgoing mail items ')),
                          'url'   => '');
    $itemtypes[2] = array('label' => xarVarPrepForDisplay(xarML('Incoming mail queue')),
                          'title' => xarVarPrepForDisplay(xarML('Manage incoming mail queues')),
                          'url'   => '');
    return $itemtypes;    
}
?>