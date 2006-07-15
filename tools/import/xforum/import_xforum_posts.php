<?php
/**
 * File: $Id$
 *
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
 * Note : this file is part of import_xForum.php and cannot be run separately
 */
    echo "<strong>$step. Importing posts</strong><br/>\n";
    //Just incase we haven't imported users yet
     $users = xarModGetVar('installer','userid');
    if (!empty($users)) {
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
     $regid = xarModGetIDFromName('xarbb');
    $pid2cid = array();
            @set_time_limit(600);
// TODO: fix issue for large # of comments (64 KB limit)
    $pids = xarModGetVar('installer','commentid');
    if (!empty($pids)) {
        $pid2cid = unserialize($pids);
        $pids = '';
    }
       // Get datbase setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_XForum_posts';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count posts failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $query = 'SELECT p.fid, p.pid, p.tid, p.author, p.message, p.dateline, p.useip, m.uid, t.subject
              FROM ' . $oldprefix . '_XForum_posts as p
              LEFT JOIN ' . $oldprefix . '_XForum_threads as t
              ON t.tid = p.tid
              LEFT JOIN ' . $oldprefix . '_XForum_members as m
              ON m.username = p.author
              ORDER BY p.pid ASC';
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
        list($fid,$ppid,$tid,$author,$message,$postdate,$hostname,$uid,$subject) = $result->fields;

       if (isset($postid[$ppid])) {
        // we've seen this one before as a topic
            $num++;
            $result->MoveNext();
            continue;
        } elseif (!isset($topicid[$tid])) {
            echo "Unknown topic id $tid for post ($ppid) $subject<br/>\n";
            $num++;
            $result->MoveNext();
            continue;
        }

        if (!empty($subject)) {
            $subject = xarML('Re: ') . $subject;
        }

        $pid = 0;
   //let's try and assign correct UID - may not exist yet if users not imported
        if (isset($userid[$uid])) {
            $uid = $userid[$uid];
        } // else we're lost :)
        if (empty($uid) || $uid < 2) {
            $fuid = xarConfigGetVar('Site.User.AnonymousUID'); //make them all Anonymous
        }
        if ($uid ==2) {
        $uid=xarModGetVar('roles','admin');//Let's make the old PN admin user2 new Xar Admin - check!
        }
        $fuid=$uid; 
        


        $pid = 0;
        $data['modid'] = xarModGetIDFromName('xarbb');
        $data['objectid'] = $topicid[$tid];
        if (!empty($pid) && !empty($pid2cid[$pid])) {
            $pid = $pid2cid[$pid];
        }
        $data['itemtype']=0;
        $data['pid'] = $pid;
        $data['author'] = $fuid;
        $data['title'] = $subject;
        $data['comment'] = $message;
        $data['hostname'] = $hostname;
        $data['tid'] = $tid;
        $data['date'] = $postdate;
        $data['postanon'] = 0;

        $cid = xarModAPIFunc('comments','user','add',$data);

        $cupdata = array("fid" => $fid,
                        "ttitle" => $subject,
                        "tposter" =>$fuid,
                        "treplies" => 1,
                        "treplier" => $fuid);

        echo "Post $cid for post ". $subject." imported.<br />";

        $cidup=xarModAPIFunc('xarbb','user','updatetopic',$cupdata);

        if ((empty($cid))) {
            echo "Failed inserting post ($ppid $tid) $uname - $subject : ".$dbconn->ErrorMsg()."<br/>\n";
        } elseif ($count < 200) {
            echo "Inserted post ($ppid $tid) $uname - $subject<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted post " . ($num + $startnum) . "<br/>\n";
            flush();
        }

// no threading
        $pid2cid[$pid] = $cid;
        $num++;
        $result->MoveNext();
    }
    $result->Close();

    echo '<a href="import_xforum.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
  if ($count > $numitems && $startnum + $numitems < $count) {
        xarModSetVar('installer','commentid',serialize($pid2cid));
        $startnum += $numitems;
        echo '<a href="import_xforum.php?step=' . $step . '&module=articles&startnum=' . $startnum . '">Go to step ' . $step . ' - comments ' . $startnum . '+ of ' . $count . '</a><br/>';
    } else {
        xarModDelVar('installer','commentid');
        echo '<a href="import_xforum.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    }
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['comments']);

?>
