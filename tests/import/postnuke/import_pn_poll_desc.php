<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ poll descriptions into your Xaraya test site
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

    echo "<strong>$step. Importing old poll descriptions</strong><br/>\n";

    if (!xarModIsAvailable('polls')) {
        echo "The polls module is not activated in Xaraya<br/>\n";
        $step++;
        return;
    }

    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_poll_desc';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count polls failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();

    // Use different GROUP BY for MySQL and PostgreSQL databases
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
                $groupby = 'GROUP BY pdata.pollID';
            break;
        case 'postgres':
                $groupby = 'GROUP BY pdesc.pollID, pollTitle, timeStamp, voters';
            break;
        default:
            die("Unknown database type");
            break;
    }


    $query = 'SELECT pdesc.pn_pollid,pn_title,pn_timestamp,pn_voters,SUM(pn_optioncount)
              FROM ' . $oldprefix . '_poll_desc as pdesc
              LEFT JOIN ' . $oldprefix . '_poll_data as pdata
                  ON pdesc.pn_pollid = pdata.pn_pollid
              ' . $groupby . ' 
              ORDER BY pdesc.pn_pollid ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select polls failed : " . $dbconn->ErrorMsg());
    }
    $pollid = array();
    $num = 1;
    while (!$result->EOF) {
        list($pid,$title,$time,$wrongvotes,$realvotes) = $result->fields;
        if (empty($title)) {
            $title = xarML('[none]');
        }
        $newpid = xarModAPIFunc('polls','admin','create',
                                array('title' => $title,
                                      'polltype' => 'single', // does PN support any other kind ?
                                      'private' => 0,
                                      'time' => $time,
                                      'votes' => $realvotes));
        if (empty($newpid)) {
            echo "Insert poll ($pid) $title failed : " . xarExceptionRender('text') . "<br/>\n";
        } elseif ($count < 200) {
            echo "Inserted poll ($pid) $title<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted poll $num<br/>\n";
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
