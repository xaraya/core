<?php
/**
 * File: $Id$
 *
 * Send email to a user
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author John Cox
 */
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

        case 'confirm':

            if (!xarVarFetch('name','str:1:100',$name)) return;
            if (!xarVarFetch('fname','str:1:100',$fname)) return;
            if (!xarVarFetch('subject','html:restricted',$subject, '',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('message','html:restricted',$message, '',XARVAR_NOT_REQUIRED)) return;
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
            xarResponseRedirect(xarModURL('roles', 'user', 'view'));

            break;
    }

    return $data;
}

?>