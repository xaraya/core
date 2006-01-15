<?php
/**
 * Quick & dirty import of Joomla 1.0.4+ sections and categories into Xaraya test sites
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

    echo "<strong>$step. Importing old sections and categories into Xaraya categories</strong><br/>\n";

    if ($resetcat) {
        $dbconn->Execute("DELETE FROM " . $tables['categories']);
    }
    $regid = xarModGetIDFromName('articles');
    if ($reset) {
        $dbconn->Execute("DELETE FROM " . $tables['categories_linkage'] . " WHERE xar_modid=$regid");
    }
    if (!empty($docounter)) {
        if ($reset) {
            $regid2 = xarModGetIDFromName('categories');
            $dbconn->Execute("DELETE FROM " . $tables['hitcount'] . " WHERE xar_moduleid = " . $regid2);
            //$dbconn->Execute('FLUSH TABLE ' . $tables['hitcount']);
        }
    }
    echo "Creating root for old Sections<br/>\n";
    $topics = xarModAPIFunc('categories', 'admin', 'create', array(
                               'name' => 'Sections',
                               'description' => 'Joomla Sections (1.0.4+ style',
                               'parent_id' => 0));
    echo "Creating root for old Categories<br/>\n";
    $categories = xarModAPIFunc('categories', 'admin', 'create', array(
                                  'name' => 'Categories',
                                  'description' => 'Joomla Categories (1.0.4+ style)',
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

    $query = 'SELECT id, title, name, image, description, count
              FROM ' . $oldprefix . '_sections
              ORDER BY id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select sections failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($id, $title, $name, $image, $description, $counter) = $result->fields;
        $topicid[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                              'name' => $title,
                              'description' => $description,
                              'image' => "$imgurl/topics/$image",
                              'parent_id' => $topics));
        echo "Creating section ($id) $title - $name - $description - [$image]<br/>\n";
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
    echo "Creating categories<br/>\n";
    $query = 'SELECT id, title, name, image, description, count
              FROM ' . $oldprefix . '_categories
              ORDER BY id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select categories failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($id, $title, $name, $image, $description, $counter) = $result->fields;
        $catid[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                              'name' => $title,
                              'description' => $description,
                              'image' => "$imgurl/topics/$image",
                              'parent_id' => $categories));
        echo "Creating category ($id) $title - $name - $description<br/>\n";
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
    echo '<a href="import_joomla.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_joomla.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>