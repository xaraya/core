<?php
/**
 * File: $Id: s.import8.php 1.29 03/04/20 15:12:41+02:00 BRUMDall@brumdall2m.bru.fsc.net $
 *
 * Quick & dirty import of ContentExpress content (based on import8.php)
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import_ce
 * @author mikespub <mikespub@xaraya.com>
 * @author Ferenc Veres <lion@netngine.hu>
*/

// initialize the Xaraya core
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

list($step,
     $startnum) = xarVarCleanFromInput('step',
                                       'startnum');

// pre-fill the module name (if any) for hooks
xarRequestGetInfo();

if (!isset($step)) {
// start the output buffer
ob_start();
}
?>

<h3>ContentExpress import from an existing .71 site</h3>

<?php
$prefix = xarDBGetSystemTablePrefix();

// Get user entered information about the old database (from Form or mod_vars)
if (isset($step)) {
    if ($step == 1 && !isset($startnum)) {
        //list($oldprefix) = xarVarCleanFromInput('oldprefix');
        $oldprefix = "nuke";
        echo "GOT $oldprefix<br>";
    } elseif ($step > 1 || isset($startnum)) {
        $oldprefix = xarModGetVar('installer','oldprefix');
    }
}

// If just invoked display the starting form
if (!isset($oldprefix) || $oldprefix == $prefix || !preg_match('/^[a-z0-9_-]+$/i',$oldprefix)) {
?>
    Requirement : you must be using the same database, but a different prefix...
    <p></p>
    <form method="POST" action="import_ce124.php">
    <table border="0" cellpadding="4">
    <tr><td align="right">Prefix used in your .71 site</td><td>
    <input type="text" name="oldprefix" value="not '<?php echo $prefix ?>' !"></td></tr>
    <tr><td colspan=2 align="middle">
    <input type="submit" value=" Import Data "></td></tr>
    </table>
    <input type="hidden" name="step" value="1">
    <input type="hidden" name="module" value="articles">
    </form>
    <p>Note : you must at least activate the 'categories' and 'articles' modules first. Activating 'comments' and 'hitcount' is also a good idea :-)</p>
    <p>Warning: please make a backup of your database before running this script and restore it if the script fails or you are not satisfied with the result.</p>
<?php
} else {
    // If step 1 then store form data about the old DB in mod_vars.
    if ($step == 1 && !isset($startnum)) {
        xarModSetVar('installer','oldprefix',$oldprefix);
    }

/*
    // log in admin user
    if (!xarUserLogIn('Admin', 'password', 0)) {
        die('Unable to log in');
    }
*/

    // Inicialize APIs and so.

    list($dbconn) = xarDBGetConn();

    if (!xarModAPILoad('roles','admin')) {
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
    if (!xarModAPILoad('comments','user')) {
        die("Unable to load the comments user API");
    }
    if (!xarModAPILoad('dynamicdata','util')) {
        die("Unable to load the dynamicdata util API");
    }
    if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','admin')) {
        $docounter = 1;
    }
    $tables = xarDBGetTables();

    // STEP 1

    // Copy menutree into categories

    if ($step == 1) {
    echo "<strong>1. Importing menus into categories</strong><br>\n";

    $regid = xarModGetIDFromName('articles');
    echo "Creating root for old ContentExpress<br>\n";
    $contentexpress = xarModAPIFunc('categories', 'admin', 'create', array(
                               'name' => 'ContentExpress',
                               'description' => 'ContentExpress Articles',
                               'parent_id' => 0));

    // Reset the Document pubtype to have only this category. If you also
    // need Sections, add it manually later (or tell me how to get its ID)

    $settings = unserialize(xarModGetVar('articles', 'settings.2'));
    $settings['number_of_categories'] = 1;
    $settings['cids'] = array($contentexpress);
    $settings['defaultview'] = 'c'.$contentexpress;
    xarModSetVar('articles', 'settings.2', serialize($settings));
    xarModSetVar('articles', 'number_of_categories.2', 1);
    xarModSetVar('articles', 'mastercids.2', $contentexpress);

    // This array connects the old menu IDs to the new category IDs
    $ce_menus = Array();

    // Copy ce menutree's content-items starting from root (-1)
    walk_ce_menutree(-1);

    xarModSetVar('installer','contentexpress',$contentexpress);
    xarModSetVar('installer','ce_menus', serialize($ce_menus));

    echo '<a href="import_ce124.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_ce124.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br>';
    }

    // STEP 2 

    // Copy articles

    if ($step == 2) {

    $contentexpress = xarModGetVar('installer','contentexpress');
    $ce_menus = unserialize(xarModGetVar('installer','ce_menus'));

    echo "<strong>2. Importing articles</strong><br>\n";
    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_ContentExpress_contentitems';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count stories failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $regid = xarModGetIDFromName('articles');

    // Get content imtems
    // Ignores items linked to another items, coz actually I have no CE and no such
    // article in my dumpfile.

    $query = 'SELECT mc_id, mc_parent_id, mc_menu_id, mc_title, mc_text, mc_media_url, mc_media_width, mc_media_height, mc_layout_id, mc_weight, mc_active
              FROM ' . $oldprefix . '_ContentExpress_contentitems
              WHERE mc_parent_id = -1
              ORDER BY mc_weight ASC';
    $numitems = 1000;
    if (!isset($startnum)) {
        $startnum = 0;
    }
    if ($count > $numitems) {
        $result =& $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result =& $dbconn->Execute($query);
    }
    if (!$result) {
        die("Oops, select contentitems failed : " . $dbconn->ErrorMsg());
    }

    $num = 1;
    while (!$result->EOF) {
        list($id, $parent_id, $menu_id, $title, $text, $media_url, $media_width,
            $media_height, $layout_id, $weight, $active) = $result->fields;

        // Category id from the ce_menus array (or set to ce root)
        $cids = array();
        if ($menu_id > 0 && isset($ce_menus[$menu_id])) {
            $cids[] = $ce_menus[$menu_id];
        } else {
            $cids[] = $contentexpress;
        }

        $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array(//'aid' => $id,
                                      'title' => $title,
                                      //'summary' => $summary,
                                      'body' => $text,
                                      //'notes' => $notes,
                                      'status' => ($active == 1 ? 3 : 0),
                                      'ptid' => 2,
                                      //'pubdate' => $pubdate, (defaults to NOW)
                                      //'authorid' => $authorid,
                                      //'language' => $language,  what was this?
                                      'cids' => $cids,
                                      //'hits' => $counter
                                     )
                               );
        //if (!isset($newaid) || $newaid != $aid) {
        if (!isset($newaid)) {  // Not empty articles, ID will not be the same
            echo "Insert article ($id) $title failed : " . xarExceptionRender('text') . "<br>\n";
        } elseif ($count < 200) {
            echo "Inserted article ($id) $title into $cids[0]<br>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted article " . ($num + $startnum) . "<br>\n";
            flush();
        }
        $num++;

        $result->MoveNext();
    }
    $result->Close();
    
    echo "<strong>TODO : add comments, ratings.</strong><br><br>\n";
    echo '<a href="import_ce124.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import_ce124.php?step=' . $step . '&module=articles&startnum=' . $startnum . '">Go to step ' . $step . ' - contentitems ' . $startnum . '+ of ' . $count . '</a><br>';
    } else {
        echo '<a href="import_ce124.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br>';
    }
    }
    
    // STEP next

    if ($step == 9) {
    $userid = unserialize(xarModGetVar('installer','userid'));
    $regid = xarModGetIDFromName('articles');
    $pid2cid = array();
// TODO: fix issue for large # of comments
    $pids = xarModGetVar('installer','commentid');
    if (!empty($pids)) {
        $pid2cid = unserialize($pids);
        $pids = '';
    }
    echo "<strong>9. Importing comments</strong><br>\n";
    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_comments';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count comments failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $query = 'SELECT pn_tid, pn_sid, pn_pid, UNIX_TIMESTAMP(pn_date), pn_uname, pn_uid,
              pn_host_name, pn_subject, pn_comment 
              FROM ' . $oldprefix . '_comments 
              LEFT JOIN ' . $oldprefix . '_users
              ON ' . $oldprefix . '_users.pn_uname = ' . $oldprefix . '_comments.pn_name
              ORDER BY pn_tid ASC';
    $numitems = 1000;
    if (!isset($startnum)) {
        $startnum = 0;
    }

    if ($count > $numitems) {
        $result =& $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result =& $dbconn->Execute($query);
    }
    if (!$result) {
        die("Oops, select comments failed : " . $dbconn->ErrorMsg());
    }
    if ($reset && $startnum == 0) {
        $dbconn->Execute("DELETE FROM " . $tables['comments']);
    }
    $num = 1;
    while (!$result->EOF) {
        list($tid,$sid,$pid,$date,$uname,$uid,$hostname,$subject,$comment) = $result->fields;

        if (empty($uid)) {
            $uid = 0;
        }
        if (isset($userid[$uid])) {
            $uid = $userid[$uid];
        } // else we're lost :)
        $data['modid'] = $regid;
        $data['objectid'] = $sid;
        if (!empty($pid) && !empty($pid2cid[$pid])) {
            $pid = $pid2cid[$pid];
        }
        $data['pid'] = $pid;
        $data['author'] = $uid;
        $data['title'] = $subject;
        $data['comment'] = $comment;
        $data['hostname'] = $hostname;
        //$data['cid'] = $tid;
        $data['date'] = $date;
        $data['postanon'] = 0;

        $cid = xarModAPIFunc('comments','user','add',$data);
        if (empty($cid)) {
            echo "Failed inserting comment ($sid $pid) $uname - $subject : ".$dbconn->ErrorMsg()."<br>\n";
        } elseif ($count < 200) {
            echo "Inserted comment ($sid $pid) $uname - $subject<br>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted comment " . ($num + $startnum) . "<br>\n";
            flush();
        }
        $pid2cid[$tid] = $cid;
        $num++;
        $result->MoveNext();
    }
    $result->Close();

    echo "<strong>TODO : import other comments</strong><br><br>\n";
    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        xarModSetVar('installer','commentid',serialize($pid2cid));
        $startnum += $numitems;
        echo '<a href="import8.php?step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - comments ' . $startnum . '+ of ' . $count . '</a><br>';
    } else {
        xarModDelVar('installer','commentid');
        echo '<a href="import8.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br>';
    }
    }


// TODO: add the rest :-)

    if ($step == 12) {
    echo "<strong>12. Optimizing database tables</strong><br>\n";
    $result =& $dbconn->Execute('OPTIMIZE TABLE ' . $tables['roles']);
    if (!$result) {
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
    //xarModDelVar('installer','userobjectid');
    xarModDelVar('installer','oldprefix');
    xarModDelVar('installer','userid');
    xarModDelVar('installer','contentexpress');
    xarModDelVar('installer','ce_menus');
    echo '<a href="import_ce124.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="index.php">Go to your imported site</a><br>';
    }
}

?>

<?php
if (!isset($step)) {

// catch the output
$return = ob_get_contents();
ob_end_clean();

xarTplSetPageTitle(xarConfigGetVar('Site.Core.SiteName').' :: '.xarML('Import Site'));

//xarTplSetThemeName('Xaraya_Classic');
//xarTplSetPageTemplateName('admin');

// render the page
echo xarTpl_renderPage($return);
}

// Close the session
xarSession_close();

//$dbconn->Close();

flush();

// Kill the debugger
xarCore_disposeDebugger();

// done
exit;
 
// Walk the ce menutree and copy elements into the Categories
// Parent id = -1 at start to start with root CE categories
// categ_parent is set to te current Category ID, if 0 $contentexpress root is used

function walk_ce_menutree($parent_id, $categ_parent = 0) {

    GLOBAL $dbconn, $oldprefix, $contentexpress, $ce_menus;

    // Put the first category in Categories' ContentExpress tree
    if ($categ_parent == 0) { $categ_parent = $contentexpress; }

    // Select category related menu items 
    // Skip direct module links (like [News]), skip separators.
    $query = 'SELECT mc_id, mc_parent_id, mc_title, mc_anchor, mc_anchor_id, mc_active
              FROM ' . $oldprefix . '_ContentExpress_menuitems
              WHERE mc_anchor_id > 0 AND mc_parent_id = '.$parent_id.'
              ORDER BY mc_weight ASC';
    $result = $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select menuitems failed : " . $dbconn->ErrorMsg());
    }
    
    while (!$result->EOF) {
        list($id, $parent_id, $title, $anchor, $anchor_id, $active) = $result->fields;

        // Check if there are any childs for the current menu
        $count_sql = 'SELECT COUNT(*) FROM '.$oldprefix.'_ContentExpress_menuitems
              WHERE mc_anchor_id > 0 AND mc_parent_id = '.$id;
        $count_res = $dbconn->Execute($count_sql);
        list($count_rows) = $count_res->fields;
        error_log("NA $count_sql = $count_rows");

        if ($count_rows == 0) {
            // No childs for this, store as it was its parent (leaf menu
            // does not need to create a new level!)
            $ce_menus[$id] = $categ_parent;

            echo "No new category for leaf item ($id) $title - Parent: $parent_id<br>\n";
        } else {
            // Has childs, create as normal category and find its childs
            $ce_menus[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                      'name' => $title,
                      'description' => $title,
                      'image' => "",
                      'parent_id' => $categ_parent));

            echo "Creating item ($id) $title - Parent: $parent_id<br>\n";
            walk_ce_menutree($id, $ce_menus[$id]);
        }
        $result->MoveNext();
    }
    $result->Close();
}
?>
