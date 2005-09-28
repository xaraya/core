<?php

/**
 * File: $Id$
 *
 * Send mail
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Xaraya Team
 */

function roles_admin_sendmail()
{
    // Get parameters from whatever input we need
    if (!xarVarFetch('uid', 'int:0:', $uid, 0)) return;
    if (!xarVarFetch('state', 'int:0:', $state, ROLES_STATE_CURRENT)) return;
    if (!xarVarFetch('message', 'str:1:', $message)) return;
    if (!xarVarFetch('subject', 'str:1', $subject)) return;
    if (!xarVarFetch('includesubgroups','int:0:',$includesubgroups,0,XARVAR_NOT_REQUIRED));

    // Confirm authorisation code.
    if (!xarSecConfirmAuthKey()) return;
    // Security check
    if (!xarSecurityCheck('MailRoles')) return;
    // Get user information

    // Get the current query
    $q = new xarQuery();
    $q = $q->sessiongetvar('rolesquery');

    // only need the uid, name and email fields
    $q->clearfields();
    $q->addfields(array('r.xar_uid','r.xar_name','r.xar_uname','r.xar_email'));

    // Open a connection and run the query
    $q->run();

    foreach ($q->output() as $user) {
        $users[$user['r.xar_uid']] = array('uid' => $user['r.xar_uid'],
                                            'name' => $user['r.xar_name'],
                                            'email' => $user['r.xar_email'],
                                            'username' => $user['r.xar_uname']
        );
    }

    // Check if we also want to send to subgroups
    // In this case we'll just pick out the descendants in the same state
    // Note the nice use of the array keys to overwrite users we already have
    if ($uid != 0 && ($includesubgroups == 1)) {
        $roles = new xarRoles();
        $parentgroup = $roles->getRole($uid);
        $descendants = $parentgroup->getDescendants($state);

        while (list($key, $user) = each($descendants)) {
            $users[$user->getID()] = array('uid' => $user->getID(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'username' => $user->getUser()
                );
        }
    }

    // Get the template that defines the substitution vars
    $messaginghome = xarCoreGetVarDirPath() . "/messaging/roles";
    if (!file_exists($messaginghome . "/includes/message-vars.xd")) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException('The variables template was not found.'));
    }
    $string = '';
    $fd = fopen($messaginghome . "/includes/message-vars.xd", 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }

    // To prevent the template comments from being sent with the mail
    // messages, we turn it off temporarily
    $themecomments = xarModGetVar('themes','ShowTemplates');
    xarModSetVar('themes','ShowTemplates',0);
    $subject  = xarTplCompileString($string . $subject);
    $message  = xarTplCompileString($string . $message);

// now send the mails
    foreach ($users as $user) {
        //Get the common search and replace values
        $data['recipientuid'] = $user['uid'];
        $data['recipientname'] = $user['name'];
        $data['recipientusername'] = $user['username'];
        $data['recipientemail'] = $user['email'];

        $mailsubject = xarTplString($subject, $data);
        $mailmessage = xarTplString($message, $data);

        if (!xarModAPIFunc('mail',
            'admin',
            'sendmail',
            array('info' => $user['email'],
                'name' => $user['name'],
                'subject' => $mailsubject,
                'message' => $mailmessage))) return;
    }
    // If it was on, turn it back on
    xarModSetVar('themes','ShowTemplates',$themecomments);
    // Return
    xarResponseRedirect(xarModURL('roles', 'admin', 'createmail'));
    return true;
}

?>
