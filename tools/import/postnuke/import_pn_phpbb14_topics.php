<?php
/**
 * Import phpBB_14 module topics into your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 * @author voll <voll@xaraya.com>
 */

/**
 * Note : this file is part of import_pn.php and cannot be run separately
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
    $postid = array();
    $ptid = xarModGetVar('installer','ptid');

    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_phpbb14_topics';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count topics failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();

    $numitems = 1000;
    if (!isset($startnum)) {
        $startnum = 0;
    }
if ($importmodule != 'articles') {
// get last poster and time
    $query = 'SELECT t.topic_id,t.topic_last_post_id,p.poster_id,t.topic_time
              FROM ' . $oldprefix . '_phpbb14_topics as t
              LEFT JOIN ' . $oldprefix . '_phpbb14_posts as p
                  ON t.topic_last_post_id=p.post_id
              ORDER BY t.topic_id ASC';
    if ($count > $numitems) {
        $result =& $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result =& $dbconn->Execute($query);
    }
    if (!$result) {
        die("Oops, select topics failed : " . $dbconn->ErrorMsg());
    }
    $num = 1;
    $lastuid = array();
    $lasttime = array();
    while (!$result->EOF) {
        list($tid, $pid, $uid, $time) = $result->fields;
        if (isset($userid[$uid])) {
            $lastuserid = $userid[$uid];
        } // else we're lost :)
        if (empty($lastuserid) || $lastuserid < 2) {
            $lastuserid = _XAR_ID_UNREGISTERED;
        }
        $lastuid[$tid] = $lastuserid;
        $lasttime[$tid] = strtotime($time);
        $result->MoveNext();
    }
    $result->Close();
}

    $query = 'SELECT t.topic_id,t.forum_id,t.topic_title,t.topic_poster,
              p.post_time,t.topic_views,t.topic_replies,t.topic_status,
              t.topic_last_post_id,pt.post_text,p.poster_ip,MIN(p.post_id)
              FROM ' . $oldprefix . '_phpbb14_topics as t
              LEFT JOIN ' . $oldprefix . '_phpbb14_posts as p
                  ON t.topic_id=p.topic_id
              LEFT JOIN ' . $oldprefix . '_phpbb14_posts_text as pt
                  ON pt.post_id=p.post_id
              GROUP BY t.topic_id
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
        list($tid, $fid, $title, $authorid, $timef, $views, $replies, $status, $lastid, $text, $ip, $firstpostid) = $result->fields;
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

        $time = strtotime($timef);
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
// TODO: other status values ?
        if ($status == 1) {
            $status = 3; // locked
        }
if ($importmodule == 'articles') {
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
} else {
        if (empty($forumid[$fid])) {
            $forumid[$fid] = 1; // oops
            echo "Invalid forum id $fid for topic ($tid) $title<br/>\n";
        }
        if (empty($lastuid[$tid])) {
            $lastuid[$tid] = $authorid;
        }
        if (empty($lasttime[$tid])) {
            $lasttime[$tid] = $time;
        }
        $newaid=xarModAPIFunc('xarbb',
                               'user',
                               'createtopic',
                               array('fid'      => $forumid[$fid],
                                     'ttitle'   => $title,
                                     'tpost'    => $text,
                                     'tposter'  => $authorid,
                                     'ttime'    => $lasttime[$tid],
                                     'tftime'   => $time,
                                     'treplies' => $replies,
                                     'treplier' => $lastuid[$tid],
                                     'thostname' => $ip,
                                     'tstatus'  => $status,
                                     // this will be passed to the hitcount create hook
                                     'hits'     => $views));
}
        if (!isset($newaid)) {
            echo "Insert topic ($tid) $title failed : " . xarErrorRender('text') . "<br/>\n";
        } elseif ($count < 1000) {
            echo "Inserted topic ($tid - $newaid) $title<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted topic " . ($num + $startnum) . "<br/>\n";
            flush();
        }
        if (!empty($newaid)) {
            $topicid[$tid] = $newaid;
            $postid[$firstpostid] = $newaid;
        }
        $num++;

        $result->MoveNext();
    }
    $result->Close();

    xarModSetVar('installer','topicid',serialize($topicid));
    xarModSetVar('installer','postid',serialize($postid));
    //echo "<strong>TODO : add comments etc.</strong><br/><br/>\n";
    echo '<a href="import_pn.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import_pn.php?step=' . $step . '&module=' . $importmodule . '&startnum=' . $startnum . '">Go to step ' . $step . ' - topics ' . $startnum . '+ of ' . $count . '</a><br/>';
        flush();
// auto-step
        echo "<script>
document.location = '" . xarServerGetBaseURL() . 'import_pn.php?step=' . $step . '&module=' . $importmodule . '&startnum=' . $startnum . "'
</script>";
    } else {
        echo '<a href="import_pn.php?step=' . ($step+1) . '&module=' . $importmodule . '">Go to step ' . ($step+1) . '</a><br/>';
    }
if ($importmodule == 'articles') {
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['articles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories_linkage']);
} else {
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['xbbtopics']);
}
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>