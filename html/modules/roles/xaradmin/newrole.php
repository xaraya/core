<?php
/**
 * File: $Id$
 *
 * Create a new role
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf
 */
/**
 * newRole - create a new role
 * Takes no parameters
 *
 * @author Marc Lutolf
 */
function roles_admin_newrole()
{
    if (!xarVarFetch('return_url',  'isset', $return_url, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('pparentid', 'str:1:', $pparentid, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pname', 'str:1:', $name, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ptype', 'str:1:', $type, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('puname', 'str:1:35:', $uname, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pemail', 'str:1:', $email, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ppass1', 'str:1:', $pass, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state', 'str:1:', $state, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phome', 'str', $home, '', XARVAR_NOT_REQUIRED)) return;
    // Security Check
    if (!xarSecurityCheck('AddRole')) return;
    // Call the Roles class
    // should be static, but apparently not doable in php?
    $roles = new xarRoles();

    $groups = array();
    $names = array();
    foreach($roles->getgroups() as $temp) {
        $nam = $temp['name'];
        if (!in_array($nam, $names)) {
            $names[] = $nam;
            $groups[] = $temp;
        }
    }
    // Load Template
    if (isset($name)) {
        $data['pname'] = $name;
    } else {
        $data['pname'] = '';
    }

    if (isset($type)) {
        $data['ptype'] = $type;
    } else {
        $data['ptype'] = 1;
    }

    if (isset($uname)) {
        $data['puname'] = $uname;
    } else {
        $data['puname'] = '';
    }

    if (isset($email)) {
        $data['pemail'] = $email;
    } else {
        $data['pemail'] = '';
    }

    if (isset($pass)) {
        $data['ppass1'] = $pass;
    } else {
        $data['ppass1'] = '';
    }

    if (isset($state)) {
        $data['pstate'] = $state;
    } else {
        $data['pstate'] = 1;
    }

    if (isset($home)) {
        $data['phome'] = $home;
    } else {
        $data['phome'] = '';
    }

    if (isset($pparentid)) {
        $data['pparentid'] = $pparentid;
    } else {
        $data['pparentid'] = 1;
    }

    // call item new hooks (for DD etc.)
    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = $data['ptype']; // we might have something separate for groups later on
    $data['hooks'] = xarModCallHooks('item', 'new', '', $item);

    $data['authid'] = xarSecGenAuthKey();
    $data['addlabel'] = xarML('Add');
    $data['groups'] = $groups;
    $data['return_url'] = $return_url;
    return $data;
}
?>
