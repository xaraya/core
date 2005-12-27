<?php
/**
 * Display user
 *
 * @package modules
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 */
/**
 * Display user
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @param int uid
 * @return array
 */
function roles_user_display($args)
{
    extract($args);

    if (!xarVarFetch('uid','id',$uid, xarUserGetVar('uid'))) return;
    if (!xarVarFetch('itemid', 'int', $itemid, NULL, XARVAR_DONT_SET)) return;

    $uid = isset($itemid) ? $itemid : $uid;

    // Get role information
    $roles = new xarRoles();
    $role = $roles->getRole($uid);

    if (!$role) return;

    $name = $role->getName();
// Security Check
    if(!xarSecurityCheck('ViewRoles',0,'Roles',$name)) return;

    $data['uid'] = $role->getID();
    $data['itemtype'] = $role->getType();
	$data['basetype'] = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $data['itemtype']));
	$types = xarModAPIFunc('roles','user','getitemtypes');
	$data['itemtypename'] = $types[$data['itemtype']]['label'];
    $data['name'] = $name;
    //get the data for a user
    if ($data['basetype'] == ROLES_USERTYPE) {
        $data['uname'] = $role->getUser();
		$data['email'] = xarVarPrepForDisplay($role->getEmail());
        $data['state'] = $role->getState();
        $data['valcode'] = $role->getValCode();
    } else {
        //get the data for a group
    }

    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = $data['type'];
    $item['itemid']= $uid;
    $item['returnurl'] = xarModURL('roles', 'user', 'display',
                                   array('uid' => $uid));
    $data['hooks'] = xarModCallHooks('item', 'display', $uid, $item);

    xarTplSetPageTitle(xarVarPrepForDisplay($data['name']));

    return $data;
}

?>