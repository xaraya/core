<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 */

/**
 * Test the email settings
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function mail_admin_sendtest()
{
    // Get parameters from whatever input we need
    if (!xarVarFetch('message', 'str:1:', $message)) return;
    if (!xarVarFetch('subject', 'str:1', $subject)) return;
    if (!xarVarFetch('email', 'str:1', $email, '')) return;
    if (!xarVarFetch('name', 'str:1', $name, '')) return;

    // Confirm authorisation code.
    if (!xarSecConfirmAuthKey()) return;
    // Security check
    if (!xarSecurityCheck('AdminMail')) return;

    if (empty($email)) {
        $email = xarModGetVar('mail', 'adminmail');
    }
    if (empty($name)) {
        $name = xarModGetVar('mail', 'adminname');
    }

    if (!xarVarFetch('when', 'str:1', $when, '', XARVAR_NOT_REQUIRED)) return;
    if (!empty($when)) {
        $when .= ' GMT';
        $when = strtotime($when);
        $when -= xarMLS_userOffset() * 3600;
    } else {
        $when = 0;
    }

    $htmlmessage = $message;

    if (!xarModAPIFunc('mail',
            'admin',
            'sendmail',
            array('info' => $email,
                'name' => $name,
                'subject' => $subject,
                'message' => $message,
                'htmlmessage' => $htmlmessage,
                'when' => $when))) return;

    // lets update status and display updated configuration
    xarResponseRedirect(xarModURL('mail', 'admin', 'compose'));
    // Return
    return true;
}
?>