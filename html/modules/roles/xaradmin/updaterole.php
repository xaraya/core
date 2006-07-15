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
    if (!xarVarFetch('itemtype', 'int', $itemtype, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phome', 'str', $phome, '', XARVAR_NOT_REQUIRED)) return;
	$basetype = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));
    if (!xarVarFetch('pprimaryparent', 'int', $pprimaryparent, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl', 'str', $returnurl, '', XARVAR_NOT_REQUIRED)) return;

    //we want primary parent as a name string not int
    //(apparently going from other code, and for consistency with other stored roles like default one)
    //The default should also already be set
    //Grab it here if primary parent modvar is activated
    if (!empty($pprimaryparent) && is_integer($pprimaryparent) && xarModGetVar('roles','setprimaryparent')) {
        $primaryrole = new xarRoles();
        $primaryp = $primaryrole->getRole($pprimaryparent);
        $primaryparent = $primaryp->uname;
    } else {
        $primaryparent='';
    }

    //Save the old state and type
    $roles = new xarRoles();
    $oldrole = $roles->getRole($uid);
    $oldstate = $oldrole->getState();
    $oldtype = $oldrole->getType();

    // groups dont have pw etc., and can only be active
    // TODO: what about the role itemtype?
    if ($basetype != ROLES_USERTYPE) {
        $puname = $oldrole->getUser();
        $pemail = "";
        $ppass1 = "";
        $pstate = ROLES_STATE_ACTIVE;
    } else {
        if (!xarVarFetch('puname', 'str:1:35:', $puname)) return;
        if (!xarVarFetch('pemail', 'str:1:', $pemail)) return;
        if (!xarVarFetch('ppass1', 'str:1:', $ppass1,'')) return;
        if (!xarVarFetch('ppass2', 'str:1:', $ppass2,'')) return;
        if (!xarVarFetch('pstate', 'int:1:', $pstate)) return;

        // check for duplicate username
        $user = xarModAPIFunc('roles','user','get',array('uname' => $puname));

        if (($user != false) && ($user['uid'] != $uid)) {
            throw new DuplicateException(array('user',$puname));
        }
        // check for valid username
        if ((!$puname) || !(!preg_match("/[[:space:]]/", $puname))) {
            throw new BadParameterException($puname,'The username "#(1)" contains spacing characters, this is not allowed');
        }

        // TODO: Replace with DD property type check.
        // check for valid email address
        $res = preg_match('/.*@.*/', $pemail);
        if ($res == false) throw new BadParameterException($email,'The email adress "#(1)" is invalid');

        // check for valid password
        if (strcmp($ppass1, $ppass2) != 0) throw new DuplicateException(null,'The entered passwords are not the same');
    }

    // assemble the args into an array for the API function
    $duvs = array();
    if (isset($phome) && xarModGetVar('roles','userhome'))
        $duvs['userhome'] = $phome;
    if (isset($pprimaryparent) && xarModGetVar('roles','primaryparent'))
        $duvs['primaryparent'] = $pprimaryparent;
    $pargs = array('uid' => $uid,
        'name' => $pname,
        'itemtype' => $itemtype,
        'uname' => $puname,
        'userhome' => $phome,
        'primaryparent' => $pprimaryparent,
        'email' => $pemail,
        'pass' => $ppass1,
        'state' => $pstate,
		'duvs' => $duvs,
        'basetype' => $basetype,
        );

    if (!xarModAPIFunc('roles','admin','update',$pargs)) return;

    //Change the defaultgroup var values if the name is changed
    if ($basetype == ROLES_GROUPTYPE) {
    	$defaultgroup = xarModAPIFunc('roles','user','getdefaultgroup');
        $defaultgroupuid = xarModAPIFunc('roles','user','get',
                                                     array('uname'  => $defaultgroup,
                                                           'type'   => ROLES_GROUPTYPE));
        if ($uid == $defaultgroupuid) xarModSetVar(xarModGetNameFromID(xarModGetVar('roles','defaultauthmodule')), 'defaultgroup', $pname);

        // Adjust the user count if necessary
        if ($oldtype == ROLES_USERTYPE) $oldrole->adjustParentUsers(-1);
    }else {
        // Adjust the user count if necessary
        if ($oldtype == ROLES_GROUPTYPE) $oldrole->adjustParentUsers(1);
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
