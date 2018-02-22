<?php
/**
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * Show users of this role
 * @return array data for the template display
 */
function roles_admin_showusers()
{
    // Security
    if (!xarSecurityCheck('EditRoles')) return;

    if (xarVarIsCached('roles', 'defaultgroupid')) {
        $defaultgroupid = xarVarGetCached('roles', 'defaultgroupid');
    } else {
        $defaultgroupid = xarModVars::get('roles','defaultgroup');
    }
    xarVarSetCached('roles', 'defaultgroupid', $defaultgroupid);

    if (!xarVarFetch('id',       'int:0:', $id,              $defaultgroupid, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('startnum', 'int:1:', $startnum,         1,   XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state',    'int:0:', $data['state'],    xarRoles::ROLES_STATE_CURRENT, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selstyle', 'isset',  $data['selstyle'], xarSession::getVar('rolesdisplay'), XARVAR_DONT_SET)) return;
    if (!xarVarFetch('invalid',  'str:0:', $data['invalid'],  NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('order',    'str:0:', $data['order'],    'name', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('search',   'str:0:', $data['search'],   NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('reload',   'str:0:', $reload,           NULL,    XARVAR_DONT_SET)) return;
    if (!xarVarFetch('numitems', 'int:1',  $numitems,        (int)xarModVars::get('roles','items_per_page'), XARVAR_DONT_SET)) return;
    if (empty($data['selstyle'])) $data['selstyle'] = 0;
    xarSession::setVar('rolesdisplay', $data['selstyle']);

    // Get information on the group we're at
    $data['groups']     = xarMod::apiFunc('roles', 'user', 'getallgroups');
    $data['groupid']   = $id;
    $data['totalusers'] = xarMod::apiFunc('roles','user','countall');

    if ($id != 0) {
        // Call the Roles class and get the role
        $role      = xarRoles::get($id);
        $ancestors = $role->getRoleAncestors();
        $data['groupname'] = $role->getName();
        $data['itemtype'] = $role->getType();
        $data['title'] = '';
        $data['ancestors'] = array();
        foreach ($ancestors as $ancestor) {
            $data['ancestors'][] = array('name' => $ancestor->getName(),
                                          'id' => $ancestor->getID());
        }
    } else {
        $data['title'] = xarML('All ')." ";
        $data['groupname'] = '';
        $data['itemtype'] = 0;
    }

    // Check if we already have a selection
    sys::import('xaraya.structures.query');
    $q = new Query();
    $q = $q->sessiongetvar('rolesquery');
    $q = '';

    if (empty($q) || isset($reload)) {
        $xartable =& xarDB::getTables();
        $q = new Query('SELECT');
        $q->addtable($xartable['roles'],'r');
        $q->addfields(array('r.id AS id','r.name AS name'));

        //Create the selection
        $c = array();
        if (!empty($data['search'])) {
            $c[] = $q->plike('name','%' . $data['search'] . '%');
            $c[] = $q->plike('uname','%' . $data['search'] . '%');
            $c[] = $q->plike('email','%' . $data['search'] . '%');
            $q->qor($c);
        }
        $q->eq('r.itemtype', xarRoles::ROLES_USERTYPE);

        // Add state
        if ($data['state'] == xarRoles::ROLES_STATE_CURRENT) $q->ne('state',xarRoles::ROLES_STATE_DELETED);
        elseif ($data['state'] == xarRoles::ROLES_STATE_ALL) {}
        else $q->eq('state',$data['state']);

        // If a group was chosen, get only the users of that group
        if ($id != 0) {
            $q->addtable($xartable['rolemembers'],'rm');
            $q->join('r.id','rm.role_id');
            $q->eq('rm.parent_id',$id);
        }

        // Save the query so we can reuse it somewhere
        $q->sessionsetvar('rolesquery');
    }

    // Sort ye
    // FIXME: this hardwiring is only possible because this list os not configurable
    if ($data['order'] == 'regdate')  $data['order'] ='date_reg';
    $q->setorder($data['order']);

    // Add limits
    $q->setrowstodo($numitems);
    $q->setstartat($startnum);

    if(!$q->run()) return;


    $data['totalselect'] = $q->getrows();

    switch ($data['state']) {
        case xarRoles::ROLES_STATE_CURRENT :
        default:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no users');
            $data['title'] .= xarML('Users');
            break;
        case xarRoles::ROLES_STATE_INACTIVE:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no inactive users');
            $data['title'] .= xarML('Inactive Users');
            break;
        case xarRoles::ROLES_STATE_NOTVALIDATED:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no users waiting for validation');
            $data['title'] .= xarML('Users Waiting for Validation');
            break;
        case xarRoles::ROLES_STATE_ACTIVE:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no active users');
            $data['title'] .= xarML('Active Users');
            break;
        case xarRoles::ROLES_STATE_PENDING:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no pending users');
            $data['title'] .= xarML('Pending Users');
            break;
    }
    // assemble the info for the display
    $users = array();

    $ids = array();

    foreach($q->output() as $row) {
        $users[$row['id']]['frozen'] = !xarSecurityCheck('EditRoles',0,'Roles',$row['name']);

    }
    if ($id != 0) $data['title'] .= " ".xarML('of Group')." ";

    //selstyle
    $data['style'] = array('0' => xarML('Simple'),
                           '1' => xarML('Tree'),
                           '2' => xarML('Tabbed')
                           );

    $object = DataObjectMaster::getObjectList(array('name' => 'roles_users'));

    // Load Template
    $data['id']        = $id;
    $data['users']      = $users;
    $data['object']     = $object;
    $data['authid']     = xarSecGenAuthKey();
    $data['removeurl']  = xarModURL('roles', 'admin','delete', array('id' => $id));
    $filter['startnum'] = '%%';
    $filter['id']      = $id;
    $filter['state']    = $data['state'];
    $filter['search']   = $data['search'];
    $filter['order']    = $data['order'];

    $data['startnum'] = $startnum;
    $data['itemsperpage'] = $numitems;
    $data['urltemplate'] = xarModURL('roles', 'admin', 'showusers',$filter);
    $data['urlitemmatch'] = '%%';

    return $data;
}
?>
