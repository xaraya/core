<?php
/**
 * Quick & dirty import of Joomla 1.0.4+ weblinks into Xaraya web links publication type articles
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
/*, ' . $oldprefix . '_links_links.pn_name, ' . $oldprefix . '_links_links.pn_email, pn_hits,
                     pn_submitter, pn_ratingsummary, pn_totalvotes, pn_uid
*/
    $query = 'SELECT id, catid, sid, title, url, description,
                     ' . $dbfunction . ', hits, published
              FROM ' . $oldprefix . '_weblinks
              ORDER BY id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select weblinks failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($lid, $catid, $sid, $title, $url, $descr, $date, $hits, $published) = $result->fields;
        if ($published == 1) {
            $status = 2;
        } else {
            $status = $published;
        }
        $language = '';
        $uid ='';
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
                                      'notes' => 'Joomla import',//. $name . $email,
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
            echo "Insert web link ($lid) $title failed : " . xarErrorRender('text') . "<br/>\n";
        } else {
            echo "Inserted web link ($lid) $title<br/>\n";
        }
// TODO: ratings
        $result->MoveNext();
    }
    $result->Close();
    echo "<strong>TODO : import ratings, editorials, new links and modifications etc.</strong><br/><br/>\n";
    echo '<a href="import_joomla.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_joomla.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['articles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories_linkage']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>