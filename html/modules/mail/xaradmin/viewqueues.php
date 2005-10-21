<?php
/**
 * Queue management for mail module
 *
 * @package modules
 * @subpackage mail
 * @author Marcel van der Boom <marcel@xaraya.com>
 */
function mail_admin_viewqueues($args)
{
     // Security Check
    if (!xarSecurityCheck('AdminMail')) return;
    /*
     * What this should do:
     * 1. Is there a definition to manage queues in?
     * 2. If so, retrieve the queues.
     * 3. If not, offer to create a queue definition
     */
     
    // Retrieve the object which holds our queue definition
    $qDef = xarModGetVar('mail','queue-definition');
    //if($qDef != NULL) {
        // Object was found, try to fetch it.
        //$qDefObject = xarModApiFunc('mail','admin','getqdef',array('name' => $qDef));
        //die('object found');
        //} else {
        // Nothing found for sure, offer to create one.
        $data['authid'] = xarSecGenAuthKey();
        $data['qdef_name'] = isset($qDef) ? $qDef : 'mailqueues';
        $data['qdef_method'] = 1;
        $data['qdef_create'] = array(array('id' => 1,'name' => xarML('Create a queue with name')));
        $data['qdef_choose'] = array(array('id' => 2,'name' => xarML('Use object')));
        return xarTplModule('mail','admin','queue-newdef',$data);
        //}
}
?>