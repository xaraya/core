<?php

/**
 * updaterole - update a role
 * this is an action page
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_updaterole()
{
    // Check for authorization code
    if (!xarSecConfirmAuthKey()) return;
    if (!xarVarFetch('uid', 'int:1:', $uid)) return;
    if (!xarVarFetch('pname', 'str:1:35:', $pname)) return;
    if (!xarVarFetch('ptype', 'str:1:35:', $ptype)) return;
    // checks specific only to users
    if ($ptype == 1) {
        $puname = "";
        $pemail = "";
        $ppass1 = "";
        $pstate = 0;
    }
    else {
        if (!xarVarFetch('puname', 'str:1:35:', $puname)) return;
        if (!xarVarFetch('pemail', 'str:1:', $pemail)) return;
        if (!xarVarFetch('ppass1', 'str:1:', $ppass1,'')) return;
        if (!xarVarFetch('ppass2', 'str:1:', $ppass2,'')) return;
        if (!xarVarFetch('pstate', 'str:1:', $pstate)) return;
        // check for duplicate username
        $user = xarModAPIFunc('roles',
            'user',
            'get',
            array('uname' => $puname));

        if (($user != false) && ($user['uid'] != $uid)) {
            $msg = xarML('That username is already taken.');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }
        // check for valid username
        if ((!$puname) || !(!preg_match("/[[:space:]]/", $puname))) {
            $msg = xarML('There is an error in username');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }

        if (strrpos($puname, ' ') > 0) {
            $msg = xarML('There is a space in username');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }
        // TODO: Replace with DD property type check.
        // check for valid email address
        $res = preg_match('/.*@.*/', $pemail);
        if ($res == false) {
            $msg = xarML('There is an error in email address');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }
        // check for valid password
        if (strcmp($ppass1, $ppass2) != 0) {
            $msg = xarML('The two password entries are not the same');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }
        //Save the old state
        $user = xarModAPIFunc('roles',
            'user',
            'get',
            array('uid' => $uid));
        $oldstate = $user['state'];
    }
    // assemble the args into an array for the role constructor
    $pargs = array('uid' => $uid,
        'name' => $pname,
        'type' => $ptype,
        'uname' => $puname,
        'email' => $pemail,
        'pass' => $ppass1,
        'state' => $pstate);
    // create a role from the data
    $role = new xarRole($pargs);
    // Try to update the role to the repository and bail if an error was thrown
    $modifiedrole = $role->update();
    if (!$modifiedrole) {
        return;
    }


    // call item update hooks (for DD etc.)
// TODO: move to update() function
    $pargs['module'] = 'roles';
    $pargs['itemtype'] = $ptype; // we might have something separate for groups later on
    $pargs['itemid'] = $uid;
    xarModCallHooks('item', 'update', $uid, $pargs);

    //Change the defaultgroup var values if the name is changed
    if ($ptype == 1) {
        $defaultgroup = xarModGetVar('roles', 'defaultgroup');
        $defaultgroupuid = xarModAPIFunc('roles','user','get',
                                                     array('uname'  => $defaultgroup,
                                                           'type'   => 1));
        if ($uid == $defaultgroupuid) xarModSetVar('roles', 'defaultgroup', $pname);
    }
    else {
        //TODO : Be able to send 2 email if both password and type has changed... (or an single email with a overall msg...)
        //Ask to send email if the password has changed
        if ($ppass1 != '') {
            if (xarModGetVar('roles', 'askpasswordemail')) {
                xarSessionSetVar('tmppass',$ppass1);
                xarResponseRedirect(xarModURL('roles', 'admin', 'asknotification',
                array('uid' => array($uid => '1'), 'mailtype' => 'password')));
            }
            //TODO : If askpasswordemail is false, the user won't know his new password...
        }
        //Ask to send email if the state has changed
        if ($user['state'] != $pstate) {
            //Get the notice message
            switch ($pstate) {
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
            }
            if (xarModGetVar('roles', 'ask'.$mailtype.'email')) {
                xarResponseRedirect(xarModURL('roles', 'admin', 'asknotification',
                              array('uid' => array($uid => '1'), 'mailtype' => $mailtype)));
            }
            //TOTHINK : If ask$mailtypeemail is false, the user won't know his new state...
        }
    }

    // redirect to the next page
    xarResponseRedirect(xarModURL('roles', 'admin', 'modifyrole', array('uid' => $uid)));
}

?>
