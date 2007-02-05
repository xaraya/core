<?php
/*
 * Map a mail item to a queue based on defined rules
 *
 * @param object msg_structure parsed out result from the mailparser class
 * @returns array of queue idents
 */
function mail_adminapi_maptoqueue($args)
{
    extract($args);
    if(!isset($msg_structure)) return;

    sys::import('xaraya.structures.sequences.queue');
    // Test mapping, map em all to the masterq
    $q = new Queue('dd',array('name' => 'masterq'));
    return $q;
}
?>
