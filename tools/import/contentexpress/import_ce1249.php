<?php
/**
 * File: $Id$
 *
 * Importing of ContentExpress content (based on import8.php)
 * Updated for ContentExpress 1.2.4.9 (will not work with older versions!)
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

    if(!xarVarFetch('step',     'isset', $step,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('startnum', 'isset', $startnum,  NULL, XARVAR_DONT_SET)) {return;}


// pre-fill the module name (if any) for hooks
xarRequestGetInfo();

if (!isset($step)) {
// start the output buffer
ob_start();
}
?>

<h3>ContentExpress V1.2.4.9 import from an existing .71 site</h3>

<?php
$prefix = xarDBGetSystemTablePrefix();

// Get user entered information about the old database (from Form or mod_vars)
if (isset($step)) {
    if ($step == 1 && !isset($startnum)) {
        if (!xarVarFetch('oldprefix', 'str:1:', $oldprefix, '')) return;
        if (!xarVarFetch('subarticles', 'str:1:', $subarticles, '')) return;
    } elseif ($step > 1 || isset($startnum)) {
        $oldprefix = xarModGetVar('installer','oldprefix');
        $subarticles = xarModGetVar('installer','subarticles');
    }
}

// If just invoked display the starting form
if (!isset($oldprefix) || $oldprefix == $prefix || !preg_match('/^[a-z0-9_-]+$/i',$oldprefix)) {
?>
    Requirement : you must be using the same database, but a different prefix...
    <p></p>
    <form method="POST" action="import_ce1249.php">
    <table border="0" cellpadding="4">
    <tr><td align="right">Prefix used in your .71 site</td><td>
    <input type="text" name="oldprefix" value="not '<?php echo $prefix ?>' !"></td></tr>
    <tr><td align="right">What to do with articles linked to another article and not to a menu?</td>
    <td><select name="subarticles">
    <option value="1">Keep them separated and add to their parent's category
    <option value="2">Concatenate children into one article
    </select>
    </td></tr>
    <tr><td colspan=2 align="middle">
    <input type="submit" value=" Import Data "></td></tr>
    </table>
    <input type="hidden" name="step" value="1">
    <input type="hidden" name="module" value="articles">
    </form>
    <p>Note : you must at least activate the 'categories' and 'articles' modules first. Activating 'comments' and 'hitcount' is also a good idea :-)</p>
    <p>Note2: this script sets your Documents publication type category to the new ContentExpress root, you may edit this later manually to get your other docs back (for exmaple move the Sections category into ContentExpress).</p>
    <p>Images: copy your img_repository folder into var/img_repository, image tags will be directly copied into your articles text by this script.</p>
    <p>Warning: please make a backup of your database before running this script and restore it if the script fails or you are not satisfied with the result.</p>
<?php
} else {
    // If step 1 then store form data about the old DB in mod_vars.
    if ($step == 1 && !isset($startnum)) {
        xarModSetVar('installer','oldprefix',$oldprefix);
        xarModSetVar('installer','subarticles',$subarticles);
    }

/*
    // log in admin user
    if (!xarUserLogIn('Admin', 'password', 0)) {
        die('Unable to log in');
    }
*/

    // Inicialize APIs and so.

    $dbconn =& xarDBGetConn();

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
    $tables =& xarDBGetTables();

    // STEP 1
    //---------------------------------------------------------
    // Copy menutree into categories

    if ($step == 1) {
    echo "<strong>1. Importing menus into categories</strong><br/>\n";

    $regid = xarModGetIDFromName('articles');
    echo "Creating root for old ContentExpress<br/>\n";
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

    echo '<a href="import_ce1249.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_ce1249.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    }

    // STEP 2 
    //---------------------------------------------------------
    // Copy articles

    if ($step == 2) {

    $contentexpress = xarModGetVar('installer','contentexpress');
    $ce_menus = unserialize(xarModGetVar('installer','ce_menus'));

    echo "<strong>2. Importing articles</strong><br/>\n";
    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_ce_contentitems';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count stories failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $regid = xarModGetIDFromName('articles');

    // Copy articles tree structure
    walk_ce_articletree(-1,0,"");
    
    echo "<strong>TODO : add comments, ratings.</strong><br/><br/>\n";
    echo '<a href="import_ce1249.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    
    echo '<a href="index.php">Go to your imported site</a><br/>';
    //echo '<a href="import_ce1249.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    }
    
    // STEP next
    //---------------------------------------------------------

    // No more steps actually, but I keep this comments-import for ones who are 
    // interested. But since correct hooks are so new in CE, I dont think there are
    // many sites with comments/ratings.

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
    echo "<strong>9. Importing comments</strong><br/>\n";
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
        $data['itemtype'] = 2; // documents
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
            echo "Failed inserting comment ($sid $pid) $uname - $subject : ".$dbconn->ErrorMsg()."<br/>\n";
        } elseif ($count < 200) {
            echo "Inserted comment ($sid $pid) $uname - $subject<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted comment " . ($num + $startnum) . "<br/>\n";
            flush();
        }
        $pid2cid[$tid] = $cid;
        $num++;
        $result->MoveNext();
    }
    $result->Close();

    echo "<strong>TODO : import other comments</strong><br/><br/>\n";
    echo '<a href="import8.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        xarModSetVar('installer','commentid',serialize($pid2cid));
        $startnum += $numitems;
        echo '<a href="import8.php?step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - comments ' . $startnum . '+ of ' . $count . '</a><br/>';
    } else {
        xarModDelVar('installer','commentid');
        echo '<a href="import8.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    }
    }


// TODO: add the rest :-)

    if ($step == 12) {
    echo "<strong>12. Optimizing database tables</strong><br/>\n";
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

    echo "<strong>TODO : import the rest...</strong><br/><br/>\n";
    //xarModDelVar('installer','userobjectid');
    xarModDelVar('installer','oldprefix');
    xarModDelVar('installer','userid');
    xarModDelVar('installer','contentexpress');
    xarModDelVar('installer','ce_menus');
    xarModDelVar('installer','subarticles');
    echo '<a href="import_ce1249.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="index.php">Go to your imported site</a><br/>';
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

// done
exit;
 
//--------------------------------------------------------------
// Walk the ce menutree and copy elements into the Categories
// Parent id = -1 at start to start with root CE categories
// categ_parent is set to te current Category ID, if 0 $contentexpress root is used

function walk_ce_menutree($parent_id, $categ_parent = 0) 
{

    GLOBAL $dbconn, $oldprefix, $contentexpress, $ce_menus;

    // Put the first category in Categories' ContentExpress tree
    if ($categ_parent == 0) { $categ_parent = $contentexpress; }

    // Select category related menu items 
    // Skip direct module links (like [News]), skip separators.
    $query = 'SELECT mc_id, mc_parent_id, mc_title, mc_active
              FROM ' . $oldprefix . '_me_menuitems
              WHERE mc_type = 3 AND mc_parent_id = '.$parent_id.'
              ORDER BY mc_weight ASC';
    $result = $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select menuitems failed : " . $dbconn->ErrorMsg());
    }
    
    while (!$result->EOF) {
        list($id, $parent_id, $title, $active) = $result->fields;

        // Check if there are any childs for the current menu
        $count_sql = 'SELECT COUNT(*) FROM '.$oldprefix.'_me_menuitems
              WHERE mc_type = 3 AND mc_parent_id = '.$id;
        $count_res = $dbconn->Execute($count_sql);
        list($count_rows) = $count_res->fields;
        error_log("NA $count_sql = $count_rows");

        if ($count_rows == 0) {
            // No childs for this, store as it was its parent (leaf menu
            // does not need to create a new level!)
            $ce_menus[$id] = $categ_parent;

            echo "No new category for leaf item ($id) $title - Parent: $parent_id<br/>\n";
        } else {
            // Has childs, create as normal category and find its childs
            $ce_menus[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                      'name' => $title,
                      'description' => $title,
                      'image' => "",
                      'parent_id' => $categ_parent));

            echo "Creating item ($id) $title - Parent: $parent_id<br/>\n";
            walk_ce_menutree($id, $ce_menus[$id]);
        }
        $result->MoveNext();
    }
    $result->Close();
}

//--------------------------------------------------------------
// Walk the article tree and copy articles to Xaraya
// Depending on the user selection, child articles are stored as
// separated articles or concatenated into one.

function walk_ce_articletree ($parent, $latest_ok_category, $cat_string) 
{

    GLOBAL $dbconn, $oldprefix, $contentexpress, $ce_menus, $subarticles;

    // First ok category is the CE root
    if (!$latest_ok_category) { $latest_ok_category = $contentexpress; }

    // Get content imtems
    $query = 'SELECT mc_id, mc_parent_id, mc_cat_id, mc_title, mc_text, mc_media_url, mc_media_width, mc_media_height, mc_layout_id, mc_status, mc_weight, mc_active
              FROM ' . $oldprefix . '_ce_contentitems
              WHERE mc_parent_id = '.$parent.'
              ORDER BY mc_weight ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select contentitems failed : " . $dbconn->ErrorMsg());
    }

    $num = 1;
    while (!$result->EOF) {
        // Get the next article
        list($id, $parent_id, $cat_id, $title, $text, $media_url, $media_width,
            $media_height, $layout_id, $status, $weight, $active) = $result->fields;


        // Target category
        $cids = array();

        if ($parent == -1) {
            // Root categories should have a menu (or attach to $contentexpress)

            // Find the menu id (1.2.4.9 stopped using a direct reference, the menu ID
            // has to be found in the URI of the menus.)
            $menufind_sql = "SELECT mc_id FROM ".$oldprefix."_me_menuitems
                WHERE mc_uri LIKE '%ceid=$id' 
                LIMIT 1";
            $menufind_result=$dbconn->Execute($menufind_sql);

            // Add category ID to CIDS
            if ($menufind_result->EOF) {
                // Not found, put the article in root
                $cids[] = $contentexpress;
            } else {
                // Found, connect to the right category
                list($menu_id) = $menufind_result->fields;
                $cids[] = $ce_menus[$menu_id];
            }
            // This category will be used for children too, if any
            $latest_ok_category = $cids[0];
        } else {
            // Nonroot categories use the $latest_ok_category if
            // creating multiple articles for children
            $cids[] = $latest_ok_category;
        }

        if ($media_url) {
            // Add media to the article (TODO: non image media does not work.)
            $imgtags = '';
            if ($media_width > 0) { $imgtags .= ' width='.$media_width; }
            if ($media_height > 0) { $imgtags .= ' height='.$media_height; }
            if (($layout_id == 1) || ($layout_id == 3)) { $imgtags .= ' align="left"'; }
            if (($layout_id == 2) || ($layout_id == 4)) { $imgtags .= ' align="right"'; }
            if (($layout_id == 5) || ($layout_id == 6)) { $imgtags .= ' align="center"'; }
            // Add to top
            if (($layout_id == 1) || ($layout_id == 2) || ($layout_id == 5)) {
                $text = '<img src=var/'.$media_url.' '.$imgtags.'/>'.$text;

            // Add to bottom
            } elseif (($layout_id == 3) || ($layout_id == 4) || ($layout_id == 6)) {
                $text .= '<img src=var/'.$media_url.' '.$imgtags.'/>';
            }
        }
        
        // Child articles, separate or concatenate?
        if ($subarticles == 2) {
            // Contatenating all children articles into one big article before storing
            if (!$cat_string) { 
                // If no cat_string yet, initiate collecting
                $cat_string = $text;
                $base_title = $title;
                $base_cids = $cids;
                $store_now = 0;
            } else {
                // Already collected some
                if ($parent_id == -1) {
                    // Back at root? Then collection is over.
                    $store_now = 1;
                } else {
                    // Not root yet, add article and continue
                    $cat_string .= "<br clear=\"all\"/><h1>$title</h1>$text";
                    $store_now = 0;
                }
            }

        } else {
            // Creating separated articles for each children article
            // and store then in the latest ok category (biggest parent)
            $store_now = 1;
            $cat_string = $text;
            $base_title = $title;
            $base_cids = $cids;
        }

        if ($store_now) {
            // Create new article
            $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array(//'aid' => $id,
                                      'title' => $base_title,
                                      //'summary' => $summary,
                                      'body' => $cat_string,
                                      //'notes' => $notes,
                                      'status' => ($active == 1 ? 3 : 0),
                                      'ptid' => 2,
                                      //'pubdate' => $pubdate, (defaults to NOW)
                                      //'authorid' => $authorid,
                                      //'language' => $language,  what was this?
                                      'cids' => $base_cids,
                                      //'hits' => $counter
                                     )
                               );

            // Print status or error
            if (!isset($newaid)) {
                echo "Insert article ($id) $title failed : " . xarExceptionRender('text') . "<br/>\n";
            } elseif ($count < 200) {
                echo "Inserted article ($id) $title into $cids[0]<br/>\n";
            } elseif ($num % 100 == 0) {
                echo "Inserted article " . ($num + $startnum) . "<br/>\n";
                flush();
            }
            $num++;

            // Keep the current item for next childsearching
            if ($parent_id == -1) {
                $cat_string = $text;
                $base_title = $title;
                $base_cids = $cids;
            }
        }

        // Walk the tree!
        $cat_string = walk_ce_articletree($id, $latest_ok_category, $cat_string);

        $result->MoveNext();
    }
    $result->Close();
    return $cat_string; // Return for the collection
}
?>
