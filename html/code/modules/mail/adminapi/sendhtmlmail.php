<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\AdminApi;
use EmptyParameterException;

/**
 * mail adminapi sendhtmlmail function
 * @extends MethodClass<AdminApi>
 */
class SendhtmlmailMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * This is a utility function that is called to send html mail
     * from any module regardless if the admin has configured html mail
     * @author John Cox <niceguyeddie@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['info'] is the email address we are sending (required)<br/>
     * string   $args['name'] is the name of the email recipient (optional)<br/>
     * array    $args['recipients'] is an array of recipients (required) // NOTE: $info or $recipients is required, not both<br/>
     * string   $args['ccinfo'] is the email address we are sending (optional)<br/>
     * string   $args['ccname'] is the name of the email recipient (optional)<br/>
     * array    $args['ccrecipients'] is an array of cc recipients (optional)<br/>
     * string   $args['bccinfo'] is the email address we are sending (required)<br/>
     * string   $args['bccname'] is the name of the email recipient (optional)<br/>
     * array    $args['bccrecipients'] is an array of bcc recipients (optional)<br/>
     * string   $args['subject'] is the subject of the email (required)<br/>
     * string   $args['message'] is the body of the email (required)<br/>
     * string   $args['htmlmessage'] is the html body of the email<br/>
     * integer  $args['priority'] is the priority of the message<br/>
     * string   $args['encoding'] is the encoding of the message<br/>
     * string   $args['wordwrap'] is the column width of the message<br/>
     * string   $args['from'] is who the email is from<br/>
     * string   $args['fromname'] is the name of the person the email is from<br/>
     * array    $args['attachments'] is an array of attachment definitions, each with name and path or string (if not a file)<br/>
     * string   $args['usetemplates'] set to true to use templates in xartemplates (default = true)<br/>
     * string   $args['when'] timestamp specifying that this mail should be sent 'no earlier than' (default is now)<br/>
     *          This requires installation and configuration of the scheduler module<br/>
     * string   $args['redirectsending'] set this to redirect email.(optional)<br/>
     * string   $args['redirectaddress'] is the email address we are redirecting mails.(optional)
     * @see AdminApi::sendhtmlmail()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Get arguments from argument array
        extract($args);

        // Check for required arguments
        if (!isset($info) && !isset($recipients)) {
            throw new EmptyParameterException('info or recipients');
        }
        if (!isset($subject)) {
            throw new EmptyParameterException('subject');
        }
        if (!isset($message)) {
            throw new EmptyParameterException('message');
        }

        // Check info
        if (!isset($info)) {
            $info = '';
        }
        // Check name
        if (!isset($name)) {
            $name = '';
        }
        // Check recpipients
        if (!isset($recipients)) {
            $recipients = [];
        }
        // Check CC info/name
        if (!isset($ccinfo)) {
            $ccinfo = '';
        }
        if (!isset($ccname)) {
            $ccname = '';
        }
        if (!isset($ccrecipients)) {
            $ccrecipients = [];
        }
        // Check BCC info/name
        if (!isset($bccinfo)) {
            $bccinfo = '';
        }
        if (!isset($bccname)) {
            $bccname = '';
        }
        if (!isset($bccrecipients)) {
            $bccrecipients = [];
        }
        // Check from
        if (empty($from)) {
            $from = $this->mod()->getVar('adminmail');
        }
        // Check fromname
        if (empty($fromname)) {
            $fromname = $this->mod()->getVar('adminname');
        }
        // Check wordwrap
        if (!isset($wordwrap)) {
            $wordwrap = $this->mod()->getVar('wordwrap');
        }
        // Check priority
        if (!isset($priority)) {
            $priority = $this->mod()->getVar('priority');
        }
        // Check encoding
        if (!isset($encoding)) {
            $encoding = $this->mod()->getVar('encoding');
        }
        // Check if using mail templates - default is true
        if (!isset($usetemplates)) {
            $usetemplates = true;
        }

        $parsedmessage = '';

        // Check if a valid htmlmessage was sent
        if (!empty($htmlmessage)) {
            // Set the html version of the message

            // Check if headers/footers have been configured by the admin
            $htmlheadfoot = $this->mod()->getVar('htmluseheadfoot');

            $parsedmessage .= $htmlheadfoot ? $this->mod()->getVar('htmlheader') : '';
            $parsedmessage .= $htmlmessage;
            $parsedmessage .= $htmlheadfoot ? $this->mod()->getVar('htmlfooter') : '';

        } else {
            // If the module did not send us an html version of the
            // message ($htmlmessage),
            // then we have to play around with this one a bit by adding some <pre> tags

            // Check if headers/footers have been configured by the admin
            $textheadfoot = $this->mod()->getVar('textuseheadfoot');

            $parsedmessage .= '<pre>';
            $parsedmessage .= $textheadfoot ? $this->mod()->getVar('textheader') : '';
            $parsedmessage .= $message;
            $parsedmessage .= $textheadfoot ? $this->mod()->getVar('textfooter') : '';
            $parsedmessage .= '</pre>';

        }

        // Check if we want delayed delivery of this mail message
        if (!isset($when)) {
            $when = null;
        }

        if (!isset($attachments) || !is_array($attachments)) {
            $attachments = [];
        }

        //Check redirect sending
        if (!isset($redirectsending)) {
            $redirectsending = '';
        }
        //Check redirect address
        if (!isset($redirectaddress)) {
            $redirectaddress = '';
        }
        if (!isset($custom_header)) {
            $custom_header = [];
        }
        if (!isset($message_envelope)) {
            $message_envelope = "";
        }

        // Call private sendmail
        return $adminapi->internal_sendmail(['info'             => $info,
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
            'htmlmessage'      => $parsedmessage, // set to $parsedmessage
            'priority'         => $priority,
            'encoding'         => $encoding,
            'wordwrap'         => $wordwrap,
            'from'             => $from,
            'fromname'         => $fromname,
            'usetemplates'     => $usetemplates,
            'when'             => $when,
            'attachments'      => $attachments,
            'redirectsending'  => $redirectsending,
            'redirectaddress'  => $redirectaddress,
            'htmlmail'         => true,
            'custom_header'    => $custom_header,
            'message_envelope' => $message_envelope]);
    }
}
