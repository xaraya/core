<?php
/**
 * Update users from roles_admin_showusers
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/* Update users from roles_admin_showusers
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_updatestate()
{
    // Security Check
    if (!xarSecurityCheck('EditRole')) return;
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
        return xarResponseRedirect(xarModURL('roles','admin', 'showusers',
                             array('authid'  => $data['authid'],
                                   'state'   => $data['state'],
                                   'invalid' => $invalid,
                                   'id'     => $data['groupid'])));
    }
    //Get the notice message
    switch ($data['status']) {
        case ROLES_STATE_INACTIVE :
            $mailtype = 'deactivation';
        break;
        case ROLES_STATE_NOTVALIDATED :
            $mailtype = 'validation';
        break;
        case ROLES_STATE_ACTIVE :
            $mailtype = 'welcome';
        break;
        case ROLES_STATE_PENDING :
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
            if ($data['status'] == ROLES_STATE_NOTVALIDATED) $valcode = xarModAPIFunc('roles','user','makepass');
            else $valcode = null;
            //Update the user
            if (!xarModAPIFunc('roles', 'admin', 'stateupdate',
                              array('id'     => $id,
                                    'state'   => $data['status'],
                                    'valcode' => $valcode))) return;
            $idnotify[$id] = 1;
        }
    }
    $ids = $idnotify;
    // Success
     if ((!xarModVars::get('roles', 'ask'.$mailtype.'email')) || (count($idnotify) == 0)) {
            xarResponseRedirect(xarModURL('roles', 'admin', 'showusers',
                          array('id' => $data['groupid'], 'state' => $data['state'])));
            return true;
     }
     else {
        xarResponseRedirect(xarModURL('roles', 'admin', 'asknotification',
                          array('id' => $ids, 'mailtype' => $mailtype, 'groupid' => $data['groupid'], 'state' => $data['state'])));
     }
}
?>
