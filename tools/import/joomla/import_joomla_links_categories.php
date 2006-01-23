<?php
/**
 * Quick & dirty import of Joomla 1.0.4+ weblinks categories into Xaraya web links categories
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

    echo "<strong>$step. Importing old web link categories</strong><br/>\n";

    $weblinks[0] = xarModAPIFunc('categories', 'admin', 'create', array(
                                'name' => 'Web Links',
                                'description' => 'Web Link Categories (Joomla 1.0.4+ style)',
                                'parent_id' => 0));

    $query = 'SELECT id, parent_id, title, name, image, description
              FROM ' . $oldprefix . '_categories
              WHERE section LIKE "com_weblinks"
              ORDER BY parent_id ASC, id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select links_categories failed : " . $dbconn->ErrorMsg());
    }

    while (!$result->EOF) {
        list($id, $parent, $title, $name, $image, $descr) = $result->fields;
        // See if there is parent category associated with this category
        // The parent_id is always 0 here it seems.
        if (empty($parent) || ($parent ==0)) {
            // Set parent category to the weblinks category we just created
            $parent = $weblinks[0];
        } elseif (isset($weblinks[$parent]) && ($weblinks[$parent] > 0)) {
            // Set parent category to the equivalent weblinks category
            $parent = $weblinks[$parent];
        } else {
            // TODO: now what ?
            $parent = $weblinks[0];
        }

        $weblinks[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                                      'name' => $title,
                                      'description' => $name .'-'. $descr,
                                      'image' => "$imgurl/stories/$image",
                                      'parent_id' => $parent));

        echo "Creating web link category ($id) $title - $name - $descr<br/>\n";
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

    echo '<a href="import_joomla.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_joomla.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories']);


?>