<?php
/**
 * File: $Id$
 * Quick & dirty import of xForum data into Xaraya test sites
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage import
 * @ based on phpBB import by author mikespub <mikespub@xaraya.com>
 * @ author jojodee <jojodee@xaraya.com>
 */

/**
 * Note : this file is part of import_xforum.php and cannot be run separately
 */

  echo "<strong>$step. Importing users</strong><br/>\n";
   // Get datbase setup
  $dbconn =& xarDBGetConn();
  $xartable =& xarDBGetTables();

  $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_XForum_members';
  $result =& $dbconn->Execute($query);
  if (!$result) {
     die("Oops, count users failed : " . $dbconn->ErrorMsg());
  }
  $count = $result->fields[0];
  $result->Close();
    // Select all PN users from XForum >1, assuming that 1 is Admin CHECK!!!!
  $query = 'SELECT uid, username, password, regdate, postnum, email, site,
                     aim, status, location, bio, sig, showemail,
                     timeoffset, icq, avatar, yahoo, bday,langfile,lastvisit
              FROM ' . $oldprefix . '_XForum_members
              WHERE uid > 1
              ORDER BY uid ASC';
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



    $users = xarModGetVar('installer', 'userid');
    if (!empty($users)) {
        $userid = unserialize($users);
    } else {
        $userid = array();
        $userid[0] = xarConfigGetVar('Site.User.AnonymousUID');//_XAR_ID_UNREGISTERED;  Anonymous account
        $userid[1] = xarModGetVar('roles','admin'); //CHECk!!! in XForum 1==Admin
        //$userid[2] = xarModGetVar('roles','admin'); //_XAR_ID_UNREGISTERED + 1;  Admin account - VERIFY !
    }

  $num = 0;
  while (!$result->EOF) {
        list($uid,$uname,$pass,$date,$postnum,$email,$url,
             $aim,$status,$location,$interests,$signature,$showemail,
             $timezone,$icq,$avatar,$yim,$bday,$language, $lastvist) = $result->fields;
        $extra_info = '';
        $name = $uname;

        if (empty($date)) {
            $date = time();
        }
        $user = array(//'uid'        => $uid,
                      'uname'      => $uname,
                      'realname'   => $uname,
                      'email'      => $email,
                      'cryptpass'  => $pass,
                      'pass'       => '', // in case $pass is empty
                      'date'       => $date,
                      'valcode'    => 'createdbyadmin',
                      'authmodule' => 'authsystem',
                      'state'      => 3);
     //Let's check see if this user already exists in Xaraya db

     if ($importusers<>1) { //assume all users already in xaraya but maybe different uid
          $userinfo=xarFindRole("$name");
          $realuid = $userinfo->getID();

          echo "<br />";
          echo "Checking: user name ".$name." exists and has id : ".$realuid."<br />";
          $userid[$uid]=$realuid;

     } elseif ($importusers==1) {  //we assume all xForum users are to be imported


         $user = array(//'uid'        => $uid,
                      'uname'      => $uname,
                      'realname'   => $uname,
                      'email'      => $email,
                      'cryptpass'  => $pass,
                      'pass'       => '', // in case $pass is empty
                      'date'       => $date,
                      'valcode'    => 'createdbyadmin',
                      'authmodule' => 'authsystem',
                      'state'      => 3);
         // this will *not* fill in the dynamic properties now
         $newuid = xarModAPIFunc('roles',
                                'admin',
                                'create',
                                $user);

         //why are these commented out??? They are in further down??
         //test this later
         //  $num++;
         //  $result->MoveNext();

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
             echo "Inserted user ($uid) ->$newuid is $uname<br/>\n";
         } elseif ($num % 100 == 0) {
             echo "Inserted user " . ($num + $startnum) . "<br/>\n";
             flush();
         }
         $userid[$uid] = $newuid;

         if ($url === 'http://') {
             $url = '';
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

         // add user to the default group
         xarMakeRoleMemberByID($newuid, $defaultgid);

/*       // TODO: import groups once roles/privileges are ok
         if (!xarModAPIFunc('groups',
                           'user',
                           'newuser', array('gname' => $usergroup,
                                            'uid'   => $uid))) {
            echo "Insert user ($uid) $uname in group $usergroup failed : " . xarErrorRender('text') . "<br/>\n";
         }
*/
    } //end if
     $num++;
     $result->MoveNext();

    } //end while
    $result->Close();
    if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        xarErrorRender('text');
        xarErrorHandled();
    }
    xarModSetVar('installer','userid',serialize($userid));
    echo "<strong>TODO : import groups and ranks</strong><br/><br/>\n";
    echo '<a href="import_xforum.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import_xforum.php?module=roles&step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - users ' . $startnum . '+ of ' . $count . '</a><br/>';
    } else {
        // Enable dynamicdata hooks for roles
        xarModAPIFunc('modules','admin','enablehooks',
                      array('callerModName' => 'roles', 'hookModName' => 'dynamicdata'));
        echo '<a href="import_xforum.php?module=categories&step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    }

    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['roles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['rolemembers']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['dynamic_data']);

?>
