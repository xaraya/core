<?php

include 'includes/xarDate.php';

/**
 * showusers - display the users of this role
 */
function roles_admin_showusers()
{
    // Get parameters
    if (!xarVarFetch('uid', 'int:1:', $uid)) return;
    if (!xarVarFetch('startnum', 'int:1:', $startnum, 1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase', 'str:1:', $phase, 'viewall', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    // Security Check
    if (!xarSecurityCheck('ReadRole')) return;
    // Call the Roles class and get the role
    $roles = new xarRoles();
    $role = $roles->getRole($uid);

    $numitems = xarModGetVar('roles', 'rolesperpage');

    // Make sure a value was retrieved for rolesperpage
    if (empty($numitems))
        $numitems = -1;

    // get all children of this role that are users
    switch (strtolower($phase)) {
        case 'viewall':
        default:
            $total = $role->countUsers(0);
            if ($total == 0) {
                $data['message'] = xarML('There are no users');
            }
            $usrs = $role->getUsers(0, $startnum, $numitems);
            $data['phase'] = 'viewall';
            $data['title'] = xarML('All Users');
            break;

        case 'inactive':
            $total = $role->countUsers(1);
            if ($total == 0) {
                $data['message'] = xarML('There are no inactive users');
            }
            $usrs = $role->getUsers(1, $startnum, $numitems);
            $data['phase'] = 'inactive';
            $data['title'] = xarML('Inactive Users');
            break;

        case 'notvalidated':
            $total = $role->countUsers(2);
            if ($total == 0) {
                $data['message'] = xarML('There are no users waiting for validation');
            }
            $usrs = $role->getUsers(2, $startnum, $numitems);
            $data['phase'] = 'notvalidated';
            $data['title'] = xarML('Users Waiting for Validation');
            break;

        case 'active':
            $total = $role->countUsers(3);
            if ($total == 0) {
                $data['message'] = xarML('There are no active users');
            }
            $usrs = $role->getUsers(3, $startnum, $numitems);
            $data['phase'] = 'active';
            $data['title'] = xarML('Active Users');
            break;

        case 'pending':
            $total = $role->countUsers(4);
            if ($total == 0) {
                $data['message'] = xarML('There are no pending users');
            }
            $usrs = $role->getUsers(4, $startnum, $numitems);
            $data['phase'] = 'pending';
            $data['title'] = xarML('Pending Users');
            break;
    }
    // assemble the info for the display
    $thisdate = new xarDate();
    $users = array();
    while (list($key, $user) = each($usrs)) {

        // adjust the display format
        // TODO: needs to be made variable
        if(is_numeric($user->getDateReg())) {
            $thisdate->setTimestamp($user->getDateReg());
        }
        else {
            $thisdate->DBtoTS($user->getDateReg());
        }
        $regdate = $thisdate->display("m-d-Y");

        $users[] = array('uid' => $user->getID(),
            'name' => $user->getName(),
            'uname' => $user->getUser(),
            'email' => $user->getEmail(),
            'date_reg' => $regdate,
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
