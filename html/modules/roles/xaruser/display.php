<?php
/**
 * display user
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
 * Display user
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @param int uid
 * @return array
 */
function roles_user_display($args)
{
    extract($args);

    if (!xarVarFetch('uid','id',$uid, xarUserGetVar('uid'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemid', 'int', $itemid, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('itemtype', 'int', $itemtype, 1, XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('tplmodule', 'str', $args['tplmodule'], 'roles', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('template', 'str', $args['template'], '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('layout', 'str', $args['layout'], '', XARVAR_NOT_REQUIRED)) {return;}

    $uid = isset($itemid) ? $itemid : $uid;

    if ($uid) {
        // Get role information
        $roles = new xarRoles();
        $role = $roles->getRole($uid);

        if (!$role) return;

        $name = $role->getName();
    // Security Check
        if(!xarSecurityCheck('ViewRoles',0,'Roles',$name)) return;

        $data['uid'] = $role->getID();
        $itemtype = $role->getType();
        $data['itemtype'] = $itemtype;
        $data['name'] = $name;
        //get the data for a user
        $data['basetype'] = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));
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
        $item['itemtype'] = $data['itemtype'];
        $item['itemid']= $uid;
        $item['returnurl'] = xarModURL('roles', 'user', 'display',
                                       array('uid' => $uid));
        $data['hooks'] = xarModCallHooks('item', 'display', $uid, $item);

        xarTplSetPageTitle(xarVarPrepForDisplay($data['name']));
    } else {
        $data['uid'] = $uid;
    }

    $types = xarModAPIFunc('roles','user','getitemtypes');
    $data['itemtypename'] = $types[$itemtype]['label'];
    $data['layout'] = $args['layout'];

    return xarTplModule($args['tplmodule'],'user','display',$data,$args['template']);
}

?>
