<?php
/**
 * File: $Id$
 *
 * Import Slashcode comments into your Xaraya test site
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

    echo "<strong>$step. Importing comments</strong><br/>\n";

    // Initialize table names
    $table_comments = 'comments';
    $table_comment_text = 'comment_text';
    $table_userids = xarDBGetSiteTablePrefix() . '_installer_userids';

    // Import comments
    $commentcount = xarModGetVar('installer','commentcount');
    echo "Found " . $commentcount . " comments<br/>\n";

    $regid = xarModGetIDFromName('articles');
/*
    $pid2cid = array();
    $pids = xarModGetVar('installer','commentid');
    if (!empty($pids)) {
        $pid2cid = unserialize($pids);
        $pids = '';
    }
*/

    // Use different unix timestamp conversion function for
    // MySQL and PostgreSQL databases
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
                $dbfunction = "UNIX_TIMESTAMP($table_comments.date)";
            break;
        case 'postgres':
                $dbfunction = "DATE_PART('epoch',$table_comments.date)";
            break;
        default:
            die("Unknown database type");
            break;
    }

    // Select all of the comments
    $query = "SELECT $table_comments.cid, 
                     $table_comments.sid,
                     $table_comments.pid,
                     $dbfunction, 
                     $table_comments.uid, 
                     $table_comments.subject, 
                     $table_comment_text.comment 
              FROM   $table_comments, $table_comment_text
              WHERE  $table_comments.cid = $table_comment_text.cid
              ORDER BY $table_comments.cid ASC";

    $numitems = xarModGetVar('installer','commentimport');
    if (!isset($startnum)) {
        $startnum = 0;
    }
    if ($commentcount > $numitems) {
        $result =& $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result =& $dbconn->Execute($query);
    }
    if (!$result) {
        die("Oops, select comments failed : " . $dbconn->ErrorMsg());
    }
    if ($reset && $startnum == 0) {
        $dbconn->Execute("DELETE FROM " . $tables['comments']);
    }

    $num = 1;
    while (!$result->EOF) {
        list($cid,
             $sid,
             $pid,
             $date,
             $uid,
             $subject,
             $comment) = $result->fields;

        // Retrieve Xaraya userid based on Slashcode uid
        $query2 = "SELECT xar_uid 
                   FROM   $table_userids
                   WHERE  slash_uid = $uid";
        $result2 =& $dbconn->Execute($query2);
        if (!$result2) {
            die("Oops, could not select user id from " . $table_userids . ": " . $dbconn->ErrorMsg());
        } 
        $authorid = $result2->fields[0];
        $result2->Close();

        if (empty($authorid) || $authorid < 6) {
            $authorid = _XAR_ID_UNREGISTERED;
        }
        $data = array();
        $data['modid'] = $regid;
        $data['itemtype'] = 1; // news articles
        $data['objectid'] = $sid;
/*
        if (!empty($pid) && !empty($pid2cid[$pid])) {
            $pid = $pid2cid[$pid];
        }
*/
        $data['pid'] = $pid;
        $data['author'] = $authorid;
        $data['title'] = $subject;
        $data['comment'] = $comment;
        $data['hostname'] = ''; // no hostname;
        //$data['cid'] = $cid;
        $data['date'] = $date;
        $data['postanon'] = 0;

        // Add comment
        $newcid = xarModAPIFunc('comments',
                                'user',
                                'add',
                                $data);

        if (empty($newcid)) {
            echo "Failed inserting comment ($sid $pid) $authorid - $subject : ".$dbconn->ErrorMsg()."<br/>\n";
        } elseif ($commentcount < 200) {
            echo "Inserted comment ($sid $pid) $authorid - $subject<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted comment " . ($num + $startnum) . "<br/>\n";
            flush();
        }
        //$pid2cid[$cid] = $newcid;
        $num++;
        $result->MoveNext();
    }
    $result->Close();

    echo '<a href="import_slashcode.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($commentcount > $numitems && $startnum + $numitems < $commentcount) {
        //xarModSetVar('installer','commentid',serialize($pid2cid));
        $startnum += $numitems;
        echo '<a href="import_slashcode.php?step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - comments ' . $startnum . '+ of ' . $commentcount . '</a><br/>';
    } else {
        xarModDelVar('installer','commentid');
        echo '<a href="import_slashcode.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    }

    // Optimize tables
    $dbconn->$dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
            $query = 'OPTIMIZE TABLE ' . $tables['comments'];
            $result =& $dbconn->Execute($query);
            break;
        case 'postgres':
            $query = 'VACUUM ANALYZE ' . $tables['comments'];
            $result =& $dbconn->Execute($query);
            break;
        default:
            break;
    }

?>
