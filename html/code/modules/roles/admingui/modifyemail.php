<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminGui;
use Xaraya\Modules\Roles\AdminApi;
use ConfigurationException;
use DataObjectFactory;
use DirectoryNotFoundException;
use FileNotFoundException;
use xarController;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin modifyemail function
 * @extends MethodClass<AdminGui>
 */
class ModifyemailMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the  email for users
     * @return array|bool|void data for the template display
     * @see AdminGui::modifyemail()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('EditRoles')) {
            return;
        }

        extract($args);
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        if (!isset($mailtype)) {
            $this->var()->find('mailtype', $data['mailtype'], 'str:1:100', 'welcome');
        } else {
            $data['mailtype'] = $mailtype;
        }

        // Get the list of available templates
        $messaginghome = sys::varpath() . "/messaging/roles";
        if (!file_exists($messaginghome)) {
            throw new DirectoryNotFoundException($messaginghome);
        }

        $dd = opendir($messaginghome);
        // FIXME: what's the blank template supposed to do ?
        //$templates = array(array('key' => 'blank', 'value' => $this->ml('Empty')));
        $templates = [];
        while (($filename = readdir($dd)) !== false) {
            if (!is_dir($messaginghome . "/" . $filename)) {
                $pos = strpos($filename, '-message.xt');
                if (!($pos === false)) {
                    $templatename = substr($filename, 0, $pos);
                    $templatelabel = ucfirst($templatename);
                    $templates[] = ['key' => $templatename, 'value' => $templatelabel];
                }
            }
        }
        closedir($dd);
        $data['templates'] = $templates;

        switch (strtolower($phase)) {
            case 'modify':
            default:
                $strings = $adminapi->getmessagestrings(['template' => $data['mailtype']]);
                $data['subject'] = $strings['subject'];
                $data['message'] = $strings['message'];
                $data['authid'] = $this->sec()->genAuthKey();

                $object = $this->data()->getObject(['name' => 'roles_users']);
                if (isset($object) && !empty($object->objectid)) {
                    // get the Dynamic Properties of this object
                    $data['properties'] = &$object->getProperties();
                }
                break;

            case 'update':

                $this->var()->find('message', $message, 'str:1:');
                $this->var()->find('subject', $subject, 'str:1:');
                // Confirm authorisation code
                //            if (!$this->sec()->confirmAuthKey()) return;
                //            xarModVars::set('roles', $data['mailtype'].'email', $message);
                //            xarModVars::set('roles', $data['mailtype'].'title', $subject);

                $messaginghome = sys::varpath() . "/messaging/roles";
                $filebase = $messaginghome . "/" . $data['mailtype'] . "-";

                $filename = $filebase . 'subject.xt';
                if (is_writable($filename) && is_writable($messaginghome)) {
                    unlink($filename);
                    if (!$handle = fopen($filename, 'a')) {
                        throw new FileNotFoundException($filename, 'Could not open the file "#(1)" for appending');
                    }
                    if (fwrite($handle, $subject) === false) {
                        throw new FileNotFoundException($filename, 'Could not write to the file "#(1)" for writing');
                    }
                    fclose($handle);
                } else {
                    $msg = 'The messaging template "#(1)" is not writable or it is not allowed to delete files from #(2)';
                    throw new ConfigurationException([$filename,$messaginghome], $msg);
                }
                $filename = $filebase . 'message.xt';
                if (is_writable($filename) && is_writable($messaginghome)) {
                    unlink($filename);
                    if (!$handle = fopen($filename, 'a')) {
                        throw new FileNotFoundException($filename, 'Could not open the file "#(1)" for appending');
                    }
                    if (fwrite($handle, $message) === false) {
                        throw new FileNotFoundException($filename, 'Could not write to the file "#(1)" for writing');
                    }
                    fclose($handle);
                } else {
                    $msg = 'The messaging template "#(1)" is not writable or it is not allowed to delete files from #(2)';
                    throw new ConfigurationException([$filename,$messaginghome], $msg);
                }
                $this->ctl()->redirect($this->ctl()->getModuleURL(
                    'roles',
                    'admin',
                    'modifyemail',
                    ['mailtype' => $data['mailtype']]
                ));
                return true;
        }
        return $data;
    }
}
