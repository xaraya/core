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
 * This is a hook function that is called to send mail on creation of an item
 *
 * @param  $ 'modid' is the module that is sending mail.
 * @param  $ 'itemid' is the item created.
 */
function mail_adminapi_hookmailcreate($args)
{
    extract($args);

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
            'object ID', 'admin', 'hookmail', 'Mail - hooks');
        xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
            new SystemException($msg));
        return;
    }
    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $extrainfo = array();
    }

    // When called via hooks, modname wil be empty, but we get it from the
    // extrainfo or the current module
    if (empty($modname)) {
        if (!empty($extrainfo['module'])) {
            $modname = $extrainfo['module'];
        } else {
            $modname = xarModGetName();
        }
    }

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
            'module name', 'admin', 'deletewc', 'adminpanels - waiting content');
        xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
            new SystemException($msg));
        return;
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
         if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
             $itemtype = $extrainfo['itemtype'];
         } else {
             $itemtype = 0;
         }
    }

    $from = xarModGetVar('mail', 'adminmail');
    $fromname = xarModGetVar('mail', 'adminname');

// Get the templates for this message
    $strings = xarModAPIFunc('roles','admin','getmessagestrings', array('module' => 'mail', 'template' => 'createhook'));

    $subject = $strings['subject'];
    $message = $strings['message'];

// Get the template that defines the substitution vars
    $messaginghome = "var/messaging/mail";
    if (!file_exists($messaginghome . "/includes/message-vars.xd")) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException('The variables template was not found.'));
    }
    $string = '';
    $fd = fopen($messaginghome . "/includes/message-vars.xd", 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }

// Substitute the static vars in the template
    $subject  = xarTplCompileString($string . $subject);
    $message  = xarTplCompileString($string . $message);

// Substitute the dynamic vars in the template
    $data = array('modulename' => $modname, 'objectid' => $objectid);
    $subject = xarTplString($subject, $data);
    $message = xarTplString($message, $data);

    // TODO How to do html message with BL? Create yet another template? Don't think so.

//    $message = xarModGetVar('mail', 'hooktemplate');

/*
    $search = array('/%%name%%/',
                    '/%%sitename%%/',
                    '/%%siteslogan%%/',
                    '/%%siteurl%%/',
                    '/%%uid%%/',
                    '/%%siteadmin%%/');

    $replace = array("$name",
                     "$sitename",
                     "$siteslogan",
                     "$siteurl",
                     "$uid",
                     "$siteadmin");

    $message = preg_replace($search,
                            $replace,
                            $message);

    $message = "" . xarML('A new item was created in the') . " $modname " . xarML('module') . " -- $objectid " . xarML('is the new id for the item') . "\r\n\n";
    $message .= "" . xarML('Site Name') . ": $sitename :: $slogan\n";
    $message .= "" . xarML('Site URL') . ": " . xarServerGetBaseURL() . "\n";
    // Send a formatted html message to the mail module for use if the admin has the html turned on.
    $htmlmessage = "" . xarML('A new item was created in the') . " $modname " . xarML('module') . " -- $objectid " . xarML('is the new id for the item') . "<br /><br />";
    $htmlmessage .= "" . xarML('Site Name') . ": $sitename :: <i>$slogan</i> <br />";
    $htmlmessage .= "" . xarML('Site URL') . ": <a href='" . xarServerGetBaseURL() . "'>" . xarServerGetBaseURL() . "</a><br />";
*/
    // Set mail args array
    $mailargs = array('info' => $from, // set info to $from
        'subject' => $subject,
        'message' => $message,
        'name' => $fromname, // set name to $fromname
        'from' => $from,
        'fromname' => $fromname);
    // Check if HTML mail has been configured by the admin
    if (xarModGetVar('mail', 'html')) {
        xarModAPIFunc('mail', 'admin', 'sendhtmlmail', $mailargs);
    } else {
        xarModAPIFunc('mail', 'admin', 'sendmail', $mailargs);
    }
    // life goes on, and so do hook calls :)
    return $extrainfo;
}
?>