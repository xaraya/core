<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ topics and categories into your Xaraya test site
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

    echo "<strong>$step. Importing old news topics into categories</strong><br/>\n";

    if ($resetcat) {
        $dbconn->Execute("DELETE FROM " . $tables['categories']);
        //$dbconn->Execute('FLUSH TABLE ' . $tables['categories']);
    }
    $regid = xarModGetIDFromName('articles');
    if ($reset) {
        $dbconn->Execute("DELETE FROM " . $tables['categories_linkage'] . " WHERE xar_modid=$regid");
        //$dbconn->Execute('FLUSH TABLE ' . $tables['categories_linkage']);
    }
    if (!empty($docounter)) {
        if ($reset) {
            $regid2 = xarModGetIDFromName('categories');
            $dbconn->Execute("DELETE FROM " . $tables['hitcount'] . " WHERE xar_moduleid = " . $regid2);
            //$dbconn->Execute('FLUSH TABLE ' . $tables['hitcount']);
        }
    }
    echo "Creating root for old news topics<br/>\n";
    $topics = xarModAPIFunc('categories', 'admin', 'create', array(
                               'name' => 'Topics',
                               'description' => 'News Topics (.7x style)',
                               'parent_id' => 0));
    echo "Creating root for old news categories<br/>\n";
    $categories = xarModAPIFunc('categories', 'admin', 'create', array(
                                  'name' => 'Categories',
                                  'description' => 'News Categories (.7x style)',
                                  'parent_id' => 0));
    // preset the article categories to those two types
    if ($reset) {
        $settings = unserialize(xarModGetVar('articles', 'settings.1'));
        $settings['number_of_categories'] = 2;
        $settings['cids'] = array($topics, $categories);
        xarModSetVar('articles', 'settings.1', serialize($settings));
        xarModSetVar('articles', 'number_of_categories.1', 2);
        xarModSetVar('articles', 'mastercids.1', $topics .';'.$categories);
    } else {
        // you'll be in trouble with your categories here...
    }
    echo "Creating old default 'Articles' news category<br/>\n";
    $catid[0] = xarModAPIFunc('categories', 'admin', 'create', array(
                                 'name' => 'Articles',
                                 'description' => 'Articles',
                                 'parent_id' => $categories));

    $query = 'SELECT topicid, topicname, topictext, topicimage, counter
              FROM ' . $oldprefix . '_topics
              ORDER BY topicid ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select topics failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($id, $name, $text, $image, $counter) = $result->fields;
        $topicid[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                              'name' => $text,
                              'description' => $text,
                              'image' => "$imgurl/topics/$image",
                              'parent_id' => $topics));
        echo "Creating topic ($id) $text - $name [$image]<br/>\n";
        if (!empty($docounter)) {
            $hcid = xarModAPIFunc('hitcount','admin','create',array('modname' => 'categories',
                                                               'objectid' => $topicid[$id],
                                                               'hits' => $counter));
            if (!isset($hcid)) {
                echo "Couldn't create hit counter $counter for topic $topicid[$id] $text<br/>\n";
            }
        }
        $result->MoveNext();
    }
    $result->Close();

    $query = 'SELECT catid, title, counter
              FROM ' . $oldprefix . '_stories_cat
              ORDER BY catid ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select stories_cat failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($id, $title, $counter) = $result->fields;
        $catid[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                              'name' => $title,
                              'description' => $title,
                              'parent_id' => $categories));
        echo "Creating category ($id) $title - $title<br/>\n";
        if (!empty($docounter)) {
            $hcid = xarModAPIFunc('hitcount','admin','create',array('modname' => 'categories',
                                                               'objectid' => $catid[$id],
                                                               'hits' => $counter));
            if (!isset($hcid)) {
                echo "Couldn't create hit counter $counter for category $catid[$id] $title<br/>\n";
            }
        }
        $result->MoveNext();
    }
    $result->Close();
    echo "<strong>TODO : copy the topic images to modules/categories/xarimages or elsewhere someday</strong><br/><br/>\n";
    xarModSetVar('installer','topics',$topics);
    xarModSetVar('installer','topicid',serialize($topicid));
    xarModSetVar('installer','categories',$categories);
    xarModSetVar('installer','catid',serialize($catid));
    echo '<a href="import_nuke.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_nuke.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>
