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
 * This is a utility function that is called to send mail
 * from any module
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
 * @param  $ 'usetemplates' set to true to use templates in xartemplates
 * @param  $ 'when' timestamp specifying that this mail should be sent 'no earlier than' (default is now)
 *                  This requires installation and configuration of the scheduler module
 */
function mail_adminapi_sendmail($args)
{
    // Check if HTML mail has been configured by the admin
    // and send to sendhtmlmail()
    if (xarModGetVar('mail', 'html')) {
        return xarModAPIFunc('mail', 'admin', 'sendhtmlmail', $args);
    } else {
        // Call private sendmail
        return xarModAPIFunc('mail', 'admin', '_sendmail', $args);
    }
}

?>
