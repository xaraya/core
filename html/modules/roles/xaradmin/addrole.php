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
    if (!xarVarFetch('pparentid',  'str:1:', $pparentid,  NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url', 'isset',  $return_url, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('itemtype', 'int', $itemtype, 0, XARVAR_NOT_REQUIRED)) return;
    // get the rest for users only
    // TODO: need to see what to do with auth module
	$basetype = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));
    if ($basetype == ROLES_USERTYPE) {
        xarVarFetch('puname', 'str:1:35:', $puname, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('pemail', 'str:1:', $pemail, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('ppass1', 'str:1:', $ppass1, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('ppass2', 'str:1:', $ppass2, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('pstate', 'str:1:', $pstate, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('phome', 'str', $phome, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('pprimaryparent', 'int', $pprimaryparent, NULL, XARVAR_NOT_REQUIRED); // this seems redundant here
    }
    // checks specific only to users
    if ($basetype == ROLES_USERTYPE) {
        // check for valid username
        // TODO: do this in the xarVarFetch above, no need to do this here.
        if ((!$puname) || !(!preg_match("/[[:space:]]/", $puname))) {
            throw new BadParameterException($puname,'The username "#(1)" contains spacing characters, this is not allowed');
        }

        // check for duplicate username
        $user = xarModAPIFunc('roles',
            'user',
            'get',
            array('uname' => $puname));

        if ($user != false) {
            throw new DuplicateException(array('user',$puname));
        }

        // check for empty email address
        if ($pemail == '') {
            throw new BadParameterException(null,'Email address should have a value');
        }
        // check for duplicate email address
        if(xarModGetVar('roles','uniqueemail')) {
            $user = xarModAPIFunc('roles','user', 'get', array('email' => $pemail));
            if ($user != false) throw new DuplicateException(array('email',$pemail));
        }
        // TODO: Replace with DD property type check.
        // check for valid email address
        $res = preg_match('/.*@.*/', $pemail);
        if ($res == false) throw new BadParameterException($pemail,'The email address "#(1)" is invalid');

        if (strcmp($ppass1, $ppass2) != 0) {
            throw new BadParameterException(null,'The two entered passwords are not the same');
        }
    }
    // assemble the args into an array for the role constructor
    if ($basetype == ROLES_USERTYPE) {
        $duvs = array();
        if (isset($phome) && xarModGetVar('roles','setuserhome'))
            $duvs['userhome'] = $phome;
        if (xarModGetVar('roles','setprimaryparent')) { //For a new role surely this is the same as the parentid
            //the primary parent is a string name inline with default role etc
	        $parentrole= xarModAPIFunc('roles', 'user', 'get', array('uid'  => $pparentid, 'type'   => 1));
            $duvs['primaryparent'] = $parentrole['uname'];
        }

        $args = array('name' => $pname,
            'itemtype' => $itemtype,
            'parentid' => $pparentid,
            'uname' => $puname,
            'email' => $pemail,
            'pass' => $ppass1,
            'val_code' => 'createdbyadmin',
            'state' => $pstate,
            'auth_module' => 'authsystem',
            'duvs' => $duvs,
            'basetype' => $basetype,
            );
    } else {
        $args = array('name' => $pname,
            'itemtype' => $itemtype,
            'parentid' => $pparentid,
            'uname' => xarSessionGetVar('uid') . time(),
            'val_code' => 'createdbyadmin',
            'auth_module' => 'authsystem',
            'basetype' => $basetype,
            );
    }
    $uid = xarModAPIFunc('roles','admin','create',$args);

    // call item create hooks (for DD etc.)
// TODO: move to add() function
    $pargs['module'] = 'roles';
    $pargs['itemtype'] = $itemtype;
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
