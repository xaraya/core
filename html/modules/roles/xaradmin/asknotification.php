<?php
/**
 * File: $Id$
 *
 * Update users from roles_admin_showusers
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

/**
 * update users from roles_admin_showusers
 */
function roles_admin_asknotification($args)
{
    // Security Check
    if (!xarSecurityCheck('EditRole')) return;
    // Get parameters
    if (!xarVarFetch('phase', 'str:0:', $data['phase'], 'display', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('mailtype', 'str:0:', $data['mailtype'], 'blank', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if(!xarVarFetch('uid', 'isset', $uid, NULL, XARVAR_NOT_REQUIRED)) return;
    //Maybe some kind of return url will make this function available for other modules
    if (!xarVarFetch('state', 'int:0:', $data['state'], ROLES_STATE_CURRENT, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('groupuid', 'int:0:', $data['groupuid'], 0, XARVAR_NOT_REQUIRED)) return;
    //optional value
    if (!xarVarFetch('pass', 'str:0:', $data['pass'], NULL, XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('ip', 'str:0:', $data['ip'], NULL, XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    switch ($data['phase']) {
        case 'display' :
                $data['pass'] = xarSessionGetVar('tmppass');
                xarSessionDelVar('tmppass');
                if ($data['mailtype'] == 'blank') {
                    $data['subject'] = '';
                    $data['message'] = '';
                } elseif (xarModGetVar('roles', 'ask'.$data['mailtype'].'email')) {
                        $data['subject'] = xarModGetVar('roles', $data['mailtype'].'title');
                        $data['message'] = xarModGetVar('roles', $data['mailtype'].'email');
                }
                //Display the notification form
                if (!xarVarFetch('subject', 'str:1:', $data['subject'], $data['subject'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('message', 'str:1:', $data['message'], $data['message'], XARVAR_NOT_REQUIRED)) return;
                $data['authid'] = xarSecGenAuthKey();
                $data['uid'] = base64_encode(serialize($uid));

                // dynamic properties (if any)
                $data['properties'] = null;
                if (xarModIsAvailable('dynamicdata')) {
                    // get the Dynamic Object defined for this module (and itemtype, if relevant)
                    $object = &xarModAPIFunc('dynamicdata', 'user', 'getobject',
                        array('module' => 'roles'));
                    if (isset($object) && !empty($object->objectid)) {
                        // get the Dynamic Properties of this object
                        $data['properties'] = &$object->getProperties();
                    }
                }
                return $data;
            break;
        case 'notify' :
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            if (!xarVarFetch('subject', 'str:1:', $data['subject'], NULL, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('message', 'str:1:', $data['message'], NULL, XARVAR_NOT_REQUIRED)) return;
            //Send notification
            $uid = unserialize(base64_decode($uid));
            if (!xarModAPIFunc('roles','admin','senduseremail', array( 'uid' => $uid, 'mailtype' => $data['mailtype'], 'subject' => $data['subject'], 'message' => $data['message'], 'pass' => $data['pass'], 'ip' => $data['ip']))) {
                $msg = xarML('Problem sending email for #(1) Userid: #(2)',$data['mailtype'],$uid);
                xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            }
            xarResponseRedirect(xarModURL('roles', 'admin', 'showusers',
                              array('uid' => $data['groupuid'], 'state' => $data['state'])));
           break;
    }
}
?>
