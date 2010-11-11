<?php
/**
 * Get all users
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * get all users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array   $args array of parameters
 * @param $args['order'] comma-separated list of order items; default 'name'
 * @param $args['selection'] extra coonditions passed into the where-clause
 * @param $args['group'] comma-separated list of group names or IDs, or
 * @param $args['idlist'] array of user ids
 * @return mixed array of users, or false on failure
 */
function roles_userapi_getall(Array $args=array())
{
    extract($args);
    // LEGACY
    if ((empty($idlist) && !empty($uidlist))) {
        $idlist = $uidlist;
    }

    // Optional arguments.
    if (!isset($startnum)) $startnum = 1;
    if (!isset($numitems)) $numitems = -1;

    // Security check - need overview level to see that the roles exist
    if (!xarSecurityCheck('ViewRoles')) return;

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $rolestable = $xartable['roles'];
    $rolemembtable = $xartable['rolemembers'];

    // Create the order array.
    if (!isset($order)) {
        $order_clause = array('roletab.name');
    } else {
        $order_clause = array();
        foreach (explode(',', $order) as $order_field) {
            if (preg_match('/^[-]?(name|uname|email|id|state|date_reg)$/', $order_field)) {
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
            $group = xarMod::apiFunc(
                'roles', 'user', 'get',
                array(
                    (is_numeric($group) ? 'id' : 'name') => $group,
                    'itemtype' => xarRoles::ROLES_GROUPTYPE
                )
            );
            if (isset($group['id']) && is_numeric($group['id'])) {
                $group_list[] = (int) $group['id'];
            }
        }
        if (empty($group_list)) return array();
    }

    $where_clause = array();
    $bindvars = array();
    if (!empty($state) && is_numeric($state) && $state != xarRoles::ROLES_STATE_CURRENT) {
        $where_clause[] = 'roletab.state = ?';
        $bindvars[] = (int) $state;
    } else {
        $where_clause[] = 'roletab.state <> ?';
        $bindvars[] = (int) xarRoles::ROLES_STATE_DELETED;
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
        $where_clause[] = 'roletab.id = rolememb.role_id';
        if (count($group_list) > 1) {
            $bindmarkers = '?' . str_repeat(',?',count($group_list)-1);
            $where_clause[] = 'rolememb.parent_id in (' . $bindmarkers. ')';
            $bindvars = array_merge($bindvars, $group_list);
        } else {
            $where_clause[] = 'rolememb.parent_id = ?';
            $bindvars[] = $group_list[0];
        }
    }

    // Hide pending users from non-admins
    if (!xarSecurityCheck('AdminRoles', 0)) {
        $where_clause[] = 'roletab.state <> ?';
        $bindvars[] = (int) xarRoles::ROLES_STATE_PENDING;
    }

    // If we aren't including anonymous in the query,
    // then find the anonymous user's id and add
    // a where clause to the query.
    if (isset($include_anonymous) && !$include_anonymous) {
        $thisrole = xarMod::apiFunc('roles', 'user', 'get', array('uname'=>'anonymous'));
        $where_clause[] = 'roletab.id <> ?';
        $bindvars[] = (int) $thisrole['id'];
    }

    // Return only users (not groups).
    $where_clause[] = 'roletab.itemtype = ' . xarRoles::ROLES_USERTYPE;

    // Add the where-clause to the query.
    $query .= ' WHERE ' . implode(' AND ', $where_clause);

    // Add extra where-clause criteria.
    if (isset($selection)) {
        $query .= ' ' . $selection;
    }

    if (isset($idlist) && is_array($idlist) && count($idlist) > 0) {
        $query .= ' AND roletab.id IN (' . join(',',$idlist) . ') ';
    }

    // Add the order clause.
    if (!empty($order_clause)) {
        $query .= ' ORDER BY ' . implode(', ', $order_clause);
    }

    // We got the complete query, prepare it
    $stmt = $dbconn->prepareStatement($query);

    // cfr. xarcachemanager - this approach might change later
    $expire = xarModVars::get('roles', 'cache.userapi.getall');

    if($startnum > 0) {
        $stmt->setLimit($numitems);
        $stmt->setOffset($startnum - 1 );
    }
    // Statement constructed, create a resultset out of it
    $result = $stmt->executeQuery($bindvars);

    // Put users into result array
    sys::import('modules.dynamicdata.class.properties.master');
    $nameproperty = DataPropertyMaster::getProperty(array('name' => 'name'));
    
    $roles = array();
    while($result->next()) {
        list($id, $uname, $name, $email, $pass, $state, $date_reg) = $result->fields;
        if (xarSecurityCheck('ReadRoles', 0, 'Roles', "$uname")) {

            $nameproperty->value = $name;
            $name = $nameproperty->getValue();
            
            if (!empty($idlist)) {
                $roles[$id] = array(
                    'id'       => (int) $id,
                    'uname'     => $uname,
                    'name'      => $name,
                    'email'     => $email,
                    'pass'      => $pass,
                    'state'     => $state,
                    'date_reg'  => $date_reg
                );
            } else {
                $roles[] = array(
                    'id'       => (int) $id,
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
            if (!empty($idlist)) {
                $roles[$id] = array(
                    'id'       => (int) $id,
                    'name'      => $name,
                    'date_reg'  => $date_reg
                );
            } else {
                $roles[] = array(
                    'id'       => (int) $id,
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