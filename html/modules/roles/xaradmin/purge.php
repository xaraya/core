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
    if (!xarVarFetch('purgestate', 'int:1:', $data['purgestate'], NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('recallstate', 'int:1:', $data['recallstate'], NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('confirmation', 'int:1', $confirmation, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('submit', 'str', $submit, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('search', 'str', $data['search'], NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('startnum', 'int:1:', $startnum, 1, XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('uids', 'isset', $uids, array(), XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('groupuid', 'int:1', $data['groupuid'], 0, XARVAR_NOT_REQUIRED)) return;

    extract($args);

    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $rolestable = $xartable['roles'];

    $deleted = '[' . xarML('deleted') . ']';

    // Check for confirmation.
    if ($confirmation == 0) {

    }
    elseif ($confirmation ==1) {

        // The API function is called
            if (!xarModAPIFunc('roles',
                           'admin',
                           'purge',
                            array('state' => $data['purgestate']))) return;
    }
    elseif ($confirmation ==2) {

        $roleslist = new xarRoles();
        if ($data['groupuid'] != 0) $parentgroup = $roleslist->getRole($data['groupuid']);
        foreach ($uids as $uid => $val) {
            $role = $roleslist->getRole($uid);
            $state = $role->getType() ? 3 : $data['recallstate'];
            $uname = explode($deleted,$role->getUser());
//            echo $uname[0];exit;
            $query = "UPDATE $rolestable
                    SET xar_uname = '" . xarVarPrepForStore($uname[0]) .
                        "', xar_state = " . xarVarPrepForStore($state) ;
            $query .= " WHERE xar_uid = ".xarVarPrepForStore($uid);

            $result =& $dbconn->Execute($query);
            if (!$result) return;

            $parentgroup->addmember($role);
        }
    } else {
    }

     $numitems = xarModGetVar('roles', 'rolesperpage');
    // Make sure a value was retrieved for rolesperpage
    if (empty($numitems))
        $numitems = -1;

        $selection = " WHERE xar_state=0";
    //Create the selection
    if (!empty($data['search'])) {
        $selection .= " AND (";
        $selection .= "(xar_name LIKE '%" . $data['search'] . "%')";
        $selection .= " OR (xar_uname LIKE '%" . $data['search'] . "%')";
        $selection .= " OR (xar_email LIKE '%" . $data['search'] . "%')";
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
    if ($startnum != 0) {
        $result = $dbconn->SelectLimit($query, $numitems, $startnum-1);
        if (!$result) {return;}
    }

    if ($data['totalselect'] == 0) {
        $data['message'] = xarML('There are no deleted groups/users ');
    }
    else {
        $data['message']         = '';
    }

    $roles = array();
    $deleted = '[' . xarML('deleted') . ']';
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
            $roles[] = array(
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

    $data['groups'] = xarModAPIFunc('roles',
                                    'user',
                                    'getallgroups');
    $filter['startnum'] = '%%';
    $filter['state'] = $data['recallstate'];
    $filter['search'] = $data['search'];

    $data['authid']         = xarSecGenAuthKey();
    $data['submitPurge']    = xarML('Purge');
    $data['submitRecall']    = xarML('Recall');
    $data['roles'] = $roles;
    $data['pager'] = xarTplGetPager($startnum,
        $data['totalselect'],
        xarModURL('roles', 'admin', 'showusers',
            $filter),
        $numitems);
    // Return
    return $data;

}

?>