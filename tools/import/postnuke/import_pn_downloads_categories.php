<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ downloads categories into your Xaraya test site
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

    echo "<strong>$step. Importing old downloads categories</strong><br/>\n";

    $downloads_cats[0] = xarModAPIFunc('categories', 'admin', 'create', array(
                                'name' => 'Downloads',
                                'description' => 'Downloads Categories (.7x style)',
                                'parent_id' => 0));

    $query = 'SELECT pn_cid, pn_title, pn_description
              FROM ' . $oldprefix . '_downloads_categories
              ORDER BY pn_cid ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select downloads_categories failed : " . $dbconn->ErrorMsg());
    }

    while (!$result->EOF) {
        list($id, $title, $descr) = $result->fields;
        // Set parent category to the weblinks category we just created
        $parent = $downloads_cats[0];

        $downloads_cats[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                                      'name' => $title,
                                      'description' => $descr,
                                      'parent_id' => $parent));

        echo "Creating downloads category ($id) $title - $descr<br/>\n";
        $result->MoveNext();
    }
    $result->Close();
    xarModSetVar('installer','downloads_cats',serialize($downloads_cats));

    $settings = unserialize(xarModGetVar('articles', 'settings.8'));
    $settings['number_of_categories'] = 1;
    $settings['cids'] = array($downloads_cats[0]);
    xarModSetVar('articles', 'settings.8', serialize($settings));
    xarModSetVar('articles', 'number_of_categories.8', 1);
    xarModSetVar('articles', 'mastercids.8', $downloads_cats[0]);

    echo '<a href="import_pn.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_pn.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories']);


?>