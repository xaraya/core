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
    // Get parameters from whatever input we need
    list($uid,
         $phase) = xarVarCleanFromInput('uid',
                                        'phase');

    // Security Check
    if(!xarSecurityCheck('ReadRole')) return;

    if (empty($phase)){
        $phase = 'modify';
    }

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

            xarTplSetPageTitle(xarModGetVar('themes', 'SiteName').' :: '.
                               xarVarPrepForDisplay(xarML('Mail User')));
            break;

        case 'update':

           list($name,
                 $uid,
                 $fname,
                 $femail,
                 $message,
                 $subject) = xarVarCleanFromInput('name',
                                                  'uid',
                                                  'fname',
                                                  'femail',
                                                  'message',
                                                  'subject');

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;

            // Security Check
            if(!xarSecurityCheck('ReadRole')) return;

            // Check arguments
            if (empty($subject)) {
                $msg = xarML('No Subject Provided for Email');
                xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                return;
            }

            if (empty($message)) {
                $msg = xarML('No Message Provided for Email');
                xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                return;
            }

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