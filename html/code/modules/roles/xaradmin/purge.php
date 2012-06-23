<?php
/**
 * Purge users by status
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * purge users by status
 * @param 'status' the status we are purging
 * @param 'confirmation' confirmation that this item can be purge
 * @todo kinda long, no?
 * @return array data for the template display
 */
function roles_admin_purge(Array $args=array())
{
    // Security
    if(!xarSecurityCheck('ManageRoles')) return;

    // Get parameters from whatever input we need
    if (!xarVarFetch('operation',    'str', $data['operation'], 'recall', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirmation', 'str', $confirmation,       0,       XARVAR_NOT_REQUIRED)) return;

    extract($args);

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $rolestable = $xartable['roles'];

    $deleted = '[' . xarML('deleted') . ']';
    $numitems = (int)xarModVars::get('roles', 'items_per_page');
    // Make sure a value was retrieved for items_per_page
    if (empty($numitems)) $numitems = -1;

    if ($data['operation'] == 'recall')
    {
        if (!xarVarFetch('recallstate',    'int:1:', $data['recallstate'],  NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('recallsubmit',   'str',    $recallsubmit,         NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('recallsearch',   'str',    $data['recallsearch'], NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('startnum', 'int:1:', $startnum,       1,    XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('recallids',      'isset',  $recallids,           array(), XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('groupid',       'int:1',  $data['groupid'],     0,    XARVAR_NOT_REQUIRED)) return;

        if ($confirmation == xarML("Recall"))
        {
 // --- recall users and groups
            if(!xarSecurityCheck('ManageRoles')) return;
            if ($data['groupid'] != 0) $parentgroup = xarRoles::get($data['groupid']);
            foreach ($recallids as $id => $val) {
                $role = xarRoles::get($id);
                $state = $role->getType() ? xarRoles::ROLES_STATE_ACTIVE : $data['recallstate'];
                $recalled = xarMod::apiFunc('roles','admin','recall',
                    array('id' => $id,
                          'state' => $state));
                $parentgroup->addmember($role);
            }
        }
// --- display roles that can be recalled
        //Create the selection
        $query = "SELECT id, uname, name, email, itemtype, date_reg FROM $rolestable WHERE state = ? AND date_reg != ?" ;
        $bindvars[] = xarRoles::ROLES_STATE_DELETED;
        $bindvars[] = 0;

        if (!empty($data['recallsearch'])) {
            $query .= " AND (name LIKE %" . $data['recallsearch'] . "%";
            $query .= " OR uname LIKE %" . $data['recallsearch'] . "%";
            $query .= " OR email LIKE %" . $data['recallsearch'] . "%)";
        }
        $query .= " ORDER BY name";
        $result = $dbconn->SelectLimit($query, $numitems, $startnum-1, $bindvars);
        $roles = array();

        while(!$result->EOF) {
            list($id,$uname,$name,$email,$itemtype,$date_reg) = $result->fields;
            $roles[] = array(
                'id' => $id,
                'uname' => $uname,
                'name' => $name,
                'email' => $email,
                'itemtype' => $itemtype,
                'date_reg' => $date_reg,
            );
            $result->next();
        }
        $data['totalselect'] = count($roles);

        if ($data['totalselect'] == 0) {
            $data['recallmessage'] = xarML('There are no deleted groups/users ');
        } else {
            $data['recallmessage']         = '';
        }

        $recallroles = array();
        foreach ($roles as $role) {
// check each role's user name
            if (empty($role['uname'])) {
                $msg = xarML('Execution halted: the role with id #(1) has an empty name. This needs to be corrected manually in the database.', $role['id']);
                throw new Exception($msg);
            }
            if (xarSecurityCheck('ReadRoles', 0, 'All', $role['uname'] . ":All:" . $role['id'])) {
                $skip = 0;
                $unique = 1;
                $thisrole = xarRoles::get($role['id']);
                $existinguser = xarMod::apiFunc('roles','user','get',array('uname' => $role['uname'], 'state' => xarRoles::ROLES_STATE_CURRENT));
                if ($thisrole->getType() != xarRoles::ROLES_USERTYPE) {
                    if (is_array($existinguser)) $unique = 0;
                    $role['uname'] = "";
                } else {
                    $uname1 = explode($deleted,$role['uname']);
// checking empty unames for code robustness :-)
                    if($uname1[0] == '') {
                        $existinguser = 0;
                        $skip = 1;
                    } else
                    if (is_array($existinguser)) $unique = 0;
                    $role['uname'] = $uname1[0];
// now check that email is unique if this has to be checked (fix for nonexisting Bug)
                    if (xarModVars::get('roles', 'uniqueemail')) {
                        $existinguser = xarMod::apiFunc('roles','user','get',array('email' => $email[0], 'state' => xarRoles::ROLES_STATE_CURRENT));
                        if (is_array($existinguser)) $unique = 0;
                    }
               }
                if (!$skip) {
                    $types = xarMod::apiFunc('roles','user','getitemtypes');
                    $role['itemtype'] = $types[$role['itemtype']]['label'];
                    $role['unique'] = $unique;
                    $recallroles[] = $role;
                }
            }
        }
// --- send to template
        $data['groups'] = xarMod::apiFunc('roles', 'user', 'getallgroups');
        $recallfilter['startnum'] = '%%';
        $filter['state']         = $data['recallstate'];
        $recallfilter['recallsearch']   = $data['recallsearch'];
        $data['submitRecall']    = xarML('Recall');
        $data['recallroles']     = $recallroles;
        $data['startnum'] = $startnum;
        $data['urltemplate'] = xarModURL('roles', 'admin', 'purge', $recallfilter);
        $data['urlitemmatch'] = '%%';
        $data['itemsperpage'] = $numitems;

    }
//--------------------------------------------------------
    elseif ($data['operation'] == 'purge')
    {
        if (!xarVarFetch('purgestate',    'int',    $data['purgestate'], -1,      XARVAR_DONT_SET)) return;
        if (!xarVarFetch('purgesearch',   'str',    $data['purgesearch'], NULL,   XARVAR_DONT_SET)) return;
        if (!xarVarFetch('purgesubmit',   'str',    $purgesubmit,         NULL,   XARVAR_DONT_SET)) return;
        if (!xarVarFetch('startnum', 'int:1:', $startnum,       1,      XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('purgeids',     'isset',  $purgeids,           array(), XARVAR_NOT_REQUIRED)) return;

        // Check for confirmation.
        if ($confirmation == xarML("Purge"))
        {
// --- purge users
            if(!xarSecurityCheck('AdminRoles')) return;
            foreach ($purgeids as $id => $val) {
// --- skip if we are trying to remove the designated site admin.
// TODO: insert error feedabck here somehow
                if($id == xarModVars::get('roles','admin')) continue;
// --- do this in 2 stages. First, delete the role: this will update the user
// --- count on all the role's parents
                $role = xarRoles::get($id);
                $role->deleteItem();
// --- now actually remove the data from the role's entry
                $query = "UPDATE $rolestable SET name = ?, uname = ?, pass = ?, email = ?, date_reg = ?, state = ? WHERE id = ?" ;
                $bindvars = array();
                $bindvars[] = '';
                $bindvars[] = $deleted . microtime(TRUE) .'.'. $id;
                $bindvars[] = '';
                $bindvars[] = '';
                $bindvars[] = 0;
                $bindvars[] = xarRoles::ROLES_STATE_DELETED;
                $bindvars[] = $id;
                $dbconn = xarDB::getConn();
                $result = $dbconn->Execute($query,$bindvars);
// --- Let any hooks know that we have purged this user.
                $item['module'] = 'roles';
                $item['itemid'] = $id;
                $item['method'] = 'purge';
                xarModCallHooks('item', 'delete', $id, $item);
            }
        }

// --- display users that can be purged
        $bindvars = array();
        $selection = " WHERE email != ?";
        $bindvars[] = '';
        //Create the selection
        if ($data['purgestate'] != -1) {
            $selection .= " AND state = ? ";
            $bindvars[] = $data['purgestate'];
            switch ($data['purgestate']):
                case xarRoles::ROLES_STATE_DELETED :
                    $data['purgestatetext'] = 'deleted';
                    break ;
                case xarRoles::ROLES_STATE_INACTIVE :
                    $data['purgestatetext'] = 'inactive';
                    break ;
                case xarRoles::ROLES_STATE_NOTVALIDATED :
                    $data['purgestatetext'] = 'not validated';
                    break ;
                case xarRoles::ROLES_STATE_ACTIVE :
                    $data['purgestatetext'] = 'active';
                    break ;
                case xarRoles::ROLES_STATE_PENDING :
                    $data['purgestatetext'] = 'pending';
                    break ;
            endswitch ;
        } else {
            $data['purgestatetext'] = '';
        }
        if (!empty($data['purgesearch'])) {
            $selection .= " AND (
                                  (name LIKE ?) OR
                                  (uname LIKE ?) OR
                                  (email LIKE ?)
                                )";
            $bv = '%'.$data['purgesearch'].'%';
            $bindvars[] = $bv;
            $bindvars[] = $bv;
            $bindvars[] = $bv;
        }
        // Select-clause.
        $query = '
            SELECT DISTINCT id,
                    uname,
                    name,
                    email,
                    state,
                    date_reg
                    FROM ' . $rolestable .
                    $selection .
                    ' ORDER BY name';

        $stmt = $dbconn->prepareStatement($query);

        $result = $stmt->executeQuery($bindvars);
        $data['totalselect'] = $result->getRecordCount();

        if ($startnum != 0) {
            $stmt->setLimit($numitems);
            $stmt->setOffset($startnum-1);
            $result = $stmt->executeQuery($bindvars);
        }

        if ($data['totalselect'] == 0) {
            $data['purgemessage'] = xarML('There are no users selected');
        } else {
            $data['purgemessage']         = '';
        }

        $purgeusers = array();
        while($result->next()) {
            list($id, $uname, $name, $email, $state, $date_reg) = $result->fields;
            // check each role's name and user name
            if (empty($name) || empty($uname)) {
                $msg = xarML('Execution halted: the role with id #(1) has an empty name or user name. This needs to be corrected manually in the database.', $id);
                throw new Exception($msg);
            }
            switch ($state):
                case xarRoles::ROLES_STATE_DELETED :
                    $state = 'deleted';
                    break ;
                case xarRoles::ROLES_STATE_INACTIVE :
                    $state = 'inactive';
                    break ;
                case xarRoles::ROLES_STATE_NOTVALIDATED :
                    $state = 'not validated';
                    break ;
                case xarRoles::ROLES_STATE_ACTIVE :
                    $state = 'active';
                    break ;
                case xarRoles::ROLES_STATE_PENDING :
                    $state = 'pending';
                    break ;
            endswitch ;
            $purgeusers[] = array(
                'id'        => $id,
                'uname'     => $uname,
                'name'      => $name,
                'email'     => $email,
                'state'      => $state,
                'date_reg'  => $date_reg
            );
        }
        // --- send to template
        $purgefilter['startnum'] = '%%';
        $purgefilter['purgesearch'] = $data['purgesearch'];

        $data['submitPurge'] = xarML('Purge');
        $data['purgeusers']  = $purgeusers;
        $data['startnum'] = $startnum;
        $data['urltemplate'] = xarModURL('roles', 'admin', 'purge', $purgefilter);
        $data['urlitemmatch'] = '%%';
        $data['itemsperpage'] = $numitems;

    } // end elseif

    // --- finish up
    $data['authid'] = xarSecGenAuthKey();
    // Return
    return $data;
}

?>
