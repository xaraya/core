<?php
/**
 * File: $Id$
 *
 * Display the users of this role
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Xaraya Team
 */
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
    if (!xarVarFetch('order', 'str:0:', $data['order'], 'name', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('search', 'str:0:', $data['search'], NULL, XARVAR_NOT_REQUIRED)) return;
    
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
    $selection = NULL;
    //Create the selection
    if (!empty($data['search'])) {
    	$selection = " AND (";
        $selection .= "(xar_name LIKE '%" . $data['search'] . "%')";
        $selection .= " OR (xar_uname LIKE '%" . $data['search'] . "%')";
        $selection .= " OR (xar_email LIKE '%" . $data['search'] . "%')";
        $selection .= ")";
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
        $data['title'] = "";
        $data['ancestors'] = array();
        foreach ($ancestors as $ancestor) {
            $data['ancestors'][] = array('name' => $ancestor->getName(),
                                        'uid' => $ancestor->getID());
        }
        //$subgroups = $roles->getsubgroups($uid);
    }
    else {
        $data['title'] = xarML('All ')." ";
		$data['groupname'] = '';
    }

     if ($uid != 0) {
	$usrs = $role->getUsers($data['state'], $startnum, $numitems, $data['order'], $selection);
	$data['totalselect'] = count($role->getUsers($data['state'], 0, 0, 'name', $selection));
     } else {
	$usrs = xarModAPIFunc('roles','user','getall', array('state' => $data['state'], 'startnum' => $startnum, 'numitems' => $numitems, 'order' => $data['order'], 'selection' => $selection));
	$data['totalselect']  = count(xarModAPIFunc('roles','user','getall', array('state' => $data['state'], 'selection' => $selection)));
     }
     $data['totaldisplay'] = count($usrs);
    // get all children of this role that are users
    switch ($data['state']) {
        case 0 :
        default:
            if ($data['totalselect'] == 0) {
                $data['message'] = xarML('There are no users');
            }
            $data['title'] .= xarML('Users');
            break;
        case 1:
            if ($data['totalselect'] == 0) {
                $data['message'] = xarML('There are no inactive users');
            }
            $data['title'] .= xarML('Inactive Users');
            break;
        case 2:
            if ($data['totalselect'] == 0) {
                $data['message'] = xarML('There are no users waiting for validation');
            }
            $data['title'] .= xarML('Users Waiting for Validation');
            break;
        case 3:
            if ($data['totalselect'] == 0) {
                $data['message'] = xarML('There are no active users');
            }
            $data['title'] .= xarML('Active Users');
            break;
        case 4:
            if ($data['totalselect'] == 0) {
                $data['message'] = xarML('There are no pending users');
            }
            $data['title'] .= xarML('Pending Users');
            break;
    }
    // assemble the info for the display
    $users = array();
    if ($uid != 0) {
        //$data['pname'] = $role->getName();
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
	 $data['title'] .= " ".xarML('of group')." ";
    } else {
        //$data['pname'] = xarML("All Users");
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
    $filter['search'] = $data['search'];
    $filter['order'] = $data['order'];
    $data['pager'] = xarTplGetPager($startnum,
        $data['totalselect'],
        xarModURL('roles', 'admin', 'showusers',
            $filter),
        $numitems);
    return $data;
    // redirect to the next page
    xarResponseRedirect(xarModURL('roles', 'admin', 'newrole'));
}

?>