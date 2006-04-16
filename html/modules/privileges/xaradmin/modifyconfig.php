<?php
/**
 * Modify configuration of this module
 *
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
    if (!xarVarFetch('testergroup', 'int', $testergroup, xarModGetVar('privileges', 'testergroup'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('tester', 'int', $tester, xarModGetVar('privileges', 'tester'), XARVAR_NOT_REQUIRED)) return;

    switch (strtolower($phase)) {
        case 'modify':
        default:
            if (!isset($phase)) {
                xarSessionSetVar('statusmsg', '');
            }
            $data['inheritdeny'] = xarModGetVar('privileges', 'inheritdeny');
            $data['authid'] = xarSecGenAuthKey();
            switch ($data['tab']) {
                case 'lastresort':
                    //Check for existence of a last resort admin for feedback to user
                    $lastresort  = xarModGetVar('privileges', 'lastresort');
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
                    $data['showrealms'] = xarModGetVar('privileges', 'showrealms');
                    $realmvalue = xarModGetVar('privileges', 'realmvalue');
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
                     $testmask=trim(xarModGetVar('privileges', 'testmask'));
                     if (!isset($testmask) || empty($testmask)) {
                         $testmask='All';
                     }
                     $data['testmask']=$testmask;
                     $settestergroup=xarModGetVar('privileges','testergroup');
                     if (!isset($settestergroupp) || empty($settestergroup)) {
                         $settestergroup='Administrators';
                     }
                     if (!isset($testergroup) || empty($testergroup)) {
                         $testergroup=$settestergroup;
                     }
                     $data['testergroup']=$testergroup;

                     $grouplist=xarGetGroups();
                     $data['grouplist']=$grouplist;

                     $testgrouprole=xarFindRole('Administrators');
                     $testgroupuid=$testgrouprole->uid;

                     $testusers=xarModAPIFunc('roles','user','getUsers',array('uid'=>$testergroup));
                     $data['testusers']=$testusers;

                     $tester=xarModGetVar('privileges','tester');
                     if (!isset($tester) || empty($tester)) {
                         $tester='Administrator';
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
                    xarModSetVar('privileges', 'inheritdeny', $inheritdeny);
                    if (!xarVarFetch('lastresort', 'checkbox', $lastresort, false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'lastresort', $lastresort);
                    if (!$lastresort) {
                        xarModDelVar('privileges', 'lastresort',$lastresort);
                    }
                    if (!xarVarFetch('exceptionredirect', 'checkbox', $data['exceptionredirect'], false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'exceptionredirect', $data['exceptionredirect']);
                    break;
                case 'realms':
                    if (!xarVarFetch('enablerealms', 'bool', $data['enablerealms'], false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'showrealms', $data['enablerealms']);
                    if (!xarVarFetch('realmvalue', 'str', $realmvalue, 'none', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('realmcomparison', 'str', $realmcomparison, 'exact', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('textvalue', 'str', $textvalue, '', XARVAR_NOT_REQUIRED)) return;
                    if ($realmvalue == 'string') {
                        $realmvalue = empty($textvalue) ? 'none' : 'string:' . $textvalue;
                    }
                    xarModSetVar('privileges', 'realmvalue', $realmvalue);
                    xarModSetVar('privileges', 'realmcomparison', $realmcomparison);
                    break;
                case 'lastresort':
                    if (!xarVarFetch('name', 'str', $name, '', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('password', 'str', $password, '', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('password2', 'str', $password2, '', XARVAR_NOT_REQUIRED)) return;
                    
                    // rudimentary check for valid password for now - fix so nicer presentation to user
                    if (strcmp($password, $password2) != 0) {
                        $msg = xarML('Last Resort Admin Creation failed! <br />The two password entries are not the same, please try again.');
                        xarSessionSetVar('statusmsg', $msg);
                       xarResponseRedirect(xarModURL('privileges', 'admin', 'modifyconfig',array('tab' => $data['tab'])));
                    }
                    $secret = array(
                                'name' => MD5($name),
                                'password' => MD5($password)
                                );
                    xarSessionSetVar('statusmsg', xarML('Last Resort Administrator successfully created!'));
                    xarModSetVar('privileges','lastresort',serialize($secret));
                    break;
                case 'testing':
                    if (!xarVarFetch('tester', 'int', $data['tester'], xarModGetVar('privileges', 'tester'), XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'tester', $data['tester']);
                    if (!xarVarFetch('test', 'checkbox', $test, false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'test', $test);
                    if (!xarVarFetch('testdeny', 'checkbox', $testdeny, false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'testdeny', $testdeny);
                    if (!xarVarFetch('testmask', 'str', $testmask, 'All', XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'testmask', $testmask);
                    xarModSetVar('privileges', 'testergroup', $testergroup);
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