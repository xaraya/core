<?php
/*
 * Queue status management
 *
 */
function mail_admin_qstatus($args)
{
    // Security Check
    if (!xarSecurityCheck('AdminMail')) return;

    $data = array();

    // Do we have the master ?
    if(!$qdefInfo = xarModApiFunc('mail','admin','getqdef')) {
        // Redirect to the view page, which offers to create one
        xarResponseRedirect(xarModUrl('mail','admin','view'));
        return true;
    }
    // Retrieve the queues
    $queues = xarModApiFunc('mail','user','getqueues');
    $measures = array();
    $data['qtypes'] = xarModApiFunc('mail','user','getqueuetypes');
    foreach($queues as $index => $qInfo) {
        // Get some info on the Q
        $qName = 'q_'.$qInfo['name'];
        $qStore = xarModApiFunc('dynamicdata','user','getobjectinfo',array('name'=>$qName));
        if(!isset($qStore)) {
            // Not there, we know enough
            $queues[$index]['status'] = 'problematic';
            $queues[$index]['count'] = 0;
            $queues[$index]['msg'] = xarML('The storage object of this queue cannot be found ( #(1) )',$qName);
            $measures[$qInfo['name']][] = array('action' => 'createq', 'text' => xarML('Create storage and link to queue'));
        } else {
            // We have some qInfo, retrieve details
            // We have an object, so we can count the items in it.
            sys::import('xaraya.structures.sequences.queue');
            $q = new Queue('dd',array('name'=>$qName));
            $queues[$index]['count'] = $q->size;
            // Determine status
            if(!xarModApiFunc('mail','user','qisactive',$qInfo)) {
                // Queue is inactive
                $queues[$index]['status'] = 'inactive';
                $queues[$index]['msg'] = xarML('Queue is not activated');
                $measures[$qInfo['name']][] = array('action' => 'activate', 'text' => xarML('Activate the queue'));
            } else {
                // Queue is active
                $queues[$index]['status'] = 'active';
                $measures[$qInfo['name']][] = array('action' => 'deactivate', 'text' => xarML('Deactivate the queue'));

                $queues[$index]['msg'] ='No msg yet';
            }

        }
    }
    $data['authid'] = xarSecGenAuthKey();
    $data['queues'] = $queues;
    $data['measures'] = $measures;
    return $data;
}
?>
