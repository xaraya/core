<?php

/**
 * updaterole - update a role
 * this is an action page
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_updaterole()
{
    // Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    // get the data from the previous page
    list($uid,
         $pname,
         $ptype,
         $puname,
         $pemail,
         $ppass1,
         $ppass2,
         $pstate) = xarVarCleanFromInput('uid',
                                       'pname',
                                       'ptype',
                                       'puname',
                                       'pemail',
                                       'ppass1',
                                       'ppass2',
                                       'pstate');

    // check for empty name
    if (empty($pname)){
        $msg = xarML('You must provide a name');
        xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
        return;
    }

    // checks specific only to users
    if ($ptype == 0) {

        // check for empty username
        if (empty($puname)){
            $msg = xarML('You must provide a preferred username');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }

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
        if ((!$puname) || !(!preg_match("/[[:space:]]/",$puname))) {
            $msg = xarML('There is an error in username');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }

        if (strlen($puname) > 255) {
            $msg = xarML('username is too long.');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }

        if (strrpos($puname,' ') > 0) {
            $msg = xarML('There is a space in username');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }

        // check for empty email address
        if (empty($pemail)){
            $msg = xarML('You must provide a valid email address');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }

        //TODO: Replace with DD property type check.
        // check for valid email address
        $res = preg_match( '/.*@.*/',$pemail);
        if($res == false) {
            $msg = xarML('There is an error in email address');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }

    // check for valid password
        if(strcmp($ppass1,$ppass2) != 0) {
            $msg = xarML('The two password entries are not the same');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }
    }

    // assemble the args into an array for the role constructor
    $pargs = array('uid'=>$uid,
                    'name'=>$pname,
                    'type'=>$ptype,
                    'uname'=>$puname,
                    'email'=>$pemail,
                    'pass'=>$ppass1,
                    'state'=>$pstate);

    // create a role from the data
    $role = new xarRole($pargs);

    //Try to update the role to the repository and bail if an error was thrown
    $modifiedrole = $role->update();
    if (!$modifiedrole) {return;}

    // redirect to the next page
    xarResponseRedirect(xarModURL('roles', 'admin', 'newrole'));
}

?>