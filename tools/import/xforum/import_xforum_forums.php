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
 //
    echo "<strong>$step. Creating xForum forums and allocating to categories</strong><br/>\n";

   $forumusers= xarModGetVar('installer','userid');
    if (!isset($forumusers)) {
     //   $forumusers = array();
    } else {
        $forumusers = unserialize($forumusers);
    }
   // Get datbase setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $catids= unserialize(xarModGetVar('installer', 'catid'));
    $query = 'SELECT fid, name, status, lastpost,description, displayorder, posts, threads, fup
              FROM `'.$oldprefix.'_XForum_forums`
              WHERE type=\'forum\' OR type=\'sub\'
              ORDER BY fup ASC, fid ASC';

    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select forums failed : " . $dbconn->ErrorMsg());
    }

    $forumid = array();
    $catinfo = array();
    while (!$result->EOF) {
        list($id, $name, $status, $lastpost, $descr, $order, $fposts, $fthreads, $fup) = $result->fields;
        if (!isset($catids[$fup])) {
            echo "Oops - no category id for $id<br />\n";
        }

        if ((empty($fup)) || ($fup==0)){
            //This is an xforum category - don't worry about forum
            //$cids[$id] = xarModGetVar('xarbb', 'mastercids.1');
        } else {
            $cids[$id]=array($catids[$fup]);
        }
        if (empty($fposts) || !isset($fposts)) {
            $fposts=0;
        }
        if (empty($fthreads) || !isset($fthreads)) {
            $fthreads=0;
        }

        if ((!empty($fup)) && ($fup<>0)){ //This is a forum so let's process it
            //Let's try and get the last poster and last post date if this is a forum
            $lastposted =array();
            $lastposted = explode('|',$lastpost);
            $lastposttime =(isset($lastposted[0]) && !empty($lastposted[0])) ? $lastposted[0]:time();
            $lastpostuser =(isset($lastposted[1]) && !empty($lastposted[1]))? $lastposted[1] : 'Admin';

            $oldmemberstable=$oldprefix."_XForum_members";
            $query2 = "SELECT uid
                      FROM $oldmemberstable
                      WHERE username = '".$lastpostuser."'";

            $result2=& $dbconn->Execute($query2);

            if (!$result2) {
                die("Oops, select last poster failed : " . $dbconn->ErrorMsg());
            }
            for(; !$result2->EOF; $result2->MoveNext()) {
                $olduid=$result2->fields[0];
            }
            if (isset($forumusers[$olduid])) {
                 $lastposter = $forumusers[$olduid];
            } else { //we are in trouble
                $lastposter=xarConfigGetVar('Site.User.AnonymousUID');
            }
            // if ($olduid== 1) {
            //      $lastposter=xarConfigGetVar('Site.User.AnonymousUID');   //make them all Anonymous
            // }
            // if (($olduid ==2) || !isset($olduid)) {
            //Assumes xaraya v0.9.8
            //  $lastposter=xarModGetVar('roles','admin');
            // }

            //Get the forum status
            $fstatus =(isset($status) && ($status=='on'))? 0: 1;

            // The API function is called
            // Hmmm - can't directly create all new data in forum here with current xarBB function
            // heh - just modifed the function so we can pass in values
            $forumid[$id]=xarModAPIFunc('xarbb',
                               'admin',
                               'create',
                               array('fname'    => $name,
                                     'fdesc'    => $descr,
                                     'cids'     => $cids[$id],
                                     'ftopics'  => $fthreads,
                                     'fposts'   => $fposts,
                                     'fposter'  => $lastposter,
                                     'fpostid'  => $lastposttime,
                                     'fstatus'  => $fstatus)); //Set all forum creation to Admin


            if ($forumid[$id]) {
                echo "Creating forum ($fup) ->$forumid[$id] - $name - $descr<br/>\n";
            }else {
                echo "Problem with forum ". $forumid[$id];
            }

            $result->MoveNext();
        }
    }
    $result->Close();
    xarModSetVar('installer','forumid',serialize($forumid));
    $cats=xarModGetVar('installer','categories');
    //Why is base categories getting lost ...
    xarModSetVar('xarbb', 'number_of_categories.1', 1);
    xarModSetVar('xarbb', 'mastercids.1', $xarbbcats);

    echo '<a href="import_xforum.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_xforum.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>
