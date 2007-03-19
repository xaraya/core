<?php
/**
 * Get all users
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
 * get all users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['order'] comma-separated list of order items; default 'name'
 * @param $args['selection'] extra coonditions passed into the where-clause
 * @param $args['group'] comma-separated list of group names or IDs, or
 * @param $args['uidlist'] array of user ids
 * @returns array
 * @return array of users, or false on failure
 */
function roles_userapi_getall($args)
{
    extract($args);

    // Optional arguments.
    if (!isset($startnum)) $startnum = 1;
    if (!isset($numitems)) $numitems = -1;

    // Security check - need overview level to see that the roles exist
    if (!xarSecurityCheck('ViewRoles')) return;

    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolestable = $xartable['roles'];
    $rolemembtable = $xartable['rolemembers'];

    // Create the order array.
    if (!isset($order)) {
        $order_clause = array('roletab.name');
    } else {
        $order_clause = array();
        foreach (explode(',', $order) as $order_field) {
            if (preg_match('/^[-]?(name|uname|email|uid|state|date_reg)$/', $order_field)) {
                if (strstr($order_field, '-')) {
                    $order_clause[] = 'roletab.' . str_replace('-', '', $order_field) . ' desc';
                } else {
                    $order_clause[] = 'roletab.' . $order_field;
                }
            }
        }
    }

    // Restriction by group.
    if (isset($group)) {
        $groups = explode(',', $group);
        $group_list = array();
        foreach ($groups as $group) {
            $group = xarModAPIFunc(
                'roles', 'user', 'get',
                array(
                    (is_numeric($group) ? 'uid' : 'name') => $group,
                    'type' => ROLES_GROUPTYPE
                )
            );
            if (isset($group['uid']) && is_numeric($group['uid'])) {
                $group_list[] = (int) $group['uid'];
            }
        }
    }

    $where_clause = array();
    $bindvars = array();
    if (!empty($state) && is_numeric($state) && $state != ROLES_STATE_CURRENT) {
        $where_clause[] = 'roletab.state = ?';
        $bindvars[] = (int) $state;
    } else {
        $where_clause[] = 'roletab.state <> ?';
        $bindvars[] = (int) ROLES_STATE_DELETED;
    }

    if (empty($group_list)) {
        // Simple query.
        $query = '
            SELECT  roletab.id,
                    roletab.uname,
                    roletab.name,
                    roletab.email,
                    roletab.pass,
                    roletab.state,
                    roletab.date_reg';
        $query .= ' FROM ' . $rolestable . ' AS roletab';
    } else {
        // Select-clause.
        $query = '
            SELECT  DISTINCT roletab.id,
                    roletab.uname,
                    roletab.name,
                    roletab.email,
                    roletab.pass,
                    roletab.state,
                    roletab.date_reg';
        // Restrict by group(s) - join to the group_members table.
        $query .= ' FROM ' . $rolestable . ' AS roletab, ' . $rolemembtable . ' AS rolememb';
        $where_clause[] = 'roletab.id = rolememb.id';
        if (count($group_list) > 1) {
            $bindmarkers = '?' . str_repeat(',?',count($group_list)-1);
            $where_clause[] = 'rolememb.parentid in (' . $bindmarkers. ')';
            $bindvars = array_merge($bindvars, $group_list);
        } else {
            $where_clause[] = 'rolememb.parentid = ?';
            $bindvars[] = $group_list[0];
        }
    }

    // Hide pending users from non-admins
    if (!xarSecurityCheck('AdminRole', 0)) {
        $where_clause[] = 'roletab.state <> ?';
        $bindvars[] = (int) ROLES_STATE_PENDING;
    }

    // If we aren't including anonymous in the query,
    // then find the anonymous user's uid and add
    // a where clause to the query.
    // By default, include both 'myself' and 'anonymous'.
    if (isset($include_anonymous) && !$include_anonymous) {
        $thisrole = xarModAPIFunc('roles', 'user', 'get', array('uname'=>'anonymous'));
        $where_clause[] = 'roletab.id <> ?';
        $bindvars[] = (int) $thisrole['uid'];
    }
    if (isset($include_myself) && !$include_myself) {

        $thisrole = xarModAPIFunc('roles', 'user', 'get', array('uname'=>'myself'));
        $where_clause[] = 'roletab.id <> ?';
        $bindvars[] = (int) $thisrole['uid'];
    }

    // Return only users (not groups).
    $where_clause[] = 'roletab.type = ' . ROLES_USERTYPE;

    // Add the where-clause to the query.
    $query .= ' WHERE ' . implode(' AND ', $where_clause);

    // Add extra where-clause criteria.
    if (isset($selection)) {
        $query .= ' ' . $selection;
    }

    if (isset($uidlist) && is_array($uidlist) && count($uidlist) > 0) {
        $query .= ' AND roletab.id IN (' . join(',',$uidlist) . ') ';
    }

    // Add the order clause.
    if (!empty($order_clause)) {
        $query .= ' ORDER BY ' . implode(', ', $order_clause);
    }

    // We got the complete query, prepare it
    $stmt = $dbconn->prepareStatement($query);

    // cfr. xarcachemanager - this approach might change later
    $expire = xarModGetVar('roles', 'cache.userapi.getall');

    if($startnum > 0) {
        $stmt->setLimit($numitems);
        $stmt->setOffset($startnum - 1 );
    }
    // Statement constructed, create a resultset out of it
    $result = $stmt->executeQuery($bindvars);

    // Put users into result array
    $roles = array();
    while($result->next()) {
        list($uid, $uname, $name, $email, $pass, $state, $date_reg) = $result->fields;
        if (xarSecurityCheck('ReadRole', 0, 'Roles', "$uname")) {
            if (!empty($uidlist)) {
                $roles[$uid] = array(
                    'uid'       => (int) $uid,
                    'uname'     => $uname,
                    'name'      => $name,
                    'email'     => $email,
                    'pass'      => $pass,
                    'state'     => $state,
                    'date_reg'  => $date_reg
                );
            } else {
                $roles[] = array(
                    'uid'       => (int) $uid,
                    'uname'     => $uname,
                    'name'      => $name,
                    'email'     => $email,
                    'pass'      => $pass,
                    'state'     => $state,
                    'date_reg'  => $date_reg
                );
            }
        } elseif (xarSecurityCheck('ViewRoles', 0, 'Roles', "$uname")) {
            // If we only have overview privilege, then supply more restricted information.
            if (!empty($uidlist)) {
                $roles[$uid] = array(
                    'uid'       => (int) $uid,
                    'name'      => $name,
                    'date_reg'  => $date_reg
                );
            } else {
                $roles[] = array(
                    'uid'       => (int) $uid,
                    'name'      => $name,
                    'date_reg'  => $date_reg
                );
            }
        }
    }
    // Return the users
    return $roles;
}

?>
