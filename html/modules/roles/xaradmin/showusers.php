<?php

/**
 * showusers - display the users of this role
 */
function roles_admin_showusers()
{
    // Get parameters
    if (!xarVarFetch('uid', 'int:1:', $uid)) return;
    if (!xarVarFetch('startnum', 'str:1:', $startnum, 1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase', 'str:1:', $phase, 'viewall', XARVAR_NOT_REQUIRED)) return;
    // Security Check
    if (!xarSecurityCheck('ReadRole')) return;
    // Call the Roles class and get the role
    $roles = new xarRoles();
    $role = $roles->getRole($uid);

    $numitems = xarModGetVar('roles', 'itemsperpage');
    // get all children of this role that are users
    switch (strtolower($phase)) {
        case 'viewall':
        default:
            $total = count($role->getUsers(0));
            if ($total == 0) {
                $data['message'] = xarML('There are no users');
            }
            $usrs = $role->getUsers(0, $startnum, $numitems);
            $data['phase'] = 'viewall';
            $data['title'] = xarML('All Users');
            break;

        case 'inactive':
            $total = count($role->getUsers(1));
            if ($total == 0) {
                $data['message'] = xarML('There are no inactive users');
            }
            $usrs = $role->getUsers(1, $startnum, $numitems);
            $data['phase'] = 'inactive';
            $data['title'] = xarML('Inactive Users');
            break;

        case 'notvalidated':
            $total = count($role->getUsers(2));
            if ($total == 0) {
                $data['message'] = xarML('There are no users waiting for validation');
            }
            $usrs = $role->getUsers(2, $startnum, $numitems);
            $data['phase'] = 'notvalidated';
            $data['title'] = xarML('Users Waiting for Validation');
            break;

        case 'active':
            $total = count($role->getUsers(3));
            if ($total == 0) {
                $data['message'] = xarML('There are no active users');
            }
            $usrs = $role->getUsers(3, $startnum, $numitems);
            $data['phase'] = 'active';
            $data['title'] = xarML('Active Users');
            break;

        case 'pending':
            $total = count($role->getUsers(4));
            if ($total == 0) {
                $data['message'] = xarML('There are no pending users');
            }
            $usrs = $role->getUsers(4, $startnum, $numitems);
            $data['phase'] = 'pending';
            $data['title'] = xarML('Pending Users');
            break;
    }
    // assemble the info for the display
    $users = array();
    while (list($key, $user) = each($usrs)) {
        $users[] = array('uid' => $user->getID(),
            'name' => $user->getName(),
            'uname' => $user->getUser(),
            'email' => $user->getEmail(),
            'frozen' => !xarSecurityCheck('EditRole',0,'Roles',$user->getName())
            );
    }

    // Load Template
    $data['pname'] = $role->getName();
    $data['uid'] = $uid;
    $data['users'] = $users;
    $data['authid'] = xarSecGenAuthKey();
    $data['removeurl'] = xarModURL('roles',
        'admin',
        'deleterole',
        array('roleid' => $uid));
    $filter['startnum'] = '%%';
    $filter['uid'] = $uid;
    $data['pager'] = xarTplGetPager($startnum,
        $total,
        xarModURL('roles', 'admin', 'showusers',
            $filter),
        $numitems);
    return $data;
    // redirect to the next page
    xarResponseRedirect(xarModURL('roles', 'admin', 'newrole'));
}

?>