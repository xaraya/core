<?php
/**
 * Create email
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
function roles_admin_createmail()
{
    // TODO allow selection by group or user or all users.
    // Security check
    if (!xarSecurityCheck('MailRoles')) return;

    if (!xarVarFetch('uid', 'int:0:', $uid, -1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('uids', 'isset', $uids, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state', 'int:0:', $state, -1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('startnum', 'int:1:', $startnum, 1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('order', 'str:0:', $data['order'], 'xar_name', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('includesubgroups', 'int:0:', $data['includesubgroups'],0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('mailtype', 'str:0:', $data['mailtype'], 'blank', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selstyle', 'isset', $selstyle, 0, XARVAR_NOT_REQUIRED)) return;

   // what type of email: a selection or a single email?
    if ($uid < 1) {
        $type = 'selection';
    } else {
        $roles = new xarRoles();
        $role = $roles->getRole($uid);
        $type = $role->getType() ? 'selection' : 'single';
    }

    $xartable =& xarDBGetTables();
    if ($type == 'single') {
        $uid = $role->getID();
        $data['users'][$role->getID()] = array('uid' => $uid,
            'name' => $role->getName(),
            'uname' => $role->getUser(),
            'email' => $role->getEmail(),
            'status' => $role->getState(),
            'date_reg' => $role->getDateReg()
        );
        if ($selstyle == 0) $selstyle =2;
        // Create a query to send to sendmail
        $q = new xarQuery('SELECT');
        $q->addtable($xartable['roles'],'r');
        $q->addfields(array('r.xar_uid AS uid',
                            'r.xar_name AS name',
                            'r.xar_uname AS uname',
                            'r.xar_email AS email',
                            'r.xar_state AS state',
                            'r.xar_date_reg AS date_reg'));
        $q->eq('r.xar_uid',$uid);
        $q->sessionsetvar('rolesquery');
    }
    else {
        if ($selstyle == 0) $selstyle =1;

        // Get the current query or create a new one if need be
        if ($uid == -1) {
            $q = new xarQuery();
            $q = $q->sessiongetvar('rolesquery');
        }
        if(empty($q)) {
            $q = new xarQuery('SELECT');
            $q->addtable($xartable['roles'],'r');
            $q->addfields(array('r.xar_uid AS uid',
                                'r.xar_name AS name',
                                'r.xar_uname AS uname',
                                'r.xar_email AS email',
                                'r.xar_state AS state',
                                'r.xar_date_reg AS date_reg'));
            $q->eq('xar_type',ROLES_USERTYPE);
        }
        // Set the paging and order stuff for this particular page
        $numitems = xarModGetVar('roles', 'itemsperpage');
        $q->setrowstodo($numitems);
        $q->setstartat($startnum);
        $q->setorder($data['order']);

        // Add state
        if ($uid != -1) {
            $q->removecondition('xar_state');
            if ($state == ROLES_STATE_CURRENT) $q->ne('xar_state',ROLES_STATE_DELETED);
            elseif ($state == ROLES_STATE_ALL) {}
            else $q->eq('xar_state',$state);
        }
        else $state = -1;

        if ($uid != -1) {
            if ($uid != 0) {
                // If a group was chosen, get only the users of that group
                $q->addtable($xartable['rolemembers'],'rm');
                $q->join('r.xar_uid','rm.xar_uid');
                $q->eq('rm.xar_parentid',$uid);
            }
        }

        // Save the query so we can reuse it somewhere
        $q->sessionsetvar('rolesquery');

        // open a connection and run the query
        $q->run();

        foreach($q->output() as $role) {
                // Remove the next line eventually. It comes from a special situation upgrading from 0.9.10 to 0.9.11
                if (empty($role)) continue;
                $data['users'][$role['uid']] = array('uid' => $role['uid'],
                'name' => $role['name'],
                'uname' => $role['uname'],
                'email' => $role['email'],
                'status' => $role['state'],
                'date_reg' => $role['date_reg'],
                'frozen' => !xarSecurityCheck('EditRole',0,'Roles',$role['name'])
                );
        }

        // Check if we also want to send to subgroups
        // In this case we'll just pick out the descendants in the same state
        if ($uid != 0 && ($data['includesubgroups'] == 1)) {
            $parentgroup = $roles->getRole($uid);
            $descendants = $parentgroup->getDescendants($state);

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

    // Get the list of available templates
    $messaginghome = sys::varpath() . "/messaging/roles";
    if (!file_exists($messaginghome)) throw new DirectoryNotFoundException($messaginghome);

    $dd = opendir($messaginghome);
    $templates = array(array('key' => 'blank', 'value' => xarML('Empty')));
    while ($filename = readdir($dd)) {
        if (!is_dir($messaginghome . "/" . $filename)) {
            $pos = strpos($filename,'-message.xd');
            if (!($pos === false)) {
                $templatename = substr($filename,0,$pos);
                $templatelabel = ucfirst($templatename);
                $templates[] = array('key' => $templatename, 'value' => $templatelabel);
            }
        }
    }
    closedir($dd);

// Assemble the data for the template
    $data['templates'] = $templates;
    $data['type'] = $type;
    $data['selstyle'] = $selstyle;
    $data['uid'] = $uid;
    $data['state'] = $state;
    $data['authid'] = xarSecGenAuthKey();
    $data['groups'] = xarModAPIFunc('roles',
                                    'user',
                                    'getallgroups');
    //selstyle
    $data['style'] = array('1' => xarML('No'),
                                       '2' => xarML('Yes')
                                       );
    if (isset($data['users'])) $data['totalselected'] = count($data['users']);
    //templates select
    if ($data['mailtype'] == 'blank') {
        $data['subject'] = '';
        $data['message'] = '';
    } else {
        $strings = xarModAPIFunc('roles','admin','getmessagestrings', array('template' => $data['mailtype']));
        if (!isset($strings)) return;

        $data['subject'] = $strings['subject'];
        $data['message'] = $strings['message'];
    }
    // Return the output that has been generated by this function to BL
    return $data;
}

?>
