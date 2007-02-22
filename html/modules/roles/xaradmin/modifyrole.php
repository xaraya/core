<?php
/**
 * Modify role details
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * modifyrole - modify role details
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_modifyrole()
{
    // Call the Roles class and get the role to modify
    sys::import('modules.roles.class.roles');
    if (!xarVarFetch('uid', 'id', $uid, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemid', 'id', $itemid, NULL, XARVAR_DONT_SET)) return;
    $uid = isset($itemid) ? $itemid : $uid;
    $role = xarRoles::getRole($uid);

    if (!xarVarFetch('pname', 'str:1:', $name, $role->getName(), XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('itemtype', 'id', $itemtype, $role->getType(), XARVAR_DONT_SET)) return;
    if (!xarVarFetch('puname', 'str:1:35:', $uname, $role->getUser(), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pemail', 'str:1:', $email, $role->getEmail(), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ppass', 'str:1:', $pass, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state', 'str:1:', $state, $role->getState(), XARVAR_DONT_SET)) return;
    if (!xarVarFetch('duvs', 'array', $data['duvs'], array(), XARVAR_NOT_REQUIRED)) return;






    // get the array of parents of this role
    // need to display this in the template
    // we also use this loop to fill the names array with groups that this group shouldn't be added to
    $parents = array();
    $names = array();
    foreach ($role->getParents() as $parent) {
        if(xarSecurityCheck('RemoveRole',0,'Relation',$parent->getName() . ":" . $role->getName())) {
            $parents[] = array('parentid' => $parent->getID(),
                               'parentname' => $parent->getName(),
                               'parentuname'=> $parent->getUname());
            $names[] = $parent->getName();
        }
    }
    $data['parents'] = $parents;

    // remove duplicate entries from the list of groups
    // get the array of all roles, minus the current one
    // need to display this in the template
    $groups = array();
    foreach(xarRoles::getgroups() as $temp) {
        $nam = $temp['name'];
        // TODO: this is very inefficient. Here we have the perfect use case for embedding security checks directly into the SQL calls
        if(!xarSecurityCheck('AttachRole',0,'Relation',$nam . ":" . $role->getName())) continue;
        if (!in_array($nam, $names) && $temp['uid'] != $uid) {
            $names[] = $nam;
            $groups[] = array('duid' => $temp['uid'],
                'dname' => $temp['name']);
        }
    }

    if (!xarSecurityCheck('EditRole',0,'Roles',$name)) {
        if (!xarSecurityCheck('ReadRole',1,'Roles',$name)) return;
    }
    $data['frozen'] = !xarSecurityCheck('EditRole',0,'Roles',$name);

    $data['basetype'] = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));
    $types = xarModAPIFunc('roles','user','getitemtypes');
    $data['itemtypename'] = $types[$itemtype]['label'];

    $data['itemtype'] = $itemtype;
    $data['pname'] = $name;
    $data['puname'] = $uname;
    $data['pemail'] = $email;
    $data['pstate'] = $state;

    // call item modify hooks (for DD etc.)
    $item = $data;
    $item['module']= 'roles';
    $item['itemtype'] = $data['itemtype'];
    $item['itemid']= $uid;
    $data['hooks'] = xarModCallHooks('item', 'modify', $uid, $item);

    $data['uid'] = $uid;
    $data['groups'] = $groups;
    $data['parents'] = $parents;
    $data['haschildren'] = $role->countChildren();
    $data['authid'] = xarSecGenAuthKey();
    return $data;
}

?>
