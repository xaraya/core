<?php
/**
 * File: $Id$
 *
 * Import Slashcode stories into your Xaraya test site
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

    echo "<strong>$step. Importing Slashcode poll answers</strong><br/>\n";

    if (!xarModIsAvailable('polls')) {
        echo "The polls module is not activated in Xaraya<br/>\n";
        return;
    }

    // Set table names
    $table_pollanswers = 'pollanswers';

    // Count number of poll answers
    $query = 'SELECT COUNT(*) FROM ' . $table_pollanswers;
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count of " . $table_pollanswers . " failed : " . $dbconn->ErrorMsg());
    } 
    $pollcount = $result->fields[0];
    $result->Close();

    echo "Found " . $pollcount . " poll answers<br/>\n";

    // Select poll answers
    $query = "SELECT qid,
                     aid,
                     answer,
                     votes
              FROM   $table_pollanswers
              ORDER BY qid ASC, aid ASC";

    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select from " . $table_pollanswers . "  failed : " . $dbconn->ErrorMsg());
    }

    $num = 1;
    while (!$result->EOF) {
        list($qid,
             $aid,
             $answer,
             $votes) = $result->fields;

        if ($answer === '') {
            $num++;
            $result->MoveNext();
            continue;
        } elseif (!isset($pollid[$qid])) {
            echo "Unknown poll id $qid for option $answer<br />\n";
            $num++;
            $result->MoveNext();
            continue;
        }

        // Create new poll
        $newvid = xarModAPIFunc('polls',
                                'admin',
                                'createopt',
                                array('pid' => $pollid[$qid],
                                      'option' => $answer,
                                      'votes' => $votes));

        if (empty($newvid)) {
            echo "Insert poll option ($qid $aid) $answer failed :";
            xarErrorRender('text');
            echo "<br/>\n";
            xarErrorHandled();
        } elseif ($pollcount < 100) {
            echo "Inserted poll option ($qid $aid) $answer<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted poll option $num<br/>\n";
            flush();
        }
        $num++;
        $result->MoveNext();
    }
    $result->Close();

    echo '<a href="import_slashcode.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_slashcode.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';

    // Enable comments hooks for polls
    xarModAPIFunc('modules',
                  'admin',
                  'enablehooks',
                  array('callerModName' => 'polls', 'hookModName' => 'comments'));

    // Optimize tables
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
            $query = 'OPTIMIZE TABLE ' . $tables['polls'];
            $result =& $dbconn->Execute($query);
            $query = 'OPTIMIZE TABLE ' . $tables['polls_info'];
            $result =& $dbconn->Execute($query);
            break;
        case 'postgres':
            $query = 'VACUUM ANALYZE ' . $tables['polls'];
            $result =& $dbconn->Execute($query);
            $query = 'VACUUM ANALYZE ' . $tables['polls_info'];
            $result =& $dbconn->Execute($query);
            break;
        default:
            break;
    }

?>
