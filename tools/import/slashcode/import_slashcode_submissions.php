<?php
/**
 * File: $Id$
 *
 * Import Slashcode submissions into your Xaraya test site
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

    echo "<strong>$step. Importing submissions</strong><br/>\n";

    //$userid = unserialize(xarModGetVar('installer','userid'));
    $categories_cid = xarModGetVar('installer','categories_cid');
    $categories = unserialize(xarModGetVar('installer','categories'));

    // Initialize table names
    $table_submissions = 'submissions';
    $table_userids = xarDBGetSiteTablePrefix() . '_installer_userids';

    // Import submissions
    $submissioncount = xarModGetVar('installer','submissioncount');
    echo "Found " . $submissioncount . " submissions<br/>\n";

    $regid = xarModGetIDFromName('articles');

    // Use different unix timestamp conversion function for
    // MySQL and PostgreSQL databases
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
                $dbfunction = "UNIX_TIMESTAMP($table_submissions.time)";
            break;
        case 'postgres':
                $dbfunction = "DATE_PART('epoch',$table_submissions.time)";
            break;
        default:
            die("Unknown database type");
            break;
    }

    // Select all of the stories
    $query = "SELECT $table_submissions.subid, 
                     $table_userids.xar_uid,
                     $table_submissions.tid,
                     $table_submissions.subj, 
                     $table_submissions.story, 
                     $table_submissions.comment,
                     $dbfunction,
                     $table_submissions.del
              FROM   $table_submissions
              LEFT JOIN $table_userids
                     ON $table_submissions.uid = $table_userids.slash_uid
              ORDER BY $table_submissions.subid ASC";

    $numitems = xarModGetVar('installer','submissionimport');
    if (!isset($startnum)) {
        $startnum = 0;
    }
    if ($submissioncount > $numitems) {
        $result =& $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result =& $dbconn->Execute($query);
    }
    if (!$result) {
        die("Oops, select stories failed : " . $dbconn->ErrorMsg());
    }

    $num = 1;
    $language = xarMLSGetCurrentLocale();
    $summary = ''; // no summary in submissions

    while (!$result->EOF) {
        list($subid, 
             $authorid,
             $tid,
             $subj, 
             $story, 
             $comment, 
             $time,
             $del) = $result->fields;

        // Set status of the new article to submission
        $status = 0;

        // Check if submission was flagged for deletion
        if ($del = 1 ) {
            $status = 1;
        }

        // Check if userid set
        if (empty($authorid) || $authorid < 6) {
            $authorid = _XAR_ID_UNREGISTERED;
        }
        $cids = array();
        // Check if topic id set
        if ($tid != 0) {
            if (isset($categories[$tid])) {
                $cids[] = $categories[$tid];
            }
        }
        if (empty($subj)) {
            $subj = xarML('[none]');
        }
    // Note: we don't try to use the same article id as the old submission id here
        $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array(//'aid' => $subid,
                                      'title' => $subj,
                                      'summary' => $summary, 
                                      'body' => $story,
                                      'notes' => $comment,
                                      'status' => $status,
                                      'ptid' => 1,
                                      'pubdate' => $time,
                                      'authorid' => $authorid,
                                      'language' => $language, 
                                      'cids' => $cids,
                                      'hits' => 0
                                     )
                               );
        if (empty($newaid)) {
            echo "Insert submission ($subid) $title failed :";
            xarErrorRender('text');
            echo "<br/>\n";
            xarErrorHandled();
        } elseif ($submissioncount < 200) {
            echo "Inserted submission ($subid) $title<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted submission " . ($num + $startnum) . "<br/>\n";
            flush();
        }
        // we're not interested in associating newaid with subid either, since
        // submissions should have no comments etc. attached
        //$submissions[$subid] = $newaid;
        $num++;

        $result->MoveNext();
    }
    $result->Close();

    echo '<a href="import_slashcode.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($submissioncount > $numitems && $startnum + $numitems < $submissioncount) {
        $startnum += $numitems;
        echo '<a href="import_slashcode.php?step=' . $step . '&module=articles&startnum=' . $startnum . '">Go to step ' . $step . ' - articles ' . $startnum . '+ of ' . $submissioncount . '</a><br/>';
    } else {
        echo '<a href="import_slashcode.php?step=' . ($step+1) . '&module=polls">Go to step ' . ($step+1) . '</a><br/>';
    }

    // Optimize tables
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
            $query = 'OPTIMIZE TABLE ' . $tables['articles'];
            $result =& $dbconn->Execute($query);
            $query = 'OPTIMIZE TABLE ' . $tables['categories_linkage'];
            $result =& $dbconn->Execute($query);
            if (!empty($docounter)) {
                $query = 'OPTIMIZE TABLE ' . $tables['hitcount'];
                $result =& $dbconn->Execute($query);
            }
            break;
        case 'postgres':
            $query = 'VACUUM ANALYZE ' . $tables['articles'];
            $result =& $dbconn->Execute($query);
            $query = 'VACUUM ANALYZE ' . $tables['categories_linkage'];
            $result =& $dbconn->Execute($query);
            if (!empty($docounter)) {
            $query = 'VACUUM ANALYZE ' . $tables['hitcount'];
            $result =& $dbconn->Execute($query);
            }
            break;
        default:
            break;
    }
?>
