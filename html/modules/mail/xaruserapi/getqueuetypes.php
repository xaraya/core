<?php
  /**
   * Return a list of queue types in a structured format, also suitable for dd validation
   *
   */
function mail_userapi_getqueuetypes($args)
{
    // We do this here because this way the queue types are translateable
    // and we can easily add qTypes later (like a /dev/null like one) without
    // the definition object changing.
    // This function is called by dd on the validation of the type property 
    // of the queues object we are using.
    $qTypes[1] = xarML('Incoming mail');
    $qTypes[2] = xarML('Outgoing mail');
    return $qTypes;
}
?>

