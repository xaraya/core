<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ queued stories into your Xaraya test site
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

    echo "<strong>$step. Importing queued articles</strong><br/>\n";

    $userid = unserialize(xarModGetVar('installer','userid'));
    $topics = xarModGetVar('installer','topics');
    $topicid = unserialize(xarModGetVar('installer','topicid'));
    $categories = xarModGetVar('installer','categories');
    $catid = unserialize(xarModGetVar('installer','catid'));

    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_queue';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count queue failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $regid = xarModGetIDFromName('articles');

    // Use different unix timestamp conversion function for 
    // MySQL and PostgreSQL databases
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
                $dbfunction = "UNIX_TIMESTAMP(pn_timestamp)";
            break;
        case 'postgres':
                $dbfunction = "DATE_PART('epoch',pn_timestamp)";
            break;
        default:
            die("Unknown database type");
            break;
    }

    $query = 'SELECT pn_qid, pn_subject, pn_story, pn_bodytext, pn_uid,
                     ' . $dbfunction . ', pn_language, pn_topic,
                     pn_arcd
              FROM ' . $oldprefix . '_queue
              ORDER BY pn_qid ASC';
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
        die("Oops, select queue failed : " . $dbconn->ErrorMsg());
    }
    $num = 1;
    while (!$result->EOF) {
        list($qid, $title, $summary, $body, $authorid, $pubdate, $language,
            $topic, $arcd) = $result->fields;
        if (empty($arcd)) {
            $status = 0;
        } else {
            $status = 1;
        }
        if (isset($userid[$authorid])) {
            $authorid = $userid[$authorid];
        } // else we're lost :)
        $notes = '';
        $cids = array();
        if (isset($topicid[$topic])) {
            $cids[] = $topicid[$topic];
        }
        $counter = 0;
        if (empty($title)) {
            $title = xarML('[none]');
        }
        $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array('title' => $title,
                                      'summary' => $summary,
                                      'body' => $body,
                                      'notes' => $notes,
                                      'status' => $status,
                                      'ptid' => 1,
                                      'pubdate' => $pubdate,
                                      'authorid' => $authorid,
                                      'language' => $language,
                                      'cids' => $cids,
                                      'hits' => $counter
                                     )
                               );
        if (!isset($newaid)) {
            echo "Insert queued article ($qid) $title failed : " . xarExceptionRender('text') . "<br/>\n";
        } elseif ($count < 200) {
            echo "Inserted queued article ($qid) $title<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted queued article " . ($num + $startnum) . "<br/>\n";
            flush();
        }
        $num++;
        $result->MoveNext();
    }
    $result->Close();
    //echo "<strong>TODO : add comments etc.</strong><br/><br/>\n";
    echo '<a href="import_pn.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import_pn.php?step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - articles ' . $startnum . '+ of ' . $count . '</a><br/>';
    } else {
        echo '<a href="import_pn.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    }

?>
