<?php
/**
 * Quick & dirty import of Joomla 1.0.4+ polls into Xaraya polls
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

    echo "<strong>$step. Importing old polls</strong><br/>\n";

    if (!xarModIsAvailable('polls')) {
        echo "The polls module is not activated in Xaraya<br/>\n";
        $step++;
        return;
    }

    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_polls';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count polls failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();

    // Use different GROUP BY for MySQL and PostgreSQL databases
    // Nonsense here, because Joomla only knows MySGQL...
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
                $groupby = 'GROUP BY pdesc.id';
            break;
        case 'postgres':
// FIXME: where do these columns names come from ?
                $groupby = 'GROUP BY pdesc.pollID, pollTitle, timeStamp, voters';
            break;
        default:
            die("Unknown database type");
            break;
    }


    $query = 'SELECT pdesc.id,title,COUNT(pdata.date)
              FROM ' . $oldprefix . '_polls as pdesc
              LEFT JOIN ' . $oldprefix . '_poll_date as pdata
                  ON pdesc.id = pdata.poll_id
              ' . $groupby . '
              ORDER BY pdesc.id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select polls failed : " . $dbconn->ErrorMsg());
    }
    $pollid = array();
    $num = 1;
    while (!$result->EOF) {
        list($pid,$title,$realvotes) = $result->fields;
        if (empty($title)) {
            $title = xarML('[none]');
        }
        $newpid = xarModAPIFunc('polls','admin','create',
                                array('title' => $title,
                                      'polltype' => 'single', // does Joomla support any other kind ?
                                      'private' => 0,
                                      'time' => '',//timestamp?
                                      'votes' => $realvotes));
        if (empty($newpid)) {
            echo "Insert poll ($pid) $title failed : " . xarErrorRender('text') . "<br/>\n";
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