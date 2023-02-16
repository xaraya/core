<?php
/**
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 */
/**
 * Modify the email templates for hooked notifications
 *
 * @return array data for the template display
 */
function mail_admin_template(Array $args=array())
{
    // Security
    if (!xarSecurity::check('AdminMail')) return;

    extract($args);
    if (!xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) return;
    if (!isset($mailtype)) xarVar::fetch('mailtype', 'str:1:100', $data['mailtype'], 'createhook', xarVar::NOT_REQUIRED);
    else $data['mailtype'] = $mailtype;

    // Get the list of available templates
    $data['templates'] = xarMod::apiFunc('mail','admin','getmessagetemplates',
                                       array('module' => 'mail'));

    switch (strtolower($phase)) {
        case 'modify':
        default:
            $strings = xarMod::apiFunc('mail','admin','getmessagestrings',
                                     array('module' => 'mail',
                                           'template' => $data['mailtype']));
            $data['subject'] = $strings['subject'];
            $data['message'] = $strings['message'];
            $data['authid'] = xarSec::genAuthKey();
            break;

        case 'update':
            if (!xarVar::fetch('message', 'str:1:', $message)) return;
            if (!xarVar::fetch('subject', 'str:1:', $subject)) return;
            // Confirm authorisation code
            if (!xarSec::confirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }        

            if (!xarMod::apiFunc('mail','admin','updatemessagestrings',
                               array('module' => 'mail',
                                     'template' => $data['mailtype'],
                                     'subject' => $subject,
                                     'message' => $message))) {
                return;
            }

            xarController::redirect(xarController::URL('mail', 'admin', 'template',
                                          array('mailtype' => $data['mailtype'])));
            return true;
            break;
    }

    $data['settings'] = array();
    $hookedmodules = xarMod::apiFunc('modules', 'admin', 'gethookedmodules',
                                   array('hookModName' => 'mail'));
    if (isset($hookedmodules) && is_array($hookedmodules)) {
        foreach ($hookedmodules as $modname => $value) {
            // we have hooks for individual item types here
            if (!isset($value[0])) {
                // Get the list of all item types for this module (if any)
                try {
                    $mytypes = xarMod::apiFunc($modname,'user','getitemtypes',
                    // don't throw an exception if this function doesn't exist
                    array());
                } catch (Exception $e) {
                    $mytypes = [];
                }
                foreach ($value as $itemtype => $val) {
                    if (isset($mytypes[$itemtype])) {
                        $type = $mytypes[$itemtype]['label'];
                        $link = $mytypes[$itemtype]['url'];
                    } else {
                        $type = xarML('type #(1)',$itemtype);
                        $link = xarController::URL($modname,'user','view',array('itemtype' => $itemtype));
                    }
                    $data['settings']["$modname.$itemtype"] = array('modname' => $modname,
                                                                    'type' => $type,
                                                                    'link' => $link);
                }
            } else {
                $type = '';
                $link = xarController::URL($modname,'user','main');
                $data['settings'][$modname] = array('modname' => $modname,
                                                    'type' => $type,
                                                    'link' => $link);
            }
        }
    }
    return $data;
}
?>
