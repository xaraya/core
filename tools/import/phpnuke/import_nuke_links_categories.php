<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ web link categories into your Xaraya test site
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

    echo "<strong>$step. Importing old web link categories</strong><br/>\n";

    $weblinks[0] = xarModAPIFunc('categories', 'admin', 'create', array(
                                'name' => 'Web Links',
                                'description' => 'Web Link Categories (.7x style)',
                                'parent_id' => 0));
    $query = 'SELECT cid, parentid, title, cdescription
              FROM ' . $oldprefix . '_links_categories
              ORDER BY parentid ASC, cid ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select links_categories failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($id, $parent, $title, $descr) = $result->fields;
    
        // See if there is parent category associated with this category
        if (empty($parent)) {
            // Set parent category to the weblinks category we just created
            $parent = $weblinks[0];
        } elseif (isset($weblinks[$parent])) {
            // Set parent category to the equivalent weblinks category
            $parent = $weblinks[$parent];
        } else {
            // TODO: now what ?
            $parent = $weblinks[0];
        }

        $weblinks[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                                      'name' => $title,
                                      'description' => $descr,
                                 //     'image' => "$imgurl/topics/$image",
                                      'parent_id' => $parent));
        echo "Creating web link category ($id) $title - $descr<br/>\n";
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

    echo '<a href="import_nuke.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_nuke.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories']);

?>