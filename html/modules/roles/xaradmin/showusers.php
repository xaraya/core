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
    if (!xarVarFetch('state', 'int:0:', $data['state'], ROLES_STATE_CURRENT, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selstyle', 'isset', $data['selstyle'], xarSessionGetVar('rolesdisplay'), XARVAR_DONT_SET)) return;
    if (!xarVarFetch('invalid', 'str:0:', $data['invalid'], NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('order', 'str:0:', $data['order'], 'xar_name', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('search', 'str:0:', $data['search'], NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('reload', 'str:0:', $reload, NULL, XARVAR_DONT_SET)) return;

    if (empty($data['selstyle'])) $data['selstyle'] = 0;
    xarSessionSetVar('rolesdisplay', $data['selstyle']);

    //Create the role tree
    if ($data['selstyle'] == '1') {
        include_once 'modules/roles/xartreerenderer.php';
        $renderer = new xarTreeRenderer();
        $data['roletree'] = $renderer->drawtree($renderer->maketree());
    }

// Get information on the group we're at
    $data['groups'] = xarModAPIFunc('roles',
                                    'user',
                                    'getallgroups');
    $data['groupuid'] = $uid;
    $data['totalusers'] = xarModAPIFunc('roles','user','countall');

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

    // Check if we already have a selection
        $q = new xarQuery();
        $q = $q->sessiongetvar('rolesquery');
    if (empty($q) || isset($reload)) {
        $xartable =& xarDBGetTables();
        $q = new xarQuery('SELECT');
        $q->addtable($xartable['roles'],'r');
        $q->addfields(array(
            'r.xar_uid AS uid',
            'r.xar_name AS name',
            'r.xar_uname AS uname',
            'r.xar_email AS email',
            'r.xar_state AS state',
            'r.xar_date_reg AS date_reg'));

        //Create the selection
        if (!empty($data['search'])) {
            $c[1] = $q->like('xar_name','%' . $data['search'] . '%');
            $c[2] = $q->like('xar_uname','%' . $data['search'] . '%');
            $c[3] = $q->like('xar_email','%' . $data['search'] . '%');
            $q->qor($c);
        }

        $q->setorder($data['order']);
        $q->eq('xar_type',0);

        // Add state
        if ($data['state'] == ROLES_STATE_CURRENT) $q->ne('xar_state',ROLES_STATE_DELETED);
        elseif ($data['state'] == ROLES_STATE_ALL) {}
        else $q->eq('xar_state',$data['state']);

        // If a group was chosen, get only the users of that group
        if ($uid != 0) {
            $q->addtable($xartable['rolemembers'],'rm');
            $q->join('r.xar_uid','rm.xar_uid');
            $q->eq('rm.xar_parentid',$uid);
        }

        // Save the query so we can reuse it somewhere
        $q->sessionsetvar('rolesquery');
    }

    // Add limits
    $numitems = xarModGetVar('roles', 'rolesperpage');
    $q->setrowstodo($numitems);
    $q->setstartat($startnum);
    if(!$q->run()) return;

    $data['totalselect'] = $q->getrows();

    switch ($data['state']) {
        case ROLES_STATE_CURRENT :
        default:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no users');
            $data['title'] .= xarML('Users');
            break;
        case ROLES_STATE_INACTIVE:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no inactive users');
            $data['title'] .= xarML('Inactive Users');
            break;
        case ROLES_STATE_NOTVALIDATED:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no users waiting for validation');
            $data['title'] .= xarML('Users Waiting for Validation');
            break;
        case ROLES_STATE_ACTIVE:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no active users');
            $data['title'] .= xarML('Active Users');
            break;
        case ROLES_STATE_PENDING:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no pending users');
            $data['title'] .= xarML('Pending Users');
            break;
    }
    // assemble the info for the display
        $users = array();
        foreach($q->output() as $user)
            $users[] = array_merge($user, array('frozen' => !xarSecurityCheck('EditRole',0,'Roles',$user['name'])));

    if ($uid != 0) $data['title'] .= " ".xarML('of group')." ";

    //selstyle
    $data['style'] = array('0' => xarML('Simple'),
                                       '1' => xarML('Tree'),
                                       '2' => xarML('Tabbed')
                                       );

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
