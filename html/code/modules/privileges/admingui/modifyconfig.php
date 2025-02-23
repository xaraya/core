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
        if (!xarSecurity::check('AdminPrivileges')) {
            return;
        }

        $data = [];
        xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
        xarVar::fetch('tab', 'str:1:100', $data['tab'], 'general', xarVar::NOT_REQUIRED);
        xarVar::fetch('testergroup', 'int', $testergroup, xarModVars::get('privileges', 'testergroup'), xarVar::NOT_REQUIRED);
        xarVar::fetch('tester', 'int', $tester, xarModVars::get('privileges', 'tester'), xarVar::NOT_REQUIRED);

        switch ($data['tab']) {
            case 'lastresort':
                //Check for existence of a last resort admin for feedback to user
                $lastresort  = xarModVars::get('privileges', 'lastresort');
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
                $data['showrealms'] = xarModVars::get('privileges', 'showrealms');
                $realmvalue = xarModVars::get('privileges', 'realmvalue');
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
                $settestergroup = xarModVars::get('privileges', 'testergroup');
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

                $testusers = xarMod::apiFunc('roles', 'user', 'getUsers', ['id' => $testergroup]);
                $defaultadminid = (int) xarModVars::get('roles', 'admin');

                $data['testusers'] = $testusers; //array

                $settester = xarModVars::get('privileges', 'tester'); //id
                if (!isset($settester) || empty($settester)) {
                    $settester = $defaultadminid; //bug 5832 set it to the default admin, cannot assume it is Administrator
                }
                if (!isset($tester) || empty($tester)) {
                    $tester = $settester;
                }
                $data['tester'] = $tester;
                break;

            default:
                $data['module_settings'] = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'privileges']);
                $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls');
                $data['module_settings']->getItem();
                break;

        }

        switch (strtolower($phase)) {
            case 'modify':
            default:
                if (!isset($phase)) {
                    xarSession::setVar('statusmsg', '');
                }
                $data['inheritdeny'] = xarModVars::get('privileges', 'inheritdeny');
                break;

            case 'update':
                // Confirm authorisation code
                if (!xarSec::confirmAuthKey()) {
                    return xarController::badRequest('bad_author', $this->getContext());
                }
                switch ($data['tab']) {
                    case 'general':
                        xarVar::fetch('inheritdeny', 'checkbox', $inheritdeny, false, xarVar::NOT_REQUIRED);
                        xarVar::fetch('lastresort', 'checkbox', $lastresort, false, xarVar::NOT_REQUIRED);
                        xarVar::fetch('exceptionredirect', 'checkbox', $data['exceptionredirect'], false, xarVar::NOT_REQUIRED);

                        $isvalid = $data['module_settings']->checkInput();
                        if (!$isvalid) {
                            $data['context'] ??= $this->getContext();
                            return xarTpl::module('privileges', 'admin', 'modifyconfig', $data);
                        } else {
                            $itemid = $data['module_settings']->updateItem();
                        }

                        xarModVars::set('privileges', 'inheritdeny', $inheritdeny);
                        xarModVars::set('privileges', 'lastresort', $lastresort);
                        if (!$lastresort) {
                            xarModVars::delete('privileges', 'lastresort');
                        }
                        xarModVars::set('privileges', 'exceptionredirect', $data['exceptionredirect']);

                        break;
                    case 'realms':
                        xarVar::fetch('enablerealms', 'checkbox', $data['enablerealms'], false, xarVar::NOT_REQUIRED);
                        xarModVars::set('privileges', 'showrealms', $data['enablerealms']);
                        xarVar::fetch('realmvalue', 'str', $realmvalue, 'none', xarVar::NOT_REQUIRED);
                        xarVar::fetch('realmcomparison', 'str', $realmcomparison, 'exact', xarVar::NOT_REQUIRED);
                        xarVar::fetch('textvalue', 'str', $textvalue, '', xarVar::NOT_REQUIRED);
                        if ($realmvalue == 'string') {
                            $realmvalue = empty($textvalue) ? 'none' : 'string:' . $textvalue;
                        }
                        xarModVars::set('privileges', 'realmvalue', $realmvalue);
                        xarModVars::set('privileges', 'realmcomparison', $realmcomparison);
                        break;
                    case 'lastresort':
                        xarVar::fetch('name', 'str', $name, '', xarVar::NOT_REQUIRED);
                        xarVar::fetch('password', 'str', $password, '', xarVar::NOT_REQUIRED);
                        xarVar::fetch('password2', 'str', $password2, '', xarVar::NOT_REQUIRED);

                        // rudimentary check for valid password for now - fix so nicer presentation to user
                        if (strcmp($password, $password2) != 0) {
                            $msg = xarML('Last Resort Admin Creation failed! <br />The two password entries are not the same, please try again.');
                            xarSession::setVar('statusmsg', $msg);
                            xarController::redirect(xarController::URL(
                                'privileges',
                                'admin',
                                'modifyconfig',
                                ['tab' => $data['tab']]
                            ), null, $this->getContext());
                        }
                        $secret = [
                            'name' => MD5($name),
                            'password' => MD5($password),
                        ];
                        xarSession::setVar('statusmsg', xarML('Last Resort Administrator successfully created!'));
                        xarModVars::set('privileges', 'lastresort', serialize($secret));
                        break;
                    case 'testing':
                        xarVar::fetch('tester', 'int', $data['tester'], xarModVars::get('privileges', 'tester'), xarVar::NOT_REQUIRED);
                        xarModVars::set('privileges', 'tester', $data['tester']);
                        xarVar::fetch('test', 'checkbox', $test, false, xarVar::NOT_REQUIRED);
                        xarModVars::set('privileges', 'test', $test);
                        xarVar::fetch('testdeny', 'checkbox', $testdeny, false, xarVar::NOT_REQUIRED);
                        xarModVars::set('privileges', 'testdeny', $testdeny);
                        xarVar::fetch('testmask', 'str', $testmask, 'All', xarVar::NOT_REQUIRED);
                        xarModVars::set('privileges', 'testmask', $testmask);
                        xarModVars::set('privileges', 'testergroup', $testergroup);
                        break;
                }
                break;
        }
        return $data;
    }
}
