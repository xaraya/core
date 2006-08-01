<?php
/**
 * Modify role details
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
 * modifyrole - modify role details
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_modifyrole()
{
    if (!xarVarFetch('uid', 'int:1:', $uid)) return;
    if (!xarVarFetch('pname', 'str:1:', $name, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ptype', 'str:1', $type, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('puname', 'str:1:35:', $uname, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pemail', 'str:1:', $email, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ppass', 'str:1:', $pass, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state', 'str:1:', $state, '', XARVAR_DONT_SET)) return;
    if (!xarVarFetch('phome', 'str', $data['phome'], '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pprimaryparent', 'int', $data['primaryparent'], '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('utimezone','str:1:',$utimezone,'',XARVAR_NOT_REQUIRED)) return;
    // Call the Roles class and get the role to modify
    $roles = new xarRoles();
    $role = $roles->getRole($uid);

    // get the array of parents of this role
    $parents = array();
    $names = array();
    foreach ($role->getParents() as $parent) {
        //jojodee - This sec instance check works?
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
    if (!xarSecurityCheck('EditRole',1,'Roles',$name)) return;
    $data['frozen'] = !xarSecurityCheck('EditRole',0,'Roles',$name);

    if (isset($type)) {
        $data['ptype'] = $type;
    } else {
        $data['ptype'] = $role->getType();
    }

    if (!empty($uname)) {
        $data['puname'] = $uname;
    } else {
        $data['puname'] = $role->getUser();
    }

    if (!empty($phome)) {
        $data['phome'] = $phome;
    } else {
        $data['phome'] = $role->getHome();
    }
    //jojodee - this code is confusing - sometimes primary parent is int and sometimes string, very inconsistent
    //Let's decide - it is a string and just pass it's uid around for forms
    if (xarModGetVar('roles','setprimaryparent')) {
        if (!empty($primaryparent) && is_int($primaryparent)) { //we have a uid
            $data['pprimaryparent'] = $primaryparent;
        } else {
            $primaryparent = $role->getPrimaryParent(); //this is a string name
            $prole = xarUFindRole($primaryparent);
            $data['pprimaryparent'] = $prole->getID();//pass in the uid
        }
    } else {
        $data['pprimaryparent'] ='';
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
    if (xarModGetVar('roles','setpasswordupdate')) {
         $data['upasswordupdate'] = $role->getPasswordUpdate();
    }else {
         $data['upasswordupdate'] ='';
    }
    if (xarModGetVar('roles','setusertimezone')) {
        $usertimezone= $role->getUserTimezone();
        $usertimezonedata =unserialize($usertimezone);
        $data['utimezone']=$usertimezonedata['timezone'];
    } else {
        $data['utimezone']='';
    }

    if (xarModGetVar('roles','setuserlastlogin')) {
        //only display it for current user or admin
        if (xarUserIsLoggedIn() && xarUserGetVar('uid')==$uid) {
            $data['userlastlogin']=xarSessionGetVar('roles_thislastlogin');
        }elseif (xarSecurityCheck('AdminRole',0,'Roles',$name)&& xarUserGetVar('uid')!= $uid){
            $data['userlastlogin']= xarModGetUserVar('roles','userlastlogin',$uid);
        }else{
            $data['userlastlogin']='';
        }
    }else{
        $data['userlastlogin']='';
    }
    // call item modify hooks (for DD etc.)
    $item = $data;
    $item['module']= 'roles';
    $item['itemtype'] = $data['ptype']; // we might have something separate for groups later on
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