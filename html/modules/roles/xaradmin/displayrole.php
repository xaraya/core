<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * display role
 */
function roles_admin_displayrole()
{
    if (!xarVarFetch('uid','int:1:',$uid)) return;

    sys::import('modules.roles.class.roles');
    $role = xarRoles::getRole($uid);

    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    foreach ($role->getParents() as $parent) {
        $parents[] = array('parentid' => $parent->getID(),
                           'parentname' => $parent->getName(),
                           'parentuname' => $parent->getUname());
    }
    $data['parents'] = $parents;

    $name = $role->getName();

    if (!xarSecurityCheck('EditRole',1,'Roles',$name)) return;
    $data['frozen'] = xarSecurityCheck('ViewRoles',0,'Roles',$name);

    $data['uid'] = $role->getID();
    $data['itemtype'] = $role->getType();
    $data['basetype'] = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $data['itemtype']));
    $types = xarModAPIFunc('roles','user','getitemtypes');
    $data['itemtypename'] = $types[$data['itemtype']]['label'];
    $data['name'] = $name;

    //get the data for a user
    if ($data['basetype'] == ROLES_USERTYPE) {
        $data['uname'] = $role->getUser();
        $data['email'] = $role->getEmail();
        $data['state'] = $role->getState();
        $data['valcode'] = $role->getValCode();
    } else {
        //get the data for a group

    }

    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = $data['itemtype']; // handle groups differently someday ?
    $item['returnurl'] = xarModURL('roles', 'user', 'display',
                                   array('uid' => $uid));
    $hooks = array();
    $hooks = xarModCallHooks('item', 'display', $uid, $item);
    $data['hooks'] = $hooks;
    xarTplSetPageTitle(xarVarPrepForDisplay($data['name']));
    return $data;
}
?>
