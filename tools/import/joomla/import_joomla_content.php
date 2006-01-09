<?php
/**
 * Quick & dirty import of Joomla 1.0.4+ content into Xaraya test sites
 *
 * @package tools
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 * @author MichelV <michelv@xaraya.com>
 */

/**
 * Note : this file is part of import_joomla.php and cannot be run separately
 */

    echo "<strong>$step. Importing from content to articles</strong><br/>\n";

    $userid = unserialize(xarModGetVar('installer','userid'));
    $topics = xarModGetVar('installer','topics');
    $topicid = unserialize(xarModGetVar('installer','topicid'));
    $categories = xarModGetVar('installer','categories');
    $catid = unserialize(xarModGetVar('installer','catid'));

    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_content';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count content stories failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $regid = xarModGetIDFromName('articles');
    // Use different unix timestamp conversion function for
    // MySQL and PostgreSQL databases
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
                $dbfunction = "UNIX_TIMESTAMP(created)";
            break;
        case 'postgres':
                $dbfunction = "DATE_PART('epoch',created)";
            break;
        default:
            die("Unknown database type");
            break;
    }//' . $dbfunction . '

    $query = 'SELECT `id`,
                     title,
                     introtext,
                     `fulltext`,
                     created_by,
                     created,
                     catid,
                     sectionid,
                     metadesc,
                     state,
                     hits
              FROM ' . $oldprefix . '_content
              ORDER BY id ASC ';
              //echo $query;
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
        list($aid, $title, $summary, $body, $authorid, $pubdate,
            $cat, $topic, $notes, $state, $counter) = $result->fields;

        // Rewrite state value
        if ($state == 1) {
            $status =2;
        } else {
            $status = 0;
        }
        // Now frontpage status
        $query = "SELECT COUNT(*)
              FROM ' . $oldprefix . '_content_frontpage
              WHERE content_id = $aid";
        $resultc =& $dbconn->Execute($query);
        if ($resultc==1 && $status == 2) {
            $status == 3;
        }
        //$resultc->Close();
        // Language
        $language = 'en_US.utf-8';

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
                                      'pubdate' => strtotime($pubdate),
                                      'authorid' => $authorid,
                                      'language' => $language,
                                      'cids' => $cids,
                                      'hits' => $counter
                                     )
                               );
        if (!isset($newaid) || $newaid != $aid) {
            echo "Insert article ($aid) $title failed : " . xarErrorRender('text') . "<br/>\n";
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
    //echo "<strong>TODO : add comments etc.</strong><br/><br/>\n";
    echo '<a href="import_joomla.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import_joomla.php?step=' . $step . '&module=articles&startnum=' . $startnum . '">Go to step ' . $step . ' - articles ' . $startnum . '+ of ' . $count . '</a><br/>';
    } else {
        echo '<a href="import_joomla.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    }
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['articles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories_linkage']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>