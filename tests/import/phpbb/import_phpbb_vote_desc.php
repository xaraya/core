<?php
/**
 * File: $Id$
 *
 * Import phpBB vote descriptions into your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Note : this file is part of import_phpbb.php and cannot be run separately
 */

    echo "<strong>$step. Importing vote descriptions</strong><br>\n";

    if (!xarModIsAvailable('polls')) {
        echo "The polls module is not activated in Xaraya<br>\n";
        return;
    }

    $topics = xarModGetVar('installer','topicid');
    if (!isset($topics)) {
        $topicid = array();
    } else {
        $topicid = unserialize($topics);
    }
    $ptid = xarModGetVar('installer','ptid');

    $regid = xarModGetIDFromName('articles');

    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_vote_desc';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count votes failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();

    $query = 'SELECT vdesc.vote_id,topic_id,vote_text,vote_start,SUM(vote_result)
              FROM ' . $oldprefix . '_vote_desc as vdesc
              LEFT JOIN ' . $oldprefix . '_vote_results as vresults
                  ON vdesc.vote_id = vresults.vote_id
              GROUP BY vresults.vote_id
              ORDER BY vdesc.vote_id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select votes failed : " . $dbconn->ErrorMsg());
    }
    $pollid = array();
    $num = 1;
    while (!$result->EOF) {
        list($pid,$tid,$title,$time,$votes) = $result->fields;
        if (empty($title)) {
            $title = xarML('[none]');
        }
        if (!isset($topicid[$tid])) {
            echo "Unknown topic id $tid for vote $pid $title<br />\n";
            $num++;
            $result->MoveNext();
            continue;
        }
        $newpid = xarModAPIFunc('polls','admin','create',
                                array('title' => $title,
                                      'polltype' => 'single', // does phpBB support any other kind ?
                                      'private' => 0,
                                      'time' => $time,
                                      'module' => 'articles',
                                      'itemtype' => $ptid,
                                      'itemid' => $topicid[$tid],
                                      'votes' => $votes));
        if (empty($newpid)) {
            echo "Insert vote ($pid) $title failed : " . xarExceptionRender('text') . "<br>\n";
        } elseif ($count < 200) {
            echo "Inserted vote ($pid) $title<br>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted vote $num<br>\n";
            flush();
        }

        if (!empty($newpid)) {
            $pollid[$pid] = $newpid;
        }
        $num++;
        $result->MoveNext();
    }
    $result->Close();

?>
