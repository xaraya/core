<?php
/**
 * Send mail
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * This is a private utility function that is called to send mail
 * It is used by public functions sendmail() and sendhtmlmail()
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        string   $args['info'] is the email address we are sending (required)<br/>
 *        string   $args['name'] is the name of the email receipitent (optional)<br/>
 *        array    $args['recipients'] is an array of recipients (required) // NOTE: $info or $recipients is required, not both<br/>
 *        string   $args['ccinfo'] is the email address we are sending (optional)<br/>
 *        string   $args['ccname'] is the name of the email recipient (optional)<br/>
 *        array    $args['ccrecipients'] is an array of cc recipients (optional)<br/>
 *        string   $args['bccinfo'] is the email address we are sending (required)<br/>
 *        string   $args['bccname'] is the name of the email recipient (optional)<br/>
 *        array    $args['bccrecipients'] is an array of bcc recipients (optional)<br/>
 *        string   $args['subject'] is the subject of the email (required)<br/>
 *        string   $args['message'] is the body of the email (required)<br/>
 *        string   $args['htmlmessage'] is the html body of the email<br/>
 *        string   $args['priority'] is the priority of the message<br/>
 *        string   $args['encoding'] is the encoding of the message<br/>
 *        string   $args['wordwrap'] is the column width of the message<br/>
 *        string   $args['from'] is who the email is from<br/>
 *        string   $args['fromname'] is the name of the person the email is from<br/>
 *        string   $args['attachName'] is the name of an attachment to a message<br/>
 *        string   $args['attachPath'] is the path of the attachment<br/>
 *        string   $args['attachData'] is the data of the attachment if it is not a file<br/>
 *        string   $args['htmlmail'] is set to true for an html email<br/>
 *        string   $args['usetemplates'] set to true to use templates in xartemplates<br/>
 *        string   $args['when' timestamp specifying that this mail should be sent 'no earlier than' (default is now)
 *                  This requires installation and configuration of the scheduler module
 * @param  $args['redirectsending' set this to redirect email.(optional)
 * @param  $args['redirectaddress' is the email address we are redirecting mails.(optional)
 * 
 */
function mail_adminapi__sendmail(Array $args=array())
{
    // Branch off if we are using a newer version of PHPMailer in the lib directory
    $use_lib = xarModVars::get('mail', 'use_external_lib');
    if (!empty($use_lib) && file_exists(sys::lib() . 'PHPMailer')) {
        return xarMod::apiFunc('mail', 'admin', '_sendmail_new', $args);
    }
    
    if (xarModVars::get('mail', 'suppresssending')) return true;
// Get arguments from argument array

    extract($args);

    // Check for required arguments
    if (!isset($info) && !isset($recipients)) throw new EmptyParameterException('info or recipients');
    if (!isset($subject)) throw new EmptyParameterException('subject');
    if (!isset($message)) throw new EmptyParameterException('message');

    if (!empty($when) && $when > time() && xarModIsAvailable('scheduler')) {
        if (xarMod::apiFunc('mail','admin','_queuemail', $args)) {
            // we're done here
            return true;
        }
    }

    // Global search and replace %%text%%
    $replace = xarMod::apiFunc('mail',
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

    sys::import('modules.mail.class.phpmailer');

    $mail = new phpmailer();
    $mail->PluginDir = sys::code() . 'modules/mail/class/';
    $mail->ClearAllRecipients();

    // Set default language path to English.  This is necessary as
    // phpmailer will set an invalid path to the language directory
    // and throw an error.
    $mail->SetLanguage("en", sys::code() . "modules/mail/class/language/");

    // Get type of mail server
    $serverType = xarModVars::get('mail', 'server');

    switch($serverType) {
        case 'smtp':
            $mail->IsSMTP(); // telling the class to use SMTP
            $mail->Host = xarModVars::get('mail', 'smtpHost'); // SMTP server
            $mail->Port = xarModVars::get('mail', 'smtpPort'); // SMTP Port default 25.
            $mail->Helo = xarServer::getVar('SERVER_NAME'); // identification string sent to MTA at smtpHost

            // the smtp server might require authentication
            if (xarModVars::get('mail', 'smtpAuth')) {
                $mail->SMTPAuth = true; // turn on SMTP authentication
                $mail->Username = xarModVars::get('mail', 'smtpUserName'); // SMTP username
                $mail->Password = xarModVars::get('mail', 'smtpPassword'); // SMTP password
            }
            break;

        case 'sendmail':
            $mail->IsSendmail();
            $mail->Sendmail = xarModVars::get('mail', 'sendmailpath'); // Use the correct path to sendmail
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

    if (xarModVars::get('mail', 'replyto')) {
        $mail->AddReplyTo(xarModVars::get('mail', 'replytoemail'), xarModVars::get('mail', 'replytoname'));
    }

    // The parameters below are the bare minimum sent to the API.
    // $info = Where its being mailed to
    // $recipients = array of recipients -- meant to replace $info/$name
    // $subject = The subject of the mail
    // $message = The body of the email
    // $name = name of person receiving email (not required)
    if (!isset($redirectsending)){
        $redirectsending = '';
    }
    if(!isset($redirectaddress)) {
        $redirectaddress = '';
    }
    if (xarModVars::get('mail','redirectsending')) {
        $redirectsending = xarModVars::get('mail','redirectsending');
        $redirectaddress = xarModVars::get('mail','redirectaddress');        
    }
    if ($redirectsending) {
        $mail->ClearAddresses();
        $recipients = array();
        if (!empty($redirectaddress)) {
            $info = $redirectaddress;
            $name = xarML('Xaraya Mail Debugging');
        } else {
            return true;
        }
    }

    if($message_envelope) {
        $mail->Sender = $message_envelope;
    }

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
    if ($redirectsending) {
        $mail->ClearCCs();
        $ccrecipients = array();
    }
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
    if ($redirectsending) {
        $mail->ClearBCCs();
        $bccrecipients = array();
    }
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

    $mailShowTemplates  = xarModVars::get('mail', 'ShowTemplates');

    // If mailShowTemplates is undefined, then the modvar is missing for some reason
    // If so, we assume off, since the GUI will also show off in this case
    if (!isset($mailShowTemplates)) {
        xarModVars::set('mail','ShowTemplates',false);
        $mailShowTemplates = false;
    }

    // go ahead and override the show *theme* templates value,
    // using the mail modules settings instead :-)
    $oldShowTemplates = xarModVars::get('themes', 'ShowTemplates');
    xarModVars::set('themes', 'ShowTemplates', $mailShowTemplates);

    // Check if this is HTML mail and set Body appropriately
    if ($htmlmail) {
        // Sets the text-only body of the message.
        // This automatically sets the email to multipart/alternative.
        // This body can be read by mail clients that do not have HTML email
        // capability such as mutt. Clients that can read HTML will view the normal Body.
        /*if (!empty($message)) {
            if ($usetemplates) {
                $mail->AltBody = xarTpl::module('mail',
                                              'admin',
                                              'sendmail',
                                              array('message'=>$message),
                                              'text');
            } else {
                $mail->AltBody = $message;
            }
        }*/
        // HTML message body
        if ($usetemplates) {
            $mail->Body = xarTpl::module('mail',
                                       'admin',
                                       'sendmail',
                                       array('htmlmessage'=>$htmlmessage),
                                       'html');
        } else {
            $mail->Body = $htmlmessage;
        }
        //TODO:Handle the code for embedding images in the mail.
        //Parse a html body for getting the no of images used in the body.
        $html_images = array();
        $image_types = array(
                    'gif'  => 'image/gif',
                    'jpg'  => 'image/jpeg',
                    'jpeg'  => 'image/jpeg',
                    'jpe'  => 'image/jpeg',
                    'bmp'  => 'image/bmp',
                    'png'  => 'image/png',
                    'tif'  => 'image/tiff',
                    'tiff'  => 'image/tiff',
                    'swf'  => 'application/x-shockwave-flash'
                    );
 
        while(list($key,) = each($image_types)){
            $extensions[] = $key;
        }
        preg_match_all('/"([^"]+\.('.implode('|', $extensions).'))"/Ui', $mail->Body, $images);

        for($i = 0; $i < count($images[1]); $i++) {
            if(@is_file($images[1][$i]) && @fopen($images[1][$i], "rb"))
            {
                $html_images[] = $images[1][$i];
                $mail->Body = str_replace($images[1][$i], basename($images[1][$i]), $mail->Body);
            }
        }
        if(!empty($html_images)){
            $html_images = array_unique($html_images);
            sort($html_images);
            for($i = 0; $i < count($html_images); $i++){
                $cid = md5(uniqid(time()));
                //It will only work with the local path of images.
                $path = sys::root() . "./html/" . $html_images[$i];
                $mail->AddEmbeddedImage($path, $cid, basename($path));
                $mail->Body = str_replace(basename($path), "cid:$cid", $mail->Body);
            }
        }
    } else {
        if ($usetemplates) {
            $mail->Body = xarTpl::module('mail',
                                       'admin',
                                       'sendmail',
                                       array('message'=>$message),
                                       'text');
        } else {
            $mail->Body = $message;
        }
    }

    // Set the showTemplates back to what it was previously
    xarModVars::set('themes', 'ShowTemplates', $oldShowTemplates);

    // We are now setting up the advance options that can be used by the modules
    // Add Attachment will look to see if there is a var passed called
    // attachName and attachPath and attach it to the message

    if (!empty($attachments)) {
        foreach ($attachments as $attachment) {
            if (isset($attachment['path'])) {
                if (!empty($attachment['name'])) {
                    $mail->AddAttachment($attachment['path'], $attachment['name']);
                } else {
                    $mail->AddAttachment($attachment['path']);
                }
            } elseif  (isset($attachment['string'])){
                if (!empty($attachment['name'])) {
                    $mail->AddStringAttachment($attachment['string'], $attachment['name']);
                } else {
                    // For now just do nothing
                    // throw new EmptyParameterExeption(xarML('Missing a filename for an attachment'));
                }
            } else {
            }
        }
    }
    
    if(isset($custom_header) && !empty($custom_header)) {
        foreach ($custom_header as $key => $value)
        $mail->AddCustomHeader($value);
    }
    
    // Send the mail, or send an exception.
    $result = true;

    // CHECKME: does this hurt when a batch of emails is going out?
    try {
        $result = $mail->Send();
    } catch (Exception $e) {
        if (xarModVars::get('nail', 'debugmode') &&
            in_array(xarUserGetVar('uname'), xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
            echo '<pre>',var_dump($e->getMessage),'</pre>';
        }
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
