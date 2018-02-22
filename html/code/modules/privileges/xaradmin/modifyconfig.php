<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * Modify the configuration settings of this module
 *
 * Standard GUI function to display and update the configuration settings of the module based on input data.
 *
 * @return mixed data array for the template display or output display string if invalid data submitted
 */
function privileges_admin_modifyconfig()
{
    // Security
    if (!xarSecurityCheck('AdminPrivileges')) return;
    
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('testergroup', 'int', $testergroup, xarModVars::get('privileges', 'testergroup'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('tester', 'int', $tester, xarModVars::get('privileges', 'tester'), XARVAR_NOT_REQUIRED)) return;

    switch ($data['tab']) {
        case 'lastresort':
            //Check for existence of a last resort admin for feedback to user
            $lastresort  = xarModVars::get('privileges', 'lastresort');
            if (($lastresort) && strlen(trim($lastresort))>1) {
              //could just be true, we want to know if the name is set
              $islastresort=unserialize($lastresort);
              if (isset($islastresort['name'])){
                 $data['lastresortname']=$islastresort['name'];
              } else{
                  $data['lastresortname']='';
              }
            }
            break;
        case 'realms':
            $data['showrealms'] = xarModVars::get('privileges', 'showrealms');
            $realmvalue = xarModVars::get('privileges', 'realmvalue');
            if (strpos($realmvalue,'string:') === 0) {
               $textvalue = substr($realmvalue,7);
               $realmvalue = 'string';
            } else {
                $textvalue = '';
            }
            $data['realmvalue'] = $realmvalue;
            $data['textvalue'] = $textvalue;
        break;

        case 'testing':
             $settestergroup=xarModVars::get('privileges','testergroup');
             if (!isset($settestergroupp) || empty($settestergroup)) {
                 $settestergrouprole = xarFindRole('Administrators');
                 $settestergroup = $settestergrouprole->getID();
             }
             if (!isset($testergroup) || empty($testergroup)) {
                 $testergroup = $settestergroup;
             }
             $data['testergroup'] = $testergroup;

             $grouplist=xarGetGroups();
             $data['grouplist']=$grouplist;

             $testusers=xarMod::apiFunc('roles','user','getUsers',array('id'=>$testergroup));
             $defaultadminid = (int)xarModVars::get('roles','admin');

             $data['testusers']=$testusers; //array

             $settester=xarModVars::get('privileges','tester'); //id
             if (!isset($settester) || empty($settester)) {
                 $settester=$defaultadminid; //bug 5832 set it to the default admin, cannot assume it is Administrator
             }
             if (!isset($tester) || empty($tester)) {
                 $tester=$settester;
             }
             $data['tester']=$tester;
        break;
        
        default:
            $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'privileges'));
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
            if (!xarSecConfirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            switch ($data['tab']) {
                case 'general':
                    if (!xarVarFetch('inheritdeny', 'checkbox', $inheritdeny, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('lastresort', 'checkbox', $lastresort, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('exceptionredirect', 'checkbox', $data['exceptionredirect'], false, XARVAR_NOT_REQUIRED)) return;

                    $isvalid = $data['module_settings']->checkInput();
                    if (!$isvalid) {
                        return xarTpl::module('privileges','admin','modifyconfig', $data);        
                    } else {
                        $itemid = $data['module_settings']->updateItem();
                    }

                    xarModVars::set('privileges', 'inheritdeny', $inheritdeny);
                    xarModVars::set('privileges', 'lastresort', $lastresort);
                    if (!$lastresort) {
                        xarModVars::delete('privileges', 'lastresort',$lastresort);
                    }
                    xarModVars::set('privileges', 'exceptionredirect', $data['exceptionredirect']);

                    break;
                case 'realms':
                    if (!xarVarFetch('enablerealms', 'checkbox', $data['enablerealms'], false, XARVAR_NOT_REQUIRED)) return;
                    xarModVars::set('privileges', 'showrealms', $data['enablerealms']);
                    if (!xarVarFetch('realmvalue', 'str', $realmvalue, 'none', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('realmcomparison', 'str', $realmcomparison, 'exact', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('textvalue', 'str', $textvalue, '', XARVAR_NOT_REQUIRED)) return;
                    if ($realmvalue == 'string') {
                        $realmvalue = empty($textvalue) ? 'none' : 'string:' . $textvalue;
                    }
                    xarModVars::set('privileges', 'realmvalue', $realmvalue);
                    xarModVars::set('privileges', 'realmcomparison', $realmcomparison);
                    break;
                case 'lastresort':
                    if (!xarVarFetch('name', 'str', $name, '', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('password', 'str', $password, '', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('password2', 'str', $password2, '', XARVAR_NOT_REQUIRED)) return;

                    // rudimentary check for valid password for now - fix so nicer presentation to user
                    if (strcmp($password, $password2) != 0) {
                        $msg = xarML('Last Resort Admin Creation failed! <br />The two password entries are not the same, please try again.');
                        xarSession::setVar('statusmsg', $msg);
                       xarController::redirect(xarModURL('privileges', 'admin', 'modifyconfig',array('tab' => $data['tab'])));
                    }
                    $secret = array(
                                'name' => MD5($name),
                                'password' => MD5($password)
                                );
                    xarSession::setVar('statusmsg', xarML('Last Resort Administrator successfully created!'));
                    xarModVars::set('privileges','lastresort',serialize($secret));
                    break;
                case 'testing':
                    if (!xarVarFetch('tester', 'int', $data['tester'], xarModVars::get('privileges', 'tester'), XARVAR_NOT_REQUIRED)) return;
                    xarModVars::set('privileges', 'tester', $data['tester']);
                    if (!xarVarFetch('test', 'checkbox', $test, false, XARVAR_NOT_REQUIRED)) return;
                    xarModVars::set('privileges', 'test', $test);
                    if (!xarVarFetch('testdeny', 'checkbox', $testdeny, false, XARVAR_NOT_REQUIRED)) return;
                    xarModVars::set('privileges', 'testdeny', $testdeny);
                    if (!xarVarFetch('testmask', 'str', $testmask, 'All', XARVAR_NOT_REQUIRED)) return;
                    xarModVars::set('privileges', 'testmask', $testmask);
                    xarModVars::set('privileges', 'testergroup', $testergroup);
                    break;
            }
            break;
    }
    return $data;
}
?>
