<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of this file: mikespub
// Purpose of this file: Quick & dirty import of .71 data to a .8 test site
// ----------------------------------------------------------------------

// initialize the Xaraya core
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

$step = xarVarCleanFromInput('step');
if (!isset($step)) {
// start the output buffer
ob_start();
}
?>

<h3>Quick and dirty import of .8 test data from an existing .71 site</h3>

<?php
$prefix = xarDBGetSystemTablePrefix();
if (isset($step) && ($step > 1 || isset($startnum))) {
    $oldprefix = xarModGetVar('installer','oldprefix');
    $reset = xarModGetVar('installer','reset');
    $resetcat = xarModGetVar('installer','resetcat');
    $imgurl = xarModGetVar('installer','imgurl');
}
if (!isset($oldprefix) || $oldprefix == $prefix || !preg_match('/^[a-z0-9]+$/i',$oldprefix)) {
?>
    Requirement : you must be using the same database, but a different prefix...
    <p></p>
    <form method="POST" action="import8.php">
    <table border="0" cellpadding="4">
    <tr><td align="right">Prefix used in your .71 site</td><td>
    <input type="text" name="oldprefix" value="not '<?php echo $prefix ?>' !"></td></tr>
    <tr><td align="right">URL of the /images directory on your .71 site</td><td>
    <input type="text" name="imgurl" value="/images"></td></tr>
    <tr><td align="right">Reset corresponding .8 data ?</td><td>
    <input type="checkbox" name="reset" checked></td></tr>
    <tr><td align="right">Reset existing .8 categories ?</td><td>
    <input type="checkbox" name="resetcat" checked></td></tr>
    <tr><td colspan=2 align="middle">
    <input type="submit" value=" Import Data "></td></tr>
    </table>
    <input type="hidden" name="step" value="1">
    </form>
    <p></p>
    You must also have activated categories and users, and added the articles
    module from postnuke_modules first.
<?php
} else {
    if ($step == 1 && !isset($startnum)) {
        xarModSetVar('installer','oldprefix',$oldprefix);
        if (!isset($reset)) { $reset = 0; }
        xarModSetVar('installer','reset',$reset);
        if (!isset($resetcat)) { $resetcat = 0; }
        xarModSetVar('installer','resetcat',$resetcat);
        if (!isset($imgurl)) { $imgurl = 0; }
        xarModSetVar('installer','imgurl',$imgurl);
    }

    // log in admin user
    if (!xarUserLogIn('Admin', 'password', 0)) {
        die('Unable to log in');
    }

    list($dbconn) = xarDBGetConn();

    if (!xarModAPILoad('users','admin')) {
        die("Unable to load the users admin API");
    }
    if (!xarModAPILoad('categories','user')) {
        die("Unable to load the categories user API");
    }
    if (!xarModAPILoad('categories','admin')) {
        die("Unable to load the categories admin API");
    }
    if (!xarModAPILoad('articles','admin')) {
        die("Unable to load the articles admin API");
    }
    if (!xarModAPILoad('comments','admin')) {
        die("Unable to load the comments admin API");
    }
    if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','admin')) {
        $docounter = 1;
    }
    $tables = xarDBGetTables();

    if (!isset($reset)) {
        $reset = 0;
    }
    if (!isset($resetcat)) {
        $resetcat = 0;
    }

    if ($step == 1) {
    echo "<strong>1. Importing users</strong><br>\n";
    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_users';
    $result = $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, count users failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $query = 'SELECT pn_uid, pn_name, pn_uname, pn_email, pn_pass, pn_url 
              FROM ' . $oldprefix . '_users 
              WHERE pn_uid > 2
              ORDER BY pn_uid ASC';
    $numitems = 2000;
    if (!isset($startnum)) {
        $startnum = 0;
    }
    if ($count > $numitems) {
        $result = $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result = $dbconn->Execute($query);
    }
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, select users failed : " . $dbconn->ErrorMsg());
    }
    if ($reset && $startnum == 0) {
        $dbconn->Execute("DELETE FROM " . $tables['users'] . " WHERE xar_uid > 2");
        $dbconn->Execute('FLUSH TABLE ' . $tables['users']);
    }
    $num = 1;
    while (!$result->EOF) {
        list($uid,$name,$uname,$email,$pass,$url) = $result->fields;
        $newuid = xarModAPIFunc('users',
                                'admin',
                                'create',
                                array('uid' => $uid,
                                      'uname' => $uname,
                                      'realname' => $name,
                                      'email' => $email,
                                      'pass'  => $pass,
                                      'date'     => time(),
                                      'valcode'  => 'createdbyadmin',
                                      'state'   => 3));
        if (!isset($newuid)) {
            echo "Insert user ($uid) $name failed : " . xarExceptionRender('text') . "<br>\n";
        } elseif ($count < 200) {
            echo "Inserted user ($uid) $name - $uname<br>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted user " . ($num + $startnum) . "<br>\n";
            flush();
        }
        $num++;
        $result->MoveNext();
    }
    $result->Close();
    echo "<strong>TODO : import user_data</strong><br><br>\n";
    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import8.php?step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - users ' . $startnum . '+ of ' . $count . '</a><br>';
    } else {
        echo '<a href="import8.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br>';
    }
    }

    if ($step == 2) {
    echo "<strong>2. Importing old news topics into categories</strong><br>\n";
    if ($resetcat) {
        $dbconn->Execute("DELETE FROM " . $tables['categories']);
        $dbconn->Execute('FLUSH TABLE ' . $tables['categories']);
    }
    $regid = xarModGetIDFromName('articles');
    if ($reset) {
        $dbconn->Execute("DELETE FROM " . $tables['categories_linkage'] . " WHERE xar_modid=$regid");
        $dbconn->Execute('FLUSH TABLE ' . $tables['categories_linkage']);
    }
    if (!empty($docounter)) {
        if ($reset) {
            $regid2 = xarModGetIDFromName('categories');
            $dbconn->Execute("DELETE FROM " . $tables['hitcount'] . " WHERE xar_moduleid = " . $regid2);
            $dbconn->Execute('FLUSH TABLE ' . $tables['hitcount']);
        }
    }
    echo "Creating root for old news topics<br>\n";
    $topics = xarModAPIFunc('categories', 'admin', 'create', array(
                               'name' => 'Topics',
                               'description' => 'News Topics (.7x style)',
                               'parent_id' => 0));
    echo "Creating root for old news categories<br>\n";
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
    echo "Creating old default 'Articles' news category<br>\n";
    $catid[0] = xarModAPIFunc('categories', 'admin', 'create', array(
                                 'name' => 'Articles',
                                 'description' => 'Articles',
                                 'parent_id' => $categories));

    $query = 'SELECT pn_topicid, pn_topicname, pn_topictext, pn_topicimage, pn_counter
              FROM ' . $oldprefix . '_topics
              ORDER BY pn_topicid ASC';
    $result = $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, select topics failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($id, $name, $text, $image, $counter) = $result->fields;
        $topicid[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                              'name' => $text,
                              'description' => $text,
                              'image' => "$imgurl/topics/$image",
                              'parent_id' => $topics));
        echo "Creating topic ($id) $text - $name [$image]<br>\n";
        if (!empty($docounter)) {
            $hcid = xarModAPIFunc('hitcount','admin','create',array('modname' => 'categories',
                                                               'objectid' => $topicid[$id],
                                                               'hits' => $counter));
            if (!isset($hcid)) {
                echo "Couldn't create hit counter $counter for topic $topicid[$id] $text<br>\n";
            }
        }
        $result->MoveNext();
    }
    $result->Close();

    $query = 'SELECT pn_catid, pn_title, pn_counter
              FROM ' . $oldprefix . '_stories_cat
              ORDER BY pn_catid ASC';
    $result = $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, select stories_cat failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($id, $title, $counter) = $result->fields;
        $catid[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                              'name' => $title,
                              'description' => $title,
                              'parent_id' => $categories));
        echo "Creating category ($id) $title - $title<br>\n";
        if (!empty($docounter)) {
            $hcid = xarModAPIFunc('hitcount','admin','create',array('modname' => 'categories',
                                                               'objectid' => $catid[$id],
                                                               'hits' => $counter));
            if (!isset($hcid)) {
                echo "Couldn't create hit counter $counter for category $catid[$id] $title<br>\n";
            }
        }
        $result->MoveNext();
    }
    $result->Close();
    echo "<strong>TODO : copy the topic images to modules/categories/pnimages or elsewhere someday</strong><br><br>\n";
    xarModSetVar('installer','topics',$topics);
    xarModSetVar('installer','topicid',serialize($topicid));
    xarModSetVar('installer','categories',$categories);
    xarModSetVar('installer','catid',serialize($catid));
    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import8.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br>';
    }

    if ($step == 3) {
    $topics = xarModGetVar('installer','topics');
    $topicid = unserialize(xarModGetVar('installer','topicid'));
    $categories = xarModGetVar('installer','categories');
    $catid = unserialize(xarModGetVar('installer','catid'));
    echo "<strong>3. Importing articles</strong><br>\n";
    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_stories';
    $result = $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, count stories failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $regid = xarModGetIDFromName('articles');
    $query = 'SELECT pn_sid, pn_title, pn_hometext, pn_bodytext, pn_aid,
                     UNIX_TIMESTAMP(pn_time), pn_language, pn_catid, pn_topic,
                     pn_notes, pn_ihome, pn_counter
              FROM ' . $oldprefix . '_stories
              ORDER BY pn_sid ASC';
    $numitems = 1000;
    if (!isset($startnum)) {
        $startnum = 0;
    }
    if ($count > $numitems) {
        $result = $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result = $dbconn->Execute($query);
    }
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, select stories failed : " . $dbconn->ErrorMsg());
    }
    if ($reset && $startnum == 0) {
        $dbconn->Execute("DELETE FROM " . $tables['articles']);
        $dbconn->Execute('FLUSH TABLE ' . $tables['articles']);
    }
    if (!empty($docounter)) {
        if ($reset && $startnum == 0) {
            $dbconn->Execute("DELETE FROM " . $tables['hitcount'] . " WHERE xar_moduleid = " . $regid);
            $dbconn->Execute('FLUSH TABLE ' . $tables['hitcount']);
        }
    }
    $num = 1;
    while (!$result->EOF) {
        list($aid, $title, $summary, $body, $authorid, $pubdate, $language,
            $cat, $topic, $notes, $ihome, $counter) = $result->fields;
        if (empty($ihome)) {
            $status = 3;
        } else {
            $status = 2;
        }
        $cids = array();
        if (isset($topicid[$topic])) {
            $cids[] = $topicid[$topic];
        }
        if (isset($catid[$cat])) {
            $cids[] = $catid[$cat];
        }
        $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array('aid' => $aid,
                                      'title' => $title,
                                      'summary' => $summary,
                                      'body' => $body,
                                      'notes' => $notes,
                                      'status' => $status,
                                      'ptid' => 1,
                                      'pubdate' => $pubdate,
                                      'authorid' => $authorid,
                                      'language' => $language,
                                      'cids' => $cids,
                                      'hits' => $counter
                                     )
                               );
        if (!isset($newaid) || $newaid != $aid) {
            echo "Insert article ($aid) $title failed : " . xarExceptionRender('text') . "<br>\n";
        } elseif ($count < 200) {
            echo "Inserted article ($aid) $title<br>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted article " . ($num + $startnum) . "<br>\n";
            flush();
        }
        $num++;

        $result->MoveNext();
    }
    $result->Close();
    //echo "<strong>TODO : add comments etc.</strong><br><br>\n";
    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import8.php?step=' . $step . '&module=articles&startnum=' . $startnum . '">Go to step ' . $step . ' - articles ' . $startnum . '+ of ' . $count . '</a><br>';
    } else {
        echo '<a href="import8.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br>';
    }
    }

    if ($step == 4) {
    $topics = xarModGetVar('installer','topics');
    $topicid = unserialize(xarModGetVar('installer','topicid'));
    $categories = xarModGetVar('installer','categories');
    $catid = unserialize(xarModGetVar('installer','catid'));
    echo "<strong>4. Importing queued articles</strong><br>\n";
    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_queue';
    $result = $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, count queue failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $regid = xarModGetIDFromName('articles');
    $query = 'SELECT pn_qid, pn_subject, pn_story, pn_bodytext, pn_uid,
                     UNIX_TIMESTAMP(pn_timestamp), pn_language, pn_topic,
                     pn_arcd
              FROM ' . $oldprefix . '_queue
              ORDER BY pn_qid ASC';
    $numitems = 1000;
    if (!isset($startnum)) {
        $startnum = 0;
    }
    if ($count > $numitems) {
        $result = $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result = $dbconn->Execute($query);
    }
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, select queue failed : " . $dbconn->ErrorMsg());
    }
    $num = 1;
    while (!$result->EOF) {
        list($qid, $title, $summary, $body, $authorid, $pubdate, $language,
            $topic, $arcd) = $result->fields;
        if (empty($arcd)) {
            $status = 0;
        } else {
            $status = 1;
        }
        $notes = '';
        $cids = array();
        if (isset($topicid[$topic])) {
            $cids[] = $topicid[$topic];
        }
        $counter = 0;
        $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array('title' => $title,
                                      'summary' => $summary,
                                      'body' => $body,
                                      'notes' => $notes,
                                      'status' => $status,
                                      'ptid' => 1,
                                      'pubdate' => $pubdate,
                                      'authorid' => $authorid,
                                      'language' => $language,
                                      'cids' => $cids,
                                      'hits' => $counter
                                     )
                               );
        if (!isset($newaid)) {
            echo "Insert queued article ($qid) $title failed : " . xarExceptionRender('text') . "<br>\n";
        } elseif ($count < 200) {
            echo "Inserted queued article ($qid) $title<br>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted queued article " . ($num + $startnum) . "<br>\n";
            flush();
        }
        $num++;
        $result->MoveNext();
    }
    $result->Close();
    //echo "<strong>TODO : add comments etc.</strong><br><br>\n";
    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import8.php?step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - articles ' . $startnum . '+ of ' . $count . '</a><br>';
    } else {
        echo '<a href="import8.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br>';
    }
    }

    if ($step == 5) {
    echo "<strong>5. Importing old sections into categories</strong><br>\n";
    echo "Creating root for old sections<br>\n";
    $sections = xarModAPIFunc('categories', 'admin', 'create', array(
                             'name' => 'Sections',
                             'description' => 'Document Sections (.7x style)',
                             'parent_id' => 0));
    if ($reset) {
        $settings = unserialize(xarModGetVar('articles', 'settings.2'));
        $settings['number_of_categories'] = 1;
        $settings['cids'] = array($sections);
        xarModSetVar('articles', 'settings.2', serialize($settings));
        xarModSetVar('articles', 'number_of_categories.2', 1);
        xarModSetVar('articles', 'mastercids.2', $sections);
    }
    if ($sections > 0) {
        $query = 'SELECT pn_secid, pn_secname, pn_image
                  FROM ' . $oldprefix . '_sections
                  ORDER BY pn_secid ASC';
        $result = $dbconn->Execute($query);
        if ($dbconn->ErrorNo() != 0) {
            die("Oops, select sections failed : " . $dbconn->ErrorMsg());
        }
        while (!$result->EOF) {
            list($id, $name, $image) = $result->fields;
            $sectionid[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                              'name' => $name,
                              'description' => $name,
                              'image' => "$imgurl/sections/$image",
                              'parent_id' => $sections));
            echo "Creating section ($id) $name [$image]<br>\n";
            $result->MoveNext();
        }
        $result->Close();
    }
    echo "<strong>TODO : copy the section images to modules/categories/pnimages or elsewhere someday</strong><br><br>\n";
    xarModSetVar('installer','sections',$sections);
    xarModSetVar('installer','sectionid',serialize($sectionid));
    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import8.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br>';
    }

    if ($step == 6) {
    $regid = xarModGetIDFromName('articles');
    $sections = xarModGetVar('installer','sections');
    $sectionid = unserialize(xarModGetVar('installer','sectionid'));
    echo "<strong>6. Importing section content</strong><br>\n";
    $query = 'SELECT pn_artid, pn_secid, pn_title, pn_content, pn_language, pn_counter
              FROM ' . $oldprefix . '_seccont
              ORDER BY pn_artid ASC';
    $result = $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
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
                                      'authorid' => 1,
                                      'language' => $language,
                                      'cids' => $cids,
                                      'hits' => $counter
                                     )
                               );
        if (!isset($newaid)) {
            echo "Insert section content ($artid) $title failed : " . xarExceptionRender('text') . "<br>\n";
        } else {
            echo "Inserted section content ($artid) $title<br>\n";
        }
        $result->MoveNext();
    }
    $result->Close();
    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import8.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br>';
    }

    if ($step == 7) {
    echo "<strong>7. Importing old FAQs into categories</strong><br>\n";
    echo "Creating root for old FAQs<br>\n";
    $faqs = xarModAPIFunc('categories', 'admin', 'create', array(
                             'name' => 'FAQs',
                             'description' => 'Frequently Asked Questions (.7x style)',
                             'parent_id' => 0));
    if ($reset) {
        $settings = unserialize(xarModGetVar('articles', 'settings.4'));
        $settings['number_of_categories'] = 1;
        $settings['cids'] = array($faqs);
        xarModSetVar('articles', 'settings.4', serialize($settings));
        xarModSetVar('articles', 'number_of_categories.4', 1);
        xarModSetVar('articles', 'mastercids.4', $faqs);
    }
    if ($faqs > 0) {
        $query = 'SELECT pn_id_cat, pn_categories, pn_parent_id
                  FROM ' . $oldprefix . '_faqcategories 
                  ORDER BY pn_parent_id ASC, pn_id_cat ASC';
        $result = $dbconn->Execute($query);
        if ($dbconn->ErrorNo() != 0) {
            die("Oops, select faqcategories failed : " . $dbconn->ErrorMsg());
        }
        // set parent 0 to root FAQ category
        $faqid[0] = $faqs;
        while (!$result->EOF) {
            list($id, $name, $parent) = $result->fields;
            if (!isset($parent) || $parent < 0) {
                $parent = 0;
            }
            if (!isset($faqid[$parent])) {
                echo "Oops, missing parent $parent for FAQ ($id) $name<br>\n";
            } else {
                $faqid[$id] = xarModAPIFunc('categories', 'admin', 'create',
                                           array('name' => $name,
                                           'description' => $name,
                                           'parent_id' => $faqid[$parent]));
                echo "Creating FAQ ($id) $name [parent $parent]<br>\n";
            }
            $result->MoveNext();
        }
        $result->Close();
    }
    xarModSetVar('installer','faqs',$faqs);
    xarModSetVar('installer','faqid',serialize($faqid));
    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import8.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br>';
    }

    if ($step == 8) {
    $regid = xarModGetIDFromName('articles');
    $faqs = xarModGetVar('installer','faqs');
    $faqid = unserialize(xarModGetVar('installer','faqid'));
    echo "<strong>8. Importing FAQ questions & answers</strong><br>\n";
    $query = 'SELECT pn_id, pn_id_cat, pn_question, pn_answer, pn_submittedby
              FROM ' . $oldprefix . '_faqanswer
              ORDER BY pn_id ASC';
    $result = $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, select faq answer failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($id, $catid, $title, $content, $notes) = $result->fields;
        $language = '';
        $cids = array();
    // TODO: check if we want to add articles to the Sections root too or not
        //$cids[] = $sections;
        if (isset($faqid[$catid])) {
            $cids[] = $faqid[$catid];
        }
        if (count($cids) == 0) {
            $cids[] = $faqs;
        }
        $counter = 0;
        $status = 2;
        $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array('title' => $title,
                                      'summary' => '',
                                      'body' => $content,
                                      'notes' => $notes,
                                      'status' => $status,
                                      'ptid' => 4,
                                      'pubdate' => 0,
                                      'authorid' => 1,
                                      'language' => $language,
                                      'cids' => $cids,
                                      'hits' => $counter
                                     )
                               );
        if (!isset($newaid)) {
            echo "Insert FAQ ($id) $title failed : " . xarExceptionRender('text') . "<br>\n";
        } else {
            echo "Inserted FAQ ($id) $title<br>\n";
        }
        $result->MoveNext();
    }
    $result->Close();
    echo "<strong>TODO : do something with FAQ display</strong><br><br>\n";
    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import8.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br>';
    }

    if ($step == 9) {
    $regid = xarModGetIDFromName('articles');
    echo "<strong>9. Importing comments</strong><br>\n";
    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_comments';
    $result = $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, count comments failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $query = 'SELECT pn_tid, pn_sid, pn_pid, pn_date, pn_uname, pn_uid,
              pn_host_name, pn_subject, pn_comment 
              FROM ' . $oldprefix . '_comments 
              LEFT JOIN ' . $oldprefix . '_users
              ON ' . $oldprefix . '_users.pn_uname = ' . $oldprefix . '_comments.pn_name
              ORDER BY pn_tid ASC';
    $numitems = 2000;
    if (!isset($startnum)) {
        $startnum = 0;
    }
/*
    if ($count > $numitems) {
        $result = $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result = $dbconn->Execute($query);
    }
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, select comments failed : " . $dbconn->ErrorMsg());
    }
    if ($reset && $startnum == 0) {
        $dbconn->Execute("DELETE FROM " . $tables['comments']);
    }
    $num = 1;
    include_once('modules/comments/xarincludes/backend/backend.php');
    while (!$result->EOF) {
        list($tid,$sid,$pid,$date,$uname,$uid,$hostname,$subject,$comment) = $result->fields;

        if (empty($uid)) {
            $uid = 0;
        }
        $data['modid'] = $regid;
        $data['itemid'] = $sid;
        $data['pid'] = $pid;
        $data['author'] = $uid;
        $data['title'] = xarVarPrepForStore($subject);
        $data['comment'] = xarVarPrepForStore($comment);
        $data['hostname'] = xarVarPrepForStore($hostname);
        $data['cid'] = $tid;
        $data['date'] = $date;

        if (!pnComments_Add($data)) {
            echo "Failed inserting comment ($sid $pid) $uname - $subject<br>\n";
        } elseif ($count < 200) {
            echo "Inserted comment ($sid $pid) $uname - $subject<br>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted comment " . ($num + $startnum) . "<br>\n";
            flush();
        }
        $num++;
        $result->MoveNext();
    }
    $result->Close();
*/
    echo "<strong>TODO : import other comments</strong><br><br>\n";
    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import8.php?step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - comments ' . $startnum . '+ of ' . $count . '</a><br>';
    } else {
        echo '<a href="import8.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br>';
    }
    }


    if ($step == 10) {
    echo "<strong>10. Importing old web link categories</strong><br>\n";
    $weblinks[0] = xarModAPIFunc('categories', 'admin', 'create', array(
                                'name' => 'Web Links',
                                'description' => 'Web Link Categories (.7x style)',
                                'parent_id' => 0));
    $query = 'SELECT pn_cat_id, pn_parent_id, pn_title, pn_description
              FROM ' . $oldprefix . '_links_categories
              ORDER BY pn_parent_id ASC, pn_cat_id ASC';
    $result = $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, select links_categories failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($id, $parent, $title, $descr) = $result->fields;
        if (!isset($weblinks[$parent])) {
            echo "Oops, missing parent $parent for category ($id) $title<br>\n";
            $result->MoveNext();
            continue;
        }
        $weblinks[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                                      'name' => $title,
                                      'description' => $descr,
                                 //     'image' => "$imgurl/topics/$image",
                                      'parent_id' => $weblinks[$parent]));
        echo "Creating web link category ($id) $title - $descr<br>\n";
        $result->MoveNext();
    }
    $result->Close();
    xarModSetVar('installer','weblinks',serialize($weblinks));

    $settings = unserialize(xarModGetVar('articles', 'settings.6'));
    $settings['number_of_categories'] = 1;
    $settings['cids'] = array($weblinks[0]);
    xarModSetVar('articles', 'settings.6', serialize($settings));
    xarModSetVar('articles', 'number_of_categories.6', 1);
    xarModSetVar('articles', 'mastercids.6', $weblinks[0]);

    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import8.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br>';
    }

    if ($step == 11) {
    echo "<strong>11. Importing old web links</strong><br>\n";
    if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','admin')) {
        $docounter = 1;
    }
    $weblinks = unserialize(xarModGetVar('installer','weblinks'));
    $regid = xarModGetIDFromName('articles');
    $query = 'SELECT pn_lid, pn_cat_id, pn_title, ' . $oldprefix . '_links_links.pn_url, pn_description,
                     UNIX_TIMESTAMP(pn_date), ' . $oldprefix . '_links_links.pn_name, ' . $oldprefix . '_links_links.pn_email, pn_hits,
                     pn_submitter, pn_ratingsummary, pn_totalvotes, pn_uid
              FROM ' . $oldprefix . '_links_links
              LEFT JOIN ' . $oldprefix . '_users
              ON ' . $oldprefix . '_users.pn_uname = ' . $oldprefix . '_links_links.pn_submitter
              ORDER BY pn_lid ASC';
    $result = $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, select links failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($lid, $catid, $title, $url, $descr, $date, $name,
            $email, $hits, $submitter, $rating, $votes, $uid) = $result->fields;
        $status = 2;
        $language = '';
        if (empty($uid)) {
            $uid = 0;
        }
        if (!empty($email)) {
            $email = ' <' . $email . '>';
        }
        $cids = array();
        if (isset($weblinks[$catid])) {
            $cids[] = $weblinks[$catid];
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
            echo "Insert web link ($lid) $title failed : " . xarExceptionRender('text') . "<br>\n";
        } else {
            echo "Inserted web link ($lid) $title<br>\n";
        }
// TODO: ratings
        $result->MoveNext();
    }
    $result->Close();
    echo "<strong>TODO : import ratings, editorials, new links and modifications etc.</strong><br><br>\n";
    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import8.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br>';
    }

// TODO: add the rest :-)

    if ($step == 12) {
    echo "<strong>12. Optimizing database tables</strong><br>\n";
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['users']);
    if ($dbconn->ErrorNo() != 0) {
        echo $dbconn->ErrorMsg();
    }
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['articles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories_linkage']);
    if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','admin')) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['comments']);

    echo "<strong>TODO : import the rest...</strong><br><br>\n";
    xarModDelVar('installer','oldprefix');
    xarModDelVar('installer','reset');
    xarModDelVar('installer','resetcat');
    xarModDelVar('installer','imgurl');
    xarModDelVar('installer','topics');
    xarModDelVar('installer','topicid');
    xarModDelVar('installer','categories');
    xarModDelVar('installer','catid');
    xarModDelVar('installer','sections');
    xarModDelVar('installer','sectionid');
    xarModDelVar('installer','faqs');
    xarModDelVar('installer','faqid');
    xarModDelVar('installer','weblinks');
    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="index.php">Go to your imported site</a><br>';
    }
}

?>

<?php
if (!isset($step)) {

// catch the output
$return = ob_get_contents();
ob_end_clean();

// render the page
echo xarTpl_renderPage($return);
}

// Close the session
xarSession_close();

$dbconn->Close();

flush();

// Kill the debugger
xarCore_disposeDebugger();

// done
exit;
 
?>
