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
 * This is a utility function that is called to send html mail
 * from any module regardless if the admin has configured html mail
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
 * @param  $ 'when' timestamp specifying that this mail should be sent 'no earlier than' (default is now)
 *                  This requires installation and configuration of the scheduler module
 */
function mail_adminapi_sendhtmlmail($args)
{
    // Get arguments from argument array
    extract($args);
    // Have to do a bit more work as some variables may not have
    // been passed in and we need to set others before calling
    // private sendmail()
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
    if (empty($name)) {
        $name = '';
    }
    if (empty($info)) {
        $info = '';
    }
    if (empty($recipients)) {
        $recipients = '';
    }

    // Check if HTML mail has been configured by the admin
    $adminhtml = xarModGetVar('mail', 'html');

    $parsedmessage = '';
    // Check if a valid htmlmessage was sent
    if (!empty($htmlmessage)) {
        // If admin set HTML mail then include header and footer
        if ($adminhtml) {
            $htmlheader = xarModGetVar('mail', 'htmlheader');
            $parsedmessage .= $htmlheader;
            $parsedmessage .= '<br /><br />';
        }
        // Set the html version of the message
        $parsedmessage .= $htmlmessage;
    } else {
        // If the module did not send us an html version of the
        // message ($htmlmessage),
        // then we have to play around with this one a bit.
        $parsedmessage .= '<pre>';
        $parsedmessage .= $message;
        $parsedmessage .= '</pre>';
    }
    // If admin set HTML mail then include header and footer
    if ($adminhtml) {
        $htmlfooter = xarModGetVar('mail', 'htmlfooter');
        $parsedmessage .= '<br /><br />';
        $parsedmessage .= $htmlfooter;
    }
    // Call private sendmail
    return xarModAPIFunc('mail', 'admin', '_sendmail',
        array('info'        => $info,
            'recipients'  => $recipients,
            'subject'       => $subject,
            'message'       => $message,
            'htmlmessage'   => $parsedmessage, // set to $parsedmessage
            'name'          => $name,
            'priority'      => $priority,
            'encoding'      => $encoding,
            'wordwrap'      => $wordwrap,
            'from'          => $from,
            'fromname'      => $fromname,
            'htmlmail'      => true));
}

?>