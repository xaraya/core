<?php
/**
 * File: $Id: s.xaradmin.php 1.28 03/02/08 17:38:40-05:00 John.Cox@mcnabb. $
 *
 * Mail System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage mail module
 * @author John Cox <admin@dinerminor.com>
 */

/**
 * This is a private utility function that is called to send mail
 * It is used by public functions sendmail() and sendhtmlmail()
 *
 * @param  $ 'info' is the email address we are sending (required)
 * @param  $ 'recipients' is an array of recipients (required) // NOTE: $info or $recipients is required, not both
 * @param  $ 'subject' is the subject of the email (required)
 * @param  $ 'message' is the body of the email (required)
 * @param  $ 'htmlmessage' is the html body of the email
 * @param  $ 'name' is the name of the email receipitent
 * @param  $ 'priority' is the priority of the message
 * @param  $ 'encoding' is the encoding of the message
 * @param  $ 'wordwrap' is the column width of the message
 * @param  $ 'from' is who the email is from
 * @param  $ 'fromname' is the name of the person the email is from
 * @param  $ 'attachName' is the name of an attachment to a message
 * @param  $ 'attachPath' is the path of the attachment
 * @param  $ 'htmlmail' is set to true for an html email
 * @param  $ 'when' timestamp specifying that this mail should be sent 'no earlier than' (default is now)
 *                  This requires installation and configuration of the scheduler module
 */
function mail_adminapi__sendmail($args)
{
    // Get arguments from argument array
    extract($args);
    // Argument check
    $invalid = array();

    if (!isset($info) && !isset($recipients))
        $invalid[] = 'info/recipients';
    if (!isset($subject))
        $invalid[] = 'subject';
    if (!isset($message))
        $invalid[] = 'message';

    if (count($invalid) > 0) {
        $msg = xarML('Wrong arguments to mail_adminapi', join(', ', $invalid), 'admin', '_sendmail', 'Mail');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    if (!empty($when) && $when > time() && xarModIsAvailable('scheduler')) {
        if (xarModAPIFunc('mail','admin','_queuemail', $args)) {
            // we're done here
            return true;
        }
    }

    if (empty($htmlmessage)) {
        $htmlmessage = '';
    }

    // global search and replace.
    $replace = xarModAPIFunc('mail',
                             'admin',
                             'replace',
                             array('message'        => $message,
                                   'subject'        => $subject,
                                   'htmlmessage'    => $htmlmessage));

    $subject = $replace['subject'];
    $message = $replace['message'];
    $htmlmessage = $replace['htmlmessage'];

    // Set up variables
    if (empty($wordwrap)) {
        $wordwrap = xarModGetVar('mail', 'wordwrap');
    }
    if (empty($priority)) {
        $priority = xarModGetVar('mail', 'priority');
    }
    if (empty($encoding)) {
        $encoding = '8bit';
    }
    if (empty($from)) {
        $from = xarModGetVar('mail', 'adminmail');
    }
    if (empty($fromname)) {
        $fromname = xarModGetVar('mail', 'adminname');
    }
    // Check if htmlmail parameter passed in - sendmail()
    // does not set this in, only sendhtmlmail()
    if (!isset($htmlmail))
        $htmlmail = false;

    ini_set("sendmail_from", $from);

    include_once 'modules/mail/xarclass/class.phpmailer.php';

    $mail = new phpmailer();
    $mail->PluginDir = 'modules/mail/xarclass/';
    $mail->ClearAllRecipients();

    $serverType = xarModGetVar('mail', 'server');

    if ($serverType == 'smtp') {
        $mail->IsSMTP(); // telling the class to use SMTP
        // $mail->Host = "smtp.email.com"; // SMTP server
        $mail->Host = xarModGetVar('mail', 'smtpHost'); // SMTP server
        $mail->Port = xarModGetVar('mail', 'smtpPort'); // SMTP Port default 25.
        $mail->Helo = "localhost.localdomain"; // Need to research

        // Not sure if this is working
        if (xarModGetVar('mail', 'smtpAuth')) {
            $mail->SMTPAuth = true; // turn on SMTP authentication
            $mail->Username = xarModGetVar('mail', 'smtpUserName'); // SMTP username
            $mail->Password = xarModGetVar('mail', 'smtpPassword'); // SMTP password
        }
    }

    if ($serverType == 'sendmail') {
        $mail->IsSendmail();
        $mail->Sendmail = xarModGetVar('mail', 'sendmailpath'); // Use the correct path to sendmail
    }
    if ($serverType == 'qmail') {
        $mail->IsQmail();
    }
    if ($serverType == 'mail') {
        $mail->IsMail();
    }

    $mail->WordWrap = $wordwrap;
    $mail->Priority = $priority;
    $mail->Encoding = $encoding;
    $mail->CharSet = "iso-8859-1"; // Need to get the var for the ML
    $mail->From = $from;
    $mail->FromName = $fromname;

    if (xarModGetVar('mail', 'replyto')) {
        $mail->AddReplyTo(xarModGetVar('mail', 'replytoemail'), xarModGetVar('mail', 'replytoname'));
    }

    // The parameters below are the bare minimum sent to the API.
    // $info = Where its being mailed to
    // $recipients = array of recipients -- meant to replace $info/$name  #cls
    // $subject = The subject of the mail
    // $message = The body of the email
    // $name = name of person recieving email (not required)
    if (isset($recipients) && !empty($recipients)){
        if (is_array($recipients)) {
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
        }
    } else {
        if (!empty($name)) {
            $mail->AddAddress("$info", "$name");
        } else {
            $mail->AddAddress("$info");
        }
    }// if

    $mail->Subject = "$subject";
    // Set IsHTML - this is true for HTML mail
    $mail->IsHTML($htmlmail);
    // Check if this is HTML mail and set Body appropriately
    if ($htmlmail) {
        $mail->AltBody = "$message"; // Alternate message body
        $mail->Body = "$htmlmessage"; // HTML message body
    } else {
        $mail->Body = "$message";
    }


    $mail->Subject = "$subject";
    // Set IsHTML - this is true for HTML mail
    $mail->IsHTML($htmlmail);
    // Check if this is HTML mail and set Body appropriately
    if ($htmlmail) {
        $mail->AltBody = "$message"; // Alternate message body
        $mail->Body = "$htmlmessage"; // HTML message body
    } else {
        $mail->Body = "$message";
    }

    /* We are now setting up the advance options that can be used by the modules
        * Add Attachment will look to see if there is a var passed called
        * attachName and attachPath and attach it to the message
    */
    if ((!empty($attachName)) || (!empty($attachPath))) {
        $mail->AddAttachment("$attachPath", "$attachName");
    }
    // Send the mail, or send an exception.
    if (!$mail->Send()) {
        echo "Message was not sent <br/><br/>";
        echo "Mailer Error: " . $mail->ErrorInfo;
        return;
    }

    $mail->ClearAddresses();
    $mail->ClearAttachments();

    return true;
}

?>