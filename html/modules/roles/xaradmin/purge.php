<?php
/**
 * Purge users by status
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
 * purge users by status
 * @param 'status' the status we are purging
 * @param 'confirmation' confirmation that this item can be purge
 * @todo kinda long, no?
 */
function roles_admin_purge($args)
{
    // Security Check
    if(!xarSecurityCheck('DeleteRole')) return;

    // Get parameters from whatever input we need
    if (!xarVarFetch('operation',    'str', $data['operation'], 'recall', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirmation', 'str', $confirmation,       0,       XARVAR_NOT_REQUIRED)) return;

    extract($args);

    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $rolestable = $xartable['roles'];

    $deleted = '[' . xarML('deleted') . ']';
    $numitems = xarModVars::get('roles', 'itemsperpage');
    // Make sure a value was retrieved for itemsperpage
    if (empty($numitems)) $numitems = -1;

    if ($data['operation'] == 'recall')
    {
        if (!xarVarFetch('recallstate',    'int:1:', $data['recallstate'],  NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('recallsubmit',   'str',    $recallsubmit,         NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('recallsearch',   'str',    $data['recallsearch'], NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('recallstartnum', 'int:1:', $recallstartnum,       1,    XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('recalluids',     'isset',  $recalluids,           array(), XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('groupid',       'int:1',  $data['groupid'],     0,    XARVAR_NOT_REQUIRED)) return;

        if ($confirmation == xarML("Recall"))
        {
 // --- recall users and groups
            if(!xarSecurityCheck('DeleteRole')) return;
            if ($data['groupid'] != 0) $parentgroup = xarRoles::get($data['groupid']);
            foreach ($recalluids as $id => $val) {
                $role = xarRoles::get($id);
                $state = $role->getType() ? ROLES_STATE_ACTIVE : $data['recallstate'];
                $recalled = xarModAPIFunc('roles','admin','recall',
                    array('id' => $id,
                          'state' => $state));
                $parentgroup->addmember($role);
            }
        }
// --- display roles that can be recalled
        //Create the selection
        sys::import('modules.roles.class.xarQuery');
        $q = new xarQuery('SELECT',$rolestable);
        $q->addfields(array('id',
                    'uname',
                    'name',
                    'email',
                    'type',
                    'date_reg'));
        $q->setorder('name');
        if (!empty($data['recallsearch'])) {
            $c[1] = $q->like('name','%' . $data['recallsearch'] . '%');
            $c[2] = $q->like('uname','%' . $data['recallsearch'] . '%');
            $c[3] = $q->like('email','%' . $data['recallsearch'] . '%');
            $q->qor($c);
        }
        $q->eq('state',ROLES_STATE_DELETED);
        $q->ne('date_reg','');
        $q->setrowstodo($numitems);
        $q->setstartat($recallstartnum);
//        $q->qecho();
        if(!$q->run()) return;

        $data['totalselect'] = $q->getrows();

        if ($data['totalselect'] == 0) {
            $data['recallmessage'] = xarML('There are no deleted groups/users ');
        }
        else {
            $data['recallmessage']         = '';
        }

        $recallroles = array();
        foreach ($q->output() as $role) {
// check each role's user name
            if (empty($role['uname'])) {
                $msg = xarML('Execution halted: the role with id #(1) has an empty name. This needs to be corrected manually in the database.', $role['id']);
                throw new Exception($msg);
            }
            if (xarSecurityCheck('ReadRole', 0, 'All', $role['uname'] . ":All:" . $role['id'])) {
                $skip = 0;
                $unique = 1;
                if ($role['type']) {
                    $existinguser = xarModAPIFunc('roles','user','get',array('uname' => $role['uname'], 'type' => ROLES_GROUPTYPE, 'state' => ROLES_STATE_CURRENT));
                    if (is_array($existinguser)) $unique = 0;
                    $role['uname'] = "";
                }
                else {
                    $uname1 = explode($deleted,$role['uname']);
// checking empty unames for code robustness :-)
                    if($uname1[0] == '') {
                        $existinguser = 0;
                        $skip = 1;
                    }
                    else
                        $existinguser = xarModAPIFunc('roles','user','get',array('uname' => $uname1[0], 'state' => ROLES_STATE_CURRENT));
                    if (is_array($existinguser)) $unique = 0;
                    $role['uname'] = $uname1[0];
// remove [deleted] marker from email (fix for Bug 3484)
                    $email = explode($deleted,$role['email']);
                    $role['email']=$email[0];
// now check that email is unique if this has to be checked (fix for nonexisting Bug)
                    if (xarModVars::get('roles', 'uniqueemail')) {
                        $existinguser = xarModAPIFunc('roles','user','get',array('email' => $email[0], 'state' => ROLES_STATE_CURRENT));
                        if (is_array($existinguser)) $unique = 0;
                    }

               }
                if (!$skip) {
                    $types = xarModAPIFunc('roles','user','getitemtypes');
                    $role['type'] = $types[$role['type']]['label'];
                    $role['unique'] = $unique;
                    $recallroles[] = $role;
                }
            }
        }
// --- send to template
        $data['groups'] = xarModAPIFunc('roles', 'user', 'getallgroups');
        $recallfilter['recallstartnum'] = '%%';
        $filter['state']         = $data['recallstate'];
        $recallfilter['recallsearch']   = $data['recallsearch'];
        $data['submitRecall']    = xarML('Recall');
        $data['recallroles']     = $recallroles;
        $data['recallpager']     = xarTplGetPager($recallstartnum,
                                                  $data['totalselect'],
                                                  xarModURL('roles', 'admin', 'purge', $recallfilter),
                                                  $numitems);
    }
//--------------------------------------------------------
    elseif ($data['operation'] == 'purge')
    {
        if (!xarVarFetch('purgestate',    'int',    $data['purgestate'], -1,      XARVAR_DONT_SET)) return;
        if (!xarVarFetch('purgesearch',   'str',    $data['purgesearch'], NULL,   XARVAR_DONT_SET)) return;
        if (!xarVarFetch('purgesubmit',   'str',    $purgesubmit,         NULL,   XARVAR_DONT_SET)) return;
        if (!xarVarFetch('purgestartnum', 'int:1:', $purgestartnum,       1,      XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('purgeids',     'isset',  $purgeids,           array(), XARVAR_NOT_REQUIRED)) return;

        // Check for confirmation.
        if ($confirmation == xarML("Purge"))
        {
// --- purge users
            if(!xarSecurityCheck('AdminRole')) return;
            foreach ($purgeids as $id => $val) {
// --- skip if we are trying to remove the designated site admin.
// TODO: insert error feedabck here somehow
                if($id == xarModVars::get('roles','admin')) continue;
// --- do this in 2 stages. First, delete the role: this will update the user
// --- count on all the role's parents
                $role = xarRoles::get($id);
                $role->deleteItem();
// --- now actually remove the data from the role's entry
                $state = ROLES_STATE_DELETED;
                $uname = $deleted . microtime(TRUE) .'.'. $id;
                $name = '';
                $pass = '';
                $email = '';
                $date_reg = '';
                $q = new xarQuery('UPDATE',$rolestable);
                $q->addfield('name',$name);
                $q->addfield('uname',$uname);
                $q->addfield('pass',$pass);
                $q->addfield('email',$email);
                $q->addfield('date_reg',$date_reg);
                $q->addfield('state',$state);
                $q->eq('id',$id);
                $q->run();
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

        if ($purgestartnum != 0) {
            $stmt->setLimit($numitems);
            $stmt->setOffset($purgestartnum-1);
            $result = $stmt->executeQuery($bindvars);
        }

        if ($data['totalselect'] == 0) {
            $data['purgemessage'] = xarML('There are no users selected');
        }
        else {
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
                'id'        => $id,
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

        $data['submitPurge'] = xarML('Purge');
        $data['purgeusers']  = $purgeusers;
        $data['purgepager']  = xarTplGetPager($purgestartnum,
                                              $data['totalselect'],
                                              xarModURL('roles', 'admin', 'purge', $purgefilter),
                                              $numitems);
    } // end elseif

    // --- finish up
    $data['authid']         = xarSecGenAuthKey();
    // Return
    return $data;
}

?>
