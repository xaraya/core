<?php

/**
 * showusers - display the users of this role
 */
function roles_admin_showusers()
{
    // Security Check
    if (!xarSecurityCheck('EditRole')) return;
    // Get parameters
    if (xarVarIsCached('roles', 'defaultgroupuid')) {
        $defaultgroupuid = xarVarGetCached('roles', 'defaultgroupuid');
    } else {
        $defaultgroup = xarModGetVar('roles', 'defaultgroup');
        $defaultgroupuid = xarModAPIFunc('roles','user','get',
                                                 array('uname'  => $defaultgroup,
                                                       'type'   => 1));
    }
    xarVarSetCached('roles', 'defaultgroupuid', $defaultgroupuid);

    if (!xarVarFetch('uid', 'int:0:', $uid, $defaultgroupuid['uid'], XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('startnum', 'int:1:', $startnum, 1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state', 'int:0:', $data['state'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('display', 'isset', $data['display'], NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('invalid', 'str:0:', $data['invalid'], NULL, XARVAR_NOT_REQUIRED)) return;

    $userdisplay = xarSessionGetVar('rolesdisplay');
    if (!isset($data['display'])) {
        if (isset($userdisplay)) {
            $data['display'] = $userdisplay;
        } else {
            $data['display'] ="tabbed";
        }
    }
    xarSessionSetVar('rolesdisplay', $data['display']);

    //Create the role tree
    if ($data['display'] == 'tree') {
        include_once 'modules/roles/xartreerenderer.php';
        $renderer = new xarTreeRenderer();
        $data['roletree'] = $renderer->drawtree($renderer->maketree());
    }

    // Load Template
    $data['groups'] = xarModAPIFunc('roles',
                                    'user',
                                    'getallgroups');
    $data['groupuid'] = $uid;
    $numitems = xarModGetVar('roles', 'rolesperpage');
    $data['totalusers'] = xarModAPIFunc('roles','user','countall');
    // Make sure a value was retrieved for rolesperpage
    if (empty($numitems))
        $numitems = -1;
    if ($uid != 0) {
        // Call the Roles class and get the role
        $roles = new xarRoles();
        $role = $roles->getRole($uid);
        $ancestors = $role->getAncestors();
        $data['groupname'] = $role->getName();
        $data['title'] = $data['groupname']." > ";
        $data['ancestors'] = array();
        foreach ($ancestors as $ancestor) {
            $data['ancestors'][] = array('name' => $ancestor->getName(),
                                        'uid' => $ancestor->getID());
        }
        //$subgroups = $roles->getsubgroups($uid);
    }
    else {
        $data['title'] = xarML('All Users')." > ";
    }

    // get all children of this role that are users
    switch ($data['state']) {
        case 0 :
        default:
            if ($uid != 0) {
                $usrs = $role->getUsers(0, $startnum, $numitems);
                $data['totalstate'] = $role->countUsers(0);
            } else {
                $usrs = xarModAPIFunc('roles','user','getall', array('startat' => $startnum, 'numitems' => $numitems));
                $data['totalstate'] = xarModAPIFunc('roles','user','countall');
            }
            if ($data['totalstate'] == 0) {
                $data['message'] = xarML('There are no users');
            }
            $data['title'] .= xarML('All States');
            break;

        case 1:
            if ($uid != 0) {
                $data['totalstate'] = $role->countUsers(1);
                $usrs = $role->getUsers(1, $startnum, $numitems);
            } else {
                $usrs = xarModAPIFunc('roles','user','getall', array('state' => 1, 'startat' => $startnum, 'numitems' => $numitems));
                $data['totalstate'] = xarModAPIFunc('roles','user','countall', array('state' => 1));
            }
            if ($data['totalstate'] == 0) {
                $data['message'] = xarML('There are no inactive users');
            }
            $data['title'] .= xarML('Inactive Users');
            break;

        case 2:
             if ($uid != 0) {
                $data['totalstate'] = $role->countUsers(2);
                $usrs = $role->getUsers(2, $startnum, $numitems);
             } else {
                $data['totalstate'] = xarModAPIFunc('roles','user','countall', array('state' => 2));
                $usrs = xarModAPIFunc('roles','user','getall', array('state' => 2, 'startat' => $startnum, 'numitems' => $numitems));
             }
            if ($data['totalstate'] == 0) {
                $data['message'] = xarML('There are no users waiting for validation');
            }
            $data['title'] .= xarML('Users Waiting for Validation');
            break;

        case 3:
            if ($uid != 0) {
                $data['totalstate'] = $role->countUsers(3);
                $usrs = $role->getUsers(3, $startnum, $numitems);
            } else {
                $data['totalstate'] = xarModAPIFunc('roles','user','countall', array('state' => 3));
                $usrs = xarModAPIFunc('roles','user','getall', array('state' => 3, 'startat' => $startnum, 'numitems' => $numitems));
             }
            if ($data['totalstate'] == 0) {
                $data['message'] = xarML('There are no active users');
            }
            $data['title'] .= xarML('Active Users');
            break;

        case 4:
            if ($uid != 0) {
                $data['totalstate'] = $role->countUsers(4);
                $usrs = $role->getUsers(4, $startnum, $numitems);
            } else {
                $data['totalstate'] = xarModAPIFunc('roles','user','countall', array('state' => 4));
                $usrs = xarModAPIFunc('roles','user','getall', array('state' => 4, 'startat' => $startnum, 'numitems' => $numitems));
             }
            if ($data['totalstate'] == 0) {
                $data['message'] = xarML('There are no pending users');
            }
            $data['title'] .= xarML('Pending Users');
            break;
    }
    // assemble the info for the display
    $users = array();
    if ($uid != 0) {
        $data['pname'] = $role->getName();
        while (list($key, $user) = each($usrs)) {
            $users[] = array('uid' => $user->getID(),
                'name' => $user->getName(),
                'uname' => $user->getUser(),
                'email' => $user->getEmail(),
                'status' => $user->getState(),
                'date_reg' => $user->getDateReg(),
                'frozen' => !xarSecurityCheck('EditRole',0,'Roles',$user->getName())
                );
        }
    } else {
        $data['pname'] = xarML("All Users");
        while (list($key, $user) = each($usrs)) {
            $users[] = array('uid' => $user['uid'],
                'name' => $user['name'],
                'uname' => $user['uname'],
                'email' => $user['email'],
                'status' => $user['state'],
                'date_reg' => $user['date_reg'],
                'frozen' => !xarSecurityCheck('EditRole',0,'Roles',$user['name'])
                );
        }
    }

    // Load Template
    $data['uid'] = $uid;
    $data['users'] = $users;
    $data['changestatuslabel'] = xarML('Change Status');
    $data['authid'] = xarSecGenAuthKey();
    $data['removeurl'] = xarModURL('roles',
        'admin',
        'deleterole',
        array('roleid' => $uid));
    $filter['startnum'] = '%%';
    $filter['uid'] = $uid;
    $filter['state'] = $data['state'];
    $data['pager'] = xarTplGetPager($startnum,
        $data['totalstate'],
        xarModURL('roles', 'admin', 'showusers',
            $filter),
        $numitems);
    return $data;
    // redirect to the next page
    xarResponseRedirect(xarModURL('roles', 'admin', 'newrole'));
}

?>
