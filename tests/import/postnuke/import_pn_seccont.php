<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ section content into your Xaraya test site
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

    echo "<strong>$step. Importing section content</strong><br/>\n";

    $regid = xarModGetIDFromName('articles');
    $sections = xarModGetVar('installer','sections');
    $sectionid = unserialize(xarModGetVar('installer','sectionid'));

    $query = 'SELECT pn_artid, pn_secid, pn_title, pn_content, pn_language, pn_counter
              FROM ' . $oldprefix . '_seccont
              ORDER BY pn_artid ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select section content failed : " . $dbconn->ErrorMsg());
    }
    if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','admin')) {
        $docounter = 1;
    }
    while (!$result->EOF) {
        list($artid, $secid, $title, $content, $language, $counter) = $result->fields;
        $cids = array();
    // TODO: check if we want to add articles to the Sections root too or not
        //$cids[] = $sections;
        if (isset($sectionid[$secid])) {
            $cids[] = $sectionid[$secid];
        }
        if (count($cids) == 0) {
            $cids[] = $sections;
        }
        $status = 2;
        if (empty($title)) {
            $title = xarML('[none]');
        }
        $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array('title' => $title,
                                      'summary' => '',
                                      'body' => $content,
                                      'notes' => '',
                                      'status' => $status,
                                      'ptid' => 2,
                                      'pubdate' => 0,
                                      'authorid' => _XAR_ID_UNREGISTERED,
                                      'language' => $language,
                                      'cids' => $cids,
                                      'hits' => $counter
                                     )
                               );
        if (!isset($newaid)) {
            echo "Insert section content ($artid) $title failed : " . xarExceptionRender('text') . "<br/>\n";
        } else {
            echo "Inserted section content ($artid) $title<br/>\n";
        }
        $result->MoveNext();
    }
    $result->Close();
    echo '<a href="import_pn.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_pn.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['articles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories_linkage']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>