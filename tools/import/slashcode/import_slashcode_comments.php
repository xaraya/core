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
    $table_discussions = 'discussions';
    $table_stories = 'stories';
    $table_userids = xarDBGetSiteTablePrefix() . '_installer_userids';

    $table_commentids = xarDBGetSiteTablePrefix() . '_installer_commentids';
    
    // Because the comments table could contain a large number of entries (+100,000),
    // create a new table to store the Slashcode and Xaraya comment ids.

    // In case the commentids table exits, drop the table
    if (empty($startnum)) {
        $dbconn->Execute("DROP TABLE " . $table_commentids);

        // Create topic tree table
        $fields = array(
            'slash_cid'   => array('type'=>'integer','null'=>FALSE),
            'xar_cid'     => array('type'=>'integer','null'=>FALSE)
        );

        // Create the table DDL
        $query = xarDBCreateTable($table_commentids,$fields);
        if (empty($query)) {
            echo "Couldn't create query for table $table_commentids<br/>\n";
            return; // throw back
        }

        // Pass the Table Create DDL to adodb to create the table
        $dbconn->Execute($query);

        // Check for an error with the database
        if ($dbconn->ErrorNo() != 0) {
            die("Oops, create of table " . $table_commentids . " failed : " . $dbconn->ErrorMsg());
        }

        // Add index for slash_cid
        $query = xarDBCreateIndex($table_commentids,
                                  array('name'   => 'i_' . $table_commentids,
                                        'fields' => array('slash_cid')));
        if (empty($query)) return; // throw back
        $result = $dbconn->Execute($query);
        if (!isset($result)) return;
    }

    // Import comments
    $commentcount = xarModGetVar('installer','commentcount');
    echo "Found " . $commentcount . " comments<br/>\n";

    $artid = xarModGetIDFromName('articles');
    $pollid = xarModGetIDFromName('polls');

    $polldiscussions = unserialize(xarModGetVar('installer','polldiscussions'));

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

    // Select all of the comments (for stories only)
    $query = "SELECT $table_comments.cid, 
                     $table_comments.sid,
                     $table_comments.pid,
                     $dbfunction, 
                     $table_userids.xar_uid,
                     $table_comments.subject, 
                     $table_comment_text.comment, 
                     $table_discussions.stoid
              FROM   $table_comments
              LEFT JOIN $table_comment_text
                     ON $table_comments.cid = $table_comment_text.cid
              LEFT JOIN $table_discussions
                     ON $table_comments.sid = $table_discussions.id
              LEFT JOIN $table_userids
                     ON $table_comments.uid = $table_userids.slash_uid
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

    // Retrieve Xaraya cid based on Slashcode cid
    $query2 = "SELECT xar_cid 
               FROM   $table_commentids
               WHERE  slash_cid = ?";

    // Add cid to the temporary commentids table
    $query3 = "INSERT INTO $table_commentids (xar_cid,slash_cid) VALUES (?,?)";

    $num = 1;
    while (!$result->EOF) {
        list($cid,
             $sid,
             $pid,
             $date,
             $authorid,
             $subject,
             $comment,
             $storyid) = $result->fields;

        if (empty($authorid) || $authorid < 6) {
            $authorid = _XAR_ID_UNREGISTERED;
        }
        $data = array();

        // it's a comment on a story
        if (!empty($storyid)) {
            $data['modid'] = $artid;
            $data['itemtype'] = 1; // news articles
        // Note: we try to use the same article id as the old story id here
            $data['objectid'] = $storyid;

        // it's a comment on a poll
        } elseif (!empty($polldiscussions[$sid])) {
            $data['modid'] = $pollid;
            $data['itemtype'] = 0; // polls
            $data['objectid'] = $polldiscussions[$sid];

        // it's a comment on something else
        } else {
            $num++;
            $result->MoveNext();
            continue;
        }

        if (!empty($pid)) {
            $result2 =& $dbconn->Execute($query2,array((int)$pid));
            if (!$result2) {
                die("Oops, could not select comment id from " . $table_commentids . ": " . $dbconn->ErrorMsg());
            } 
            if (!$result->EOF) {
                $pid = $result2->fields[0];
            }
            $result2->Close();
        }
        if (empty($pid)) {
            $pid = 0;
        }

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
            echo "Failed inserting comment $cid ($sid $pid) $authorid - $subject :";
            xarErrorRender('text');
            echo "<br/>\n";
            xarErrorHandled();
        } elseif ($commentcount < 200) {
            echo "Inserted comment ($sid $pid) $authorid - $subject<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted comment " . ($num + $startnum) . "<br/>\n";
            flush();
        }

        $result3 =& $dbconn->Execute($query3,array((int)$newcid,(int)$cid));
        if (!$result3) {
            die("Oops, could not insert comment id in " . $table_commentids . ": " . $dbconn->ErrorMsg());
        } 
        $num++;
        $result->MoveNext();
    }
    $result->Close();

    echo '<a href="import_slashcode.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($commentcount > $numitems && $startnum + $numitems < $commentcount) {
        $startnum += $numitems;
        echo '<a href="import_slashcode.php?step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - comments ' . $startnum . '+ of ' . $commentcount . '</a><br/>';
    } else {
        xarModDelVar('installer','commentid');
        echo '<a href="import_slashcode.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    }

    // Optimize tables
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
            $query = 'OPTIMIZE TABLE ' . $tables['comments'];
            $result =& $dbconn->Execute($query);
            $query = 'OPTIMIZE TABLE ' . $table_commentids;
            $result =& $dbconn->Execute($query);
            break;
        case 'postgres':
            $query = 'VACUUM ANALYZE ' . $tables['comments'];
            $result =& $dbconn->Execute($query);
            $query = 'VACUUM ANALYZE ' . $table_commentids;
            $result =& $dbconn->Execute($query);
            break;
        default:
            break;
    }

?>
