<?php
/**
 * File: $Id$
 *
 * Import phpBB forums into your Xaraya test site
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

    echo "<strong>$step. Importing phpBB forums into categories</strong><br/>\n";

    $query = 'SELECT forum_id, cat_id, forum_name, forum_desc, forum_order
              FROM ' . $oldprefix . '_forums
              ORDER BY cat_id ASC, forum_order ASC, forum_id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select forums failed : " . $dbconn->ErrorMsg());
    }
    $forumid = array();
    while (!$result->EOF) {
        list($fid, $cid, $name, $descr, $order) = $result->fields;
        if (!isset($catid[$cid])) {
            echo "Oops - no category id for $cid<br />\n";
            $catid[$cid] = 0;
        }
        $forumid[$fid] = xarModAPIFunc('categories', 'admin', 'create', array(
                              'name' => $name,
                              'description' => $descr,
                              'parent_id' => $catid[$cid]));
        echo "Creating forum ($fid) $name - $descr<br/>\n";
        $result->MoveNext();
    }
    $result->Close();
    xarModSetVar('installer','forumid',serialize($forumid));
    echo '<a href="import_phpbb.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_phpbb.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>
