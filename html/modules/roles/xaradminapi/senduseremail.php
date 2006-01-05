<?php
/**
 * Send emails to users
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/**
 * Send emails to users by mailtype 
 * 
 * Ex: Lost Password, Confirmation
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] array of uid of the user(s) array($uid => '1')
 * @param $args['mailtype'] type of the message to send (confirmation, deactivation, ...)
 * @param $args['message'] the message of the mail (optionnal)
 * @param $args['subject'] the subject of the mail (optionnal)
 * @param $args['pass'] new password of the user (optionnal)
 * @param $args['ip'] ip adress of the user (optionnal)
 * @returns bool
 * @return true on success, false on failures
 * @raise BAD_PARAM
 */
function roles_adminapi_senduseremail($args)
{

    // Send Email
    extract($args);
    if ((!isset($uid)) || (!isset($mailtype))) {
        $msg = xarML('Wrong arguments to roles_adminapi_senduseremail. uid: #(1) type: #(2)',$uid,$mailtype);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    // Get the predefined email if none is defined
    $strings = xarModAPIFunc('roles','admin','getmessagestrings', array('module' => 'roles','template' => $mailtype));

    $vars  = xarModAPIFunc('roles','admin','getmessageincludestring', array('module' => 'roles','template' => 'message-vars'));

    if (!isset($subject)) $subject = xarTplCompileString($vars . $strings['subject']);
    if (!isset($message)) $message = xarTplCompileString($vars . $strings['message']);

    //Get the common search and replace values
    //if (is_array($uid)) {
        foreach ($uid as $userid => $val) {
            ///get the user info
            $user = xarModAPIFunc('roles','user','get', array('uid' => $userid));
            if (!isset($pass)) $pass = '';
            if (!isset($ip)) $ip = '';
            if (isset($user['valcode'])) $validationlink = xarServerGetBaseURL() . "val.php?v=".$user['valcode']."&u=".$userid;
            else $validationlink = '';

            //user specific data
            $data = array('myname' => $user['name'],
                          'name' => $user['name'],
                          'myusername' => $user['uname'],
                          'username' => $user['uname'],
                          'myemail' => $user['email'],
                          'email' => $user['email'],
                          'mystate' => $user['state'],
                          'state' => $user['state'],
                          'mypassword' => $pass,
                          'password' => $pass,
                          'myipaddress' => $ip,
                          'ipaddress' => $ip,
                          'myvalcode' => $user['valcode'],
                          'valcode' => $user['valcode'],
                          'myvalidationlink' => $validationlink,
                          'validationlink' => $validationlink,
                          'recipientname' => $user['name']);

            // retrieve the dynamic properties (if any) for use in the e-mail too
            if (xarModIsAvailable('dynamicdata')) {
                // get the Dynamic Object defined for this module and item id
                $object = xarModAPIFunc('dynamicdata','user','getobject',
                                         array('module' => 'roles',
                                               // we know the item id now...
                                               'itemid' => $userid));
                if (isset($object) && !empty($object->objectid)) {
                    // retrieve the item itself
                    $itemid = $object->getItem();
                    if (!empty($itemid) && $itemid == $userid) {
                        // get the Dynamic Properties of this object
                        $properties =& $object->getProperties();
                        foreach (array_keys($properties) as $key) {
                            // add the property name/value to the search/replace lists
                            if (isset($properties[$key]->value)) {
                                $data[$key] = $properties[$key]->value; // we'll use the raw value here, not ->showOutput();
                            }
                        }
                    }
                }
            }

            $subject = xarTplString($subject, $data);
            $message = xarTplString($message, $data);
            // TODO Make HTML Message.
            // Send confirmation email
            if (!xarModAPIFunc('mail',
                               'admin',
                               'sendmail',
                               array('info' => $user['email'],
                                     'name' => $user['name'],
                                     'subject' => $subject,
                                     'message' => $message))) return false;
    }
    return true;
}

?>
