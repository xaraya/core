<?php

/**
 * Send email to a user
 *
 * @author  John Cox
 * @access  public
 * @param   uid is the uid of the user being sent
 * @return  true on success or void on falure
 * @throws  XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
 */
function roles_user_email($args)
{
    if (!xarVarFetch('uid','int:1:',$uid)) return;
    if (!xarVarFetch('phase','str:1:100',$phase,'modify',XARVAR_NOT_REQUIRED)) return;

    // Security Check
    if(!xarSecurityCheck('ReadRole')) return;

    switch(strtolower($phase)) {

        case 'modify':
        default:

            // Get user information
            $data = xarModAPIFunc('roles',
                                  'user',
                                  'get',
                                   array('uid' => $uid));

            if ($data == false) return;

            $data['authid'] = xarSecGenAuthKey();
            $data['confirm'] = xarML('Confirm');

            xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Mail User')));
            break;

        case 'update':

            if (!xarVarFetch('name','str:1:100',$name)) return;
            if (!xarVarFetch('fname','str:1:100',$fname)) return;
            if (!xarVarFetch('subject','html:strict',$subject)) return;
            if (!xarVarFetch('message','html:strict',$message)) return;
            if (!xarVarFetch('femail','str:1:100',$femail)) return;

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;

            // Security Check
            if(!xarSecurityCheck('ReadRole')) return;

            list($message) = xarModCallHooks('item',
                                             'transform',
                                             $uid,
                                             array($message));

            // Get user information
            $data = xarModAPIFunc('roles',
                                  'user',
                                  'get',
                                   array('uid' => $uid));

            if ($data == false) return;

            if (!xarModAPIFunc('mail',
                               'admin',
                               'sendmail',
                               array('info'     => $data['email'],
                                     'name'     => $name,
                                     'subject'  => $subject,
                                     'message'  => $message,
                                     'from'     => $femail,
                                     'fromname' => $fname))) return;


            // lets update status and display updated configuration
            xarResponseRedirect(xarModURL('roles', 'user', 'main'));

            break;
    }

    return $data;
}

?>