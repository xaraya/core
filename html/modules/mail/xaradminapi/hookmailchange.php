<?php
/**
 * Hook function called to send mail
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * This is a hook function that is called to send mail when an item changes
 *
 * @param  $ 'modid' is the module that is sending mail.
 * @param  $ 'objectid' is the item changed.
 */
function mail_adminapi_hookmailchange($args)
{
    extract($args);

    if (!isset($objectid)) throw new EmptyParameterException('objectid');
    if (!is_numeric($objectid)) throw new BadParameterException(array('objectid',$objectid),'Parameter #(1) ["#(2)"] is not numeric');

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
    if (empty($modid)) throw new IDNotFoundException("modid for $modname");


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
//    if (!xarSecurityCheck('ChangeMail', 0, 'All', "$modname::$objectid", 'mail')) return;

    // Set up variables
    $wordwrap = xarModVars::Get('mail', 'wordwrap');
    $priority = xarModVars::Get('mail', 'priority');
    $encoding = xarModVars::Get('mail', 'encoding');
    if (empty($encoding)) {
        $encoding = '8bit';
        xarModVars::set('mail', 'encoding', $encoding);
    }
    $from = xarModVars::Get('mail', 'adminmail');
    $fromname = xarModVars::Get('mail', 'adminname');

// Get the templates for this message
    $strings = xarModAPIFunc('mail','admin','getmessagestrings',
                             array('module' => 'mail',
                                   'template' => 'changehook'));

    $subject = $strings['subject'];
    $message = $strings['message'];

// Get the template that defines the substitution vars
    $vars  = xarModAPIFunc('mail','admin','getmessageincludestring',
                           array('module' => 'mail',
                                 'template' => 'message-vars'));

// Substitute the static vars in the template
    $subject  = xarTplCompileString($vars . $subject);
    $message  = xarTplCompileString($vars . $message);

// Substitute the dynamic vars in the template
    $data = $extrainfo;
    $data['modulename'] = $modname;
    $data['objectid'] = $objectid;
    $subject = xarTplString($subject, $data);
    $message = xarTplString($message, $data);

    // TODO How to do this with BL? Create yet another template? Don't think so.
// Send a formatted html message to the mail module for use if the admin has the html turned on.
    $htmlmessage = $message;

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
    if (xarModVars::Get('mail', 'html')) {
        xarModAPIFunc('mail', 'admin', 'sendhtmlmail', $mailargs);
    } else {
        xarModAPIFunc('mail', 'admin', 'sendmail', $mailargs);
    }
// life goes on, and so do hook calls :)
    return $extrainfo;
}

?>
