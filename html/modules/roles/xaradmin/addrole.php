<?php

/**
 * addRole - add a role
 * This function tries to create a user and provides feedback on the
 * result.
 * @author Jan Schrage, Marc Lutolf
 */
function roles_admin_addrole()
{

    // Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    // get some vars for both groups and users
        list($pname,
        $ptype,
        $pparentid) = xarVarCleanFromInput('pname',
                                            'ptype',
                                            'pparentid');
    // get the rest for users only
    // TODO: need to see what to do with auth_module
    if ($ptype == 0){
        list($puname,
            $pemail,
            $ppass1,
            $ppass2,
            $pstate) = xarVarCleanFromInput('puname',
                                            'pemail',
                                            'ppass1',
                                            'ppass2',
                                            'pstate');
    }

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

         if ($user != false) {
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

        // check for duplicate email address
        $user = xarModAPIFunc('roles',
                              'user',
                              'get',
                               array('email' => $pemail));

        if ($user != false) {
            $msg = xarML('That email address is already registered.');
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


        if(strcmp($ppass1,$ppass2) != 0) {
            $msg = xarML('The two password entries are not the same');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }
    }

    // assemble the args into an array for the role constructor
    if ($ptype == 0) {
        $pargs = array('name' => $pname,
                        'type' => $ptype,
                        'parentid' => $pparentid,
                        'uname' => $puname,
                        'email' => $pemail,
                        'pass' => $ppass1,
                        'date_reg'     => time(),
                        'val_code'  => 'createdbyadmin',
                        'state'   => $pstate,
                        'auth_module'  => 'authsystem',
                        );
    }
    else {
        $pargs = array('name' => $pname,
                        'type' => $ptype,
                        'parentid' => $pparentid,
                        'uname' => xarSessionGetVar('uid').time(),
                        'date_reg'     => time(),
                        'val_code'  => 'createdbyadmin',
                        'auth_module'  => 'authsystem',
                        );
    }
    // create a new role object
    $role = new xarRole($pargs);

    //Try to add the role to the repositoryand bail if an error was thrown
    if (!$role->add()) {return;}

    // redirect to the next page
    xarResponseRedirect(xarModURL('roles', 'admin', 'newrole'));
}

?>