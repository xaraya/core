<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ poll comments into your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Note : this file is part of import_pn.php and cannot be run separately
 */

    echo "<strong>$step. Importing old poll comments</strong><br/>\n";

    if (!xarModIsAvailable('polls')) {
        echo "The polls module is not activated in Xaraya<br/>\n";
        $step++;
        return;
    }

    $users = xarModGetVar('installer','userid');
    if (!isset($users)) {
        $userid = array();
    } else {
        $userid = unserialize($users);
    }
    $regid = xarModGetIDFromName('polls');
    $pid2cid = array();
// TODO: fix issue for large # of poll comments
/*
    $pids = xarModGetVar('installer','commentid');
    if (!empty($pids)) {
        $pid2cid = unserialize($pids);
        $pids = '';
    }
*/
    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_pollcomments';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count poll comments failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $query = 'SELECT pn_tid, pn_pollid, pn_pid, UNIX_TIMESTAMP(pn_date), pn_uname, pn_uid,
              pn_host_name, pn_subject, pn_comment 
              FROM ' . $oldprefix . '_pollcomments 
              LEFT JOIN ' . $oldprefix . '_users
              ON ' . $oldprefix . '_users.pn_uname = ' . $oldprefix . '_pollcomments.pn_name
              ORDER BY pn_tid ASC';
/* if you try to match against Xaraya users someday
    $query = 'SELECT pn_tid, pn_pollid, pn_pid, UNIX_TIMESTAMP(pn_date), xar_uname, xar_uid,
              pn_host_name, pn_subject, pn_comment 
              FROM ' . $oldprefix . '_pollcomments 
              LEFT JOIN ' . $tables['roles'] . '
              ON ' . $tables['roles'] . '.xar_uname = ' . $oldprefix . '_pollcomments.pn_name
              ORDER BY pn_tid ASC';
*/
// TODO: fix issue for large # of poll comments
//    $numitems = 1500;
    $numitems = $count;
    if (!isset($startnum)) {
        $startnum = 0;
    }

    if ($count > $numitems) {
        $result =& $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result =& $dbconn->Execute($query);
    }
    if (!$result) {
        die("Oops, select poll comments failed : " . $dbconn->ErrorMsg());
    }
    $num = 1;
    while (!$result->EOF) {
        list($tid,$sid,$pid,$date,$uname,$uid,$hostname,$subject,$comment) = $result->fields;

        if (!isset($pollid[$sid])) {
            echo "Unknown poll id $sid for comment ($tid) $subject<br/>\n";
            $num++;
            $result->MoveNext();
            continue;
        }

        if (isset($userid[$uid])) {
            $uid = $userid[$uid];
        } // else we're lost :)
        if (empty($uid) || $uid < 2) {
            $uid = _XAR_ID_UNREGISTERED;
        }
        $data = array();
        $data['modid'] = $regid;
        $data['objectid'] = $pollid[$sid];
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
            echo "Failed inserting poll comment ($sid $pid) $uname - $subject : ".$dbconn->ErrorMsg()."<br/>\n";
        } elseif ($count < 200) {
            echo "Inserted poll comment ($sid $pid) $uname - $subject<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted poll comment " . ($num + $startnum) . "<br/>\n";
            flush();
        }
        $pid2cid[$tid] = $cid;
        $num++;
        $result->MoveNext();
    }
    $result->Close();
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['comments']);

    echo '<a href="import_pn.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_pn.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    // Enable comments hooks for polls
    xarModAPIFunc('modules','admin','enablehooks',
                  array('callerModName' => 'polls', 'hookModName' => 'comments'));

?>
