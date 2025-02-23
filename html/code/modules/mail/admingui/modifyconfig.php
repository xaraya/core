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
use xarController;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarTpl;
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail admin modifyconfig function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the configuration settings of this module
     * Standard GUI function to display and update the configuration settings of the module based on input data.
     * @author John Cox <niceguyeddie@xaraya.com>
     * @access public
     * @return mixed data array for the template display or output display string if invalid data submitted
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminMail')) {
            return;
        }

        $data = [];
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $this->var()->find('tab', $data['tab'], 'str:1', 'general');

        // Get encoding
        $data['encoding'] = xarModVars::get('mail', 'encoding');

        // Redirect address - ensure it's set
        $address = trim(xarModVars::get('mail', 'redirectaddress') ?? '');
        if (isset($address) && !empty($address)) {
            $data['redirectaddress'] = xarVar::prepForDisplay($address);
        } else {
            $data['redirectaddress'] = '';
        }

        $data['library_exists'] = file_exists(sys::lib() . 'PHPMailer');

        if (xarMod::isAvailable('scheduler')) {
            $intervals = xarMod::apiFunc('scheduler', 'user', 'intervals');
            $data['intervals'][] = ['id' => '', 'name' => xarML('not supported')];
            foreach ($intervals as $id => $name) {
                $data['intervals'][] = ['id' => $id, 'name' => $name];
            }
            // see if we have a scheduler job running to send queued mail
            $job = xarMod::apiFunc(
                'scheduler',
                'user',
                'get',
                ['module' => 'mail',
                    'type' => 'scheduler',
                    'func' => 'sendmail']
            );
            if (empty($job) || empty($job['interval'])) {
                $data['interval'] = '';
            } else {
                $data['interval'] = $job['interval'];
            }
            // get the waiting queue
            $serialqueue = xarModVars::get('mail', 'queue');
            if (!empty($serialqueue)) {
                $queue = unserialize($serialqueue);
            } else {
                $queue = [];
            }
            $data['unsent'] = count($queue);
        }

        $data['module_settings'] = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'mail']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls');
        $data['module_settings']->getItem();
        switch (strtolower($phase)) {
            case 'modify':
            default:
                break;

            case 'update':
                // Confirm authorisation code
                if (!xarSec::confirmAuthKey()) {
                    return xarController::badRequest('bad_author', $this->getContext());
                }
                switch ($data['tab']) {
                    case 'general':
                        // new modvar in 2.0.0, only store the id of the designated admin
                        $this->var()->find('admin_outgoing', $admin_outgoing, 'id');
                        $this->var()->find('showtemplates', $showtemplates, 'checkbox', false);
                        $this->var()->find('replyto', $replyto, 'checkbox', false);
                        $this->var()->find('replytoname', $replytoname, 'str:1:', '');
                        $this->var()->find('replytoemail', $replytoemail, 'str:1:', '');
                        $this->var()->find('debugmode', $debugmode, 'checkbox', false);

                        $isvalid = $data['module_settings']->checkInput();
                        if (!$isvalid) {
                            $data['context'] ??= $this->getContext();
                            return xarTpl::module('mail', 'admin', 'modifyconfig', $data);
                        } else {
                            $itemid = $data['module_settings']->updateItem();
                        }

                        if (isset($admin_outgoing)) {
                            xarModVars::set('mail', 'admin_outgoing', $admin_outgoing);
                        }
                        // set the modvars used by sendmail as default from name, address
                        $adminname = xarUser::getVar('name', $admin_outgoing);
                        $adminmail = xarUser::getVar('email', $admin_outgoing);
                        xarModVars::set('mail', 'adminname', $adminname);
                        xarModVars::set('mail', 'adminmail', $adminmail);

                        xarModVars::set('mail', 'ShowTemplates', $showtemplates);
                        xarModVars::set('mail', 'replyto', $replyto);
                        xarModVars::set('mail', 'replytoname', $replytoname);
                        xarModVars::set('mail', 'replytoemail', $replytoemail);

                        xarModVars::set('mail', 'debugmode', $debugmode);

                        // Suppoert for PHPMailer as an external library
                        if (file_exists(sys::lib() . 'PHPMailer')) {
                            $this->var()->find('use_external_lib', $use_external_lib, 'checkbox', false);
                            xarModVars::set('mail', 'use_external_lib', $use_external_lib);
                        }

                        break;
                    case 'incoming':
                        break;
                    case 'outgoing':
                        $this->var()->find('html', $html, 'checkbox', false);
                        $this->var()->find('htmluseheadfoot', $htmluseheadfoot, 'checkbox', false);
                        $this->var()->find('htmlheader', $htmlheader, 'str:1:', '');
                        $this->var()->find('htmlfooter', $htmlfooter, 'str:1:', '');
                        $this->var()->find('textuseheadfoot', $textuseheadfoot, 'checkbox', false);
                        $this->var()->find('textheader', $textheader, 'str:1:', '');
                        $this->var()->find('textfooter', $textfooter, 'str:1:', '');
                        $this->var()->find('wordwrap', $wordwrap, 'int:1:', '50');
                        $this->var()->find('priority', $priority, 'str:1:', 'normal');
                        $this->var()->find('encoding', $encoding, 'str:1:');
                        $this->var()->find('embed_images', $embed_images, 'checkbox', false);
                        $this->var()->find('server', $server, 'str:1:', 'mail');
                        $this->var()->find('smtpHost', $smtpHost, 'str:1:', '');
                        $this->var()->find('smtpPort', $smtpPort, 'int:1:', '25');
                        $this->var()->find('smtpSecure', $smtpSecure, 'str:1:', '');
                        $this->var()->find('smtpAuth', $smtpAuth, 'checkbox', false);
                        $this->var()->find('htmlheader', $htmlheader, 'str:1:', '');
                        $this->var()->find('smtpUserName', $smtpUserName, 'str:1:', '');
                        $this->var()->find('smtpPassword', $smtpPassword, 'str:1:', '');
                        $this->var()->find('sendmailpath', $sendmailpath, 'str:1:', '/usr/sbin/sendmail');
                        $this->var()->find('searchstrings', $searchstrings, 'str:1', '');
                        $this->var()->find('replacestrings', $replacestrings, 'str:1', '');
                        $this->var()->find('suppresssending', $suppresssending, 'checkbox', false);
                        $this->var()->find('redirectsending', $redirectsending, 'checkbox', false);
                        $this->var()->find('redirectaddress', $redirectaddress, 'str:1:', '');

                        // update the data
                        xarModVars::set('mail', 'html', $html);
                        xarModVars::set('mail', 'htmluseheadfoot', $htmluseheadfoot);
                        xarModVars::set('mail', 'htmlheader', $htmlheader);
                        xarModVars::set('mail', 'htmlfooter', $htmlfooter);
                        xarModVars::set('mail', 'textuseheadfoot', $textuseheadfoot);
                        xarModVars::set('mail', 'textheader', $textheader);
                        xarModVars::set('mail', 'textfooter', $textfooter);
                        xarModVars::set('mail', 'priority', $priority);
                        xarModVars::set('mail', 'encoding', $encoding);
                        xarModVars::set('mail', 'embed_images', $embed_images);
                        xarModVars::set('mail', 'wordwrap', $wordwrap);
                        xarModVars::set('mail', 'server', $server);
                        xarModVars::set('mail', 'smtpHost', $smtpHost);
                        xarModVars::set('mail', 'smtpPort', $smtpPort);
                        xarModVars::set('mail', 'smtpAuth', $smtpAuth);
                        xarModVars::set('mail', 'smtpSecure', $smtpSecure);
                        xarModVars::set('mail', 'smtpUserName', $smtpUserName);
                        if (!empty($smtpPassword)) {
                            xarModVars::set('mail', 'smtpPassword', $smtpPassword);
                        }

                        xarModVars::set('mail', 'sendmailpath', $sendmailpath);
                        xarModVars::set('mail', 'searchstrings', serialize($searchstrings));
                        xarModVars::set('mail', 'replacestrings', serialize($replacestrings));
                        xarModVars::set('mail', 'suppresssending', $suppresssending);
                        xarModVars::set('mail', 'redirectsending', $redirectsending);
                        xarModVars::set('mail', 'redirectaddress', $redirectaddress);

                        if (xarMod::isAvailable('scheduler')) {
                            $this->var()->find('interval', $interval, 'str:1', '');
                            // see if we have a scheduler job running to send queued mail
                            $job = xarMod::apiFunc(
                                'scheduler',
                                'user',
                                'get',
                                ['module' => 'mail',
                                    'type' => 'scheduler',
                                    'func' => 'sendmail']
                            );
                            if (empty($job) || empty($job['interval'])) {
                                if (!empty($interval)) {
                                    // create a scheduler job
                                    xarMod::apiFunc(
                                        'scheduler',
                                        'admin',
                                        'create',
                                        ['module' => 'mail',
                                            'type' => 'scheduler',
                                            'func' => 'sendmail',
                                            'interval' => $interval]
                                    );
                                }
                            } elseif (empty($interval)) {
                                // delete the scheduler job
                                xarMod::apiFunc(
                                    'scheduler',
                                    'admin',
                                    'delete',
                                    ['module' => 'mail',
                                        'type' => 'scheduler',
                                        'func' => 'sendmail']
                                );
                            } elseif ($interval != $job['interval']) {
                                // update the scheduler job
                                xarMod::apiFunc(
                                    'scheduler',
                                    'admin',
                                    'update',
                                    ['module' => 'mail',
                                        'type' => 'scheduler',
                                        'func' => 'sendmail',
                                        'interval' => $interval]
                                );
                            }
                        }
                }
                break;
        }
        return $data;
    }
}
