<?php
/**
 * File: $Id$
 *
 * Import phpBB vote results into your Xaraya test site
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

    echo "<strong>$step. Importing vote results</strong><br/>\n";

    if (!xarModIsAvailable('polls')) {
        echo "The polls module is not activated in Xaraya<br/>\n";
        $step++;
        return;
    }

    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_vote_results';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count vote results failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();

    $query = 'SELECT vote_id, vote_option_text, vote_result, vote_option_id
              FROM ' . $oldprefix . '_vote_results
              ORDER BY vote_id ASC, vote_option_id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select vote results failed : " . $dbconn->ErrorMsg());
    }
    $num = 1;
    while (!$result->EOF) {
        list($pid,$text,$count,$vid) = $result->fields;
        if ($text === '') {
            $num++;
            $result->MoveNext();
            continue;
        } elseif (!isset($pollid[$pid])) {
            echo "Unknown vote id $pid for option $text<br />\n";
            $num++;
            $result->MoveNext();
            continue;
        }
        $newvid = xarModAPIFunc('polls','admin','createopt',
                                array('pid' => $pollid[$pid],
                                      'option' => $text,
                                      'votes' => $count));
        if (empty($newvid)) {
            echo "Insert vote result ($pid $vid) $text failed : " . xarExceptionRender('text') . "<br/>\n";
        } elseif ($count < 100) {
            echo "Inserted vote result ($pid $vid) $text<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted vote result $num<br/>\n";
            flush();
        }
        $num++;
        $result->MoveNext();
    }
    $result->Close();
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['polls']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['polls_info']);
    echo '<a href="import_phpbb.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    echo '<a href="import_phpbb.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';

if ($importmodule == 'articles') {
    // Enable polls hooks for 'forums' pubtype of articles
    xarModAPIFunc('modules','admin','enablehooks',
                  array('callerModName' => 'articles', 'callerItemType' => $ptid, 'hookModName' => 'polls'));
} else {
    // Enable polls hooks for all forums in xarbb
    xarModAPIFunc('modules','admin','enablehooks',
                  array('callerModName' => 'xarbb', 'hookModName' => 'polls'));
}

?>
