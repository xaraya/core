<?php
/**
 * Update users from roles_admin_showusers
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/* Update users from roles_admin_showusers
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_updatestate()
{
    // Security
    if (!xarSecurity::check('EditRoles')) return;
    
    $data = [];
    // Get parameters
    if (!xarVar::fetch('status',      'int:0:', $data['status'],   NULL,    xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('state',       'int:0:', $data['state'],    0,       xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('groupid',    'int:0:', $data['groupid'], 1,       xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('updatephase', 'str:1:', $updatephase,      'update',xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('ids',        'isset',  $ids,             NULL,    xarVar::NOT_REQUIRED)) return;

    $data['authid'] = xarSec::genAuthKey();
    // invalid fields (we'll check this below)
    // check if the username is empty
    //Note : We should not provide xarML here. (should be in the template for better translation)
    //Might be additionnal advice about the invalid var (but no xarML..)
    if (!isset($ids)) {
       $invalid = xarML('You must choose the users to change their state');
    }
     if (isset($invalid)) {
        // if so, return to the previous template
        return xarController::redirect(xarController::URL('roles','admin', 'showusers',
                             array('authid'  => $data['authid'],
                                   'state'   => $data['state'],
                                   'invalid' => $invalid,
                                   'id'     => $data['groupid'])));
    }
    //Get the notice message
    switch ($data['status']) {
        case xarRoles::ROLES_STATE_INACTIVE :
            $mailtype = 'deactivation';
        break;
        case xarRoles::ROLES_STATE_NOTVALIDATED :
            $mailtype = 'validation';
        break;
        case xarRoles::ROLES_STATE_ACTIVE :
            $mailtype = 'welcome';
        break;
        case xarRoles::ROLES_STATE_PENDING :
            $mailtype = 'pending';
        break;
        default:
            $mailtype = 'blank';
        break;
    }

    // Why so late? ids check never reaches this.
    if ( (!isset($ids)) || (!isset($data['status']))
                         || (!is_numeric($data['status']))
                         || ($data['status'] < 1)
                         || ($data['status'] > 4) )       {

        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('parameters', 'admin', 'updatestate', 'Roles');
        throw new BadParameterException($vars,$msg);
    }
    $idnotify = array();
    foreach ($ids as $id => $val) {
        //check if the user must be updated :
        $role = xarRoles::get($id);
        if ($role->getState() != $data['status']) {
            if ($data['status'] == xarRoles::ROLES_STATE_NOTVALIDATED) $valcode = xarMod::apiFunc('roles','user','makepass');
            else $valcode = null;
            //Update the user
            if (!xarMod::apiFunc('roles', 'admin', 'stateupdate',
                              array('id'     => $id,
                                    'state'   => $data['status'],
                                    'valcode' => $valcode))) return;
            $idnotify[$id] = 1;
        }
    }
    $ids = $idnotify;
    // Success
     if ((!xarModVars::get('roles', 'ask'.$mailtype.'email')) || (count($idnotify) == 0)) {
            xarController::redirect(xarController::URL('roles', 'admin', 'showusers',
                          array('id' => $data['groupid'], 'state' => $data['state'])));
     }
     else {
        xarController::redirect(xarController::URL('roles', 'admin', 'asknotification',
                          array('id' => $ids, 'mailtype' => $mailtype, 'groupid' => $data['groupid'], 'state' => $data['state'])));
     }
     return true;
}
