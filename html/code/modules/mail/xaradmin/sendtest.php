<?php
/**
 * Test the email settings
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * Test the email settings
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  boolean true on success or void on failure
*/
function mail_admin_sendtest()
{
    // Security
    if (!xarSecurityCheck('ManageMail')) return;

    // Get parameters from whatever input we need
    if (!xarVarFetch('message', 'str:1:', $message)) return;
    if (!xarVarFetch('subject', 'str:1', $subject)) return;
    if (!xarVarFetch('email', 'email', $email, '')) return;
    if (!xarVarFetch('name', 'str:1', $name, '')) return;
    if (!xarVarFetch('emailcc', 'email', $emailcc, '')) return;
    if (!xarVarFetch('namecc', 'str:1', $namecc, '')) return;
    if (!xarVarFetch('emailbcc', 'email', $emailbcc, '')) return;
    if (!xarVarFetch('namebcc', 'str:1', $namebcc, '')) return;

    // Confirm authorisation code.
    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    if (empty($email)) {
        $email = xarModVars::get('mail', 'adminmail');
    }
    if (empty($name)) {
        $name = xarModVars::get('mail', 'adminname');
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

    if (!xarMod::apiFunc('mail',
            'admin',
            'sendmail',
            array('info' => $email,
                'name' => $name,
                'ccinfo' => $emailcc,
                'ccname' => $namecc,
                'bccinfo' => $emailbcc,
                'bccname' => $namebcc,
                'subject' => $subject,
                'message' => $message,
                'htmlmessage' => $htmlmessage,
                'when' => $when))) return;

    // lets update status and display updated configuration
    xarController::redirect(xarModURL('mail', 'admin', 'compose'));
    return true;
}
?>
