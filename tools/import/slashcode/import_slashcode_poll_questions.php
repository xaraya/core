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

    echo "<strong>$step. Importing Slashcode poll questions</strong><br/>\n";

    if (!xarModIsAvailable('polls')) {
        echo "The polls module is not activated in Xaraya<br/>\n";
        $step++;
        return;
    }

    // Set table names
    $table_pollquestions = 'pollquestions';

    $query = 'SELECT COUNT(qid) FROM ' . $table_pollquestions;
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count of " . $table_pollquestions . " failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();

    // Remove all polls if reset
    if ($reset) {
        $dbconn->Execute("DELETE FROM " . $tables['polls']);
        $dbconn->Execute("DELETE FROM " . $tables['polls_info']);
    }

    // Use different unix timestamp conversion function for
    // MySQL and PostgreSQL databases
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
                $dbfunction = "UNIX_TIMESTAMP($table_pollquestions.date)";
            break;
        case 'postgres':
                $dbfunction = "DATE_PART('epoch',$table_pollquestions.date)";
            break;
        default:
            die("Unknown database type");
            break;
    }

    // Select all pollquestions
    $query = "SELECT qid,
                     question,
                     voters,
                     topic,
                     discussion,
                     $dbfunction,
                     uid,
                     flags,
                     polltype
              FROM   $table_pollquestions";

    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select polls failed : " . $dbconn->ErrorMsg());
    }

    $pollid = array();
    $num = 1;
    while (!$result->EOF) {
        list($qid,
             $question,
             $voters,
             $topic,
             $discussion,
             $date,
             $uid,
             $flags,
             $polltype) = $result->fields;

        if (empty($question)) {
            $question = xarML('[none]');
        }

        // Set status of the new article
        switch ($flags) {
            case 'delete':
            case 'dirty': // TODO - what about 'dirty' stories???
                // Set status to Rejected
                $status = 1;
                break;
            case 'ok':
            default:
                // Set status to Submitted
                $status = 0;
                break;
        }
        // Create new poll
        $newpid = xarModAPIFunc('polls',
                                'admin',
                                'create',
                                array('title' => $question,
                                      'polltype' => 'single',
                                      'private' => $status,
                                      'time' => $date,
                                      'votes' => $voters));

        if (empty($newpid)) {
            echo "Insert poll ($qid) $question failed : " . xarErrorRender('text') . "<br/>\n";
        } elseif ($count < 200) {
            echo "Inserted poll ($qid) $question<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted poll $num<br/>\n";
            flush();
        }
        if (!empty($newpid)) {
            $pollid[$qid] = $newpid;
        }
        $num++;
        $result->MoveNext();
    }
    $result->Close();

?>
