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
    if (!xarSecurityCheck('EditRoles')) return;
    
    // Get parameters
    if (!xarVarFetch('status',      'int:0:', $data['status'],   NULL,    XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('state',       'int:0:', $data['state'],    0,       XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('groupid',    'int:0:', $data['groupid'], 1,       XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('updatephase', 'str:1:', $updatephase,      'update',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ids',        'isset',  $ids,             NULL,    XARVAR_NOT_REQUIRED)) return;

    $data['authid'] = xarSecGenAuthKey();
    // invalid fields (we'll check this below)
    // check if the username is empty
    //Note : We should not provide xarML here. (should be in the template for better translation)
    //Might be additionnal advice about the invalid var (but no xarML..)
    if (!isset($ids)) {
       $invalid = xarML('You must choose the users to change their state');
    }
     if (isset($invalid)) {
        // if so, return to the previous template
        return xarController::redirect(xarModURL('roles','admin', 'showusers',
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
            xarController::redirect(xarModURL('roles', 'admin', 'showusers',
                          array('id' => $data['groupid'], 'state' => $data['state'])));
     }
     else {
        xarController::redirect(xarModURL('roles', 'admin', 'asknotification',
                          array('id' => $ids, 'mailtype' => $mailtype, 'groupid' => $data['groupid'], 'state' => $data['state'])));
     }
     return true;
}
?>
