<?php
/**
 * File: $Id$
 *
 * Create email
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Xaraya Team
 */
function roles_admin_createmail()
{ 
    // TODO allow selection by group or user or all users.
    // Security check
    if (!xarSecurityCheck('MailRoles')) return;

    if (!xarVarFetch('uid', 'int:0:', $data['uid'], Null, XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('uids', 'isset', $uids, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state', 'str:0:', $data['state'], '0', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('order', 'str:0:', $data['order'], 'name', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('includesubgroups', 'int:0:', $data['includesubgroups'],0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('mailtype', 'str:0:', $data['mailtype'], 'blank', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('selstyle', 'isset', $data['selstyle'], '1', XARVAR_NOT_REQUIRED)) return;
    
    $data['groupuid'] = 0;
    $data['authid'] = xarSecGenAuthKey(); 
    $data['groups'] = xarModAPIFunc('roles',
                                   	'user',
                                   	'getallgroups');
    //selstyle
    $data['style'] = array('1' => xarML('Simple'),
                                       '2' => xarML('Details')
                                       );
    $roles = new xarRoles();           
                         
	if (isset($data['uid'])) {
        if ($data['uid'] == 0) {
          $users = xarModAPIFunc('roles','user','getall', array('state' => $data['state'], 'startnum' => '', 'numitems' => '', 'order' => $data['order'], 'selection' => ''));
           while (list($key, $user) = each($users)) {
                if (xarSecurityCheck('EditRole',0,'Roles',$user['name'])) {
                $data['users'][] = array('uid' => $user['uid'],
                    'name' => $user['name'],
                    'uname' => $user['uname'],
                    'email' => $user['email'],
                    'status' => $user['state'],
                    'date_reg' => $user['date_reg']
                    );
                }
           }
        } else {
            $role = $roles->getRole($data['uid']);
            if ($role->isUser() == 0) {
                //Get the users from this group
                $data['uid'] = 0;
                $data['groupuid'] = $role->getID();
                if ($data['includesubgroups'] == 1) $users = $role->getDescendants($data['state']);
                else $users = $role->getUsers($data['state']);
                
                while (list($key, $user) = each($users)) {
                    if (xarSecurityCheck('EditRole',0,'Roles',$user->getName())) {
                        $data['users'][] = array('uid' => $user->getID(),
                            'name' => $user->getName(),
                            'uname' => $user->getUser(),
                            'email' => $user->getEmail(),
                            'status' => $user->getState(),
                            'date_reg' => $user->getDateReg()
                            );
                    }
                }
            } else {
                $data['users'][] = array('uid' => $role->getID(),
                        'name' => $role->getName(),
                        'uname' => $role->getUser(),
                        'email' => $role->getEmail(),
                        'status' => $role->getState(),
                        'date_reg' => $role->getDateReg()
                        );
                $data['groupuid'] = -1;
            }
        }
	}
    /*
    if (isset($uids)) {
        $uidmail = array();
        foreach ($uids as $uid => $val) {
            //check if the user must be updated :
            $uidmail[] = $roles->getRole($uid);
        }
        $mailusers = $uidmail;
        while (list($key, $user) = each($mailusers)) {
              if (!xarSecurityCheck('EditRole',0,'Roles',$user->getName())) {
                $data['uids'][] = array('uid' => $user->getID(),
                    'name' => $user->getName(),
                    'uname' => $user->getUser(),
                    'email' => $user->getEmail(),
                    'status' => $user->getState(),
                    'date_reg' => $user->getDateReg()
                    );
            }
        }
    }
    */
    if (isset($data['users'])) $data['totalselected'] = count($data['users']);
    //templates select
    if ($data['mailtype'] == 'blank') {
        $data['subject'] = '';
        $data['message'] = '';
    } else {
        $data['subject'] = xarModGetVar('roles', $data['mailtype'].'title');
        $data['message'] = xarModGetVar('roles', $data['mailtype'].'email');
    }
    // Return the output that has been generated by this function to BL
    return $data;
} 

?>