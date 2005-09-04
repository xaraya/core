<?php

/**
 * update users from roles_admin_showusers
 */
function roles_admin_updatestate()
{
	// Security Check
    if (!xarSecurityCheck('EditRole')) return;
	// Get parameters
    if(!xarVarFetch('status', 'int:0:', $data['status'],  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('state', 'int:0:', $data['state'], 0, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('groupuid', 'int:0:', $data['groupuid'], 1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('updatephase', 'str:1:', $updatephase, 'update', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if(!xarVarFetch('uids', 'isset', $uids, NULL, XARVAR_NOT_REQUIRED)) return;
    $data['authid'] = xarSecGenAuthKey();
    // invalid fields (we'll check this below)
    // check if the username is empty
    //Note : We should not provide xarML here. (should be in the template for better translation)
    //Might be additionnal advice about the invalid var (but no xarML..)
    if (!isset($uids)) {
       $invalid = xarML('You must choose the users to change their state');
    }
     if (isset($invalid)) {
        // if so, return to the previous template
        return xarResponseRedirect(xarModURL('roles','admin', 'showusers', array('authid' => $data['authid'],
                                                                  'state'     => $data['state'],
                                                                  'invalid'    => $invalid,
                                                                  'uid' => $data['groupuid'])));
	}
    //Get the notice message
    switch ($data['status']) {
        case 1 :
        	$mailtype = 'deactivation';
        break;
        case 2 :
        	$mailtype = 'validation';
        break;
        case 3 :
        	$mailtype = 'welcome';
        break;
        case 4 :
        	$mailtype = 'pending';
        break;
        default:
        	$mailtype = 'blank';
        break;
    }
		    
    if ( (!isset($uids)) || (!isset($data['status']))
    || (!is_numeric($data['status'])) || ($data['status'] < 1) || ($data['status'] > 4) ) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)','parameters', 'admin', 'updatestate', 'Roles');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',new SystemException($msg." -- ".$uids." -- ".$data['status']));
        return;
    }
    $roles = new xarRoles();
    $uidnotify = array();
    foreach ($uids as $uid => $val) {
        //check if the user must be updated :
		$role = $roles->getRole($uid);
        if ($role->getState() != $data['status']) {
	    	//Update the user
	    	if (!xarModAPIFunc('roles',
	                          'admin',
	                          'stateupdate',
	                          array('uid' => $uid, 'state' => $data['status']))) return;
	        $uidnotify[$uid] = 1;
        }
    }
    $uids = $uidnotify;
    // Success
     if ((!xarModGetVar('roles', 'ask'.$mailtype.'email')) || (count($uidnotify) == 0)) {
			xarResponseRedirect(xarModURL('roles', 'admin', 'showusers',
                          array('uid' => $data['groupuid'], 'state' => $data['state'])));
            return true;
     }
     else {
     	xarResponseRedirect(xarModURL('roles', 'admin', 'asknotification',
                          array('uid' => $uids, 'mailtype' => $mailtype, 'groupuid' => $data['groupuid'], 'state' => $data['state'])));
     }
}
?>
