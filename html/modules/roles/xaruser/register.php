<?php

/**
 * add new user
 * Multiple steps to create a new user, as follows:
 *  - get user to agree to terms and conditions (if required)
 *  - get initial information from user
 *  - send confirmation email to user (if required)
 *  - obtain confirmation response from user
 *  - obtain administration permission for account (if required)
 *  - activate account
 *  - send welcome email (if required)
 */
function roles_user_register()
{
    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    //If a user is already logged in, no reason to see this.
    //We are going to send them to their account.
    if (xarUserIsLoggedIn()) {
        xarResponseRedirect(xarModURL('roles',
                                      'user',
                                      'account',
                                       array('uid' => xarUserGetVar('uid'))));
       return true;
    }

    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('New Account')));

    $phase = xarVarCleanFromInput('phase');

    if (empty($phase)){
        $phase = 'choices';
    }

    switch(strtolower($phase)) {

        case 'choices':
        default:

            $data = xarTplModule('roles','user', 'choices');
            break;

        case 'checkage':

            $minage = xarModGetVar('roles', 'minage');
            $data = xarTplModule('roles','user', 'checkage', array('minage'    => $minage));
            break;

        case 'registerform':

            // authorisation code
            $authid = xarSecGenAuthKey();

            // current values (none)
            $values = array('username'    => '',
                            'realname' => '',
                            'email'    => '',
                            'pass1'    => '',
                            'pass2'    => '');

            // invalid fields (none)
            $invalid = array();

            // dynamic properties (if any)
            $properties = null;
            if (xarModIsAvailable('dynamicdata')) {
                // get the Dynamic Object defined for this module (and itemtype, if relevant)
                $object =& xarModAPIFunc('dynamicdata','user','getobject',
                                         array('module' => 'roles'));
                if (isset($object) && !empty($object->objectid)) {
                    // get the Dynamic Properties of this object
                    $properties =& $object->getProperties();
                }
            }
            $data = xarTplModule('roles','user', 'registerform', array('authid'     => $authid,
                                                                       'values'     => $values,
                                                                       'invalid'    => $invalid,
                                                                       'properties' => $properties,
                                                                       'userlabel' => xarML('New User')));
            break;

        case 'checkregistration':

            if (!xarVarFetch('username','str:1:100',$username,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('realname','str:1:100',$realname,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('pass1','str:4:100',$pass1,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('pass2','str:4:100',$pass2,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('email','str:1:100',$email,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('agreetoterms','checkbox',$agreetoterms,false,XARVAR_NOT_REQUIRED)) return;

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;

            // TODO: check behind proxies too ?
            // check if the IP address is banned, and if so, throw an exception :)
            $ip = xarServerGetVar('REMOTE_ADDR');
            $disallowedips = xarModGetVar('roles','disallowedips');
            if (!empty($disallowedips)) {
                $disallowedips = unserialize($disallowedips);
                $disallowedips = explode("\r\n", $disallowedips);
                if (in_array ($ip, $disallowedips)) {
                    $msg = xarML('Your IP is on the banned list');
                    xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                    return;
                }
            }

            // current values (in case some field is invalid, we'll return to the previous template)
            $values = array('username'    => xarVarPrepForDisplay($username),
                            'realname' => xarVarPrepForDisplay($realname),
                            'email'    => xarVarPrepForDisplay($email),
                            'pass1'    => xarVarPrepForDisplay($pass1),
                            'pass2'    => xarVarPrepForDisplay($pass2));

            // invalid fields (we'll check this below)
            $invalid = array();

            // check if the username is empty
            if (empty($username)) {
                $invalid['username'] = xarML('You must provide a preferred username to continue.');

            // check for spaces in the username
            } elseif (preg_match("/[[:space:]]/",$username)) {
                $invalid['username'] = xarML('There is a space in the username');

            // check the length of the username
            } elseif (strlen($username) > 255) {
                $invalid['username'] = xarML('Your username is too long.');

            // check for spaces in the username (again ?)
            } elseif (strrpos($username,' ') > 0) {
                $invalid['username'] = xarML('There is a space in your username');

            } else {
                // check for duplicate usernames
                $user = xarModAPIFunc('roles',
                                      'user',
                                      'get',
                                       array('uname' => $username));
                if ($user != false) {
                    unset($user);
                    $invalid['username'] = xarML('That username is already taken.');

                } else {
                    // check for disallowed usernames
                    $disallowednames = xarModGetVar('roles','disallowednames');
                    if (!empty($disallowednames)) {
                        $disallowednames = unserialize($disallowednames);
                        $disallowednames = explode("\r\n", $disallowednames);
                        if (in_array ($username, $disallowednames)) {
                            $invalid['username'] = xarML('That username is either reserved or not allowed on this website');
                        }
                    }
                }
            }

            // check if the real name is empty
            if (empty($realname)){
                $invalid['realname'] = xarML('You must provide your display name to continue.');

            } else {
                // TODO: add some other limitations ?
            }

            // check if the email is empty
            if (empty($email)){
                $invalid['email'] = xarML('You must provide a valid email address to continue.');
            }

            $emailcheck = xarModAPIFunc('roles',
                                        'user',
                                        'validatevar',
                                        array('var' => $email,
                                              'type' => 'email'));

            if ($emailcheck == false) {
                $invalid['email'] = xarML('There is an error in your email address');
            }

            // check for duplicate email address
            $user = xarModAPIFunc('roles',
                                  'user',
                                  'get',
                                   array('email' => $email));
            if ($user != false) {
                unset($user);
                $invalid['email'] = xarML('That email address is already registered.');

            } else {
                // check for disallowed email addresses
                $disallowedemails = xarModGetVar('roles','disallowedemails');
                if (!empty($disallowedemails)) {
                    $disallowedemails = unserialize($disallowedemails);
                    $disallowedemails = explode("\r\n", $disallowedemails);
                    if (in_array ($email, $disallowedemails)) {
                        $invalid['email'] = xarML('That email address is either reserved or not allowed on this website');
                    }
                }
            }

            if (empty($agreetoterms)){
                $invalid['agreetoterms'] = xarML('You must agree to the terms and conditions of this website.');
            }

            // Check password and set
            if (xarModGetVar('roles', 'chooseownpassword')) {
                if ((empty($pass1)) || (empty($pass2))) {
                    $invalid['pass2'] = xarML('You must enter the same password twice');
                } elseif ($pass1 != $pass2) {
                    $invalid['pass2'] = xarML('The passwords do not match');
                } else {
                    $pass = $pass1;
                }
            }
            if (empty($pass)){
                $pass = '';
            }

            // dynamic properties (if any)
            $properties = null;
            $isvalid = true;
            if (xarModIsAvailable('dynamicdata')) {
                // get the Dynamic Object defined for this module (and itemtype, if relevant)
                $object =& xarModAPIFunc('dynamicdata','user','getobject',
                                         array('module' => 'roles'));
                if (isset($object) && !empty($object->objectid)) {

                    // check the input values for this object !
                    $isvalid = $object->checkInput();

                    // get the Dynamic Properties of this object
                    $properties =& $object->getProperties();
                }
            }

            // new authorisation code
            $authid = xarSecGenAuthKey();

            // check if any of the fields (or dynamic properties) were invalid
            if (count($invalid) > 0 || !$isvalid) {
                // if so, return to the previous template
                return xarTplModule('roles','user', 'registerform', array('authid'     => $authid,
                                                                          'values'     => $values,
                                                                          'invalid'    => $invalid,
                                                                          'properties' => $properties,
                                                                          'createlabel' => xarML('Create Account'),
                                                                          'userlabel' => xarML('New User')));
            }

            // everything seems OK -> go on to the next step
            $data = xarTplModule('roles','user', 'confirmregistration', array('username'    => $username,
                                                                             'email'     => $email,
                                                                             'realname'  => $realname,
                                                                             'pass'      => $pass,
                                                                             'ip'        => $ip,
                                                                             'authid'    => $authid,
                                                                             'properties' => $properties,
                                                                             'createlabel' => xarML('Create Account')));

            break;

        case 'createuser':

            if (!xarVarFetch('username','str:1:100',$username,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('realname','str:1:100',$realname,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('pass','str:4:100',$pass,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('ip','str:4:100',$ip,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('email','str:1:100',$email,'',XARVAR_NOT_REQUIRED)) return;

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;

            if (empty($pass)){
                $pass = xarModAPIFunc('roles',
                                      'user',
                                      'makepass');
            }

            // Create confirmation code and time registered
            $confcode = xarModAPIFunc('roles',
                                      'user',
                                      'makepass');
            $now = time();
            
            $requireValidation = xarModGetVar('roles', 'requirevalidation');
            if ($requireValidation == false) {
                // Create user and then log them in.  No sense in hesitating when its not required.
                if (!xarModAPIFunc('roles',
                                   'admin',
                                   'create',
                                    array('uname' => $username,
                                          'realname' => $realname,
                                          'email' => $email,
                                          'pass'  => $pass,
                                          'date'     => $now,
                                          'valcode'  => $confcode,
                                          'state'   => 3))) return;

                // check for user and grab uid if exists
                $user = xarModAPIFunc('roles',
                                      'user',
                                      'get',
                                       array('uname' => $username));

                // Check for user creation failure
                if (empty($user)) return;

                //Insert the user into the default users role
                $userRole = xarModGetVar('roles', 'defaultgroup');

                // Get the group id
                $defaultRole = xarModAPIFunc('roles',
                                             'user',
                                             'get',
                                             array('uname'  => $userRole,
                                                   'type'   => 1));

                if (empty($defaultRole)) return;

                // Make the user a member of the users role
                if(!xarMakeRoleMemberByID($user['uid'], $defaultRole['uid'])) return;
                
                xarModAPIFunc('roles',
                              'user',
                              'login',
                              array('uname' => $username,
                                    'pass' => $pass,
                                    'rememberme' => 0));

                xarModSetVar('roles', 'lastuser', $username);
                xarResponseRedirect('index.php');

            } else {
                // Create user - this will also create the dynamic properties (if any) via the create hook
                if (!xarModAPIFunc('roles',
                                   'admin',
                                   'create',
                                    array('uname' => $username,
                                          'realname' => $realname,
                                          'email' => $email,
                                          'pass'  => $pass,
                                          'date'     => $now,
                                          'valcode'  => $confcode,
                                          'state'   => 2))) return;

                // check for user and grab uid if exists
                $user = xarModAPIFunc('roles',
                                      'user',
                                      'get',
                                       array('uname' => $username));

                // Check for user creation failure
                if (empty($user)) return;

                //Insert the user into the default users role
                $userRole = xarModGetVar('roles', 'defaultgroup');

                // Get the group id
                $defaultRole = xarModAPIFunc('roles',
                                             'user',
                                             'get',
                                             array('uname'  => $userRole,
                                                   'type'   => 1));

                if (empty($defaultRole)) return;

                // Make the user a member of the users role
                if(!xarMakeRoleMemberByID($user['uid'], $defaultRole['uid'])) return;

                // TODO: make sending mail configurable too, depending on the other options ?
                // Set up confirmation email
                $confemail = xarModGetVar('roles', 'confirmationemail');
                $conftitle = xarModGetVar('roles', 'confirmationtitle');

                $sitename = xarModGetVar('themes', 'SiteName');
                $siteadmin = xarModGetVar('mail', 'adminname');
                $baseurl = xarServerGetBaseURL();

                $confemailsearch = array('/%%link%%/',
                                         '/%%name%%/',
                                         '/%%username%%/',
                                         '/%%ipaddr%%/',
                                         '/%%sitename%%/',
                                         '/%%password%%/',
                                         '/%%siteadmin%%/',
                                         '/%%valcode%%/');

                $confemailreplace = array("".$baseurl."val.php?v=".$confcode."&u=".$user['uid']."",
                                          "$realname",
                                          "$username",
                                          "$ip",
                                          "$sitename",
                                          "$pass",
                                          "$siteadmin",
                                          "$confcode");

                // retrieve the dynamic properties (if any) for use in the e-mail too
                if (xarModIsAvailable('dynamicdata')) {
                    // get the Dynamic Object defined for this module and item id
                    $object =& xarModAPIFunc('dynamicdata','user','getobject',
                                             array('module' => 'roles',
                                                   // we know the item id now...
                                                   'itemid' => $user['uid']));
                    if (isset($object) && !empty($object->objectid)) {

                        // retrieve the item itself
                        $itemid = $object->getItem();

                        if (!empty($itemid) && $itemid == $user['uid']) {
                            // get the Dynamic Properties of this object
                            $properties =& $object->getProperties();
                            foreach (array_keys($properties) as $key) {
                                // add the property name/value to the search/replace lists
                                if (isset($properties[$key]->value)) {
                                    $confemailsearch[] = '/%%'.$key . '%%/';
                                    $confemailreplace[] = $properties[$key]->value; // we'll use the raw value here, not ->showOutput();
                                }
                            }
                        }
                    }
                }

                $confemail = preg_replace($confemailsearch,
                                          $confemailreplace,
                                          $confemail);

                $conftitle = preg_replace($confemailsearch,
                                          $confemailreplace,
                                          $conftitle);

                // TODO Make HTML Message.
                // Send confirmation email
                if (!xarModAPIFunc('mail',
                                   'admin',
                                   'sendmail',
                                   array('info' => $email,
                                         'name' => $realname,
                                         'subject' => $conftitle,
                                         'message' => $confemail))) return;

               $data = xarTplModule('roles','user', 'waitingconfirm');
                }

                break;

           case 'validate':

                break;
        }

    return $data;
}

?>