<?php
/**
 * Quick & dirty import of Joomla 1.0.4+ users into Xaraya test sites
 *
 * @package tools
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 * @author MichelV <michelv@xaraya.com>
 * @author jojodee <jojodee@xaraya.com>
 */
/**
 * Note : this file is part of import_joomla.php and cannot be run separately
 */
    // Tricky stuff below: delete previous data
    if ($reset && $startnum == 0) {
        $dbconn->Execute("DELETE FROM " . $tables['roles'] . " WHERE xar_uid > 6"); // TODO: VERIFY !
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['roles']);
        $dbconn->Execute("DELETE FROM " . $tables['rolemembers'] . " WHERE xar_uid > 6 OR xar_parentid > 6"); // TODO: VERIFY !
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['rolemembers']);
    }
    // End tricky

   // Create the Groups
    echo "<strong>$step.a. Importing and Creating groups</strong><br/>\n";

   //If some option in the start up page indicates to use the Joomla/Mambo groups - then let's do that

   $usejoomlagroups =xarModGetVar('installer','usegroups');

   if ($usejoomlagroups=='on') { //do we use joomla groups yes
       //let's start getting the groups and then we create them
       $query = 'SELECT group_id, parent_id, name FROM mos_core_acl_aro_groups  ';
       $result =& $dbconn->Execute($query);

       if (!$result) {
           die("Oops, Get Joomla groups failed : " . $dbconn->ErrorMsg());
       }

       $jgroups= array();

       for (; !$result->EOF; $result->MoveNext()) {
           list($jgroup_id,$jparent_id,$jname) = $result->fields;

           $jgroups[] = array('jgroup_id'   => $jgroup_id,
                              'jparent_id'  => $jparent_id,
                              'jname'  => $jname);

       }
       //create the groups in xar
       if (is_array($jgroups)) {
            $importgroup =xarModGetVar('installer','importgroup');
            foreach ($jgroups as $jgroup) {
                 if (in_array($jgroup['jname'], array('ROOT','USERS','Public Frontend','Registered','Public Backend','Administrator','Super Administrator'))){
                     //then we don't want to make these groups - they or equivalent already exist in xaraya
                     echo 'Skipping the group '.$jgroup['jname'].'<br />';
                 } else {
                         echo 'Making the group '.$jgroup['jname'].' in group '.$importgroup.'<br />';

                         xarMakeGroup($jgroup['jname']);
                         $roles = new xarRoles();
                         $role = $roles->getRole($importgroup);
                         xarMakeRoleMemberByName($jgroup['jname'],$importgroup);

                 }
            }
       } //end create groups

   } //end if

   // starting joomla user import

   echo "<strong>$step.b. Importing users</strong><br/>\n";
   //Count the users. When there are too many we will not echo them all.
   $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_users';
   $result =& $dbconn->Execute($query);
   if (!$result) {
       die("Oops, count users failed : " . $dbconn->ErrorMsg());
   }
   $count = $result->fields[0];
   $result->Close();
   $query = 'SELECT  id,
                      name,
                      username,
                      email,
                      password,
                      usertype,
                      block,
                      sendEmail,
                      gid,
                      registerDate,
                      lastvisitDate,
                      activation,
                      params
              FROM ' . $oldprefix . '_users
              ORDER BY id ASC';
//              WHERE id > 2 This doesn't work as there is an id of 62 for the admin already. Going to import him as user

   $numitems = 1000;
   if (!isset($startnum)) {
       $startnum = 0;
   }
   if ($count > $numitems) {
       $result =& $dbconn->SelectLimit($query, $numitems, $startnum);
   } else {
       $result =& $dbconn->Execute($query);
   }
   if (!$result) {
       die("Oops, select users failed : " . $dbconn->ErrorMsg());
   }

    // check if there's a dynamic object defined for users
    $myobject = xarModAPIFunc('dynamicdata','user','getobject',
                               array('moduleid' => xarModGetIDFromName('roles'), // it's this module
                                     'itemtype' => 0));                          // with no item type
    if (empty($myobject) || empty($myobject->objectid)) {
        // if not, import the dynamic properties for users
        $objectid = xarModAPIFunc('dynamicdata','util','import',
                                  array('file' => 'modules/dynamicdata/users.xml'));
        if (empty($objectid)) {
            die('Error creating the dynamic user properties');
        }
        $myobject = xarModAPIFunc('dynamicdata','user','getobject',
                                   array('objectid' => $objectid));
    }
    // Disable dynamicdata hooks for roles (to avoid create + update)
    if (xarModIsHooked('dynamicdata','roles')) {
        xarModAPIFunc('modules','admin','disablehooks',
                      array('callerModName' => 'roles', 'hookModName' => 'dynamicdata'));
    }
    // Check for the default users group
    $defaultgid = xarModGetVar('installer', 'defaultgid');
    if (empty($defaultgid)) {
        $userRole = xarModGetVar('roles', 'defaultgroup');

        // Get the group id
        $defaultRole = xarModAPIFunc('roles',
                                     'user',
                                     'get',
                                     array('uname'  => $userRole,
                                           'type'   => 1));
        if (empty($defaultRole)) {
            echo "Unable to find default group id : " . xarErrorRender('text') . "<br/>\n";
            die('Oops');
        }
        $defaultgid = $defaultRole['uid'];
        xarModSetVar('installer','defaultgid',$defaultgid);
    }
/*
    $users = xarModGetVar('installer', 'userid');
    if (!empty($users)) {
        $userid = unserialize($users);
    } else {
        $userid = array();
        $userid[0] = _XAR_ID_UNREGISTERED; // Anonymous account
        $userid[1] = _XAR_ID_UNREGISTERED; // Anonymous account
        $userid[2] = _XAR_ID_UNREGISTERED + 1; // Admin account - VERIFY !
    }
*/
    $num = 0;

    while (!$result->EOF) {
        list($uid,$name,$uname,$email,$pass,$usertype,$block_state,$sendemail,$gid,$date,
             $lastvisit,$activation,$extra_info) = $result->fields;
        if (empty($name)) {
            $name = $uname;
        }
        if (empty($date)) {
            $date = time();
        } else {
            $date = strtotime($date);
            //echo $date;
        }
        $user = array(//'uid'        => $uid,
                      'uname'      => $uname,
                      'realname'   => $name,
                      'email'      => $email,
                      'cryptpass'  => $pass,
                      'pass'       => '', // in case $pass is empty
                      'date'       => $date,
                      'valcode'    => 'createdbyadmin',
                      'authmodule' => 'authsystem',
                      'state'      => 3); //use the blockstate here?

        // this will *not* fill in the dynamic properties now
        $newuid = xarModAPIFunc('roles',
                                'admin',
                                'create',
                                $user);

        $userid[$uid] = $newuid;
        $num++;

        foreach ($jgroups as $jgroup=>$v ){

            if ($v['jgroup_id'] == $gid) {
                $usergroup = $v['jname'];
                //echo "Found group $usergroup<br />";

                if ($usergroup == 'ROOT') {
                    $usergroup = '';
                }elseif($usergroup == 'USERS') {
                    $usergroup = 'Users';
                }elseif($usergroup == 'Public Frontend') {
                    $usergroup = 'Users';
                }elseif($usergroup == 'Registered') {
                    $usergroup = 'Users';
                }elseif($usergroup == 'Public Backend') {
                    $usergroup = 'Users';
                }elseif($usergroup == 'Administrator') {
                    $usergroup = 'Administrators';
                }elseif($usergroup == 'Super Administrator') {
                    $usergroup = 'Administrators';
                } else {
                    $usergroup = xarModGetVar('installer', 'importgroup');
                }
                //echo "$uname $newuid New group: $usergroup<br />";
            }
        }
        $result->MoveNext();

        // Setting stuff we can't retreive
        // TODO: can we retreive this from somewhere?
        $timezone = 0;
        $avatar = '';
        $icq = '';
        $aim = '';
        $yim = '';
        $msnm = '';
        $location = '';
        $occupation = '';
        $interests = '';
        $signature = '';
        $url ='';

        if (empty($newuid)) {
            echo "Insert user ($uid) $uname failed - ";
            if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
                xarErrorRender('text');
                xarErrorHandled();
            }
        // same player, shoot again :)
            $user['uname'] .= $uid;
            echo "trying again with username " . $user['uname'] . " : ";
            $newuid = xarModAPIFunc('roles',
                                    'admin',
                                    'create',
                                    $user);
            if (empty($newuid)) {
                echo "failed<br/>\n";
                flush();
                continue;
            }
            echo "succeeded<br/>\n";
            flush();
        } elseif ($count < 200) {
            echo "Inserted user ($uid) $name - $uname going in $usergroup<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted user " . ($num + $startnum) . "<br/>\n";
            flush();
        }

        // default for timezone changed from 0 to 12 in PN
        if ($timezone > 0) {
            $timezone -= 12.0;
        }
        if ($url === 'http://') {
            $url = '';
        }
        if ($avatar === 'blank.gif') {
            $avatar = '';
        }

        // fill in the dynamic properties - cfr. users.xml !
        $dynamicvalues = array(
                               'itemid'     => $newuid,
                               'website'    => empty($url) ? null : $url,
                               'timezone'   => $timezone == 0 ? null : $timezone, // GMT default
                               'avatar'     => empty($avatar) ? null : $avatar,
                               'icq'        => empty($icq) ? null : $icq,
                               'aim'        => empty($aim)  ? null : $aim,
                               'yim'        => empty($yim) ? null : $yim,
                               'msnm'       => empty($msnm) ? null : $msnm,
                               'location'   => empty($location) ? null : $location,
                               'occupation' => empty($occupation) ? null : $occupation,
                               'interests'  => empty($interests) ? null : $interests,
                               'signature'  => empty($signature) ? null : $signature,
                               'extra_info' => empty($extra_info) ? null : $extra_info,
                              );
        $myobject->createItem($dynamicvalues);

        // Get the id of the group to insert to
        $xgroup = xarModAPIFunc('roles', 'user', 'get', array('uname'=> $usergroup, 'type' => 1));
        $xgroupid = $xgroup['uid'];
        //echo "group: $xgroupid <br />";

        if (empty($xgroupid)) {
            xarMakeRoleMemberByID($newuid, $defaultgid);
            if ($count < 200) {
                echo "Inserting into defaultgroup with id $defaultgid <br /><br />";
            }
        } else {
            xarMakeRoleMemberByID($newuid, $xgroupid);
            if ($count < 200) {
                echo "Inserting into group with id $xgroupid <br /><br />";
            }
        }
   }
   echo "<br />Imported $num users<br />";
    $result->Close();
    if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        xarErrorRender('text');
        xarErrorFree();
    }
    xarModSetVar('installer','userid',serialize($userid));
    echo "<strong>TODO : import user_data</strong><br/><br/>\n";
    echo '<a href="import_joomla.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import_joomla.php?module=roles&step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - users ' . $startnum . '+ of ' . $count . '</a><br/>';
    } else {
        // Enable dynamicdata hooks for roles
        xarModAPIFunc('modules','admin','enablehooks',
                      array('callerModName' => 'roles', 'hookModName' => 'dynamicdata'));
        echo '<a href="import_joomla.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    }

    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['roles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['rolemembers']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['dynamic_data']);

?>