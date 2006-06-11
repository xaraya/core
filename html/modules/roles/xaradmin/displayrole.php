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
                           'parentname' => $parent->getName(),
                           'parentuname' => $parent->getUname());
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
    
    $primaryparent = $role->getPrimaryParent();
    $prole = xarUFindRole($primaryparent);
    $data['primaryparent'] = $primaryparent;
    $data['pprimaryparent'] = $prole->getID();//pass in the uid
    
    if (xarModGetVar('roles','setprimaryparent')) { //we have activated primary parent
        if (!isset($data['phome']) || empty ($data['phome'])) {
            $parenthome = $prole->getHome(); //get the primary parent home
            $data['parenthome']=$parenthome;
        }
    } else {
        $data['parenthome']='';
    }
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
    if (xarModGetVar('roles','setuserlastlogin')) {
        //only display it for current user or admin
        if (xarUserIsLoggedIn() && xarUserGetVar('uid')==$uid) {
            $data['userlastlogin']=xarSessionGetVar('roles_thislastlogin');
        }elseif (xarSecurityCheck('AdminRole',0,'Roles',$name) && xarModGetUserVar('roles','userlastlogin',$uid)<>''){
            $data['userlastlogin']=xarModGetUserVar('roles','userlastlogin',$uid);
        }else{
            $data['userlastlogin']='';
        }
    }else{
        $data['userlastlogin']='';
    }

    $data['upasswordupdate'] = xarModGetUserVar('roles','passwordupdate');//now user mod var not 'duv'. $role->getPasswordUpdate();

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