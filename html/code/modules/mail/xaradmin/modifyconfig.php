<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 */

/**
 * Modify the configuration settings of this module
 *
 * Standard GUI function to display and update the configuration settings of the module based on input data.
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return mixed data array for the template display or output display string if invalid data submitted
*/
function mail_admin_modifyconfig()
{
    // Security
    if (!xarSecurity::check('AdminMail')) return;

    if (!xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) return;
    if (!xarVar::fetch('tab','str:1', $data['tab'], 'general', xarVar::NOT_REQUIRED)) return;
    
    // Get encoding
    $data['encoding'] = xarModVars::get('mail', 'encoding');

    //redirect address - ensure it's set
    $address = trim(xarModVars::get('mail', 'redirectaddress'));
    if (isset($address) && !empty($address)){
        $data['redirectaddress']=xarVar::prepForDisplay($address);
    } else {
        $data['redirectaddress']='';
    }

    if (xarMod::isAvailable('scheduler')) {
        $intervals = xarMod::apiFunc('scheduler','user','intervals');
        $data['intervals'][] = array('id' => '', 'name' => xarML('not supported'));
        foreach($intervals as $id => $name) {
            $data['intervals'][] = array('id'=>$id, 'name' => $name);
        }
        // see if we have a scheduler job running to send queued mail
        $job = xarMod::apiFunc('scheduler','user','get',
                             array('module' => 'mail',
                                   'type' => 'scheduler',
                                   'func' => 'sendmail'));
        if (empty($job) || empty($job['interval'])) {
            $data['interval'] = '';
        } else {
            $data['interval'] = $job['interval'];
        }
        // get the waiting queue
        $serialqueue = xarModVars::get('mail','queue');
        if (!empty($serialqueue)) {
            $queue = unserialize($serialqueue);
        } else {
            $queue = array();
        }
        $data['unsent'] = count($queue);
    }

    $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'mail'));
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls');
    $data['module_settings']->getItem();
    switch (strtolower($phase)) {
        case 'modify':
        default:
        break;

        case 'update':
            // Confirm authorisation code
            if (!xarSec::confirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            switch ($data['tab']) {
                case 'general':
                    // new modvar in 2.0.0, only store the id of the designated admin
                    if (!xarVar::fetch('admin_outgoing','id',$admin_outgoing)) return;
                    if (!xarVar::fetch('showtemplates', 'checkbox', $showtemplates, false, xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('replyto',       'checkbox', $replyto,       false, xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('replytoname',   'str:1:',   $replytoname,   '', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('replytoemail',  'str:1:',   $replytoemail,  '', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('debugmode',     'checkbox', $debugmode,     false, xarVar::NOT_REQUIRED)) return;
                    
                    $isvalid = $data['module_settings']->checkInput();
                    if (!$isvalid) {
                        return xarTpl::module('mail','admin','modifyconfig', $data);        
                    } else {
                        $itemid = $data['module_settings']->updateItem();
                    }

                    if(isset($admin_outgoing)) xarModVars::set('mail','admin_outgoing',$admin_outgoing);
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
                        if (!xarVar::fetch('use_external_lib', 'checkbox', $use_external_lib, false, xarVar::NOT_REQUIRED)) return;
                        xarModVars::set('mail', 'use_external_lib', $use_external_lib);
                    }
                    
                    break;
                case 'incoming':
                    break;
                case 'outgoing':
                    if (!xarVar::fetch('html', 'checkbox', $html, false, xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('htmluseheadfoot', 'checkbox', $htmluseheadfoot, false, xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('htmlheader', 'str:1:', $htmlheader, '', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('htmlfooter', 'str:1:', $htmlfooter, '', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('textuseheadfoot', 'checkbox', $textuseheadfoot, false, xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('textheader', 'str:1:', $textheader, '', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('textfooter', 'str:1:', $textfooter, '', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('wordwrap', 'int:1:', $wordwrap, '50')) return;
                    if (!xarVar::fetch('priority', 'str:1:', $priority, 'normal')) return;
                    if (!xarVar::fetch('encoding', 'str:1:', $encoding)) return;
                    if (!xarVar::fetch('embed_images', 'checkbox', $embed_images, false, xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('server', 'str:1:', $server, 'mail')) return;
                    if (!xarVar::fetch('smtpHost', 'str:1:', $smtpHost, '', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('smtpPort', 'int:1:', $smtpPort, '25', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('smtpSecure', 'str:1:', $smtpSecure, '', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('smtpAuth', 'checkbox', $smtpAuth, false, xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('htmlheader', 'str:1:', $htmlheader, '', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('smtpUserName', 'str:1:', $smtpUserName, '', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('smtpPassword', 'str:1:', $smtpPassword, '', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('sendmailpath', 'str:1:', $sendmailpath, '/usr/sbin/sendmail', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('searchstrings', 'str:1', $searchstrings, '', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('replacestrings', 'str:1', $replacestrings, '', xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('suppresssending', 'checkbox', $suppresssending, false, xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('redirectsending', 'checkbox', $redirectsending, false, xarVar::NOT_REQUIRED)) return;
                    if (!xarVar::fetch('redirectaddress', 'str:1:', $redirectaddress, '', xarVar::NOT_REQUIRED)) return;

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
                    if (!empty($smtpPassword)) xarModVars::set('mail', 'smtpPassword', $smtpPassword);

                    xarModVars::set('mail', 'sendmailpath', $sendmailpath);
                    xarModVars::set('mail', 'searchstrings', serialize($searchstrings));
                    xarModVars::set('mail', 'replacestrings', serialize($replacestrings));
                    xarModVars::set('mail', 'suppresssending', $suppresssending);
                    xarModVars::set('mail', 'redirectsending', $redirectsending);
                    xarModVars::set('mail', 'redirectaddress', $redirectaddress);

                    if (xarMod::isAvailable('scheduler')) {
                        if (!xarVar::fetch('interval', 'str:1', $interval, '', xarVar::NOT_REQUIRED)) return;
                        // see if we have a scheduler job running to send queued mail
                        $job = xarMod::apiFunc('scheduler','user','get',
                                             array('module' => 'mail',
                                                   'type' => 'scheduler',
                                                   'func' => 'sendmail'));
                        if (empty($job) || empty($job['interval'])) {
                            if (!empty($interval)) {
                                // create a scheduler job
                                xarMod::apiFunc('scheduler','admin','create',
                                              array('module' => 'mail',
                                                    'type' => 'scheduler',
                                                    'func' => 'sendmail',
                                                    'interval' => $interval));
                            }
                        } elseif (empty($interval)) {
                            // delete the scheduler job
                            xarMod::apiFunc('scheduler','admin','delete',
                                          array('module' => 'mail',
                                                'type' => 'scheduler',
                                                'func' => 'sendmail'));
                        } elseif ($interval != $job['interval']) {
                            // update the scheduler job
                            xarMod::apiFunc('scheduler','admin','update',
                                          array('module' => 'mail',
                                                'type' => 'scheduler',
                                                'func' => 'sendmail',
                                                'interval' => $interval));
                        }
                    }
            }
            break;
        break;
    }
    return $data;
}
?>
