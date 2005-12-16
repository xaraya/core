<?php
/**
 * Queue management for mail module
 *
 * @package modules
 * @subpackage mail
 * @author Marcel van der Boom <marcel@xaraya.com>
 */
function mail_admin_view($args)
{
     // Security Check
    if (!xarSecurityCheck('AdminMail')) return;
     
    // Retrieve the object which holds our queue definition
    if(!$qdefInfo = xarModApiFunc('mail','admin','getqdef')) {
        return OfferCreate();
    } else {
        $data['qdef'] = $qdefInfo;
        if(!xarVarFetch('itemid','id',$data['itemid'],0,XARVAR_NOT_REQUIRED)) return;
        return $data;
    }
}

function OfferCreate($qDef = null)
{
    $data['authid'] = xarSecGenAuthKey();
    $data['qdef_name'] = isset($qDef) ? $qDef : 'mailqueues';
    $data['qdef_method'] = 1;
    $data['qdef_create'] = array(array('id' => 1,'name' => xarML('Create new object with name')));
    $data['qdef_choose'] = array(array('id' => 2,'name' => xarML('Use an existing object')));
    return xarTplModule('mail','admin','queue-newdef',$data);
}
?>