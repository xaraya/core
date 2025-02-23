<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\AdminGui;
use Xaraya\Modules\Mail\AdminApi;
use Exception;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail admin template function
 * @extends MethodClass<AdminGui>
 */
class TemplateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the email templates for hooked notifications
     * @return array|string|bool|void data for the template display
     * @see AdminGui::template()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminMail')) {
            return;
        }

        extract($args);
        xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
        if (!isset($mailtype)) {
            xarVar::fetch('mailtype', 'str:1:100', $data['mailtype'], 'createhook', xarVar::NOT_REQUIRED);
        } else {
            $data['mailtype'] = $mailtype;
        }

        // Get the list of available templates
        $data['templates'] = $adminapi->getmessagetemplates(['module' => 'mail']);

        switch (strtolower($phase)) {
            case 'modify':
            default:
                $strings = $adminapi->getmessagestrings(['module' => 'mail',
                    'template' => $data['mailtype']]);
                $data['subject'] = $strings['subject'];
                $data['message'] = $strings['message'];
                $data['authid'] = xarSec::genAuthKey();
                break;

            case 'update':
                xarVar::fetch('message', 'str:1:', $message);
                xarVar::fetch('subject', 'str:1:', $subject);
                // Confirm authorisation code
                if (!xarSec::confirmAuthKey()) {
                    return xarController::badRequest('bad_author', $this->getContext());
                }

                if (!$adminapi->updatemessagestrings(['module' => 'mail',
                    'template' => $data['mailtype'],
                    'subject' => $subject,
                    'message' => $message])) {
                    return;
                }

                xarController::redirect(xarController::URL(
                    'mail',
                    'admin',
                    'template',
                    ['mailtype' => $data['mailtype']]
                ), null, $this->getContext());
                return true;
        }

        $data['settings'] = [];
        $hookedmodules = xarMod::apiFunc(
            'modules',
            'admin',
            'gethookedmodules',
            ['hookModName' => 'mail']
        );
        if (isset($hookedmodules) && is_array($hookedmodules)) {
            foreach ($hookedmodules as $modname => $value) {
                // we have hooks for individual item types here
                if (!isset($value[0])) {
                    // Get the list of all item types for this module (if any)
                    try {
                        $mytypes = xarMod::apiFunc($modname, 'user', 'getitemtypes');
                    } catch (Exception $e) {
                        $mytypes = [];
                    }
                    foreach ($value as $itemtype => $val) {
                        if (isset($mytypes[$itemtype])) {
                            $type = $mytypes[$itemtype]['label'];
                            $link = $mytypes[$itemtype]['url'];
                        } else {
                            $type = xarML('type #(1)', $itemtype);
                            $link = xarController::URL($modname, 'user', 'view', ['itemtype' => $itemtype]);
                        }
                        $data['settings']["$modname.$itemtype"] = ['modname' => $modname,
                            'type' => $type,
                            'link' => $link];
                    }
                } else {
                    $type = '';
                    $link = xarController::URL($modname, 'user', 'main');
                    $data['settings'][$modname] = ['modname' => $modname,
                        'type' => $type,
                        'link' => $link];
                }
            }
        }
        return $data;
    }
}
