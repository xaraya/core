<?php
/**
 * Test the email settings
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
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
    if (!xarSecurity::check('ManageMail')) return;

    // Get parameters from whatever input we need
    if (!xarVar::fetch('message', 'str:1:', $message)) return;
    if (!xarVar::fetch('subject', 'str:1', $subject)) return;
    if (!xarVar::fetch('email', 'email', $email, '')) return;
    if (!xarVar::fetch('name', 'str:1', $name, '')) return;
    if (!xarVar::fetch('emailcc', 'email', $emailcc, '')) return;
    if (!xarVar::fetch('namecc', 'str:1', $namecc, '')) return;
    if (!xarVar::fetch('emailbcc', 'email', $emailbcc, '')) return;
    if (!xarVar::fetch('namebcc', 'str:1', $namebcc, '')) return;

    // Confirm authorisation code.
    if (!xarSec::confirmAuthKey()) {
//        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    if (empty($email)) {
        $email = xarModVars::get('mail', 'adminmail');
    }
    if (empty($name)) {
        $name = xarModVars::get('mail', 'adminname');
    }

    if (!xarVar::fetch('when', 'str:1', $when, '', xarVar::NOT_REQUIRED)) return;
    if (!empty($when)) {
        $when .= ' GMT';
        $when = strtotime($when);
        $when -= xarMLS::userOffset() * 3600;
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
    xarController::redirect(xarController::URL('mail', 'admin', 'compose', array('confirm' => 1)));
    return true;
}
?>
