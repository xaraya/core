<?php
/**
 * Modify configuration of this module
 * @package core modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * modify configuration
 */
function privileges_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminPrivilege')) return;
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('testergroup', 'int', $testergroup, xarModVars::get('privileges', 'testergroup'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('tester', 'int', $tester, xarModVars::get('privileges', 'tester'), XARVAR_NOT_REQUIRED)) return;

    switch (strtolower($phase)) {
        case 'modify':
        default:
            if (!isset($phase)) {
                xarSession::setVar('statusmsg', '');
            }
            $data['inheritdeny'] = xarModVars::get('privileges', 'inheritdeny');
            $data['authid'] = xarSecGenAuthKey();
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
                     $testmask=trim(xarModVars::get('privileges', 'testmask'));
                     if (!isset($testmask) || empty($testmask)) {
                         $testmask='All';
                     }
                     $data['testmask'] = $testmask;
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

                     $testusers=xarModAPIFunc('roles','user','getUsers',array('id'=>$testergroup));
                     $defaultadminid = xarModVars::get('roles','admin');

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
            }
            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            switch ($data['tab']) {
                case 'general':
                    if (!xarVarFetch('inheritdeny', 'checkbox', $inheritdeny, false, XARVAR_NOT_REQUIRED)) return;
                    xarModVars::set('privileges', 'inheritdeny', $inheritdeny);
                    if (!xarVarFetch('lastresort', 'checkbox', $lastresort, false, XARVAR_NOT_REQUIRED)) return;
                    xarModVars::set('privileges', 'lastresort', $lastresort);
                    if (!$lastresort) {
                        xarModVars::delete('privileges', 'lastresort',$lastresort);
                    }
                    if (!xarVarFetch('exceptionredirect', 'checkbox', $data['exceptionredirect'], false, XARVAR_NOT_REQUIRED)) return;
                    xarModVars::set('privileges', 'exceptionredirect', $data['exceptionredirect']);
                    break;
                case 'realms':
                    if (!xarVarFetch('enablerealms', 'bool', $data['enablerealms'], false, XARVAR_NOT_REQUIRED)) return;
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
                       xarResponseRedirect(xarModURL('privileges', 'admin', 'modifyconfig',array('tab' => $data['tab'])));
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

            xarResponseRedirect(xarModURL('privileges', 'admin', 'modifyconfig',array('tab' => $data['tab'])));
            // Return
            return true;
            break;
    }
    return $data;
}
?>
