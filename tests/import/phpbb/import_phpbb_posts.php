<?php
/**
 * File: $Id$
 *
 * Import phpBB posts into your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Note : this file is part of import_phpbb.php and cannot be run separately
 */

    echo "<strong>$step. Importing posts</strong><br/>\n";

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
            echo "Unknown topic id $sid for post ($tid) $subject<br/>\n";
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
        $data['itemtype'] = $ptid; // whatever the pubtype for forums is
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
            echo "Failed inserting post ($sid $pid) $uname - $subject : ".$dbconn->ErrorMsg()."<br/>\n";
        } elseif ($count < 200) {
            echo "Inserted post ($sid $pid) $uname - $subject<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted post " . ($num + $startnum) . "<br/>\n";
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
        echo '<a href="import_phpbb.php?step=' . $step . '&module=articles&startnum=' . $startnum . '">Go to step ' . $step . ' - comments ' . $startnum . '+ of ' . $count . '</a><br/>';
    } else {
        xarModDelVar('installer','commentid');
        echo '<a href="import_phpbb.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    }
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['comments']);

?>