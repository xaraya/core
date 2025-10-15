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
        if (!$this->sec()->checkAccess('AdminMail')) {
            return;
        }

        extract($args);
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        if (!isset($mailtype)) {
            $this->var()->find('mailtype', $data['mailtype'], 'str:1:100', 'createhook');
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
                $data['authid'] = $this->sec()->genAuthKey();
                break;

            case 'update':
                $this->var()->find('message', $message, 'str:1:');
                $this->var()->find('subject', $subject, 'str:1:');
                // Confirm authorisation code
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }

                if (!$adminapi->updatemessagestrings(['module' => 'mail',
                    'template' => $data['mailtype'],
                    'subject' => $subject,
                    'message' => $message])) {
                    return;
                }

                $this->ctl()->redirect($this->ctl()->getModuleURL(
                    'mail',
                    'admin',
                    'template',
                    ['mailtype' => $data['mailtype']]
                ));
                return true;
        }

        $data['settings'] = [];
        $hookedmodules = $this->mod()->apiFunc(
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
                        $mytypes = $this->mod()->apiFunc($modname, 'user', 'getitemtypes');
                    } catch (Exception $e) {
                        $mytypes = [];
                    }
                    foreach ($value as $itemtype => $val) {
                        if (isset($mytypes[$itemtype])) {
                            $type = $mytypes[$itemtype]['label'];
                            $link = $mytypes[$itemtype]['url'];
                        } else {
                            $type = $this->ml('type #(1)', $itemtype);
                            $link = $this->ctl()->getModuleURL($modname, 'user', 'view', ['itemtype' => $itemtype]);
                        }
                        $data['settings']["$modname.$itemtype"] = ['modname' => $modname,
                            'type' => $type,
                            'link' => $link];
                    }
                } else {
                    $type = '';
                    $link = $this->ctl()->getModuleURL($modname, 'user', 'main');
                    $data['settings'][$modname] = ['modname' => $modname,
                        'type' => $type,
                        'link' => $link];
                }
            }
        }
        return $data;
    }
}
