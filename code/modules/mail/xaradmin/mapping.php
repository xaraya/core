<?php
/* Map a msg structure to a queue
 *
 * This shows the mapping screen for mapping
 * messages onto a queue. The mapping is done
 * based on (simple) rules.
*/
function mail_admin_mapping($args)
{
    // Construct the list of queues.
    $queues = xarModApiFunc('mail','user','getitemtypes');
    $data = array(); 
    foreach($queues as $id => $props) {
        $data['qlist'][] = array('id' => $id, 'name' => $props['label']);
    }
    return $data;
}
?>