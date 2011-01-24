<?php
/**
 * Hook called to send mail on deletion of an item
 *
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * This is a hook function that is called to send mail on deletion of an item
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['modid'] is the module that is sending mail.<br/>
 *        integer  $args['objectid'] is the item deleted.
 */
function mail_adminapi_hookmaildelete(Array $args=array())
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

    $modid = xarMod::getRegID($modname);
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
//    if (!xarSecurityCheck('ManageMail', 0, 'All', "$modname::$objectid", 'mail')) return;

    // Set up variables
    $wordwrap = xarModVars::get('mail', 'wordwrap');
    $priority = xarModVars::get('mail', 'priority');
    $encoding = xarModVars::get('mail', 'encoding');
    if (empty($encoding)) {
        $encoding = '8bit';
        xarModVars::set('mail', 'encoding', $encoding);
    }
    $from = xarModVars::get('mail', 'adminmail');
    $fromname = xarModVars::get('mail', 'adminname');

// Get the templates for this message
    $strings = xarMod::apiFunc('mail','admin','getmessagestrings',
                             array('module' => 'mail',
                                   'template' => 'deletehook'));

    $subject = $strings['subject'];
    $message = $strings['message'];

    // Add root tage and compile the subject and message
    $subject  = xarTpl::compileString('<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">'.$subject.'</xar:template>');
    $message  = xarTpl::compileString('<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">'.$message.'</xar:template>');

    // Define the variables automatically available to all templates
    // LEGACY
    $data = array(
        'sitename'   => xarModVars::get('themes', 'SiteName'),
        'siteslogan' => xarModVars::get('themes', 'SiteSlogan'),
        'siteadmin'  => xarModVars::get('mail', 'adminname'),
        'adminmail'  => xarModVars::get('mail', 'adminmail'),
        'siteurl'    => xarServer::getBaseURL(),
        'myname'     => xarUserGetVar('name'),
        'myuname'    => xarUserGetVar('uname'),
        'myuid'      => xarUserGetVar('id'),
    );

// Substitute the dynamic vars in the template
    $data = array_merge($data,$extrainfo);
    $data['modulename'] = $modname;
    $data['objectid'] = $objectid;
    $subject = xarTpl::string($subject, $data);
    $message = xarTpl::string($message, $data);

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
    if ((bool)xarModVars::get('mail', 'html')) {
        xarMod::apiFunc('mail', 'admin', 'sendhtmlmail', $mailargs);
    } else {
        xarMod::apiFunc('mail', 'admin', 'sendmail', $mailargs);
    }
// life goes on, and so do hook calls :)
    return $extrainfo;
}

?>