<?php
/**
 * send mail
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
 * This is a utility function that is called to send mail
 * from any module
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        string   $args['info'] is the email address we are sending (required)<br/>
 *        string   $args['name'] is the name of the email recipient<br/>
 *        array    $args['recipients'] is an array of recipients (required) // NOTE: $info or $recipients is required, not both<br/>
 *        string   $args['ccinfo'] is the email address we are sending (optional)<br/>
 *        string   $args['ccname'] is the name of the email recipient (optional)<br/>
 *        array    $args['ccrecipients'] is an array of cc recipients (optional)<br/>
 *        string   $args['bccinfo'] is the email address we are sending (required)<br/>
 *        string   $args['bccname'] is the name of the email receipitent (optional)<br/>
 *        array    $args['bccrecipients'] is an array of bcc recipients (optional)<br/>
 *        string   $args['subject'] is the subject of the email (required)<br/>
 *        string   $args['message'] is the body of the email (required)<br/>
 *        string   $args['htmlmessage'] is the html body of the email<br/>
 *        integer  $args['priority'] is the priority of the message<br/>
 *        string   $args['encoding'] is the encoding of the message<br/>
 *        string   $args['wordwrap'] is the column width of the message<br/>
 *        string   $args['from'] is who the email is from<br/>
 *        string   $args['fromname'] is the name of the person the email is from<br/>
 *        string   $args['attachName'] is the name of an attachment to a message<br/>
 *        string   $args['attachPath'] is the path of the attachment<br/>
 *        string   $args['usetemplates'] set to true to use templates in xartemplates (default = true)<br/>
 *        integer  $args['when'] timestamp specifying that this mail should be sent 'no earlier than' (default is now)<br/>
 *                  This requires installation and configuration of the scheduler module<br/>
 *        string   $args['redirectsending'] set this to redirect email.(optional)<br/>
 *        string   $args['redirectaddress'] is the email address we are redirecting mails.(optional)
 */
function mail_adminapi_sendmail(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($info) && !isset($recipients)) throw new EmptyParameterException('info or recipients');
    if (!isset($subject)) throw new EmptyParameterException('subject');
    if (!isset($message)) throw new EmptyParameterException('message');

    // Check if HTML mail has been configured by the admin
    // and send to sendhtmlmail()
    if ((bool)xarModVars::get('mail', 'html')) {
        return xarMod::apiFunc('mail', 'admin', 'sendhtmlmail', $args);
    } else {
        // Check info
        if (!isset($info)){
            $info = '';
        }
        // Check name
        if(!isset($name)) {
            $name='';
        }
        // Check recipients
        if (!isset($recipients)) {
            $recipients = array();
        }
        // Check CC info/name
        if (!isset($ccinfo)) {
            $ccinfo = '';
        }
        if (!isset($ccname)) {
            $ccname = '';
        }
        if (!isset($ccrecipients)) {
            $ccrecipients = array();
        }
        // Check BCC info/name
        if (!isset($bccinfo)) {
            $bccinfo = '';
        }
        if (!isset($bccname)) {
            $bccname = '';
        }
        if (!isset($bccrecipients)) {
            $bccrecipients = array();
        }
        // If htmlmessage is empty, then set to message
        if (empty($htmlmessage)) {
            $htmlmessage = $message;
        }
        // Check from
        if (empty($from)) {
            $from = xarModVars::get('mail', 'adminmail');
        }
        // Check fromname
        if (empty($fromname)) {
            $fromname = xarModVars::get('mail', 'adminname');
        }
        // Check wordwrap
        if (!isset($wordwrap)) {
            $wordwrap = xarModVars::get('mail', 'wordwrap');
        }
        // Check priority
        if (!isset($priority)) {
            $priority = xarModVars::get('mail', 'priority');
        }
        // Check encoding
        if (!isset($encoding)) {
            $encoding = xarModVars::get('mail', 'encoding');
        }
        // Check if using mail templates - default is true
        if (!isset($usetemplates)) {
            $usetemplates = true;
        }
        // Check if headers/footers have been configured by the admin
        $textheadfoot = xarModVars::get('mail', 'textuseheadfoot');
        if (!empty($textheadfoot)) {
            $header = xarModVars::get('mail', 'textheader');
            if (!empty($header)) {
                $message = $header . $message;
            }
            $footer = xarModVars::get('mail', 'textfooter');
            if (!empty($footer)) {
                $message .= $footer;
            }
        }
        // Check if we want delayed delivery of this mail message
        if (!isset($when)) {
            $when = null;
        }
        if (!isset($attachName)) {
            $attachName = '';
        }
        if (!isset($attachPath)) {
            $attachPath = '';
        }
        //Check redirect sending
        if (!isset($redirectsending)){
            $redirectsending = '';
        }
        //Check redirect address
        if(!isset($redirectaddress)) {
            $redirectaddress = '';
        }
        if(!isset($custom_header)){
            $custom_header = array();
        }
        if(!isset($message_envelope)){
            $message_envelope = "";
        }
        // Call private sendmail
        return xarMod::apiFunc('mail', 'admin', '_sendmail',
            array('info'             => $info,
                  'name'             => $name,
                  'recipients'       => $recipients,
                  'ccinfo'           => $ccinfo,
                  'ccname'           => $ccname,
                  'ccrecipients'     => $ccrecipients,
                  'bccinfo'          => $bccinfo,
                  'bccname'          => $bccname,
                  'bccrecipients'    => $bccrecipients,
                  'subject'          => $subject,
                  'message'          => $message,
                  'htmlmessage'      => $message, // set to $message
                  'priority'         => $priority,
                  'encoding'         => $encoding,
                  'wordwrap'         => $wordwrap,
                  'from'             => $from,
                  'fromname'         => $fromname,
                  'usetemplates'     => $usetemplates,
                  'when'             => $when,
                  'attachName'       => $attachName,
                  'attachPath'       => $attachPath,
                  'redirectsending'  => $redirectsending,
                  'redirectaddress'  => $redirectaddress,
                  'htmlmail'         => false,
                  'custom_header'    => $custom_header,
                  'message_envelope' => $message_envelope));
    }
}

?>
