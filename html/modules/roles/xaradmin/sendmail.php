<?php

function roles_admin_sendmail()
{ 
    // Get parameters from whatever input we need
    if (!xarVarFetch('uid', 'int:0:', $uid, 0)) return;
    if (!xarVarFetch('state', 'int:0:', $state, 0)) return;
    if (!xarVarFetch('groupuid', 'int:0:', $groupuid, 0)) return;
    if (!xarVarFetch('message', 'str:1:', $message)) return;
    if (!xarVarFetch('subject', 'str:1', $subject)) return; 
    
    // Confirm authorisation code.
    if (!xarSecConfirmAuthKey()) return; 
    // Security check
    if (!xarSecurityCheck('MailRoles')) return; 
    // Get user information
    
    $roles = new xarRoles();
    if ($uid != 0) {
    	$role = $roles->getRole($uid);
    	//$user = xarModAPIFunc('roles','user','get', array('uid' => $uid));
    	//verify if it's not frozen
    	if (xarSecurityCheck('EditRole',0,'Roles',$role->getName()))  {
    		//send the mail
    		if (!xarModAPIFunc('mail',
                'admin',
                'sendmail',
                array('info' => $role->getEmail(),
                    'name' => $role->getName(),
                    'subject' => $subject,
                    'message' => $message))) return;
    	}
    	 
         if ($groupuid == 0) $groupuid = $role->getParents();
    }
    else {
    	if ($groupuid == 0) {
    		$users = xarModAPIFunc('roles','user','getall', array('state' => $state));
    		foreach ($users as $user) {
    			//verify if it's not frozen
	    		if (xarSecurityCheck('EditRole',0,'Roles',$user['name'])){
	    			//send the mail
	    			if (!xarModAPIFunc( 'mail',
                					'admin',
                					'sendmail',
					                array('info' => $user['email'],
					                    'name' => $user['name'],
					                    'subject' => $subject,
					                    'message' => $message))) return;
		    	}
		    	
    		}
    	} else {
		    $role = $roles->getRole($groupuid);
	    	$users = $role->getUsers($state);
	    	//foreach ($users as $user) {
	    	while (list($key, $user) = each($users)) {
    			//verify if it's not frozen
	    		if (xarSecurityCheck('EditRole',0,'Roles',$user->getName())){
	    			//send the mail
	    			if (!xarModAPIFunc( 'mail',
                					'admin',
                					'sendmail',
					                array('info' => $user->getEmail(),
					                    'name' => $user->getName(),
					                    'subject' => $subject,
					                    'message' => $message))) return;
	    		}	                    
    		}
    	}
    }
    xarResponseRedirect(xarModURL('roles', 'admin', 'showusers', array('uid' => $groupuid, 'state' => $state))); 
    // Return
    return true;
} 

?>