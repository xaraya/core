<?php
/**
 * File: $Id$
 *
 * Display user
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Xaraya Team
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
    $name = $role->getName();
// Security Check
    if(!xarSecurityCheck('EditRole',0,'Roles',$name)) return;

    $data['uid'] = $role->getID();
    $data['type'] = $role->getType();
    $data['name'] = $name;
    //get the data for a user
    if ($data['type'] == 0) {
        $data['uname'] = $role->getUser();
        $data['type'] = $role->getType();
        $data['email'] = xarVarPrepForDisplay($role->getEmail());
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
    $hooks = xarModCallHooks('item', 'display', $uid, $item);
    if (empty($hooks)) {
        $data['hooks'] = '';
    } elseif (is_array($hooks)) {
        $data['hooks'] = join('',$hooks);
    } else {
        $data['hooks'] = $hooks;
    }
    xarTplSetPageTitle(xarVarPrepForDisplay($data['name']));
    return $data;
}
?>