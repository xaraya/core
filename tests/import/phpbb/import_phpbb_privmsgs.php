<?php
/**
 * File: $Id$
 *
 * Import phpBB private messages into your Xaraya test site
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

    echo "<strong>$step. Importing private messages</strong><br/>\n";

    if (!xarModIsAvailable('messages')) {
        echo "The messages module is not activated in Xaraya<br/>\n";
        $step++;
        return;
    }

    $users = xarModGetVar('installer','userid');
    if (!isset($users)) {
        $userid = array();
    } else {
        $userid = unserialize($users);
    }

    $regid = xarModGetIDFromName('messages');

    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_privmsgs';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count privmsgs failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $query = 'SELECT privmsgs_id, privmsgs_type, privmsgs_subject, privmsgs_from_userid, privmsgs_to_userid, privmsgs_date, privmsgs_ip, privmsgs_bbcode_uid, privmsgs_text
              FROM ' . $oldprefix . '_privmsgs
              LEFT JOIN ' . $oldprefix . '_privmsgs_text
              ON privmsgs_id = privmsgs_text_id
              ORDER BY privmsgs_to_userid ASC,privmsgs_id ASC';
    $numitems = 1500;
    if (!isset($startnum)) {
        $startnum = 0;
    }

    if ($count > $numitems) {
        $result =& $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result =& $dbconn->Execute($query);
    }
    if (!$result) {
        die("Oops, select privmsgs failed : " . $dbconn->ErrorMsg());
    }
    $num = 1;
    while (!$result->EOF) {
        list($id,$type,$subject,$from,$to,$date,$hostname,$bbcode,$text) = $result->fields;

        if (!empty($bbcode) && !empty($text) && preg_match("/:$bbcode\]/",$text)) {
            $text = preg_replace("/:$bbcode\]/",']',$text);
        }
        $pid = 0;

        if (isset($userid[$from])) {
            $from = $userid[$from];
        } // else we're lost :)
        if (empty($from) || $from < 2) {
            $from = _XAR_ID_UNREGISTERED;
        }
        if (isset($userid[$to])) {
            $to = $userid[$to];
        } // else we're lost :)
        if (empty($to) || $to < 2) {
            $to = _XAR_ID_UNREGISTERED;
        }
        $data['modid'] = $regid;
        $data['objectid'] = $to;
        $data['pid'] = $pid;
        $data['author'] = $from;
        $data['title'] = $subject;
        $data['comment'] = $text;
        $data['hostname'] = $hostname;
        //$data['cid'] = $id;
        $data['date'] = $date;
        $data['postanon'] = 0;

        $cid = xarModAPIFunc('comments','user','add',$data);
        if (empty($cid)) {
            echo "Failed inserting privmsg ($id) $from $to - $subject : ".$dbconn->ErrorMsg()."<br/>\n";
        } elseif ($count < 200) {
            echo "Inserted privmsg ($id) $from $to - $subject<br/>\n";
        } elseif ($num % 250 == 0) {
            echo "Inserted privmsg " . ($num + $startnum) . "<br/>\n";
            flush();
        }
        $num++;
        $result->MoveNext();
    }
    $result->Close();

    echo '<a href="import_phpbb.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['comments']);
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import_phpbb.php?step=' . $step . '&module=messages&startnum=' . $startnum . '">Go to step ' . $step . ' - posts ' . $startnum . '+ of ' . $count . '</a><br/>';
        flush();
// auto-step
        echo "<script>
document.location = '" . xarServerGetBaseURL() . "import_phpbb.php?step=" . $step . '&module=messages&startnum=' . $startnum . "'
</script>";
    } else {
        echo '<a href="import_phpbb.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    }

?>
