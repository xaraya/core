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
    if (!xarVarFetch('itemtype','id',$itemtype, 1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('uid','int:1:',$uid)) return;


    $data = array();
    sys::import('modules.roles.class.roles');
    $role = xarRoles::getRole($uid);

    $data['itemtype'] = $role->getType();
    $data['basetype'] = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $data['itemtype']));

    $object = xarModAPIFunc('dynamicdata','user','getobject',array('module'   => 'roles',
                                                'itemtype' => $data['basetype']));

    $itemid = $object->getItem(array('itemid' => $uid));

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

    $data['uid'] = $uid;

    $types = xarModAPIFunc('roles','user','getitemtypes');

    $data['name'] = $name;

    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = $data['itemtype']; // handle groups differently someday ?
    $item['returnurl'] = xarModURL('roles', 'user', 'display',
                                   array('uid' => $uid));
    $hooks = array();
    $hooks = xarModCallHooks('item', 'display', $uid, $item);
    $data['hooks'] = $hooks;
    $data['object'] = & $object;
    xarTplSetPageTitle(xarVarPrepForDisplay($data['name']));
    return $data;
}
?>
