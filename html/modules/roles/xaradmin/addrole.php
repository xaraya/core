<?php
/**
 * Add a role
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
 * addRole - add a role
 * This function tries to create a user and provides feedback on the
 * result.
 *
 * @author Jan Schrage, Marc Lutolf
 */
function roles_admin_addrole()
{
    // Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    // get some vars for both groups and users
    if (!xarVarFetch('pname',      'str:1:', $pname,      NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ptype',      'str:1',  $ptype,      NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pparentid',  'str:1:', $pparentid,  NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url', 'isset',  $return_url, NULL, XARVAR_DONT_SET)) return;
    // get the rest for users only
    // TODO: need to see what to do with auth_module
    if ($ptype == 0) {
        xarVarFetch('puname', 'str:1:35:', $puname, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('pemail', 'str:1:', $pemail, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('ppass1', 'str:1:', $ppass1, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('ppass2', 'str:1:', $ppass2, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('pstate', 'str:1:', $pstate, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('phome', 'str', $phome, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('pprimaryparent', 'int', $pprimaryparent, NULL, XARVAR_NOT_REQUIRED); // this seems redundant here
    }
    // checks specific only to users
    if ($ptype == 0) {
        // check for valid username
        if ((!$puname) || !(!preg_match("/[[:space:]]/", $puname))) {
            $msg = xarML('There is an error in the username');
            xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }

        // check for duplicate username
        $user = xarModAPIFunc('roles',
            'user',
            'get',
            array('uname' => $puname));

        if ($user != false) {
            $msg = xarML('That username is already taken.');
            xarErrorSet(XAR_USER_EXCEPTION, 'DUPLICATE_DATA', new DefaultUserException($msg));
            return;
        }

        if (strrpos($puname, ' ') > 0) {
            $msg = xarML('There is a space in the username');
            xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
            return;
        }
        // check for empty email address
        if ($pemail == '') {
            $msg = xarML('Please enter an email address');
            xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
            return;
        }
        // check for duplicate email address
        if(xarModGetVar('roles','uniqueemail')) {
            $user = xarModAPIFunc('roles',
                'user',
                'get',
                array('email' => $pemail));

            if ($user != false) {
                $msg = xarML('That email address is already registered.');
                xarErrorSet(XAR_USER_EXCEPTION, 'DUPLICATE_DATA', new DefaultUserException($msg));
                return;
            }
        }
        // TODO: Replace with DD property type check.
        // check for valid email address
        $res = preg_match('/.*@.*/', $pemail);

        if ($res == false) {
            $msg = xarML('There is an error in the email address');
            xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
            return;
        }

        if (strcmp($ppass1, $ppass2) != 0) {
            $msg = xarML('The two password entries are not the same');
            xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
            return;
        }
    }
    // assemble the args into an array for the role constructor
    if ($ptype == 0) {
        $duvs = array();
        if (isset($phome) && xarModGetVar('roles','setuserhome'))
            $duvs['userhome'] = $phome;
        if (xarModGetVar('roles','setprimaryparent')) { //For a new role surely this is the same as the parentid
            //the primary parent is a string name inline with default role etc
            $parentrole= xarModAPIFunc('roles', 'user', 'get', array('uid'  => $pparentid, 'type'   => 1));
            $duvs['primaryparent'] = $parentrole['uname'];
        }

        $pargs = array('name' => $pname,
            'type' => $ptype,
            'parentid' => $pparentid,
            'uname' => $puname,
            'email' => $pemail,
            'pass' => $ppass1,
            'val_code' => 'createdbyadmin',
            'state' => $pstate,
            'auth_module' => 'authsystem',
            'duvs' => $duvs,
            );
    } else {
        $pargs = array('name' => $pname,
            'type' => $ptype,
            'parentid' => $pparentid,
            'uname' => xarSessionGetVar('uid') . time(),
            'val_code' => 'createdbyadmin',
            'auth_module' => 'authsystem',
            );
    }
    // create a new role object
    $role = new xarRole($pargs);
    // Try to add the role to the repositoryand bail if an error was thrown
    if (!$role->add()) {
        return;
    }

    // retrieve the uid of this new user
    $uid = $role->uid;

    // call item create hooks (for DD etc.)
// TODO: move to add() function
    $pargs['module'] = 'roles';
    $pargs['itemtype'] = $ptype; // we might have something separate for groups later on
    $pargs['itemid'] = $uid;
    xarModCallHooks('item', 'create', $uid, $pargs);

    // redirect to the next page
    if (!empty($return_url)) {
        xarResponseRedirect($return_url);
    } else {
        xarResponseRedirect(xarModURL('roles', 'admin', 'modifyrole',array('uid' => $uid)));
    }
}
?>