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

    echo "<strong>$step. Importing stories</strong><br/>\n";

    // Get categories info
    $categories_cid = xarModGetVar('installer','categories_cid');
    $categories = unserialize(xarModGetVar('installer','categories'));

    // Initialize table names
    $table_stories = 'stories';
    $table_story_text = 'story_text';
    $table_userids = xarDBGetSiteTablePrefix() . '_installer_userids';

    // Import stories
    $storycount = xarModGetVar('installer','storycount');
    echo "Found " . $storycount . " stories<br/>\n";

    $regid = xarModGetIDFromName('articles');

    // Remove all articles if reset
    if ($reset && $startnum == 0) {
        $dbconn->Execute("DELETE FROM " . $tables['articles']);
    }
    if (!empty($docounter)) {
        if ($reset && $startnum == 0) {
            $dbconn->Execute("DELETE FROM " . $tables['hitcount'] . " WHERE xar_moduleid = " . $regid);
        }
    }

    // Use different unix timestamp conversion function for
    // MySQL and PostgreSQL databases
    $importdbtype = xarModGetVar('installer','importdbtype');
    switch ($importdbtype) {
        case 'mysql':
            $dbfunction = "UNIX_TIMESTAMP($table_stories.day_published)";
            break;
        case 'postgres':
            $dbfunction = "DATE_PART('epoch',$table_stories.day_published)";
            break;
        default:
            die("Unknown database type");
            break;
    }

    // Select all of the stories
    $query = "SELECT $table_stories.stoid, 
                     $table_userids.xar_uid,
                     $table_stories.tid,
                     $table_story_text.title, 
                     $table_story_text.introtext, 
                     $table_story_text.bodytext, 
                     $table_story_text.relatedtext,
                     $table_stories.writestatus, 
                     $table_stories.is_archived, 
                     $table_stories.in_trash, 
                     $dbfunction, 
                     $table_stories.hits
              FROM   $table_stories
              LEFT JOIN $table_story_text
                     ON $table_stories.stoid = $table_story_text.stoid
              LEFT JOIN $table_userids
                     ON $table_stories.uid = $table_userids.slash_uid
              ORDER BY $table_stories.stoid ASC";

    $numitems = xarModGetVar('installer','storyimport');
    if (!isset($startnum)) {
        $startnum = 0;
    }
    if ($storycount > $numitems) {
        $result =& $dbimport->SelectLimit($query, $numitems, $startnum);
    } else {
        $result =& $dbimport->Execute($query);
    }
    if (!$result) {
        die("Oops, select from " . $table_stories . " failed : " . $dbimport->ErrorMsg());
    }

    $num = 1;
    $language = xarMLSGetCurrentLocale();
    while (!$result->EOF) {
        list($stoid, 
             $authorid,
             $tid,
             $title, 
             $introtext, 
             $bodytext, 
             $relatedtext, 
             $writestatus, 
             $is_archived,
             $in_trash,
             $day_published,
             $hits) = $result->fields;

        // Set status of the new article
        switch ($writestatus) {
            case 'ok':
                // Set status to Approved
                $status = 2;
                break;
            case 'delete':
            case 'dirty': // TODO - what about 'dirty' stories???
                // Set status to Rejected
                $status = 1;
                break;
            case 'archived':
                // Set status to Archived
                $status = 4;
                break;
            default:
                // Set status to Submitted
                $status = 0;
                break;
        }
        if ($in_trash == 'yes') {
            // Set status to Expired
            $status = 5;
        }
        
        if (empty($authorid) || $authorid < 6) {
            $authorid = _XAR_ID_UNREGISTERED;
        }
        $cids = array();
        if (isset($categories[$tid])) {
            $cids[] = $categories[$tid];
        }
        if (empty($title)) {
            $title = xarML('[none]');
        }
    // Note: we try to use the same article id as the old story id here
        $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array('aid' => $stoid,
                                      'title' => $title,
                                      'summary' => $introtext,
                                      'body' => $bodytext,
                                      'notes' => $relatedtext,
                                      'status' => $status,
                                      'ptid' => 1,
                                      'pubdate' => $day_published,
                                      'authorid' => $authorid,
                                      'language' => $language, 
                                      'cids' => $cids,
                                      'hits' => $hits
                                     )
                               );
        if (!isset($newaid) || $newaid != $stoid) {
            echo "Insert article #$num ($stoid) $title failed :";
            xarErrorRender('text');
            echo "<br/>\n";
            xarErrorHandled();
        } elseif ($storycount < 200) {
            echo "Inserted article ($stoid) $title<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted article " . ($num + $startnum) . "<br/>\n";
            flush();
        }
        // Associate newaid with stoid
        //$articles[$stoid] = $newaid;
        $num++;

        $result->MoveNext();
    }
    $result->Close();

    // Set articles modvar
    //xarModSetVar('installer','articles',serialize($articles));

    echo "<strong>TODO : check sequence number for articles in Postgres</strong><br/><br/>\n";

    echo '<a href="import_slashcode.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($storycount > $numitems && $startnum + $numitems < $storycount) {
        $startnum += $numitems;
        echo '<a href="import_slashcode.php?step=' . $step . '&module=articles&startnum=' . $startnum . '">Go to step ' . $step . ' - articles ' . $startnum . '+ of ' . $storycount . '</a><br/>';
    } else {
        echo '<a href="import_slashcode.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    }

    // Optimize tables
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
            $query = 'OPTIMIZE TABLE ' . $tables['articles'];
            $result =& $dbconn->Execute($query);
            $query = 'OPTIMIZE TABLE ' . $tables['categories_linkage'];
            $result =& $dbconn->Execute($query);
            if (!empty($docounter)) {
                $query = 'OPTIMIZE TABLE ' . $tables['hitcount'];
                $result =& $dbconn->Execute($query);
            }
            break;
        case 'postgres':
            // If we're importing to the PostgreSQL database, then we need
            // to create a sequence value for seqxar_articles that starts
            // at the last sid from nuke_stories.  Otherwise the next import
            // into xar_articles will fail because the aid already exists.
            // This isn't a problem for MySQL as it has an auto_increment column.
            $dbconn->GenID($tables['articles'], $stoid);

            $query = 'VACUUM ANALYZE ' . $tables['articles'];
            $result =& $dbconn->Execute($query);
            $query = 'VACUUM ANALYZE ' . $tables['categories_linkage'];
            $result =& $dbconn->Execute($query);
            if (!empty($docounter)) {
                $query = 'VACUUM ANALYZE ' . $tables['hitcount'];
                $result =& $dbconn->Execute($query);
            }
            break;
        default:
            break;
    }

?>
