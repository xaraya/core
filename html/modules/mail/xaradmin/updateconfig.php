<?php
/**
 * Update the configuration parameters of the module
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 */
/**
 * Update the configuration parameters of the module based on data from the modification form
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function mail_admin_updateconfig()
{
    // Confirm authorisation code (this should be done first to save cpu cycles)
    if (!xarSecConfirmAuthKey()) return;

    // Get parameters
    if (!xarVarFetch('adminname', 'str:1:', $adminname)) return;
    if (!xarVarFetch('adminmail', 'str:1:', $adminmail)) return;
    if (!xarVarFetch('replyto', 'checkbox', $replyto, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('replytoname', 'str:1:', $replytoname, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('replytoemail', 'str:1:', $replytoemail, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showtemplates', 'checkbox', $showtemplates, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('html', 'checkbox', $html, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('htmluseheadfoot', 'checkbox', $htmluseheadfoot, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('htmlheader', 'str:1:', $htmlheader, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('htmlfooter', 'str:1:', $htmlfooter, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('textuseheadfoot', 'checkbox', $textuseheadfoot, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('textheader', 'str:1:', $textheader, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('textfooter', 'str:1:', $textfooter, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('wordwrap', 'int:1:', $wordwrap, '50')) return;
    if (!xarVarFetch('priority', 'str:1:', $priority, 'normal')) return;
    if (!xarVarFetch('encoding', 'str:1:', $encoding)) return;
    if (!xarVarFetch('server', 'str:1:', $server, 'mail')) return;
    if (!xarVarFetch('smtpHost', 'str:1:', $smtpHost, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('smtpPort', 'int:1:', $smtpPort, '25', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('smtpAuth', 'checkbox', $smtpAuth, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('htmlheader', 'str:1:', $htmlheader, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('smtpUserName', 'str:1:', $smtpUserName, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('smtpPassword', 'str:1:', $smtpPassword, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('sendmailpath', 'str:1:', $sendmailpath, '/usr/sbin/sendmail', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('searchstrings', 'str:1', $searchstrings, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('replacestrings', 'str:1', $replacestrings, '', XARVAR_NOT_REQUIRED)) return;

    // update the data
    xarModSetVar('mail', 'adminname', $adminname);
    xarModSetVar('mail', 'adminmail', $adminmail);
    xarModSetVar('mail', 'replyto', $replyto);
    xarModSetVar('mail', 'replytoname', $replytoname);
    xarModSetVar('mail', 'replytoemail', $replytoemail);
    xarModSetVar('mail', 'html', $html);
    xarModSetVar('mail', 'ShowTemplates', $showtemplates);
    xarModSetVar('mail', 'htmluseheadfoot', $htmluseheadfoot);
    xarModSetVar('mail', 'htmlheader', $htmlheader);
    xarModSetVar('mail', 'htmlfooter', $htmlfooter);
    xarModSetVar('mail', 'textuseheadfoot', $textuseheadfoot);
    xarModSetVar('mail', 'textheader', $textheader);
    xarModSetVar('mail', 'textfooter', $textfooter);
    xarModSetVar('mail', 'priority', $priority);
    xarModSetVar('mail', 'encoding', $encoding);
    xarModSetVar('mail', 'wordwrap', $wordwrap);
    xarModSetVar('mail', 'server', $server);
    xarModSetVar('mail', 'smtpHost', $smtpHost);
    xarModSetVar('mail', 'smtpPort', $smtpPort);
    xarModSetVar('mail', 'smtpAuth', $smtpAuth);
    xarModSetVar('mail', 'smtpUserName', $smtpUserName);
    if (!empty($smtpPassword)) {
        xarModSetVar('mail', 'smtpPassword', $smtpPassword);
    }
    xarModSetVar('mail', 'sendmailpath', $sendmailpath);
    $searchstrings = serialize($searchstrings);
    xarModSetVar('mail', 'searchstrings', $searchstrings);
    $replacestrings = serialize($replacestrings);
    xarModSetVar('mail', 'replacestrings', $replacestrings);

    if (xarModIsAvailable('scheduler')) {
        if (!xarVarFetch('interval', 'str:1', $interval, '', XARVAR_NOT_REQUIRED)) return;
        // see if we have a scheduler job running to send queued mail
        $job = xarModAPIFunc('scheduler','user','get',
                             array('module' => 'mail',
                                   'type' => 'scheduler',
                                   'func' => 'sendmail'));
        if (empty($job) || empty($job['interval'])) {
            if (!empty($interval)) {
                // create a scheduler job
                xarModAPIFunc('scheduler','admin','create',
                              array('module' => 'mail',
                                    'type' => 'scheduler',
                                    'func' => 'sendmail',
                                    'interval' => $interval));
            }
        } elseif (empty($interval)) {
            // delete the scheduler job
            xarModAPIFunc('scheduler','admin','delete',
                          array('module' => 'mail',
                                'type' => 'scheduler',
                                'func' => 'sendmail'));
        } elseif ($interval != $job['interval']) {
            // update the scheduler job
            xarModAPIFunc('scheduler','admin','update',
                          array('module' => 'mail',
                                'type' => 'scheduler',
                                'func' => 'sendmail',
                                'interval' => $interval));
        }
    }

    // lets update status and display updated configuration
    xarResponseRedirect(xarModURL('mail', 'admin', 'modifyconfig'));
    // Return
    return true;
}
?>
