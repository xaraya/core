<?php
/**
 * File: $Id$
 *
 * Modify the email for users
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Xaraya Team
 */
/**
 * Modify the  email for users
 */
function roles_admin_modifyemail($args)
{
    // Security Check
    if (!xarSecurityCheck('EditRole')) return;

    extract($args);
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED)) return;

    if (!isset($mailtype)) xarVarFetch('mailtype', 'str:1:100', $data['mailtype'], 'welcome', XARVAR_NOT_REQUIRED);
    else $data['mailtype'] = $mailtype;

    switch (strtolower($phase)) {
        case 'modify':
        default:
            $data['subject'] = xarModGetVar('roles', $data['mailtype'].'title');
            $data['message'] = xarModGetVar('roles', $data['mailtype'].'email');
            $data['authid'] = xarSecGenAuthKey();

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
            break;

        case 'update':

            if (!xarVarFetch('message', 'str:1:', $message)) return;
            if (!xarVarFetch('subject', 'str:1:', $subject)) return;
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;

            xarModSetVar('roles', $data['mailtype'].'email', $message);
            xarModSetVar('roles', $data['mailtype'].'title', $subject);

            xarResponseRedirect(xarModURL('roles', 'admin', 'modifyemail', array('mailtype' => $data['mailtype'])));
            return true;

            break;
    }

    return $data;
}

?>
