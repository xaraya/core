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
    $data['qtypes'] = xarModApiFunc('mail','user','getqueuetypes');
    foreach($queues as $index => $qInfo) {
        // Augment them with status info
        $queues[$index]['status'] = 'active';
        $queues[$index]['count'] = rand(0,1000);
        $queues[$index]['msg'] ='No msg yet';
    }
    $data['queues'] = $queues;
    //var_dump($queues);die();
    return $data;
}
?>
