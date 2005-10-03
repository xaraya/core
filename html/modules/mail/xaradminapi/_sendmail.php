<?php
/**
 * Send mail
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 */

/**
 * This is a private utility function that is called to send mail
 * It is used by public functions sendmail() and sendhtmlmail()
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param  $ 'info' is the email address we are sending (required)
 * @param  $ 'name' is the name of the email receipitent (optional)
 * @param  $ 'recipients' is an array of recipients (required) // NOTE: $info or $recipients is required, not both
 * @param  $ 'ccinfo' is the email address we are sending (optional)
 * @param  $ 'ccname' is the name of the email receipitent (optional)
 * @param  $ 'ccrecipients' is an array of cc recipients (optional)
 * @param  $ 'bccinfo' is the email address we are sending (required)
 * @param  $ 'bccname' is the name of the email receipitent (optional)
 * @param  $ 'bccrecipients' is an array of bcc recipients (optional)
 * @param  $ 'subject' is the subject of the email (required)
 * @param  $ 'message' is the body of the email (required)
 * @param  $ 'htmlmessage' is the html body of the email
 * @param  $ 'priority' is the priority of the message
 * @param  $ 'encoding' is the encoding of the message
 * @param  $ 'wordwrap' is the column width of the message
 * @param  $ 'from' is who the email is from
 * @param  $ 'fromname' is the name of the person the email is from
 * @param  $ 'attachName' is the name of an attachment to a message
 * @param  $ 'attachPath' is the path of the attachment
 * @param  $ 'htmlmail' is set to true for an html email
 * @param  $ 'usetemplates' set to true to use templates in xartemplates
 * @param  $ 'when' timestamp specifying that this mail should be sent 'no earlier than' (default is now)
 *                  This requires installation and configuration of the scheduler module
 */
function mail_adminapi__sendmail($args)
{
    // Get arguments from argument array
    
    extract($args);

    // Check for required arguments
    $invalid = array();
    if (!isset($info) && !isset($recipients)) {
        $invalid[] = 'info/recipients';
    }
    if (!isset($subject)) {
        $invalid[] = 'subject';
    }
    if (!isset($message)) {
        $invalid[] = 'message';
    }

    if (count($invalid) > 0) {
        $msg = xarML('Wrong arguments to mail_adminapi', join(', ', $invalid), 'admin', '_sendmail', 'Mail');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }


    if (!empty($when) && $when > time() && xarModIsAvailable('scheduler')) {
        if (xarModAPIFunc('mail','admin','_queuemail', $args)) {
            // we're done here
            return true;
        }
    }

    // Global search and replace %%text%%
    $replace = xarModAPIFunc('mail',
                             'admin',
                             'replace',
                             array('message'        => $message,
                                   'subject'        => $subject,
                                   'htmlmessage'    => $htmlmessage));

    $subject = $replace['subject'];
    $message = $replace['message'];
    $htmlmessage = $replace['htmlmessage'];

    // Bug 4219 calls this out for the silly safe mode.  That said, I am not sure we want
    // to be doing this since mail could be from a user on the site.
    // so it be commented out for the time being.
    //ini_set("sendmail_from", $from);

    include_once 'modules/mail/xarclass/class.phpmailer.php';

    $mail = new phpmailer();
    $mail->PluginDir = 'modules/mail/xarclass/';
    $mail->ClearAllRecipients();

    // Set default language path to English.  This is necessary as
    // phpmailer will set an invalid path to the language directory
    // and throw an error.
    $mail->SetLanguage("en", "modules/mail/xarclass/language/");

    // Get type of mail server
    $serverType = xarModGetVar('mail', 'server');

    switch($serverType) {
        case 'smtp':
            $mail->IsSMTP(); // telling the class to use SMTP
            $mail->Host = xarModGetVar('mail', 'smtpHost'); // SMTP server
            $mail->Port = xarModGetVar('mail', 'smtpPort'); // SMTP Port default 25.
            $mail->Helo = xarServerGetVar('SERVER_NAME'); // identification string sent to MTA at smtpHost

            // the smtp server might require authentication
            if (xarModGetVar('mail', 'smtpAuth')) {
                $mail->SMTPAuth = true; // turn on SMTP authentication
                $mail->Username = xarModGetVar('mail', 'smtpUserName'); // SMTP username
                $mail->Password = xarModGetVar('mail', 'smtpPassword'); // SMTP password
            }
            break;

        case 'sendmail':
            $mail->IsSendmail();
            $mail->Sendmail = xarModGetVar('mail', 'sendmailpath'); // Use the correct path to sendmail
            break;

        case 'qmail':
            $mail->IsQmail();
            break;

        case 'mail':
            $mail->IsMail();
            break;
    }

    $mail->WordWrap = $wordwrap;
    $mail->Priority = $priority;
    $mail->Encoding = $encoding;
    $mail->CharSet = xarMLSGetCharsetFromLocale(xarMLSGetCurrentLocale());
    $mail->From = $from;
    $mail->Sender = $from;
    $mail->FromName = $fromname;

    if (xarModGetVar('mail', 'replyto')) {
        $mail->AddReplyTo(xarModGetVar('mail', 'replytoemail'), xarModGetVar('mail', 'replytoname'));
    }

    // The parameters below are the bare minimum sent to the API.
    // $info = Where its being mailed to
    // $recipients = array of recipients -- meant to replace $info/$name
    // $subject = The subject of the mail
    // $message = The body of the email
    // $name = name of person recieving email (not required)
    if (!empty($recipients)) {
        foreach($recipients as $k=>$v) {
            if (!is_numeric($k) && !is_numeric($v)) {
                // $recipients[$info] = $name describes $recipients parameter
                $mail->AddAddress($k, $v);
            } else if (!is_numeric($k)) {
                // $recipients[$info] = (int) describes $recipients parameter
                $mail->AddAddress($k);
            } else {
                // $recipients[(int)] = $info describes $recipients parameter
                $mail->AddAddress($v);
            }// if
        }// foreach
    } else {
        if (!empty($info)) {
            if (!empty($name)) {
                $mail->AddAddress($info, $name);
            } else {
                $mail->AddAddress($info);
            }
        }
    }// if

    // Add a "CC" address
    if (!empty($ccrecipients)) {
        foreach($ccrecipients as $k=>$v) {
            if (!is_numeric($k) && !is_numeric($v)) {
                // $recipients[$info] = $name describes $recipients parameter
                $mail->AddCC($k, $v);
            } else if (!is_numeric($k)) {
                // $recipients[$info] = (int) describes $recipients parameter
                $mail->AddCC($k);
            } else {
                // $recipients[(int)] = $info describes $recipients parameter
                $mail->AddCC($v);
            }// if
        }// foreach
    } else {
        if (!empty($ccinfo)) {
            if (!empty($ccname)) {
                $mail->AddCC($ccinfo, $ccname);
            } else {
                $mail->AddCC($ccinfo);
            }
        }
    }// if

    // Add a "BCC" address
    if (!empty($bccrecipients)) {
        foreach($bccrecipients as $k=>$v) {
            if (!is_numeric($k) && !is_numeric($v)) {
                // $recipients[$info] = $name describes $recipients parameter
                $mail->AddBCC($k, $v);
            } else if (!is_numeric($k)) {
                // $recipients[$info] = (int) describes $recipients parameter
                $mail->AddBCC($k);
            } else {
                // $recipients[(int)] = $info describes $recipients parameter
                $mail->AddBCC($v);
            }// if
        }// foreach
    } else {
        if (!empty($bccinfo)) {
            if (!empty($bccname)) {
                $mail->AddBCC($bccinfo, $bccname);
            } else {
                $mail->AddBCC($bccinfo);
            }
        }
    }// if

    // Set subject
    $mail->Subject = $subject;

    // Set IsHTML - this is true for HTML mail
    $mail->IsHTML($htmlmail);

    $mailShowTemplates  = xarModGetVar('mail', 'ShowTemplates');

    // If mailShowTemplates is undefined, then the modvar is missing
    // for some reason, so just go with the value of the theme show templates
    if (!isset($mailShowTemplates)) {
        $mailShowTemplates = xarModGetVar('themes', 'ShowTemplates');
    }

    // go ahead and override the show templates value,  
    // using the mail modules settings instead :-)
    $oldShowTemplates = xarModGetVar('themes', 'ShowTemplates');
    xarModSetVar('themes', 'ShowTemplates', $mailShowTemplates);
        
    // Check if this is HTML mail and set Body appropriately
    if ($htmlmail) {
        // Sets the text-only body of the message. 
        // This automatically sets the email to multipart/alternative. 
        // This body can be read by mail clients that do not have HTML email 
        // capability such as mutt. Clients that can read HTML will view the normal Body.
        if (!empty($message)) {
            if ($usetemplates) {
                $mail->AltBody = xarTplModule('mail', 
                                              'admin', 
                                              'sendmail',
                                              array('message'=>$message),
                                              'text');
            } else {
                $mail->AltBody = $message;
            }
        }
        // HTML message body
        if ($usetemplates) {
            $mail->Body = xarTplModule('mail', 
                                       'admin', 
                                       'sendmail',
                                       array('htmlmessage'=>$htmlmessage),
                                       'html');
        } else {
            $mail->Body = $htmlmessage;
        }
    } else {
        if ($usetemplates) {
            $mail->Body = xarTplModule('mail', 
                                       'admin', 
                                       'sendmail',
                                       array('message'=>$message),
                                       'text');
        } else {
            $mail->Body = $message;
        }
    }

    // Set the showTemplates back to what it was previously
    if (!$mailShowTemplates) {
        xarModSetVar('themes', 'ShowTemplates', $oldShowTemplates);
    }

    // We are now setting up the advance options that can be used by the modules
    // Add Attachment will look to see if there is a var passed called
    // attachName and attachPath and attach it to the message
 
    if (isset($attachPath) && !empty($attachPath)) {
        if (isset($attachName) && !empty($attachName)) {
            $mail->AddAttachment($attachPath, $attachName);
        } else {
            $mail->AddAttachment($attachPath);
        }
    }

    // Send the mail, or send an exception.
    $result = true;
    if (!$mail->Send()) {
        $msg = xarML('The message was not sent. Mailer Error: #(1)',$mail->ErrorInfo);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
        $result = false;
    }

    // Clear all recipients for next email
    $mail->ClearAddresses();

    // Clear all ccrecipients for next email
    $mail->ClearCCs();

    // Clear all bccrecipients for next email
    $mail->ClearBCCs();

    // Clear all attachments for next email
    $mail->ClearAttachments();

    return $result;
}

?>