<?php
/**
 * File: $Id$
 *
 * Purge users by status
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Xaraya Team
 */
/**
 * purge users by status
 * @param 'status' the status we are purging
 * @param 'confirmation' confirmation that this item can be purge
 */
function roles_admin_purge($args)
{
    // Security Check
    if(!xarSecurityCheck('DeleteRole')) return;

    // Get parameters from whatever input we need
    if (!xarVarFetch('operation', 'str', $data['operation'], 'recall', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirmation', 'str', $confirmation, 0, XARVAR_NOT_REQUIRED)) return;

    extract($args);

    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $rolestable = $xartable['roles'];

    $deleted = '[' . xarML('deleted') . ']';
    $numitems = xarModGetVar('roles', 'rolesperpage');
    // Make sure a value was retrieved for rolesperpage
    if (empty($numitems)) $numitems = -1;

    if ($data['operation'] == 'recall')
    {
        if (!xarVarFetch('recallstate', 'int:1:', $data['recallstate'], NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('recallsubmit', 'str', $recallsubmit, NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('recallsearch', 'str', $data['recallsearch'], NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('recallstartnum', 'int:1:', $recallstartnum, 1, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('recalluids', 'isset', $recalluids, array(), XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('groupuid', 'int:1', $data['groupuid'], 0, XARVAR_NOT_REQUIRED)) return;

        if ($confirmation == "Recall")
        {
    // --- recall users and groups
            if(!xarSecurityCheck('DeleteRole')) return;
            $roleslist = new xarRoles();
            if ($data['groupuid'] != 0) $parentgroup = $roleslist->getRole($data['groupuid']);
            foreach ($recalluids as $uid => $val) {
                $role = $roleslist->getRole($uid);
                $state = $role->getType() ? ROLES_STATE_ACTIVE : $data['recallstate'];
                $recalled = xarModAPIFunc('roles','admin','recall',
                    array('uid' => $uid,
                          'state' => $state));
                $parentgroup->addmember($role);
            }
        }
// --- display roles that can be recalled
        $selection = " WHERE xar_state = " . ROLES_STATE_DELETED . " AND xar_email != ''";
        //Create the selection
        if (!empty($data['recallsearch'])) {
            $selection .= " AND (";
            $selection .= "(xar_name LIKE '%" . $data['recallsearch'] . "%')";
            $selection .= " OR (xar_uname LIKE '%" . $data['recallsearch'] . "%')";
            $selection .= " OR (xar_email LIKE '%" . $data['recallsearch'] . "%')";
            $selection .= ")";
        }
        // Select-clause.
        $query = '
            SELECT DISTINCT xar_uid,
                    xar_uname,
                    xar_name,
                    xar_email,
                    xar_type,
                    xar_date_reg
                    FROM ' . $rolestable .
                    $selection .
                    ' ORDER BY xar_name';

        $result = $dbconn->Execute($query);
        $data['totalselect'] = $result->_numOfRows;
        if (!$result) {return;}
        if ($recallstartnum != 0) {
            $result = $dbconn->SelectLimit($query, $numitems, $recallstartnum-1);
            if (!$result) {return;}
        }

        if ($data['totalselect'] == 0) {
            $data['recallmessage'] = xarML('There are no deleted groups/users ');
        }
        else {
            $data['recallmessage']         = '';
        }

        $recallroles = array();
        for (; !$result->EOF; $result->MoveNext()) {
            list($uid, $uname, $name, $email, $type, $date_reg) = $result->fields;
            if (xarSecurityCheck('ReadRole', 0, 'All', "$uname:All:$uid")) {
                $unique = 1;
                if ($type) {
                     $uname = "";
                }
                else {
                    $uname1 = explode($deleted,$uname);
                    $existinguser = xarModAPIFunc('roles','user','get',array('uname' => $uname1[0]));
                    if (is_array($existinguser)) $unique = 0;
                    $uname = $uname1[0];
               }
                $type = $type ? "Group" : "User";
                $recallroles[] = array(
                    'uid'       => $uid,
                    'uname'     => $uname,
                    'name'      => $name,
                    'email'     => $email,
                    'type'      => $type,
                    'date_reg'  => $date_reg,
                    'unique'    => $unique
                );
            }
        }
// --- send to template
        $data['groups'] = xarModAPIFunc('roles',
                                        'user',
                                        'getallgroups');
        $recallfilter['recallstartnum'] = '%%';
        $filter['state'] = $data['recallstate'];
        $recallfilter['recallsearch'] = $data['recallsearch'];
        $data['submitRecall']    = xarML('Recall');
        $data['recallroles'] = $recallroles;
        $data['recallpager'] = xarTplGetPager($recallstartnum,
            $data['totalselect'],
            xarModURL('roles', 'admin', 'purge',
                $recallfilter),
            $numitems);
    }
//--------------------------------------------------------
    elseif ($data['operation'] == 'purge')
    {
        if (!xarVarFetch('purgestate', 'int', $data['purgestate'], -1, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('purgesearch', 'str', $data['purgesearch'], NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('purgesubmit', 'str', $purgesubmit, NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('purgestartnum', 'int:1:', $purgestartnum, 1, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('purgeuids', 'isset', $purgeuids, array(), XARVAR_NOT_REQUIRED)) return;

        // Check for confirmation.
        if ($confirmation == "Purge")
        {
// --- purge users
            if(!xarSecurityCheck('AdminRole')) return;
            $roleslist = new xarRoles();
            foreach ($purgeuids as $uid => $val) {
                $role = $roleslist->getRole($uid);
                $state = ROLES_STATE_DELETED;
                $uname = $deleted . mktime();
                $name = '';
                $pass = '';
                $email = '';
                $query = "UPDATE $rolestable
                        SET xar_uname = " . $dbconn->qstr($uname) . ",
                             xar_name = " . $dbconn->qstr($uname) . ",
                             xar_pass = " . $dbconn->qstr($pass) . ",
                             xar_email = " . $dbconn->qstr($email) . ",
                             xar_state = " . $state ;
                $query .= " WHERE xar_uid = ". $uid;
                $result =& $dbconn->Execute($query);
                if (!$result) return;
            }
        }

// --- display users that can be purged
        $selection = " WHERE xar_email != ''";
        //Create the selection
        if ($data['purgestate'] != -1) {
            $selection .= " AND xar_state = " . $data['purgestate'];
            switch ($data['purgestate']):
                case ROLES_STATE_DELETED :
                    $data['purgestatetext'] = 'deleted';
                    break ;
                case ROLES_STATE_INACTIVE :
                    $data['purgestatetext'] = 'inactive';
                    break ;
                case ROLES_STATE_NOTVALIDATED :
                    $data['purgestatetext'] = 'not validated';
                    break ;
                case ROLES_STATE_ACTIVE :
                    $data['purgestatetext'] = 'active';
                    break ;
                case ROLES_STATE_PENDING :
                    $data['purgestatetext'] = 'pending';
                    break ;
            endswitch ;
        }
        else {
            $data['purgestatetext'] = '';
        }
        if (!empty($data['purgesearch'])) {
            $selection .= " AND (";
            $selection .= "(xar_name LIKE '%" . $data['purgesearch'] . "%')";
            $selection .= " OR (xar_uname LIKE '%" . $data['purgesearch'] . "%')";
            $selection .= " OR (xar_email LIKE '%" . $data['purgesearch'] . "%')";
            $selection .= ")";
        }
        // Select-clause.
        $query = '
            SELECT DISTINCT xar_uid,
                    xar_uname,
                    xar_name,
                    xar_email,
                    xar_state,
                    xar_date_reg
                    FROM ' . $rolestable .
                    $selection .
                    ' ORDER BY xar_name';

        $result = $dbconn->Execute($query);
        $data['totalselect'] = $result->_numOfRows;
        if (!$result) {return;}
        if ($purgestartnum != 0) {
            $result = $dbconn->SelectLimit($query, $numitems, $purgestartnum-1);
            if (!$result) {return;}
        }

        if ($data['totalselect'] == 0) {
            $data['purgemessage'] = xarML('There are no users selected');
        }
        else {
            $data['purgemessage']         = '';
        }

        $purgeusers = array();
        for (; !$result->EOF; $result->MoveNext()) {
            list($uid, $uname, $name, $email, $state, $date_reg) = $result->fields;
            switch ($state):
                case ROLES_STATE_DELETED :
                    $state = 'deleted';
                    break ;
                case ROLES_STATE_INACTIVE :
                    $state = 'inactive';
                    break ;
                case ROLES_STATE_NOTVALIDATED :
                    $state = 'not validated';
                    break ;
                case ROLES_STATE_ACTIVE :
                    $state = 'active';
                    break ;
                case ROLES_STATE_PENDING :
                    $state = 'pending';
                    break ;
            endswitch ;
            $purgeusers[] = array(
                'uid'       => $uid,
                'uname'     => $uname,
                'name'      => $name,
                'email'     => $email,
                'state'      => $state,
                'date_reg'  => $date_reg
            );
        }
// --- send to template
        $purgefilter['purgestartnum'] = '%%';
        $purgefilter['purgesearch'] = $data['purgesearch'];

        $data['submitPurge']    = xarML('Purge');
        $data['purgeusers'] = $purgeusers;
        $data['purgepager'] = xarTplGetPager($purgestartnum,
            $data['totalselect'],
            xarModURL('roles', 'admin', 'purge',
                $purgefilter),
            $numitems);
    }
    else {}

// --- finish up
    $data['authid']         = xarSecGenAuthKey();
    // Return
    return $data;
}

?>