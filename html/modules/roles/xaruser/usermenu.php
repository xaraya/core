<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Show the user menu
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_user_usermenu($args)
{
    if (!xarSecurityCheck('ViewRoles')) return;
    extract($args);
    if(!xarVarFetch('phase','notempty', $phase, 'menu', XARVAR_NOT_REQUIRED)) {return;}
    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Your Account Preferences')));
    $data = array();
    $hooks = array();
    switch(strtolower($phase)) {
        case 'menu':
            $iconbasic = xarTplGetImage('home.gif', 'roles');
            $current = xarModURL('roles', 'user', 'account', array('moduleload' => 'roles'));
            $data = xarTplModule('roles','user', 'user_menu_icon', array('iconbasic'    => $iconbasic,
                                                                         'current'      => $current));
            break;
        case 'form':
        case 'formbasic':
            $uid = xarUserGetVar('uid');
            $object = xarModAPIFunc('dynamicdata','user','getobject',
                              array('name' => 'roles_users'));
            $object->getItem(array('itemid' => $uid));

            $uname = xarUserGetVar('uname');
            $name = xarUserGetVar('name');

            $email = xarUserGetVar('email');
            $role = xarUFindRole($uname);
            $home = xarModGetUserVar('roles','userhome');// now user mod var not 'duv'. $role->getHome();
            $allowemail = xarModGetUserVar('roles','usersendemails',$uid); //allow someone to send an email to the user via a form
            if (xarModGetVar('roles','setuserlastlogin')) {
            //only display it for current user or admin
                if (xarUserIsLoggedIn() && xarUserGetVar('uid')==$uid) { //they should be but ..
                    $userlastlogin=xarSessionGetVar('roles_thislastlogin');
                    $usercurrentlogin=xarModGetUserVar('roles','userlastlogin',$uid);
                }elseif (xarSecurityCheck('AdminRole',0,'Roles',$name) && xarModGetUserVar('roles','userlastlogin',$uid)){
                    $usercurrentlogin='';
                    $userlastlogin= xarModGetUserVar('roles','userlastlogin',$uid);
                }else{
                    $userlastlogin='';
                    $usercurrentlogin='';
                }
            }else{
                $userlastlogin='';
                $usercurrentlogin='';
            }
            $authid = xarSecGenAuthKey();

            $upasswordupdate = xarModGetUserVar('roles','passwordupdate');//now user mod var not 'duv'. $role->getPasswordUpdate();
            $usertimezonedata = xarModGetUserVar('roles','usertimezone');
            $utimezone=$usertimezonedata['timezone'];

            $item['module'] = 'roles';
            $item['itemtype'] = ROLES_USERTYPE;

            $hooks = xarModCallHooks('item','modify',$uid,$item);
            if (isset($hooks['dynamicdata'])) {
                unset($hooks['dynamicdata']);
            }

            $data = xarTplModule('roles','user', 'user_menu_form',
                                  array('authid'       => $authid,
                                  'object'       => $object,
                                  'uname'        => $uname,
                                  'home'         => $home,
                                  'hooks'        => $hooks,
                                  'uid'          => $uid,
                                  'upasswordupdate' => $upasswordupdate,
                                  'usercurrentlogin'=> $usercurrentlogin,
                                  'userlastlogin'   => $userlastlogin,
                                  'utimezone'    => $utimezone,
                                  'allowemail'   => $allowemail));
            break;
        case 'updatebasic':
            if (!xarSecConfirmAuthKey()) return;

            if(!xarVarFetch('allowemail', 'checkbox', $allowemail,   false, XARVAR_DONT_SET)) return;
            if(!xarVarFetch('utimezone','str:1:',$utimezone, NULL,XARVAR_NOT_REQUIRED)) return;

            $uid = xarUserGetVar('uid');
            $uname = xarUserGetVar('uname');

            $object = xarModAPIFunc('dynamicdata','user','getobject',
                              array('name' => 'roles_users'));
            $object->getItem(array('itemid' => $uid));

            $oldpass = $object->properties['password']->getValue();
            $oldemail = $object->properties['email']->getValue();

            $isvalid = $object->checkInput();

            // @todo add preview?
            if (!$isvalid) {
                $data = array();
                $data['uname'] = $uname;
                $data['authid'] = xarSecGenAuthKey();
                $data['object'] = & $object;

                //$data['preview'] = $preview;
                $item = array();
                $item['module'] = 'roles';
                $item['itemtype'] = ROLES_USERTYPE;
                $data['hooks'] = xarModCallHooks('item','modify','',$item);

                $data['moduleload'] = 'roles';
                return xarTplModule('roles','user','account', $data);
            }
            //set emailing options for the user
            xarModSetUserVar('roles','usersendemails',$allowemail,$uid);


            //adjust the timezone value for saving
            if (xarModGetVar('roles','setusertimezone') && (isset($utimezone))) {
               $timeinfo = xarModAPIFunc('base','user','timezones', array('timezone' => $utimezone));
               list($hours,$minutes) = explode(':',$timeinfo[0]);
               $offset = (float) $hours + (float) $minutes / 60;
               $timeinfoarray= array('timezone' => $utimezone, 'offset' => $offset);
                $usertimezone=serialize($timeinfoarray);
                xarModSetUserVar('roles','usertimezone',$usertimezone);
            }
             /* Check if external urls are allowed in home page */
            $allowexternalurl=xarModGetVar('roles','allowexternalurl');
            $url_parts = parse_url($home);
            if (!$allowexternalurl) {
                if ((preg_match("%^http://%", $home, $matches)) &&
                ($url_parts['host'] != $_SERVER["SERVER_NAME"]) &&
                ($url_parts['host'] != $_SERVER["HTTP_HOST"])) {

                  $msg  = xarML('External URLs such as #(1) are not permitted in your User Account.', $home);
                  $var  = array($home);
                  $home = '';
                  throw new BadParameterException(array($home), $msg);
                }
            }
            $pass = $object->properties['password']->getValue();

            $newpass = md5($pass);
            $passchanged = false;
            if ($oldpass != $newpass) {
                $passchanged = true;
                $object->properties['password']->value = $newpass;
            }



            $object->updateItem();

            if ($passchanged){
                // @todo CHECKME: Send an email?
            }

            $email = $object->properties['email']->getValue();
            if ($oldemail != $email){
                /* updated steps for changing email address
                   1) Check if validation is required and if so create confirmation code
                   2) Change user status to 2 (if validation is set as option)
                   3) If validation is required for a change, send the user an email about validation
                   4) if user is logged in (ie existing user), log user out
                   5) Display appropriate message
                */

                if(xarModGetVar('roles','uniqueemail')) {
                    // check for duplicate email address
                    $user = xarModAPIFunc('roles', 'user','get',
                                       array('email' => $email));
                    if ($user != false) {
                        unset($user);
                        throw new DuplicateException(array('email address',$email));
                    }
                }

                // check for disallowed email addresses
                $disallowedemails = xarModGetVar('roles','disallowedemails');
                if (!empty($disallowedemails)) {
                    $disallowedemails = unserialize($disallowedemails);
                    $disallowedemails = explode("\r\n", $disallowedemails);
                    if (in_array ($email, $disallowedemails)) {
                        $msg = 'That email address is either reserved or not allowed on this website';
                        throw new ForbiddenOperationException(null,$msg);
                    }
                }

                // Step 2 Check for validation required or not
                $requireValidation = xarModGetVar('roles', 'requirevalidation');
                if (xarModGetVar('roles', 'requirevalidation') || (xarUserGetVar('uname') != 'admin')) {
                    // Step 2
                    // Create confirmation code and time registered
                    $confcode = xarModAPIFunc('roles','user','makepass');

                    // Step 3
                    // Set the user to not validated
                     $object->properties['valcode']->setValue($confcode);
                    // Step 4
                    //Send validation email
                    if (!xarModAPIFunc( 'roles',  'admin', 'senduseremail',
                                  array('uid' => array($uid => '1'), 'mailtype' => 'validation'))) {

                        $msg = xarML('Problem sending confirmation email');
                        throw new Exception($msg);
                    }
                    $object->updateItem();
                    // Step 5
                    // Log the user out. This needs to happen last
                    xarUserLogOut();

                    //Step 6
                    //Show a nice message for the person about email validation
                    $data = xarTplModule('roles','user', 'waitingconfirm');
                    return $data;
                }
            }

            xarResponseRedirect(xarModURL('roles', 'user', 'account'));
    }
    return $data;
}
?>
