<?php
/**
 * File: $Id$
 *
 * Import phpBB topics into your Xaraya test site
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

    echo "<strong>$step. Importing topics</strong><br/>\n";

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
            echo "Insert topic ($tid) $title failed : " . xarExceptionRender('text') . "<br/>\n";
        } elseif ($count < 200) {
            echo "Inserted topic ($tid) $title<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted topic " . ($num + $startnum) . "<br/>\n";
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
    //echo "<strong>TODO : add comments etc.</strong><br/><br/>\n";
    echo '<a href="import_phpbb.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import_phpbb.php?step=' . $step . '&module=articles&startnum=' . $startnum . '">Go to step ' . $step . ' - articles ' . $startnum . '+ of ' . $count . '</a><br/>';
    } else {
        echo '<a href="import_phpbb.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    }
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['articles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories_linkage']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>