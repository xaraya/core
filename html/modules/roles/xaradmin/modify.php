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

sys::import('modules.dynamicdata.class.objects.master');
/**
 * modify - modify role details
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_modify()
{
    if (!xarVarFetch('uid', 'id', $uid, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemid', 'id', $itemid, NULL, XARVAR_DONT_SET)) return;
    $uid = isset($itemid) ? $itemid : $uid;


//    if (!xarVarFetch('itemtype', 'id', $itemtype, ROLES_USERTYPE, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('duvs', 'array', $data['duvs'], array(), XARVAR_NOT_REQUIRED)) return;


    $object = xarRoles::get($uid);
    $data['basetype'] = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $object->getType()));

//    $itemid = $object->getItem(array('itemid' => $uid));
//    $values = $object->getFieldValues();
//    $name = $values['name'];

//    $role = xarRoles::get($uid);
    // get the array of parents of this role
    // need to display this in the template
    // we also use this loop to fill the names array with groups that this group shouldn't be added to
    $parents = array();
    $names = array();

    foreach ($object->getParents() as $parent) {
        if(xarSecurityCheck('RemoveRole',0,'Relation',$parent->getName() . ":" . $object->getName())) {
            $parents[] = array('parentid' => $parent->getID(),
                               'parentname' => $parent->getName(),
                               'parentuname'=> $parent->getUname());
            $names[] = $parent->getName();
        }
    }
//    $data['parents'] = $parents;

    // remove duplicate entries from the list of groups
    // get the array of all roles, minus the current one
    // need to display this in the template
    $groups = array();
    foreach(xarRoles::getgroups() as $temp) {
        $nam = $temp['name'];
        // TODO: this is very inefficient. Here we have the perfect use case for embedding security checks directly into the SQL calls
        if(!xarSecurityCheck('AttachRole',0,'Relation',$nam . ":" . $object->getName())) continue;
        if (!in_array($nam, $names) && $temp['uid'] != $uid) {
            $names[] = $nam;
            $groups[] = array('duid' => $temp['uid'],
                'dname' => $temp['name']);
        }
    }

    xarSession::setVar('ddcontext.roles', array(
                                            'return_url' => xarServerGetCurrentURL(),
                                            'parents' => $parents,
                                            'groups' => $groups,
                                            'basetype' => $data['basetype'],
                                                ));

    if (!xarSecurityCheck('EditRole',0,'Roles',$object->getName())) {
        if (!xarSecurityCheck('ReadRole',1,'Roles',$object->getName())) return;
    }

    $data['object'] = &$object;

    // call item modify hooks (for DD etc.)
    $item = $data;
    $item['module']= 'roles';
    $item['itemtype'] = $object->getType();
    $item['itemid']= $uid;
    $data['hooks'] = xarModCallHooks('item', 'modify', $uid, $item);

    $data['groups'] = $groups;
    $data['parents'] = $parents;
    $data['authid'] = xarSecGenAuthKey('dynamicdata');
    return $data;
}

?>
