<?php
/**
 * Modify the email templates for hooked notifications
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 */
/**
 * Modify the email templates for hooked notifications
 */
function mail_admin_template($args)
{
    // Security Check
    if (!xarSecurityCheck('AdminMail')) return;

    extract($args);
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!isset($mailtype)) xarVarFetch('mailtype', 'str:1:100', $data['mailtype'], 'createhook', XARVAR_NOT_REQUIRED);
    else $data['mailtype'] = $mailtype;

    // Get the list of available templates
    $data['templates'] = xarModAPIFunc('mail','admin','getmessagetemplates',
                                       array('module' => 'mail'));

    switch (strtolower($phase)) {
        case 'modify':
        default:
            $strings = xarModAPIFunc('mail','admin','getmessagestrings',
                                     array('module' => 'mail',
                                           'template' => $data['mailtype']));
            $data['subject'] = $strings['subject'];
            $data['message'] = $strings['message'];
            $data['authid'] = xarSecGenAuthKey();
            break;

        case 'update':
            if (!xarVarFetch('message', 'str:1:', $message)) return;
            if (!xarVarFetch('subject', 'str:1:', $subject)) return;
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;

            if (!xarModAPIFunc('mail','admin','updatemessagestrings',
                               array('module' => 'mail',
                                     'template' => $data['mailtype'],
                                     'subject' => $subject,
                                     'message' => $message))) {
                return;
            }

            xarResponseRedirect(xarModURL('mail', 'admin', 'template',
                                          array('mailtype' => $data['mailtype'])));
            return true;
            break;
    }

    $data['settings'] = array();
    $hookedmodules = xarModAPIFunc('modules', 'admin', 'gethookedmodules',
                                   array('hookModName' => 'mail'));
    if (isset($hookedmodules) && is_array($hookedmodules)) {
        foreach ($hookedmodules as $modname => $value) {
            // we have hooks for individual item types here
            if (!isset($value[0])) {
                // Get the list of all item types for this module (if any)
                $mytypes = xarModAPIFunc($modname,'user','getitemtypes',
                                         // don't throw an exception if this function doesn't exist
                                         array(), 0);
                foreach ($value as $itemtype => $val) {
                    if (isset($mytypes[$itemtype])) {
                        $type = $mytypes[$itemtype]['label'];
                        $link = $mytypes[$itemtype]['url'];
                    } else {
                        $type = xarML('type #(1)',$itemtype);
                        $link = xarModURL($modname,'user','view',array('itemtype' => $itemtype));
                    }
                    $data['settings']["$modname.$itemtype"] = array('modname' => $modname,
                                                                    'type' => $type,
                                                                    'link' => $link);
                }
            } else {
                $type = '';
                $link = xarModURL($modname,'user','main');
                $data['settings'][$modname] = array('modname' => $modname,
                                                    'type' => $type,
                                                    'link' => $link);
            }
        }
    }
    return $data;
}
?>
