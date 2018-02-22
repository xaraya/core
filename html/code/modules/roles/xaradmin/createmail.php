<?php
/**
 * Create email
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * @return array data for the template display
  */
function roles_admin_createmail()
{
    // TODO allow selection by group or user or all users.
    // Security
    if (!xarSecurityCheck('MailRoles')) return;

    if (!xarVarFetch('id',       'int:0:', $id,        -1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ids',      'isset',  $ids,     NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state',    'int:0:', $state,      xarRoles::ROLES_STATE_ALL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('startnum', 'int:1:', $startnum,    1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('order',    'str:0:', $data['order'], 'name', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('includesubgroups', 'int:0:', $data['includesubgroups'],0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('mailtype', 'str:0:', $data['mailtype'], 'blank', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selstyle', 'isset',  $selstyle,    0, XARVAR_NOT_REQUIRED)) return;

   // what type of email: a selection or a single email?
    if ($id < 1) {
        $type = 'selection';
    } else {
        $role  = xarRoles::get($id);
        $type  = ($role->getType() == xarRoles::ROLES_GROUPTYPE) ? 'selection' : 'single';
    }

    sys::import('xaraya.structures.query');
    $xartable =& xarDB::getTables();
    if ($type == 'single') {
        $id = $role->getID();
        $data['users'][$role->getID()] =
            array('id'       => $id,
                  'name'     => $role->getName(),
                  'uname'    => $role->getUser(),
                  'email'    => $role->getEmail(),
                  'status'   => $role->getState(),
                  'date_reg' => $role->getDateReg()
                 );

        if ($selstyle == 0) $selstyle =2;
        // Create a query to send to sendmail
        $q = new Query('SELECT');
        $q->addtable($xartable['roles'],'r');
        $q->addfields(array('r.id AS id',
                            'r.name AS name',
                            'r.uname AS uname',
                            'r.email AS email',
                            'r.state AS state',
                            'r.date_reg AS date_reg'));
        $q->eq('r.id',$id);
        xarSession::setVar('rolesquery',serialize($q));
    } else {
        if ($selstyle == 0) $selstyle = 1;

        // Get the current query or create a new one if need be
        if ($id == -1) {
            $q = new Query();
            $q = unserialize(xarSession::getVar('rolesquery'));
        }
        if(empty($q->tables)) {
            $q = new Query('SELECT');
            $q->addtable($xartable['roles'],'r');
        }
            $q->addfields(array('r.id AS id',
                                'r.name AS name',
                                'r.uname AS uname',
                                'r.email AS email',
                                'r.state AS state',
                                'r.date_reg AS date_reg'));
            $q->eq('r.itemtype',xarRoles::ROLES_USERTYPE);
            $q->ne('r.email','');
        // Set the paging and order stuff for this particular page
        $numitems = (int)xarModVars::get('roles', 'items_per_page');
        $q->setrowstodo($numitems);
        $q->setstartat($startnum);
        $q->setorder($data['order']);

        // Add state
        if ($id != -1) {
            $q->removecondition('state');
            if ($state == xarRoles::ROLES_STATE_CURRENT) $q->ne('state',xarRoles::ROLES_STATE_DELETED);
            elseif ($state == xarRoles::ROLES_STATE_ALL) {}
            else $q->eq('state',$state);
        } else {
            $state = xarRoles::ROLES_STATE_ALL;
        }

        if ($id != -1 && $id != 0) {
            if ($role->getType() == xarRoles::ROLES_GROUPTYPE) {
                // If a group was chosen, get only the users of that group
                $q->addtable($xartable['rolemembers'],'rm');
                $q->join('r.id','rm.role_id');
                $q->eq('rm.parent_id',$id);
            } else {
                $q->eq('r.id',$id);
            }
        }

        // Save the query so we can reuse it somewhere
        xarSession::setVar('rolesquery',serialize($q));
        // open a connection and run the query
        $q->run();

        foreach($q->output() as $role) {
                $data['users'][$role['id']] =
                    array('id'      => $role['id'],
                          'name'     => $role['name'],
                          'uname'    => $role['uname'],
                          'email'    => $role['email'],
                          'status'   => $role['state'],
                          'date_reg' => $role['date_reg'],
                          'frozen'   => !xarSecurityCheck('EditRoles',0,'Roles',$role['name'])
                         );
        }

        // Check if we also want to send to subgroups
        // In this case we'll just pick out the descendants in the same state
        if ($id != 0 && ($data['includesubgroups'] == 1)) {
            $parentgroup = xarRoles::get($id);
            $descendants = $parentgroup->getDescendants($state);

            while (list($key, $user) = each($descendants)) {
                if (xarSecurityCheck('EditRoles',0,'Roles',$user->getName())) {
                    if (in_array($state, array($user->getState(),xarRoles::ROLES_STATE_ALL))) {
                        $data['users'][$user->getID()] =
                            array('id'      => $user->getID(),
                                  'name'     => $user->getName(),
                                  'uname'    => $user->getUser(),
                                  'email'    => $user->getEmail(),
                                  'status'   => $user->getState(),
                                  'date_reg' => $user->getDateReg()
                                 );}
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
            $pos = strpos($filename,'-message.xt');
            if (!($pos === false)) {
                $templatename = substr($filename,0,$pos);
                $templatelabel = ucfirst($templatename);
                $templates[] = array('key' => $templatename, 'value' => $templatelabel);
            }
        }
    }
    closedir($dd);

    $data['templates'] = $templates;
    $data['type']      = $type;
    $data['selstyle']  = $selstyle;
    $data['id']       = $id;
    $data['state']     = $state;
    $data['authid']    = xarSecGenAuthKey();
    $data['groups']    = xarMod::apiFunc('roles', 'user', 'getallgroups');
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
        $strings = xarMod::apiFunc('roles','admin','getmessagestrings', array('template' => $data['mailtype']));
        if (!isset($strings)) return;

        $data['subject'] = $strings['subject'];
        $data['message'] = $strings['message'];
    }

    return $data;
}

?>
