<?php
/**
 * File: $Id$
 *
 * Import Slashcode users into your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author Richard Cave <rcave@xaraya.com>
 */

/**
 * Note : this file is part of import_slashcode.php and cannot be run separately
 */

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
        xarModSetVar('installer', 'defaultgid', $defaultgid);
    }
    
    // Check for the Admin group
    $admingid = xarModGetVar('installer', 'admingid');
    if (empty($admingid)) {
        // Get the group id
        $adminRole = xarModAPIFunc('roles',
                                   'user',
                                   'get',
                                   array('uname'  => 'Administrators',
                                         'type'   => 1));
        if (empty($adminRole)) {
            echo "Unable to find admin group id : " . xarErrorRender('text') . "<br/>\n";
            die('Oops');
        }
        
        $admingid = $adminRole['uid'];
        xarModSetVar('installer', 'admingid', $admingid);
    }
   
    echo "<strong>$step. Importing users</strong><br/>\n";

    // Initialize table names
    $table_users = 'users';
    $table_users_info = 'users_info';
    $table_users_prefs = 'users_prefs';
    $table_tzcodes = 'tzcodes';
    $table_userids = xarDBGetSiteTablePrefix() . '_installer_userids';
    
    // Because the users table could contain a large number of users (+200,000),
    // create a new table to store the Slashcode and Xaraya user ids.
    // Note: we'll create this in the Slashcode database, so we can use table joins afterwards

    $dbtype = xarModGetVar('installer','dbtype');
    $importdbtype = xarModGetVar('installer','importdbtype');

    // In case the userids table exits, drop the table
    if (empty($startnum)) {
        $dbimport->Execute("DROP TABLE " . $table_userids);

        // Create topic tree table
        $fields = array(
            'slash_uid'   => array('type'=>'integer','null'=>FALSE),
            'xar_uid'     => array('type'=>'integer','null'=>FALSE)
        );

        // Create the table DDL
        $query = xarDBCreateTable($table_userids,$fields,$importdbtype);
        if (empty($query)) {
            echo "Couldn't create query for table $table_userids<br/>\n";
            return; // throw back
        }

        // Pass the Table Create DDL to adodb to create the table
        $dbimport->Execute($query);

        // Check for an error with the database
        if ($dbimport->ErrorNo() != 0) {
            die("Oops, create of table " . $table_userids . " failed : " . $dbimport->ErrorMsg());
        }
    }
    
    $usercount = xarModGetVar('installer','usercount');
    echo "Found " . $usercount . " Users<br/>\n";

    // Delete current users in Xaraya database
    if ($reset && empty($startnum)) {
        $dbconn->Execute("DELETE FROM " . $tables['roles'] . " WHERE xar_uid > 6"); // TODO: VERIFY !
        $dbconn->Execute("DELETE FROM " . $tables['rolemembers'] . " WHERE xar_uid > 6 OR xar_parentid > 6"); // TODO: VERIFY !
    }

    // Use different unix timestamp conversion function for
    // MySQL and PostgreSQL databases
    switch ($importdbtype) {
        case 'mysql':
            $dbfunction = "UNIX_TIMESTAMP($table_users_info.lastaccess)";
            break;
        case 'postgres':
            $dbfunction = "DATE_PART('epoch',$table_users_info.lastaccess)";
            break;
        default:
            die("Unknown database type");
            break;
    }

    // uid          - user id
    // nickname     - the name as displayed in their comments
    // realname     - their real name (legal name)    
    // matchname    - the nickname with no spaces and all lowercase (use?)
    // realemail    - their true email
    // fakeemail    - their email as displayed in comments    
    // passwd       - plaintext password (encrypted)
    // homepage     - their URL
    // lastaccess   - last access to the site (use as registration date)
    // sig          - signature text
    // bio          - spiel of who they are
    // off_set      - timezone offset in seconds 
    // seclev       - security level, 100 and above admin
    
    $query = "SELECT $table_users.uid, 
                     $table_users.nickname,                 
                     $table_users_info.realname,                     
                     $table_users.matchname,
                     $table_users.realemail,
                     $table_users.fakeemail,
                     $table_users.passwd,
                     $table_users.homepage,
                     $dbfunction,
                     $table_users.sig,
                     $table_users_info.bio,
                     $table_tzcodes.off_set,
                     $table_users.seclev
                FROM $table_users
           LEFT JOIN $table_users_info
                  ON $table_users.uid = $table_users_info.uid
           LEFT JOIN $table_users_prefs
                  ON $table_users.uid = $table_users_prefs.uid
           LEFT JOIN $table_tzcodes
                  ON $table_users_prefs.tzcode = $table_tzcodes.tz
               WHERE LENGTH($table_users.passwd) = 32
            ORDER BY $table_users.uid ASC";
    // skip users with invalid password lengths
    //           WHERE LENGTH($table_users.passwd) = 32

    $numitems = xarModGetVar('installer','userimport');
    if (!isset($startnum)) {
        $startnum = 0;
    }
    if ($usercount > $numitems) {
        $result =& $dbimport->SelectLimit($query, $numitems, $startnum);
    } else {
        $result =& $dbimport->Execute($query);
    }
    if (!$result) {
        die("Oops, select users failed : " . $dbimport->ErrorMsg());
    }
  
    // check if there's a dynamic object defined for users
    $myobject = xarModAPIFunc('dynamicdata',
                              'user',
                              'getobject',
                              array('moduleid' => xarModGetIDFromName('roles'), // it's this module
                                     'itemtype' => 0));                          // with no item type

    if (empty($myobject) || empty($myobject->objectid)) {
        // if not, import the dynamic properties for users
        $objectid = xarModAPIFunc('dynamicdata',
                                  'util',
                                  'import',
                                  array('file' => 'modules/dynamicdata/users.xml'));
                                  
        if (empty($objectid)) {
            die('Error creating the dynamic user properties');
        }
 
        $myobject = xarModAPIFunc('dynamicdata',
                                  'user',
                                  'getobject',
                                  array('objectid' => $objectid));
    }
    
    // Disable dynamicdata hooks for roles (to avoid create + update)
    if (xarModIsHooked('dynamicdata','roles')) {
        xarModAPIFunc('modules',
                      'admin',
                      'disablehooks',
                      array('callerModName' => 'roles', 'hookModName' => 'dynamicdata'));
    }
    
    $num = 0;
    while (!$result->EOF) {
        list($uid, 
             $nickname,
             $realname,
             $matchname,
             $realemail,
             $fakeemail,
             $passwd,
             $homepage,
             $lastaccess,
             $signature,
             $bio,
             $timezone,
             $seclev) = $result->fields;
             
        if (empty($nickname)) {
            $nickname = $matchname;
        }
        
        if (empty($realname)) {
            $realname = $nickname;
        }
        
        if (empty($lastaccess)) {
            $lastaccess = time();
        }
                             
        $user = array(//'uid'        => $uid,
                      'uname'      => $nickname,
                      'realname'   => $realname,
                      'email'      => $realemail,
                      'cryptpass'  => $passwd,
                      'pass'       => '', // in case $pass is empty
                      'date'       => $lastaccess,
                      'valcode'    => 'createdbyadmin',
                      'authmodule' => 'authsystem',
                      'state'      => 3);           

        // this will *not* fill in the dynamic properties now
        $newuid = xarModAPIFunc('roles',
                                'admin',
                                'create',
                                $user);

        //$userid[$uid] = $newuid;
        $num++;
        $result->MoveNext();

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
        } elseif ($usercount < 200) {
            echo "Inserted user ($uid) $realname - $nickname<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted user " . ($num + $startnum) . "<br/>\n";
            flush();
        }
      
        // Set values that we can't retrieve from Slashcode database
        if ($homepage === 'http://') {
            $homepage = '';
        }
        $avatar = '';
        $icq = '';
        $aim = '';
        $yim = '';
        $msnm = '';
        $location = '';
        $occupation = '';
        $interests = '';
        
        // fill in the dynamic properties - cfr. users.xml !
        $dynamicvalues = array('itemid'     => $newuid,
                               'website'    => empty($homepage) ? null : $homepage,
                               'timezone'   => empty($timezone) ? null : intval($timezone / 3600), // GMT default
                               'avatar'     => empty($avatar) ? null : $avatar,
                               'icq'        => empty($icq) ? null : $icq,
                               'aim'        => empty($aim)  ? null : $aim,
                               'yim'        => empty($yim) ? null : $yim,
                               'msnm'       => empty($msnm) ? null : $msnm,
                               'location'   => empty($location) ? null : $location,
                               'occupation' => empty($occupation) ? null : $occupation,
                               'interests'  => empty($interests) ? null : $interests,
                               'signature'  => empty($signature) ? null : $signature,
                               'extra_info' => empty($bio) ? null : $bio,
                              );
                              
        $myobject->createItem($dynamicvalues);

        // add user to the default group
        //xarMakeRoleMemberByID($newuid, $defaultgid);

        // import user into appropriate group
        if ($seclev >= 100) {
            // This user should be added to the Admin group
            if (!xarModAPIFunc('roles',
                               'user',
                               'addmember', 
                               array('gid' => $admingid,
                                     'uid' => $newuid))) {
                echo "Insert user ($newuid) $uname in admin group ($admingid) failed : " . xarErrorRender('text') . "<br/>\n";
            }
        } else {
            if (!xarModAPIFunc('roles',
                               'user',
                               'addmember', 
                               array('gid' => $defaultgid,
                                     'uid' => $newuid))) {
                echo "Insert user ($newuid) $uname in group id ($defaultgid) failed : " . xarErrorRender('text') . "<br/>\n";
            }
        }

        // Add user to the temporary userids table
        $query = "INSERT INTO $table_userids (slash_uid, xar_uid) VALUES (".$uid.",".$newuid.")";
        $dbimport->Execute($query);
    }
    
    $result->Close();
    
    if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        xarErrorRender('text');
        xarErrorFree();
    }
    
    //echo "<strong>TODO : import user_data</strong><br/><br/>\n";
    
    echo '<a href="import_slashcode.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($usercount > $numitems && $startnum + $numitems < $usercount) {
        $startnum += $numitems;
        echo '<a href="import_slashcode.php?module=roles&step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - users ' . $startnum . '+ of ' . $usercount . '</a><br/>';
        $nexturl = xarServerGetBaseURL() . 'import_slashcode.php?module=roles&step=' . $step . '&startnum=' . $startnum;

    } else {
        // Enable dynamicdata hooks for roles
        xarModAPIFunc('modules',
                      'admin',
                      'enablehooks',
                      array('callerModName' => 'roles', 'hookModName' => 'dynamicdata'));

        // Add index for slash_uid
        $query = xarDBCreateIndex($table_userids,
                                  array('name'   => 'i_' . $table_userids,
                                        'fields' => array('slash_uid')),
                                  $importdbtype);
        if (empty($query)) return; // throw back
        $result = $dbimport->Execute($query);
        if (!isset($result)) return;

        echo '<a href="import_slashcode.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    }

    // Optimize tables
    switch ($dbtype) {
        case 'mysql':
            $query = 'OPTIMIZE TABLE ' . $tables['roles'];
            $result =& $dbconn->Execute($query);
            $query = 'OPTIMIZE TABLE ' . $tables['rolemembers'];
            $result =& $dbconn->Execute($query);
            $query = 'OPTIMIZE TABLE ' . $tables['dynamic_data'];
            $result =& $dbconn->Execute($query);
            break;
        case 'postgres':
            $query = 'VACUUM ANALYZE ' . $tables['roles'];
            $result =& $dbconn->Execute($query);
            $query = 'VACUUM ANALYZE ' . $tables['rolemembers'];
            $result =& $dbconn->Execute($query);
            $query = 'VACUUM ANALYZE ' . $tables['dynamic_data'];
            $result =& $dbconn->Execute($query);
            break;
        default:
            break;
    }
    switch ($importdbtype) {
        case 'mysql':
            $query = 'OPTIMIZE TABLE ' . $table_userids;
            $result =& $dbimport->Execute($query);
            break;
        case 'postgres':
            $query = 'VACUUM ANALYZE ' . $table_userids;
            $result =& $dbimport->Execute($query);
            break;
        default:
            break;
    }

    // auto-step
    if (!empty($nexturl)) {
        flush();
        echo "<script>
document.location = '" . $nexturl . "'
</script>";
    }

?>
