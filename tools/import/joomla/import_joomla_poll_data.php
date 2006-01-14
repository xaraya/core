<?php
/**
 * Quick & dirty import of Joomla 1.0.4+ poll data into Xaraya polls
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

    echo "<strong>$step. Importing old poll data</strong><br/>\n";

    if (!xarModIsAvailable('polls')) {
        echo "The polls module is not activated in Xaraya<br/>\n";
        return;
    }

    $query = 'SELECT pollid, text, hits, id
              FROM ' . $oldprefix . '_poll_data
              ORDER BY pollid ASC, id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select poll options failed : " . $dbconn->ErrorMsg());
    }
    $num = 1;
    while (!$result->EOF) {
        list($pid,$text,$count,$vid) = $result->fields;
        if ($text === '') {
            $num++;
            $result->MoveNext();
            continue;
        } elseif (!isset($pollid[$pid])) {
            echo "Unknown poll id $pid for option $text<br />\n";
            $num++;
            $result->MoveNext();
            continue;
        }
        $newvid = xarModAPIFunc('polls','admin','createopt',
                                array('pid' => $pollid[$pid],
                                      'option' => $text,
                                      'votes' => $count));
        if (empty($newvid)) {
            echo "Insert poll option ($pid $vid) $text failed : " . xarErrorRender('text') . "<br/>\n";
        } elseif ($count < 100) {
            echo "Inserted poll option ($pid $vid) $text<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted poll option $num<br/>\n";
            flush();
        }
        $num++;
        $result->MoveNext();
    }
    $result->Close();
    echo '<a href="import_joomla.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_joomla.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['polls']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['polls_info']);

?>