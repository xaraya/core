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
 * Note : this file is part of import_xforum.php and cannot be run separately
 */

    echo "<strong>$step. Importing topics</strong><br/>\n";

    $users = xarModGetVar('installer','userid');
    if (!isset($users)) {
        $userid = array();
    } else {
        $userid = unserialize($users);
    }
    $categories = xarModGetVar('installer','categories');
    xarModSetVar('xarbb', 'mastercids', $categories);
    $catids = unserialize(xarModGetVar('installer','catid'));
    $forumids = unserialize(xarModGetVar('installer','forumid'));
    $threads = xarModGetVar('installer','topicid');
    if (!isset($threads)) {
        $threadids = array();
    } else {
        $threadids = unserialize($threads);
    }
    $posts = xarModGetVar('installer','postid');
    if (!isset($posts)) {
        $postids = array();
    } else {
        $postids = unserialize($posts);
    }
   // Get datbase setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_XForum_threads';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count threads failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    echo "Total number of threads is ".$count."<br /><br />";
    $result->Close();
    $regid = xarModGetIDFromName('xarbb');
    $query = 'SELECT t.tid, t.fid, t.subject, t.lastpost, t.views, t.replies, t.author, t.message, t.dateline, t.closed, t.topped, useip,bbcodeoff,m.uid,f.fup
              FROM ' . $oldprefix . '_XForum_threads as t
              LEFT JOIN ' . $oldprefix . '_XForum_forums as f
              ON f.fid = t.fid
              LEFT JOIN ' . $oldprefix . '_XForum_members as m
              ON m.username = t.author
              ORDER BY tid ASC';

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
        die("Oops, select threads failed : " . $dbconn->ErrorMsg());
    }

    $num = 1;
    while (!$result->EOF) {
        list($tid, $fid, $title, $lastpost, $views, $replies, $authorid, $text, $postdate, $closed, $topped, $userip, $bbcode, $uid, $fcid) = $result->fields;
        if (empty($title)) {
                $title = xarML('[none]');
        }
  //let's try and assign correct UID - may not exist yet if users not imported
        if (isset($userid[$uid])) {
            $uid = $userid[$uid];
        //} // else we're lost :)
        //if (empty($uid) || $uid < 2) {
       //    $uid=xarConfigGetVar('Site.User.AnonymousUID');            //make them all Anonymous
       // }
       // if ($uid ==2) {
       //Let's make the old PN admin user2 new Xar Admin
       //Assumes xaraya v0.9.8
       }else {
        $uid=xarModGetVar('roles','admin');
        }
       $fuid=$uid;

       //Let's try and find the last poster
       $lastposted=array();
       $lastposted  = explode('|',$lastpost);
       $lastposttime   = isset($lastposted[0]) ? $lastposted[0]:time();
       $lastpostuser = isset($lastposted[1])? $lastposted[1] : 'Admin';

       //Date of first post
       $firstpostdate   = isset($postdate) ? $postdate:time();
       //get the lastposter
       $oldmemberstable=$oldprefix."_XForum_members";
       $query2 = "SELECT uid
              FROM $oldmemberstable
              WHERE username = '".$lastpostuser."'";


       $result2 =& $dbconn->Execute($query2);
       if (!$result2) {
        die("Oops, select last poster failed : " . $dbconn->ErrorMsg());
       }
       for(; !$result2->EOF; $result2->MoveNext()) {
            $olduid=$result2->fields[0];
       }
       if (isset($userid[$olduid]))  {
            $luid= $userid[$olduid];
       }else {
         $luid=xarModGetVar('roles','admin');
       }
       //if (($olduid ==1)) {
       //     $luid=xarConfigGetVar('Site.User.AnonymousUID');//make them all Anonymous
      // }
      // if (($olduid ==2) || !isset($olduid)) {
       //Let's make the old PN admin user2 new Xar Admin
       //Assumes xaraya v0.9.8
      //  $luid=xarModGetVar('roles','admin');
      // }
       //Work out status
       if ($topped==1) {
           $tstatus=2;
       }elseif ($closed==1) {
           $tstatus=3;
       }else {
           $tstatus=0;
       }
       $newtid=xarModAPIFunc('xarbb',
                               'user',
                               'createtopic',
                               array('fid'      => $forumids[$fid],
                                     'ttitle'   => $title,
                                     'tpost'    => $text,
                                     'tposter'  => $fuid,
                                     'ttime'    => $lastposttime,
                                     'tftime'   => $firstpostdate,
                                     'treplies' => $replies,
                                     'treplier' => $luid,
                                     'tstatus'  => $tstatus));

       echo "The new topic for $title is ". $newtid."<br />";

  /*      if (!isset($newtid[$tid])) {
            echo "Insert topic ($tid) $title failed : " . xarErrorRender('text') . "<br/>\n";
        } elseif ($count < 200) {
            echo "Inserted topic ($tid) $title<br/>\n";
      } elseif ($num % 100 == 0) {
          echo "Inserted topic " . ($num + $startnum) . "<br/>\n";
          flush();
        }
    if (!empty($newtid)) {
            $threadids[$tid] = $newtid;
    } */

        $threadids[$tid] = $newtid;
        $postids[$tid] = $newtid;
        $num++;

       $result->MoveNext();
    }
    $result->Close();
    xarModSetVar('installer','topicid',serialize($threadids));
    xarModSetVar('installer','postid',serialize($postids));
    //echo "<strong>TODO : add comments etc.</strong><br/><br/>\n";
    echo '<a href="import_xforum.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import_xforum.php?step=' . $step . '&module=articles&startnum=' . $startnum . '">Go to step ' . $step . ' - articles ' . $startnum . '+ of ' . $count . '</a><br/>';
    } else {
        echo '<a href="import_xforum.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    }
//    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['articles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories_linkage']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>
