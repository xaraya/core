<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ web links into your Xaraya test site
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

    echo "<strong>$step. Importing old web links</strong><br/>\n";

    $userid = unserialize(xarModGetVar('installer','userid'));
    if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','admin')) {
        $docounter = 1;
    }
    $weblinks = unserialize(xarModGetVar('installer','weblinks'));
    $regid = xarModGetIDFromName('articles');

    // Use different unix timestamp conversion function for 
    // MySQL and PostgreSQL databases
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
                $dbfunction = "UNIX_TIMESTAMP(date)";
            break;
        case 'postgres':
                $dbfunction = "DATE_PART('epoch',date)";
            break;
        default:
            die("Unknown database type");
            break;
    }

    $query = 'SELECT lid, cid, title, ' . $oldprefix . '_links_links.url, 
                     description, ' . $dbfunction . ', ' . $oldprefix . '_links_links.name, ' . $oldprefix . '_links_links.email, 
                     hits, submitter, linkratingsummary, totalvotes, uid
             FROM ' . $oldprefix . '_links_links
             LEFT JOIN ' . $oldprefix . '_users
             ON ' . $oldprefix . '_users.uname = ' . $oldprefix . '_links_links.submitter
             ORDER BY lid ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select links failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($lid, $catid, $title, $url, $descr, $date, $name,
            $email, $hits, $submitter, $rating, $votes, $uid) = $result->fields;
        $status = 2;
        $language = '';
        if (isset($userid[$uid])) {
            $uid = $userid[$uid];
        } // else we're lost :)
        if (empty($uid) || $uid < 2) {
            $uid = _XAR_ID_UNREGISTERED;
        }
        if (!empty($email)) {
            $email = ' <' . $email . '>';
        }
        $cids = array();
        if (isset($weblinks[$catid])) {
            $cids[] = $weblinks[$catid];
        }
        if (empty($title)) {
            $title = xarML('[none]');
        }
        $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array('title' => $title,
                                      'summary' => $descr,
                                      'body' => $url,
                                      'notes' => $name . $email,
                                      'status' => $status,
                                      'ptid' => 6,
                                      'pubdate' => $date,
                                      'authorid' => $uid,
                                      'language' => $language,
                                      'cids' => $cids,
                                      'hits' => $hits
                                     )
                               );
        if (!isset($newaid)) {
            echo "Insert web link ($lid) $title failed : " . xarExceptionRender('text') . "<br/>\n";
        } else {
            echo "Inserted web link ($lid) $title<br/>\n";
        }
// TODO: ratings
        $result->MoveNext();
    }
    $result->Close();
    echo "<strong>TODO : import ratings, editorials, new links and modifications etc.</strong><br/><br/>\n";
    echo '<a href="import_nuke.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_nuke.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['articles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories_linkage']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>
