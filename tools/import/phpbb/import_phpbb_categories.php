<?php
/**
 * File: $Id$
 *
 * Import phpBB categories into your Xaraya test site
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

    echo "<strong>$step. Importing phpBB categories into categories</strong><br/>\n";

    $categories = xarModAPIFunc('categories', 'admin', 'create',
                                array('name' => 'Forum Index',
                                      'description' => 'Forum Index',
                                      'parent_id' => 0));
    // set this as base category for forums
    if ($importmodule == 'articles') {
        $ptid = xarModGetVar('installer','ptid');
        if (!empty($ptid)) {
            $settings = unserialize(xarModGetVar('articles', 'settings.'.$ptid));
            $settings['defaultview'] = 'c' . $categories;
            xarModSetVar('articles', 'settings.'.$ptid, serialize($settings));
            xarModSetVar('articles', 'number_of_categories.'.$ptid, 1);
            xarModSetVar('articles', 'mastercids.'.$ptid, $categories);
        }
    } else {
        xarModSetVar('xarbb', 'number_of_categories', 1);
        xarModSetVar('xarbb', 'mastercids', $categories);
    }

    $query = 'SELECT cat_id, cat_title, cat_order
              FROM ' . $oldprefix . '_categories
              ORDER BY cat_order ASC, cat_id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select categories failed : " . $dbconn->ErrorMsg());
    }
    $catid = array();
    while (!$result->EOF) {
        list($id, $title, $order) = $result->fields;
        $catid[$id] = xarModAPIFunc('categories', 'admin', 'create',
                                    array('name' => $title,
                                          'description' => $title,
                                          'parent_id' => $categories));
        echo "Creating category ($id) $title<br/>\n";
        $result->MoveNext();
    }
    $result->Close();
    xarModSetVar('installer','categories',$categories);
    xarModSetVar('installer','catid',serialize($catid));

?>
