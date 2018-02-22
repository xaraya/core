<?php
/**
 * Send emails to users
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */

/**
 * Send emails to users by mailtype
 *
 * Ex: Lost Password, Confirmation
 * @todo FIXME: change name of id parameter to something that implies plurality, review why we need k => v array
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['id'] array of id of the user(s) array($id => '1')<br/>
 *        string   $args['mailtype'] type of the message to send (confirmation, deactivation, ...)<br/>
 *        string   $args['message'] the message of the mail (optionnal)<br/>
 *        string   $args['subject'] the subject of the mail (optionnal)<br/>
 *        string   $args['pass'] new password of the user (optionnal)<br/>
 *        string   $args['ip'] ip adress of the user (optionnal)
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM
 */
function roles_adminapi_senduseremail(Array $args=array())
{
    // Send Email
    extract($args);
    if (!isset($id)) throw new EmptyParameterException('id');
    if (!isset($mailtype)) throw new EmptyParameterException('mailtype');

    // Get the predefined email if none is defined
    $strings = xarMod::apiFunc('roles','admin','getmessagestrings', array('module' => 'roles','template' => $mailtype));

    if (!isset($subject)) $subject = xarTpl::compileString($strings['subject']);
    if (!isset($message)) $message = xarTpl::compileString($strings['message']);
    //Get the common search and replace values
    //if (is_array($id)) {
        foreach ($id as $userid => $val) {
            ///get the user info
            $user = xarMod::apiFunc('roles','user','get', array('id' => $userid, 'itemtype' => xarRoles::ROLES_USERTYPE));
            if (!isset($pass)) $pass = '';
            if (!isset($ip)) $ip = '';
            
            $validationlink = isset($user['valcode']) ?
                xarModURL('roles', 'user', 'getvalidation',
                    array(
                        'uname' => $user['uname'],
                        'phase' => 'getvalidate',
                        'valcode' => $user['valcode'],
                    ), false)
                : '';            

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

            // get the DataObject defined for this module and item id
            sys::import('modules.dynamicdata.class.objects.master');
            $object = DataObjectMaster::getObject(array('name' => 'roles_users'));
            if (isset($object) && !empty($object->objectid)) {
                // retrieve the item itself
                $itemid = $object->getItem(array('itemid' => $userid));
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

            $subject = xarTpl::string($subject, $data);
            $message = xarTpl::string($message, $data);
            // TODO Make HTML Message.
            // Send confirmation email
            if (!xarMod::apiFunc('mail',
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
