<?php
/**
 * Update a role
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * updaterole - update a role
 * this is an action page
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_updaterole()
{
    // Check for authorization code
//    if (!xarSecConfirmAuthKey()) return;
    if (!xarVarFetch('uid', 'int:1:', $uid)) return;
    if (!xarVarFetch('pname', 'str:1:35:', $pname)) return;
    if (!xarVarFetch('ptype', 'int', $ptype)) return;
    if (!xarVarFetch('phome', 'str', $phome, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pprimaryparent', 'int', $pprimaryparent, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl', 'str', $returnurl, '', XARVAR_NOT_REQUIRED)) return;

    //Save the old state and type
    $roles = new xarRoles();
    $oldrole = $roles->getRole($uid);
    $oldstate = $oldrole->getState();
    $oldtype = $oldrole->getType();

    // groups dont have pw etc., and can only be active
    if ($ptype == 1) {
        $puname = $oldrole->getUser();
        $pemail = "";
        $ppass1 = "";
        $pstate = 3;
    }
    else {
        if (!xarVarFetch('puname', 'str:1:35:', $puname)) return;
        if (!xarVarFetch('pemail', 'str:1:', $pemail)) return;
        if (!xarVarFetch('ppass1', 'str:1:', $ppass1,'')) return;
        if (!xarVarFetch('ppass2', 'str:1:', $ppass2,'')) return;
        if (!xarVarFetch('pstate', 'int:1:', $pstate)) return;

        // check for duplicate username
        $user = xarModAPIFunc('roles',
            'user',
            'get',
            array('uname' => $puname));

        if (($user != false) && ($user['uid'] != $uid)) {
            $msg = xarML('That username is already taken.');
            xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }
        // check for valid username
        if ((!$puname) || !(!preg_match("/[[:space:]]/", $puname))) {
            $msg = xarML('There is an error in the username');
            xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }

        if (strrpos($puname, ' ') > 0) {
            $msg = xarML('There is a space in the username');
            xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }
        // TODO: Replace with DD property type check.
        // check for valid email address
        $res = preg_match('/.*@.*/', $pemail);
        if ($res == false) {
            $msg = xarML('There is an error in the email address');
            xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }
        // check for valid password
        if (strcmp($ppass1, $ppass2) != 0) {
            $msg = xarML('The two password entries are not the same');
            xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }
    }
    $duvs = array();
    if (isset($phome) && xarModGetVar('roles','setuserhome'))
            $duvs['userhome'] = $phome;

    // assemble the args into an array for the role constructor
    $pargs = array('uid' => $uid,
        'name' => $pname,
        'type' => $ptype,
        'uname' => $puname,
        'userhome' => $phome,
        'primaryparent' => $pprimaryparent,
        'email' => $pemail,
        'pass' => $ppass1,
        'state' => $pstate,
        'duvs'=>$duvs);
    // create a role from the data
    $role = new xarRole($pargs);

   // Try to update the role to the repository and bail if an error was thrown
    if (!$role->update()) return;

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

        // Adjust the user count if necessary
        if ($oldtype == 0) $oldrole->adjustParentUsers(-1);
    }else {
        // Adjust the user count if necessary
        if ($oldtype == 1) $oldrole->adjustParentUsers(1);
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
                case ROLES_STATE_INACTIVE :
                    $mailtype = 'deactivation';
                break;
                case ROLES_STATE_NOTVALIDATED :
                    $mailtype = 'validation';
                break;
                case ROLES_STATE_ACTIVE :
                    $mailtype = 'welcome';
                break;
                case ROLES_STATE_PENDING :
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
    if (empty($returnurl)) {
	    xarResponseRedirect(xarModURL('roles', 'admin', 'modifyrole', array('uid' => $uid)));
    } else {
	    xarResponseRedirect($returnurl);
    }
}

?>