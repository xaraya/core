<?php
/**
 * File: $Id$
 *
 * Quick & dirty import of phpBB data into Xaraya test sites
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
*/

// initialize the Xaraya core
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

list($step,
     $startnum) = xarVarCleanFromInput('step',
                                       'startnum');

// pre-fill the module name (if any) for hooks
xarRequestGetInfo();

if (!isset($step)) {
// start the output buffer
ob_start();
}
?>

<h3>Quick and dirty import of test data from an existing phpBB installation (to articles)</h3>

<?php
$prefix = xarDBGetSystemTablePrefix();
if (isset($step)) {
    if ($step == 1 && !isset($startnum)) {
        list($oldprefix,
             $importusers) = xarVarCleanFromInput('oldprefix',
                                                  'importusers');
    } elseif ($step > 1 || isset($startnum)) {
        $oldprefix = xarModGetVar('installer','oldprefix');
    }
}
if (!isset($oldprefix) || $oldprefix == $prefix || !preg_match('/^[a-z0-9_-]+$/i',$oldprefix)) {
?>
    Requirement : you must be using the same database, but a different prefix...
    <p></p>
    <form method="POST" action="import_phpbb.php">
    <table border="0" cellpadding="4">
    <tr><td align="right">Prefix used for your phpBB tables</td><td>
    <input type="text" name="oldprefix" value="phpbb"></td></tr>
    <tr><td align="right">Import phpBB users</td><td>
    <select name="importusers">
    <option value="0">Don't import users (= test only)</option>
    <option value="1">Create all users</option>
    <option value="2">Try to match usernames, and create otherwise (TODO)</option>
    <option value="3">Do something else...like using xarBB for instance :)</option>
    </select></td></tr>
    <tr><td colspan=2 align="middle">
    <input type="submit" value=" Import Data "></td></tr>
    </table>
    <input type="hidden" name="step" value="1">
    <input type="hidden" name="module" value="roles">
    </form>
    Recommended usage :<br /><ol>
    <li>don't use this on a live site</li>
    <li>install Xaraya and import your original site with import8.php</li>
    <li>copy the *-forums.xd templates over to your modules/articles/xartemplates directory</li>
    <li>copy this script to your Xaraya html directory and try it out...</li>
</ol>

<?php
} else {
    if ($step == 1 && !isset($startnum)) {
        xarModSetVar('installer','oldprefix',$oldprefix);
        if (empty($importusers)) {
            $step = 2;
        }
    }

    list($dbconn) = xarDBGetConn();

    if (!xarModAPILoad('roles','admin')) {
        die("Unable to load the users admin API");
    }
    if (!xarModAPILoad('categories','user')) {
        die("Unable to load the categories user API");
    }
    if (!xarModAPILoad('categories','admin')) {
        die("Unable to load the categories admin API");
    }
    if (!xarModAPILoad('articles','admin')) {
        die("Unable to load the articles admin API");
    }
    if (!xarModAPILoad('comments','user')) {
        die("Unable to load the comments user API");
    }
    if (!xarModAPILoad('dynamicdata','util')) {
        die("Unable to load the dynamicdata util API");
    }
    if (xarModIsAvailable('polls') && !xarModAPILoad('polls','admin')) {
        die("Unable to load the polls admin API");
    }
    if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','admin')) {
        $docounter = 1;
    }
    $tables = xarDBGetTables();

    if ($step == 1) {
    echo "<strong>1. Importing users</strong><br>\n";
    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_users';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count users failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $query = 'SELECT user_id, username, username, user_email, user_password, user_website, user_regdate,
                     user_timezone, user_avatar, user_icq, user_aim, user_yim, user_msnm,
                     user_from, user_occ, user_interests, user_sig, user_sig_bbcode_uid
              FROM ' . $oldprefix . '_users 
              WHERE user_id > 2
              ORDER BY user_id ASC';
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
    $myobject =& xarModAPIFunc('dynamicdata','user','getobject',
                               array('moduleid' => xarModGetIDFromName('roles'), // it's this module
                                     'itemtype' => 0));                          // with no item type
    if (empty($myobject) || empty($myobject->objectid)) {
        // if not, import the dynamic properties for users
        $objectid = xarModAPIFunc('dynamicdata','util','import',
                                  array('file' => 'modules/dynamicdata/users.xml'));
        if (empty($objectid)) {
            die('Error creating the dynamic user properties');
        }
        $myobject =& xarModAPIFunc('dynamicdata','user','getobject',
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
            echo "Unable to find default group id : " . xarExceptionRender('text') . "<br>\n";
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
        $userid[0] = _XAR_ID_UNREGISTERED; // Anonymous account
        $userid[1] = _XAR_ID_UNREGISTERED; // Anonymous account
        $userid[2] = _XAR_ID_UNREGISTERED + 1; // Admin account - VERIFY !
    }
    $num = 0;
    while (!$result->EOF) {
        list($uid,$name,$uname,$email,$pass,$url,$date,
             $timezone,$avatar,$icq,$aim,$yim,$msnm,
             $location,$occupation,$interests,$signature,$bbcode) = $result->fields;
        $extra_info = '';
        if (empty($name)) {
            $name = $uname;
        }
        if (empty($date)) {
            $date = time();
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
                      'state'      => 3);
        // this will *not* fill in the dynamic properties now
        $newuid = xarModAPIFunc('roles',
                                'admin',
                                'create',
                                $user);

        $num++;
        $result->MoveNext();

        if (empty($newuid)) {
            echo "Insert user ($uid) $uname failed - ";
            if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
                xarExceptionRender('text');
                xarExceptionHandled();
            }
        // same player, shoot again :)
            $user['uname'] .= $uid;
            echo "trying again with username " . $user['uname'] . " : ";
            $newuid = xarModAPIFunc('roles',
                                    'admin',
                                    'create',
                                    $user);
            if (empty($newuid)) {
                echo "failed<br>\n";
                flush();
                continue;
            }
            echo "succeeded<br>\n";
            flush();
        } elseif ($count < 200) {
            echo "Inserted user ($uid) $name - $uname<br>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted user " . ($num + $startnum) . "<br>\n";
            flush();
        }
        $userid[$uid] = $newuid;

        if ($url === 'http://') {
            $url = '';
        }
        if (!empty($bbcode) && !empty($signature) && preg_match("/:$bbcode\]/",$signature)) {
            $signature = preg_replace("/:$bbcode\]/",']',$signature);
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

/*    // TODO: import groups once roles/privileges are ok
        if (!xarModAPIFunc('groups',
                           'user',
                           'newuser', array('gname' => $usergroup,
                                            'uid'   => $uid))) {
            echo "Insert user ($uid) $uname in group $usergroup failed : " . xarExceptionRender('text') . "<br>\n";
        }
*/
    }
    $result->Close();
    if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
        xarExceptionRender('text');
        xarExceptionHandled();
    }
    xarModSetVar('installer','userid',serialize($userid));
    echo "<strong>TODO : import groups and ranks</strong><br><br>\n";
    echo '<a href="import_phpbb.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import_phpbb.php?module=roles&step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - users ' . $startnum . '+ of ' . $count . '</a><br>';
    } else {
        // Enable dynamicdata hooks for roles
        xarModAPIFunc('modules','admin','enablehooks',
                      array('callerModName' => 'roles', 'hookModName' => 'dynamicdata'));
        echo '<a href="import_phpbb.php?module=categories&step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br>';
    }

    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['roles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['rolemembers']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['dynamic_data']);
    }

    if ($step == 2) {
    echo "<strong>2. Importing phpBB categories and forums into categories</strong><br>\n";
    $regid = xarModGetIDFromName('articles');
    $categories = xarModAPIFunc('categories', 'admin', 'create',
                                array('name' => 'Forum Index',
                                      'description' => 'Forum Index',
                                      'parent_id' => 0));
// set this as base category for forums
    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
    $ptid = '';
    foreach ($pubtypes as $id => $pubtype) {
        if ($pubtype['name'] == 'forums') {
            $ptid = $id;
            break;
        }
    }
    if (empty($ptid)) {
        $ptid = xarModAPIFunc('articles', 'admin', 'createpubtype',
                              array (
                                'name' => 'forums',
                                'descr' => 'Discussion Forums',
                                'config' => 
                                array (
                                  'title' => 
                                  array (
                                    'label' => 'Subject',
                                    'format' => 'textbox',
                                    'input' => 'on',
                                  ),
                                  'summary' => 
                                  array (
                                    'label' => 'Username',
                                    'format' => 'textbox',
                                    'input' => 'on',
                                  ),
                                  'bodytext' => 
                                  array (
                                    'label' => 'Message',
                                    'format' => 'textarea_large',
                                    'input' => 'on',
                                  ),
                                  'bodyfile' => 
                                  array (
                                    'label' => '',
                                    'format' => 'fileupload',
                                  ),
                                  'notes' => 
                                  array (
                                    'label' => 'Last Post ?',
                                    'format' => 'calendar',
                                  ),
                                  'authorid' => 
                                  array (
                                    'label' => 'Author',
                                    'format' => 'username',
                                  ),
                                  'pubdate' => 
                                  array (
                                    'label' => 'Publication Date',
                                    'format' => 'calendar',
                                  ),
                                  'status' => 
                                  array (
                                    'label' => 'Status',
                                    'format' => 'status',
                                  ),
                                ),
                              )
                             );
        if (empty($ptid)) {
            echo "Creating publication type 'forums' failed : " . xarExceptionRender('text') . "<br>\n";
        } else {
            $settings = array (
                         'itemsperpage' => '40',
                         'number_of_columns' => '0',
                         'defaultview' => '1',
                         'showcategories' => 0,
                         'showprevnext' => '1',
                         'showcomments' => '1',
                         'showhitcounts' => '1',
                         'showratings' => '1',
                         'showarchives' => 0,
                         'showmap' => 0,
                         'showpublinks' => 0,
                         'dotransform' => 0,
                         'prevnextart' => '1',
                         'page_template' => '',
                        );
            xarModSetVar('articles', 'settings.'.$ptid, serialize($settings));
            xarModSetVar('articles', 'number_of_categories.'.$ptid, 0);
            xarModSetVar('articles', 'mastercids.'.$ptid, '');
            xarModSetAlias('forums','articles');
            echo "Publication type 'forums' created...<br /><br />\n";
        }
    }
    if (!empty($ptid)) {
        $settings = unserialize(xarModGetVar('articles', 'settings.'.$ptid));
        $settings['defaultview'] = 'c' . $categories;
        xarModSetVar('articles', 'settings.'.$ptid, serialize($settings));
        xarModSetVar('articles', 'number_of_categories.'.$ptid, 1);
        xarModSetVar('articles', 'mastercids.'.$ptid, $categories);

        xarModSetVar('installer','ptid',$ptid);
    }

    $query = 'SELECT cat_id, cat_title, cat_order
              FROM ' . $oldprefix . '_categories
              ORDER BY cat_order ASC, cat_id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select categories failed : " . $dbconn->ErrorMsg());
    }
    $catid = array();
    while (!$result->EOF) {
        list($id, $title, $order) = $result->fields;
        $catid[$id] = xarModAPIFunc('categories', 'admin', 'create',
                                    array('name' => $title,
                                          'description' => $title,
                                          'parent_id' => $categories));
        echo "Creating category ($id) $title<br>\n";
        $result->MoveNext();
    }
    $result->Close();

    $query = 'SELECT forum_id, cat_id, forum_name, forum_desc, forum_order
              FROM ' . $oldprefix . '_forums
              ORDER BY cat_id ASC, forum_order ASC, forum_id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select forums failed : " . $dbconn->ErrorMsg());
    }
    $forumid = array();
    while (!$result->EOF) {
        list($fid, $cid, $name, $descr, $order) = $result->fields;
        if (!isset($catid[$cid])) {
            echo "Oops - no category id for $cid<br />\n";
            $catid[$cid] = 0;
        }
        $forumid[$fid] = xarModAPIFunc('categories', 'admin', 'create', array(
                              'name' => $name,
                              'description' => $descr,
                              'parent_id' => $catid[$cid]));
        echo "Creating forum ($fid) $name - $descr<br>\n";
        $result->MoveNext();
    }
    $result->Close();
    xarModSetVar('installer','categories',$categories);
    xarModSetVar('installer','catid',serialize($catid));
    xarModSetVar('installer','forumid',serialize($forumid));
    echo '<a href="import_phpbb.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_phpbb.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }
    }

    if ($step == 3) {
    $users = xarModGetVar('installer','userid');
    if (!isset($users)) {
        $userid = array();
    } else {
        $userid = unserialize($users);
    }
    $categories = xarModGetVar('installer','categories');
    $catid = unserialize(xarModGetVar('installer','catid'));
    $forumid = unserialize(xarModGetVar('installer','forumid'));
    $topics = xarModGetVar('installer','topicid');
    if (!isset($topics)) {
        $topicid = array();
    } else {
        $topicid = unserialize($topics);
    }
    $posts = xarModGetVar('installer','postid');
    if (!isset($posts)) {
        $postid = array();
    } else {
        $postid = unserialize($posts);
    }
    $ptid = xarModGetVar('installer','ptid');

    echo "<strong>3. Importing topics</strong><br>\n";
    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_topics';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count topics failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $regid = xarModGetIDFromName('articles');
    $query = 'SELECT t.topic_id,t.forum_id,topic_title,topic_poster,topic_time,topic_views,topic_replies,topic_status,topic_vote,topic_type,topic_first_post_id,topic_last_post_id,topic_moved_id,post_username,post_subject,post_text,bbcode_uid
              FROM ' . $oldprefix . '_topics as t
              LEFT JOIN ' . $oldprefix . '_posts as p
                  ON t.topic_first_post_id=p.post_id
              LEFT JOIN ' . $oldprefix . '_posts_text as pt
                  ON pt.post_id=p.post_id
              ORDER BY t.topic_id ASC';
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
        die("Oops, select topics failed : " . $dbconn->ErrorMsg());
    }
    $num = 1;
    while (!$result->EOF) {
        list($tid, $fid, $title, $authorid, $time, $views, $replies, $status, $vote, $type, $firstid, $lastid, $movedid, $uname, $subject, $text, $bbcode) = $result->fields;
        if (empty($title)) {
            if (!empty($subject)) {
                $title = $subject;
            } else {
                $title = xarML('[none]');
            }
        }
        if (!empty($bbcode) && !empty($text) && preg_match("/:$bbcode\]/",$text)) {
            $text = preg_replace("/:$bbcode\]/",']',$text);
        }
        if (empty($uname)) {
            $uname = '';
        }
        if (isset($userid[$authorid])) {
            $authorid = $userid[$authorid];
        } // else we're lost :)
        if (empty($authorid) || $authorid < 2) {
            $authorid = _XAR_ID_UNREGISTERED;
        }
        $cids = array();
        if (isset($forumid[$fid])) {
            $cids[] = $forumid[$fid];
        }
        $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array(//'aid' => $tid, // don't keep topic id here
                                      'title' => $title,
                                      'summary' => $uname,
                                      'body' => $text,
                                      'notes' => '',
                                      'status' => 2, // $status, // probably not what we're used to here :)
                                      'ptid' => $ptid,
                                      'pubdate' => $time,
                                      'authorid' => $authorid,
                                      'language' => '',
                                      'cids' => $cids,
                                      'hits' => $views
                                     )
                               );
        if (!isset($newaid)) {
            echo "Insert topic ($tid) $title failed : " . xarExceptionRender('text') . "<br>\n";
        } elseif ($count < 200) {
            echo "Inserted topic ($tid) $title<br>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted topic " . ($num + $startnum) . "<br>\n";
            flush();
        }
        if (!empty($newaid)) {
            $topicid[$tid] = $newaid;
            $postid[$firstid] = $newaid;
        }
        $num++;

        $result->MoveNext();
    }
    $result->Close();
    xarModSetVar('installer','topicid',serialize($topicid));
    xarModSetVar('installer','postid',serialize($postid));
    //echo "<strong>TODO : add comments etc.</strong><br><br>\n";
    echo '<a href="import_phpbb.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import_phpbb.php?step=' . $step . '&module=articles&startnum=' . $startnum . '">Go to step ' . $step . ' - articles ' . $startnum . '+ of ' . $count . '</a><br>';
    } else {
        echo '<a href="import_phpbb.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br>';
    }
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['articles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories_linkage']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }
    }

    if ($step == 4) {
    $users = xarModGetVar('installer','userid');
    if (!isset($users)) {
        $userid = array();
    } else {
        $userid = unserialize($users);
    }
    $categories = xarModGetVar('installer','categories');
    $catid = unserialize(xarModGetVar('installer','catid'));
    $forumid = unserialize(xarModGetVar('installer','forumid'));
    $topics = xarModGetVar('installer','topicid');
    if (!isset($topics)) {
        $topicid = array();
    } else {
        $topicid = unserialize($topics);
    }
    $posts = xarModGetVar('installer','postid');
    if (!isset($posts)) {
        $postid = array();
    } else {
        $postid = unserialize($posts);
    }
    $ptid = xarModGetVar('installer','ptid');

    $regid = xarModGetIDFromName('articles');
    $pid2cid = array();
// TODO: fix issue for large # of comments (64 KB limit)
    $pids = xarModGetVar('installer','commentid');
    if (!empty($pids)) {
        $pid2cid = unserialize($pids);
        $pids = '';
    }
    echo "<strong>4. Importing posts</strong><br>\n";
    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_posts';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count posts failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $query = 'SELECT p.post_id, p.topic_id, post_time, post_username, poster_id,
              poster_ip, post_subject, post_text, bbcode_uid, topic_title
              FROM ' . $oldprefix . '_posts as p
              LEFT JOIN ' . $oldprefix . '_posts_text as pt
              ON p.post_id = pt.post_id
              LEFT JOIN ' . $oldprefix . '_topics as t
              ON t.topic_id = p.topic_id
              ORDER BY p.post_id ASC';
    $numitems = 1500;
    if (!isset($startnum)) {
        $startnum = 0;
    }

    if ($count > $numitems) {
        $result =& $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result =& $dbconn->Execute($query);
    }
    if (!$result) {
        die("Oops, select posts failed : " . $dbconn->ErrorMsg());
    }
    $num = 1;
    while (!$result->EOF) {
        list($tid,$sid,$date,$uname,$uid,$hostname,$subject,$comment,$bbcode,$title) = $result->fields;

        if (isset($postid[$tid])) {
        // we've seen this one before as a topic
            $num++;
            $result->MoveNext();
            continue;
        } elseif (!isset($topicid[$sid])) {
            echo "Unknown topic id $sid for post ($tid) $subject<br>\n";
            $num++;
            $result->MoveNext();
            continue;
        }

        if (empty($subject) && !empty($title)) {
            $subject = xarML('Re: ') . $title;
        }
        if (!empty($bbcode) && !empty($comment) && preg_match("/:$bbcode\]/",$comment)) {
            $comment = preg_replace("/:$bbcode\]/",']',$comment);
        }
// no threading in phpBB !?
        $pid = 0;

        if (isset($userid[$uid])) {
            $uid = $userid[$uid];
        } // else we're lost :)
        if (empty($uid) || $uid < 2) {
            $uid = _XAR_ID_UNREGISTERED;
        }
        $data['modid'] = $regid;
        $data['objectid'] = $topicid[$sid];
        if (!empty($pid) && !empty($pid2cid[$pid])) {
            $pid = $pid2cid[$pid];
        }
        $data['pid'] = $pid;
        $data['author'] = $uid;
        $data['title'] = $subject;
        $data['comment'] = $comment;
        $data['hostname'] = $hostname;
        //$data['cid'] = $tid;
        $data['date'] = $date;
        $data['postanon'] = 0;

        $cid = xarModAPIFunc('comments','user','add',$data);
        if (empty($cid)) {
            echo "Failed inserting post ($sid $pid) $uname - $subject : ".$dbconn->ErrorMsg()."<br>\n";
        } elseif ($count < 200) {
            echo "Inserted post ($sid $pid) $uname - $subject<br>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted post " . ($num + $startnum) . "<br>\n";
            flush();
        }
// no threading in phpBB !?
//        $pid2cid[$tid] = $cid;
        $num++;
        $result->MoveNext();
    }
    $result->Close();

    echo '<a href="import_phpbb.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        xarModSetVar('installer','commentid',serialize($pid2cid));
        $startnum += $numitems;
        echo '<a href="import_phpbb.php?step=' . $step . '&module=articles&startnum=' . $startnum . '">Go to step ' . $step . ' - comments ' . $startnum . '+ of ' . $count . '</a><br>';
    } else {
        xarModDelVar('installer','commentid');
        echo '<a href="import_phpbb.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br>';
    }
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['comments']);
    }

    if ($step == 5 && !xarModIsAvailable('polls')) {
        $step++;
    }

    if ($step == 5) {
    $topics = xarModGetVar('installer','topicid');
    if (!isset($topics)) {
        $topicid = array();
    } else {
        $topicid = unserialize($topics);
    }
    $ptid = xarModGetVar('installer','ptid');

    $regid = xarModGetIDFromName('articles');
    echo "<strong>5. Importing votes</strong><br>\n";

    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_vote_desc';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count votes failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();

    $query = 'SELECT vdesc.vote_id,topic_id,vote_text,vote_start,SUM(vote_result)
              FROM ' . $oldprefix . '_vote_desc as vdesc
              LEFT JOIN ' . $oldprefix . '_vote_results as vresults
                  ON vdesc.vote_id = vresults.vote_id
              GROUP BY vresults.vote_id
              ORDER BY vdesc.vote_id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select votes failed : " . $dbconn->ErrorMsg());
    }
    $pollid = array();
    $num = 1;
    while (!$result->EOF) {
        list($pid,$tid,$title,$time,$votes) = $result->fields;
        if (empty($title)) {
            $title = xarML('[none]');
        }
        if (!isset($topicid[$tid])) {
            echo "Unknown topic id $tid for vote $pid $title<br />\n";
            $num++;
            $result->MoveNext();
            continue;
        }
        $newpid = xarModAPIFunc('polls','admin','create',
                                array('title' => $title,
                                      'polltype' => 'single', // does phpBB support any other kind ?
                                      'private' => 0,
                                      'time' => $time,
                                      'module' => 'articles',
                                      'itemtype' => $ptid,
                                      'itemid' => $topicid[$tid],
                                      'votes' => $votes));
        if (empty($newpid)) {
            echo "Insert vote ($pid) $title failed : " . xarExceptionRender('text') . "<br>\n";
        } elseif ($count < 200) {
            echo "Inserted vote ($pid) $title<br>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted vote $num<br>\n";
            flush();
        }

        if (!empty($newpid)) {
            $pollid[$pid] = $newpid;
        }
        $num++;
        $result->MoveNext();
    }
    $result->Close();

    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_vote_results';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count vote results failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();

    $query = 'SELECT vote_id, vote_option_text, vote_result, vote_option_id
              FROM ' . $oldprefix . '_vote_results
              ORDER BY vote_id ASC, vote_option_id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select vote results failed : " . $dbconn->ErrorMsg());
    }
    $num = 1;
    while (!$result->EOF) {
        list($pid,$text,$count,$vid) = $result->fields;
        if ($text === '') {
            $num++;
            $result->MoveNext();
            continue;
        } elseif (!isset($pollid[$pid])) {
            echo "Unknown vote id $pid for option $text<br />\n";
            $num++;
            $result->MoveNext();
            continue;
        }
        $newvid = xarModAPIFunc('polls','admin','createopt',
                                array('pid' => $pollid[$pid],
                                      'option' => $text,
                                      'votes' => $count));
        if (empty($newvid)) {
            echo "Insert vote result ($pid $vid) $text failed : " . xarExceptionRender('text') . "<br>\n";
        } elseif ($count < 100) {
            echo "Inserted vote result ($pid $vid) $text<br>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted vote result $num<br>\n";
            flush();
        }
        $num++;
        $result->MoveNext();
    }
    $result->Close();
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['polls']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['polls_info']);
    echo '<a href="import_phpbb.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    echo '<a href="import_phpbb.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br>';

    // Enable polls hooks for 'forums' pubtype of articles
    xarModAPIFunc('modules','admin','enablehooks',
                  array('callerModName' => 'articles', 'callerItemType' => $ptid, 'hookModName' => 'polls'));
    }

// TODO: add the rest (private messages, groups, ranks, ...) :-)

    if ($step == 6) {

    echo "<strong>6. Cleaning up</strong><br>\n";

    echo "<strong>TODO : import the rest (private messages, groups, ranks, ...)</strong><br><br>\n";
    //xarModDelVar('installer','userobjectid');
    xarModDelVar('installer','oldprefix');
    xarModDelVar('installer','userid');
    xarModDelVar('installer','categories');
    xarModDelVar('installer','catid');
    xarModDelVar('installer','forumid');
    xarModDelVar('installer','topicid');
    xarModDelVar('installer','postid');
    $ptid = xarModGetVar('installer','ptid');
    $url = xarModURL('articles','user','view',
                     array('ptid' => $ptid));
    // Enable bbcode hooks for 'forums' pubtype of articles
    if (xarModIsAvailable('bbcode')) {
        xarModAPIFunc('modules','admin','enablehooks',
                      array('callerModName' => 'articles', 'callerItemType' => $ptid, 'hookModName' => 'bbcode'));
    }
    xarModDelVar('installer','ptid');
    echo '<a href="import_phpbb.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="'.$url.'">Go to your imported forums</a><br>';
    }
}

?>

<?php
if (!isset($step)) {

// catch the output
$return = ob_get_contents();
ob_end_clean();

xarTplSetPageTitle(xarConfigGetVar('Site.Core.SiteName').' :: '.xarML('Import Site'));

//xarTplSetThemeName('Xaraya_Classic');
//xarTplSetPageTemplateName('admin');

// render the page
echo xarTpl_renderPage($return);
}

// Close the session
xarSession_close();

//$dbconn->Close();

flush();

// Kill the debugger
xarCore_disposeDebugger();

// done
exit;
 
?>
