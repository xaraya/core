<?php
/**
 * File: $Id$
 *
 * Send mail
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Xaraya Team
 */
 
function roles_admin_sendmail()
{
    // Get parameters from whatever input we need
    if (!xarVarFetch('uid', 'int:0:', $uid, 0)) return;
    if (!xarVarFetch('state', 'int:0:', $state, 0)) return;
    if (!xarVarFetch('groupuid', 'int:0:', $groupuid, 0)) return;
    if (!xarVarFetch('message', 'str:1:', $message)) return;
    if (!xarVarFetch('subject', 'str:1', $subject)) return;
    xarVarFetch('includesubgroups','int:0:',$includesubgroups,0,XARVAR_NOT_REQUIRED);

    // Confirm authorisation code.
    if (!xarSecConfirmAuthKey()) return;
    // Security check
    if (!xarSecurityCheck('MailRoles')) return;
    // Get user information

    $roles = new xarRoles();
    if ($uid != 0) {
        $role = $roles->getRole($uid);
        //$user = xarModAPIFunc('roles','user','get', array('uid' => $uid));
        //verify if it's not frozen
        if (xarSecurityCheck('EditRole',0,'Roles',$role->getName()))  {
            //send the mail
            if (!xarModAPIFunc('mail',
                'admin',
                'sendmail',
                array('info' => $role->getEmail(),
                    'name' => $role->getName(),
                    'subject' => $subject,
                    'message' => $message))) return;
        }

         if ($groupuid == 0) $groupuid = $role->getParents();
    }
    else {
        if ($groupuid == 0) {
            $users = xarModAPIFunc('roles','user','getall', array('state' => $state));
            foreach ($users as $user) {
                //verify if it's not frozen
                if (xarSecurityCheck('EditRole',0,'Roles',$user['name'])){
                    //send the mail
                    if (!xarModAPIFunc( 'mail',
                                    'admin',
                                    'sendmail',
                                    array('info' => $user['email'],
                                        'name' => $user['name'],
                                        'subject' => $subject,
                                        'message' => $message))) return;
                }

            }
        } else {
            $role = $roles->getRole($groupuid);

            if(!$includesubgroups){
                $users = $role->getUsers($state);
            }else{
                //TODO: roll this into a getDescendants() method in class xarrole
                $users = roles_admin_sendmail__getsubusers($groupuid,$state);
            }
            //foreach ($users as $user) {
            while (list($key, $user) = each($users)) {
                //verify if it's not frozen
                if (xarSecurityCheck('EditRole',0,'Roles',$user->getName())){
                    //send the mail
                    if (!xarModAPIFunc( 'mail',
                                    'admin',
                                    'sendmail',
                                    array('info' => $user->getEmail(),
                                        'name' => $user->getName(),
                                        'subject' => $subject,
                                        'message' => $message))) return;
                }
            }
        }
    }
    xarResponseRedirect(xarModURL('roles', 'admin', 'showusers', array('uid' => $groupuid, 'state' => $state)));
    // Return
    return true;
}

function roles_admin_sendmail__getsubusers($uid, $state)
{

    $roles = new xarRoles();
    $role = $roles->getRole($uid);
    $users = $role->getUsers($state);

    $ua = array();
    foreach($users as $user){
        //using the ID as the key so that if a person is in more than one sub group they only get one email
        $ua[$user->getID()] = $user;
    }

    //Get the sub groups and go for another round
    $groups = $roles->getSubGroups($uid);
    foreach($groups as $group){
        $users = roles_admin_sendmail__getsubusers($group['uid'], $state);
        foreach($users as $user){
            $ua[$user->getID()] = $user;
        }
    }

    return($ua);
}

?>