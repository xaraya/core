<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ sections into your Xaraya test site
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

    echo "<strong>$step. Importing old sections into categories</strong><br/>\n";
    echo "Creating root for old sections<br/>\n";
    $sections = xarModAPIFunc('categories', 'admin', 'create', array(
                             'name' => 'Sections',
                             'description' => 'Document Sections (.7x style)',
                             'parent_id' => 0));
    if ($reset) {
        $settings = unserialize(xarModGetVar('articles', 'settings.2'));
        $settings['number_of_categories'] = 1;
        $settings['cids'] = array($sections);
        $settings['defaultview'] = 'c' . $sections;
        xarModSetVar('articles', 'settings.2', serialize($settings));
        xarModSetVar('articles', 'number_of_categories.2', 1);
        xarModSetVar('articles', 'mastercids.2', $sections);
    }
    if ($sections > 0) {
        $query = 'SELECT secid, secname, image
                  FROM ' . $oldprefix . '_sections
                  ORDER BY secid ASC';
        $result =& $dbconn->Execute($query);
        if (!$result) {
            die("Oops, select sections failed : " . $dbconn->ErrorMsg());
        }
        while (!$result->EOF) {
            list($id, $name, $image) = $result->fields;
            $sectionid[$id] = xarModAPIFunc('categories', 'admin', 'create', array(
                              'name' => $name,
                              'description' => $name,
                              'image' => "$imgurl/sections/$image",
                              'parent_id' => $sections));
            echo "Creating section ($id) $name [$image]<br/>\n";
            $result->MoveNext();
        }
        $result->Close();
    }
    echo "<strong>TODO : copy the section images to modules/categories/xarimages or elsewhere someday</strong><br/><br/>\n";
    xarModSetVar('installer','sections',$sections);
    xarModSetVar('installer','sectionid',serialize($sectionid));
    echo '<a href="import_nuke.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_nuke.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';

?>