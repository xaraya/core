<?php

/**
 * @package modules\privileges
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Privileges\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Privileges\AdminGui;
use xarController;
use xarMod;
use xarModVars;
use xarRoles;
use xarSec;
use xarSecurity;
use xarSession;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin modifyconfig function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the configuration settings of this module
     * Standard GUI function to display and update the configuration settings of the module based on input data.
     * @return mixed data array for the template display or output display string if invalid data submitted
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminPrivileges')) {
            return;
        }

        $data = [];
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $this->var()->find('tab', $data['tab'], 'str:1:100', 'general');
        $this->var()->find('testergroup', $testergroup, 'int', $this->mod()->getVar('testergroup'));
        $this->var()->find('tester', $tester, 'int', $this->mod()->getVar('tester'));

        switch ($data['tab']) {
            case 'lastresort':
                //Check for existence of a last resort admin for feedback to user
                $lastresort  = $this->mod()->getVar('lastresort');
                if (($lastresort) && strlen(trim($lastresort)) > 1) {
                    //could just be true, we want to know if the name is set
                    $islastresort = unserialize($lastresort);
                    if (isset($islastresort['name'])) {
                        $data['lastresortname'] = $islastresort['name'];
                    } else {
                        $data['lastresortname'] = '';
                    }
                }
                break;
            case 'realms':
                $data['showrealms'] = $this->mod()->getVar('showrealms');
                $realmvalue = $this->mod()->getVar('realmvalue');
                if (strpos($realmvalue, 'string:') === 0) {
                    $textvalue = substr($realmvalue, 7);
                    $realmvalue = 'string';
                } else {
                    $textvalue = '';
                }
                $data['realmvalue'] = $realmvalue;
                $data['textvalue'] = $textvalue;
                break;

            case 'testing':
                $settestergroup = $this->mod()->getVar('testergroup');
                if (!isset($settestergroupp) || empty($settestergroup)) {
                    $settestergrouprole = xarRoles::findRole('Administrators');
                    $settestergroup = $settestergrouprole->getID();
                }
                if (!isset($testergroup) || empty($testergroup)) {
                    $testergroup = $settestergroup;
                }
                $data['testergroup'] = $testergroup;

                $grouplist = xarRoles::getgroups();
                $data['grouplist'] = $grouplist;

                $testusers = $this->mod()->apiFunc('roles', 'user', 'getusers', ['id' => $testergroup]);
                $defaultadminid = (int) $this->mod('roles')->getVar('admin');

                $data['testusers'] = $testusers; //array

                $settester = $this->mod()->getVar('tester'); //id
                if (!isset($settester) || empty($settester)) {
                    $settester = $defaultadminid; //bug 5832 set it to the default admin, cannot assume it is Administrator
                }
                if (!isset($tester) || empty($tester)) {
                    $tester = $settester;
                }
                $data['tester'] = $tester;
                break;

            default:
                $data['module_settings'] = $this->mod()->apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'privileges']);
                $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls');
                $data['module_settings']->getItem();
                break;

        }

        switch (strtolower($phase)) {
            case 'modify':
            default:
                if (!isset($phase)) {
                    $this->session()->setVar('statusmsg', '');
                }
                $data['inheritdeny'] = $this->mod()->getVar('inheritdeny');
                break;

            case 'update':
                // Confirm authorisation code
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }
                switch ($data['tab']) {
                    case 'general':
                        $this->var()->find('inheritdeny', $inheritdeny, 'checkbox', false);
                        $this->var()->find('lastresort', $lastresort, 'checkbox', false);
                        $this->var()->find('exceptionredirect', $data['exceptionredirect'], 'checkbox', false);

                        $isvalid = $data['module_settings']->checkInput();
                        if (!$isvalid) {
                            $data['context'] ??= $this->getContext();
                            return $this->tpl()->module('privileges', 'admin', 'modifyconfig', $data);
                        } else {
                            $itemid = $data['module_settings']->updateItem();
                        }

                        $this->mod()->setVar('inheritdeny', $inheritdeny);
                        $this->mod()->setVar('lastresort', $lastresort);
                        if (!$lastresort) {
                            $this->mod()->delVar('lastresort');
                        }
                        $this->mod()->setVar('exceptionredirect', $data['exceptionredirect']);

                        break;
                    case 'realms':
                        $this->var()->find('enablerealms', $data['enablerealms'], 'checkbox', false);
                        $this->mod()->setVar('showrealms', $data['enablerealms']);
                        $this->var()->find('realmvalue', $realmvalue, 'str', 'none');
                        $this->var()->find('realmcomparison', $realmcomparison, 'str', 'exact');
                        $this->var()->find('textvalue', $textvalue, 'str', '');
                        if ($realmvalue == 'string') {
                            $realmvalue = empty($textvalue) ? 'none' : 'string:' . $textvalue;
                        }
                        $this->mod()->setVar('realmvalue', $realmvalue);
                        $this->mod()->setVar('realmcomparison', $realmcomparison);
                        break;
                    case 'lastresort':
                        $this->var()->find('name', $name, 'str', '');
                        $this->var()->find('password', $password, 'str', '');
                        $this->var()->find('password2', $password2, 'str', '');

                        // rudimentary check for valid password for now - fix so nicer presentation to user
                        if (strcmp($password, $password2) != 0) {
                            $msg = $this->ml('Last Resort Admin Creation failed! <br />The two password entries are not the same, please try again.');
                            $this->session()->setVar('statusmsg', $msg);
                            $this->ctl()->redirect($this->ctl()->getModuleURL(
                                'privileges',
                                'admin',
                                'modifyconfig',
                                ['tab' => $data['tab']]
                            ));
                        }
                        $secret = [
                            'name' => MD5($name),
                            'password' => MD5($password),
                        ];
                        $this->session()->setVar('statusmsg', $this->ml('Last Resort Administrator successfully created!'));
                        $this->mod()->setVar('lastresort', serialize($secret));
                        break;
                    case 'testing':
                        $this->var()->find('tester', $data['tester'], 'int', $this->mod()->getVar('tester'));
                        $this->mod()->setVar('tester', $data['tester']);
                        $this->var()->find('test', $test, 'checkbox', false);
                        $this->mod()->setVar('test', $test);
                        $this->var()->find('testdeny', $testdeny, 'checkbox', false);
                        $this->mod()->setVar('testdeny', $testdeny);
                        $this->var()->find('testmask', $testmask, 'str', 'All');
                        $this->mod()->setVar('testmask', $testmask);
                        $this->mod()->setVar('testergroup', $testergroup);
                        break;
                }
                break;
        }
        return $data;
    }
}
