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
 * This is a hook function that is called to send mail on deletion of an item
 *
 * @param  $ 'modid' is the module that is sending mail.
 * @param  $ 'itemid' is the item created.
 */
function mail_adminapi_hookmaildelete($args)
{
    extract($args);

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
            'object ID', 'admin', 'hookmail', 'Mail - hooks');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
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
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
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

    // Security Check
    //TODO: if we add to the hook to allow sending of mail to OTHER recipients than the admin
    // we will have to include the following security check and make sure the appropriate privileges are assigned
//    if (!xarSecurityCheck('DeleteMail', 0, 'All', "$modname::$objectid", 'mail')) return;

    // Set up variables
    $sitename = xarModGetVar('themes', 'SiteName');
    $slogan = xarModGetVar('themes', 'SiteSlogan');
    $wordwrap = xarModGetVar('mail', 'wordwrap');
    $priority = xarModGetVar('mail', 'priority');
    $encoding = '8bit';
    $from = xarModGetVar('mail', 'adminmail');
    $fromname = xarModGetVar('mail', 'adminname');
    $subject = xarML('An item was deleted');
// TODO: use BL template for message
    // Send a regular old text message.
    $message = "" . xarML('An item was deleted in the') . " $modname " . xarML('module') . " -- $objectid " . xarML('is the new id for the item') . "\r\n\n";
    $message .= "" . xarML('Site Name') . ": $sitename :: $slogan\n";
    $message .= "" . xarML('Site URL') . ": " . xarServerGetBaseURL() . "\n";
    // Send a formatted html message to the mail module for use if the admin has the html turned on.
    $htmlmessage = "" . xarML('An item was deleted in the') . " $modname " . xarML('module') . " -- $objectid " . xarML('is the new id for the item') . "<br /><br />";
    $htmlmessage .= "" . xarML('Site Name') . ": $sitename :: <i>$slogan</i> <br />";
    $htmlmessage .= "" . xarML('Site URL') . ": <a href='" . xarServerGetBaseURL() . "'>" . xarServerGetBaseURL() . "</a><br />";
    // Set mail args array
    $mailargs = array('info' => $from, // set info to $from
        'subject' => $subject,
        'message' => $message,
        'htmlmessage' => $htmlmessage,
        'name' => $fromname, // set name to $fromname
        'priority' => $priority,
        'encoding' => $encoding,
        'wordwrap' => $wordwrap,
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