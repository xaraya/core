<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ stories into your Xaraya test site
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

    echo "<strong>$step. Importing articles</strong><br/>\n";

    $userid = unserialize(xarModGetVar('installer','userid'));
    $topics = xarModGetVar('installer','topics');
    $topicid = unserialize(xarModGetVar('installer','topicid'));
    $categories = xarModGetVar('installer','categories');
    $catid = unserialize(xarModGetVar('installer','catid'));

    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_stories';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count stories failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $regid = xarModGetIDFromName('articles');

    // Use different unix timestamp conversion function for 
    // MySQL and PostgreSQL databases
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
                $dbfunction = "UNIX_TIMESTAMP(time)";
            break;
        case 'postgres':
                $dbfunction = "DATE_PART('epoch',time)";
            break;
        default:
            die("Unknown database type");
            break;
    }

    $query = 'SELECT sid, title, hometext, bodytext, aid,
                     ' . $dbfunction . ', alanguage, catid, topic,
                     notes, ihome, counter
              FROM ' . $oldprefix . '_stories
              ORDER BY sid ASC';
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
        die("Oops, select stories failed : " . $dbconn->ErrorMsg());
    }
    if ($reset && $startnum == 0) {
        $dbconn->Execute("DELETE FROM " . $tables['articles']);
        //$dbconn->Execute('FLUSH TABLE ' . $tables['articles']);
    }
    if (!empty($docounter)) {
        if ($reset && $startnum == 0) {
            $dbconn->Execute("DELETE FROM " . $tables['hitcount'] . " WHERE xar_moduleid = " . $regid);
            //$dbconn->Execute('FLUSH TABLE ' . $tables['hitcount']);
        }
    }
    $num = 1;
    while (!$result->EOF) {
        list($aid, $title, $summary, $body, $authorid, $pubdate, $language,
            $cat, $topic, $notes, $ihome, $counter) = $result->fields;
        if (empty($ihome)) {
            $status = 3;
        } else {
            $status = 2;
        }
        if (isset($userid[$authorid])) {
            $authorid = $userid[$authorid];
        } // else we're lost :)
        if (empty($authorid) || $authorid < 2) {
            $authorid = _XAR_ID_UNREGISTERED;
        }
        $cids = array();
        if (isset($topicid[$topic])) {
            $cids[] = $topicid[$topic];
        }
        if (isset($catid[$cat])) {
            $cids[] = $catid[$cat];
        }
        if (empty($title)) {
            $title = xarML('[none]');
        }
        $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array('aid' => $aid,
                                      'title' => $title,
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
        if (!isset($newaid) || $newaid != $aid) {
            echo "Insert article ($aid) $title failed : " . xarExceptionRender('text') . "<br/>\n";
        } elseif ($count < 200) {
            echo "Inserted article ($aid) $title<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted article " . ($num + $startnum) . "<br/>\n";
            flush();
        }
        $num++;

        $result->MoveNext();
    }
    $result->Close();

    // If we're importing to the PostgreSQL database, then we need
    // to create a sequence value for seqxar_articles that starts
    // at the last sid from nuke_stories.  Otherwise the next import
    // into xar_articles will fail because the aid already exists.
    // This isn't a problem for MySQL as it has an auto_increment column.
    if ($dbtype == 'postgres') {
        $dbconn->GenID($tables['articles'], $aid);
    }

    //echo "<strong>TODO : add comments etc.</strong><br/><br/>\n";
    echo '<a href="import_nuke.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import_nuke.php?step=' . $step . '&module=articles&startnum=' . $startnum . '">Go to step ' . $step . ' - articles ' . $startnum . '+ of ' . $count . '</a><br/>';
    } else {
        echo '<a href="import_nuke.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    }
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['articles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories_linkage']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>
