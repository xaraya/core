<?php
/**
 * Modify role details
 *
 * @package modules
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * modifyrole - modify role details
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_modifyrole()
{
    if (!xarVarFetch('uid', 'int:1:', $uid, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pname', 'str:1:', $name, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemid', 'int', $itemid, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('itemtype', 'int', $itemtype, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('puname', 'str:1:35:', $uname, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pemail', 'str:1:', $email, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ppass', 'str:1:', $pass, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state', 'str:1:', $state, '', XARVAR_DONT_SET)) return;
    if (!xarVarFetch('phome', 'str', $data['phome'], '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pprimaryparent', 'int', $data['primaryparent'], '', XARVAR_NOT_REQUIRED)) return;

    $uid = isset($itemid) ? $itemid : $uid;

    // Call the Roles class and get the role to modify
    $roles = new xarRoles();
    $role = $roles->getRole($uid);

    // get the array of parents of this role
    // need to display this in the template
    // we also use this loop to fill the names array with groups that this group shouldn't be added to
    $parents = array();
    $names = array();
    foreach ($role->getParents() as $parent) {
        if(xarSecurityCheck('RemoveRole',0,'Relation',$parent->getName() . ":" . $role->getName())) {
            $parents[] = array('parentid' => $parent->getID(),
                'parentname' => $parent->getName());
            $names[] = $parent->getName();
        }
    }
    $data['parents'] = $parents;

    // remove duplicate entries from the list of groups
    // get the array of all roles, minus the current one
    // need to display this in the template
    $groups = array();
    foreach($roles->getgroups() as $temp) {
        $nam = $temp['name'];
// TODO: this is very inefficient. Here we have the perfect use case for embedding security checks directly into the SQL calls
        if(!xarSecurityCheck('AttachRole',0,'Relation',$nam . ":" . $role->getName())) continue;
        if (!in_array($nam, $names) && $temp['uid'] != $uid) {
            $names[] = $nam;
            $groups[] = array('duid' => $temp['uid'],
                'dname' => $temp['name']);
        }
    }
   // Load Template
    if (empty($name)) $name = $role->getName();
    $data['pname'] = $name;

// Security Check
//    if (!xarSecurityCheck('EditRole',0,'Roles',$name)) return;
    $data['frozen'] = !xarSecurityCheck('EditRole',0,'Roles',$name);

    if (isset($itemtype)) {
        $data['itemtype'] = $itemtype;
    } else {
        $data['itemtype'] = $role->getType();
    }
	$data['basetype'] = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $data['itemtype']));
	$types = xarModAPIFunc('roles','user','getitemtypes');
	$data['itemtypename'] = $types[$data['itemtype']]['label'];

    if (!empty($uname)) {
        $data['puname'] = $uname;
    } else {
        $data['puname'] = $role->getUser();
    }

    if (!empty($home)) {
        $data['phome'] = $home;
    } else {
        $data['phome'] = $role->getHome();
    }

    if (!empty($primaryparent)) {
        $data['pprimaryparent'] = $primaryparent;
    } else {
        $data['pprimaryparent'] = $role->getPrimaryParent();
    }

    if (!empty($email)) {
        $data['pemail'] = $email;
    } else {
        $data['pemail'] = $role->getEmail();
    }

    if (isset($pstate)) {
        $data['pstate'] = $pstate;
    } else {
        $data['pstate'] = $role->getState();
    }

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
    $data['updatelabel'] = xarML('Update');
    $data['addlabel'] = xarML('Add');
    $data['authid'] = xarSecGenAuthKey();
    return $data;
}

?>
