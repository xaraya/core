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

    if (!xarVarFetch('uid', 'int:0:', $uid, 0, XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('uids', 'isset', $uids, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state', 'str:0:', $data['state'], ROLES_STATE_CURRENT, XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('startnum', 'int:1:', $startnum, 1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('order', 'str:0:', $data['order'], 'xar_name', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('includesubgroups', 'int:0:', $data['includesubgroups'],0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('mailtype', 'str:0:', $data['mailtype'], 'blank', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('selstyle', 'isset', $selstyle, 0, XARVAR_NOT_REQUIRED)) return;

    // what type of email: a selection or a single email?
    if ($uid == 0) $type = 'selection';
//    $data['groupuid'] = 0;
    else {
        $roles = new xarRoles();
        $role = $roles->getRole($uid);
        $type = $role->getType() ? 'selection' : 'single';
    }

    if ($type == 'single') {
        $data['users'][$role->getID()] = array('uid' => $role->getID(),
            'name' => $role->getName(),
            'uname' => $role->getUser(),
            'email' => $role->getEmail(),
            'status' => $role->getState(),
            'date_reg' => $role->getDateReg()
        );
        if ($selstyle == 0) $selstyle =2;
    }
    else {
        if ($selstyle == 0) $selstyle =1;

        // Get the current query or create a new one if need be
        $q = unserialize(xarSessionGetVar('rolesquery'));
        $xartable =& xarDBGetTables();
        if(!isset($q)) {
            $q = new xarQuery('SELECT');
            $q->addtable($xartable['roles'],'r');
            $q->addfields(array('r.xar_uid','r.xar_name','r.xar_uname','r.xar_email','r.xar_state','r.xar_date_reg'));
        }
        // Set the paging and order stuff for this particular page
        $numitems = xarModGetVar('roles', 'rolesperpage');
        $q->setrowstodo($numitems);
        $q->setstartat($startnum);
        $q->setorder($data['order']);

        // Add state
        if ($data['state'] == ROLES_STATE_CURRENT) $q->ne('xar_state',ROLES_STATE_DELETED);
        elseif ($data['state'] == ROLES_STATE_ALL) {}
        else $q->eq('xar_state',$data['state']);

        // If a group was chosen, get only the users of that group
        if ($uid != 0) {
            $q->addtable($xartable['rolemembers'],'rm');
            $q->join('r.xar_uid','rm.xar_uid');
            $q->eq('rm.xar_parentid',$uid);
        }
        else {
        // this is a single user
            $data['groupuid'] = -1;
        }

        // open a connection and run the query
        $q->open();
        $q->run();
        foreach($q->output() as $role) {
            $data['users'][$role['r.xar_uid']] = array('uid' => $role['r.xar_uid'],
                'name' => $role['r.xar_name'],
                'uname' => $role['r.xar_uname'],
                'email' => $role['r.xar_email'],
                'status' => $role['r.xar_state'],
                'date_reg' => $role['r.xar_date_reg'],
                'frozen' => !xarSecurityCheck('EditRole',0,'Roles',$role['r.xar_name'])
                );
        }

        // Check if we also want to send to subgroups
        // In this case we'll just pick out the descendants in the same state
        if ($uid != 0 && ($data['includesubgroups'] == 1)) {
            $parentgroup = $roles->getRole($uid);
            $descendants = $parentgroup->getDescendants($data['state']);

            while (list($key, $user) = each($descendants)) {
                if (xarSecurityCheck('EditRole',0,'Roles',$user->getName())) {
                    $data['users'][$user->getID()] = array('uid' => $user->getID(),
                        'name' => $user->getName(),
                        'uname' => $user->getUser(),
                        'email' => $user->getEmail(),
                        'status' => $user->getState(),
                        'date_reg' => $user->getDateReg()
                        );
                }
            }
        }
    }
    // Assemble the data for the template
    $data['type'] = $type;
    $data['selstyle'] = $selstyle;
    $data['uid'] = $uid;
    $data['authid'] = xarSecGenAuthKey();
    $data['groups'] = xarModAPIFunc('roles',
                                    'user',
                                    'getallgroups');
    //selstyle
    $data['style'] = array('1' => xarML('Simple'),
                                       '2' => xarML('Details')
                                       );
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