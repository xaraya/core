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

    $message = xarVarPrepHTMLDisplay($message);
    $subject = xarVarPrepForDisplay($subject);

    // Confirm authorisation code.
    if (!xarSecConfirmAuthKey()) return;
    // Security check
    if (!xarSecurityCheck('MailRoles')) return;
    // Get user information

    // Get the current query
    $q = unserialize(xarSessionGetVar('rolesquery'));

    // only need the uid, name and email fields
    $q->clearfields();
    $q->addfields(array('r.xar_uid','r.xar_name','r.xar_email'));

    // Open a connection and run the query
    $q->open();
    $q->run();

    foreach ($q->output() as $user) {
        $users[$user['r.xar_uid']] = array('uid' => $user['r.xar_uid'],
            'name' => $user['r.xar_name'],
            'email' => $user['r.xar_email']
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
                'email' => $user->getEmail()
                );
        }
    }


// now send the mails
    foreach ($users as $user) {
        //Get the common search and replace values
        $search = array('/%%recipientname%%/','/%%recipientuid%%/');
        $replace = array($user['name'],$user['uid']);
        $message = preg_replace($search, $replace, $message);
        $subject = preg_replace($search, $replace, $subject);
        if (!xarModAPIFunc('mail',
            'admin',
            'sendmail',
            array('info' => $user['email'],
                'name' => $user['name'],
                'subject' => $subject,
                'message' => $message))) return;
    }

    xarResponseRedirect(xarModURL('roles', 'admin', 'createmail'));
    // Return
    return true;
}

?>