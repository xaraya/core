<?php
/**
 * Display role
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * display user
 */
function roles_admin_displayrole()
{
    if (!xarVarFetch('uid','int:1:',$uid)) return;

    $roles = new xarRoles();
    $role = $roles->getRole($uid);

    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    foreach ($role->getParents() as $parent) {
        $parents[] = array('parentid' => $parent->getID(),
            'parentname' => $parent->getName());
    }
    $data['parents'] = $parents;

    $name = $role->getName();
// Security Check
    if (!xarSecurityCheck('EditRole',1,'Roles',$name)) return;
    $data['frozen'] = xarSecurityCheck('ViewRoles',0,'Roles',$name);

    $data['uid'] = $role->getID();
    $data['type'] = $role->getType();
    $data['name'] = $name;
    $data['phome'] = $role->getHome();
    $data['pprimaryparent'] = $role->getPrimaryParent();
    //get the data for a user
    if ($data['type'] == 0) {
        $data['uname'] = $role->getUser();
        $data['type'] = $role->getType();
        $data['email'] = $role->getEmail();
        $data['state'] = $role->getState();
        $data['valcode'] = $role->getValCode();
    } else {
        //get the data for a group

    }



    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = $data['type']; // handle groups differently someday ?
    $item['returnurl'] = xarModURL('roles', 'user', 'display',
                                   array('uid' => $uid));
    $hooks = array();
    $hooks = xarModCallHooks('item', 'display', $uid, $item);
    $data['hooks'] = $hooks;
    xarTplSetPageTitle(xarVarPrepForDisplay($data['name']));
    return $data;
}
?>