<?php
/**
 /**
 * File: $Id$
 *
 * Quick & dirty import of xForum data into Xaraya test sites
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @original author mikespub <mikespub@xaraya.com>
 * @author jojodee <jojodee@xaraya.com>
*/

/**
 * Note : this file is part of import_xforum.php and cannot be run separately
 */

    echo "<strong>$step. Importing xforum categories into categories</strong><br/>\n";

    $regid = xarModGetIDFromName('xarbb');
    $categories = xarModAPIFunc('categories', 'admin', 'create',
                                array('name' => 'xarBB Forum Index',
                                      'description' => 'xarBB Forum Index',
                                      'parent_id' => 0));
// set this as base category for xarbb
  //    $ptid = xarModGetVar('installer','ptid');
 //     if (!empty($ptid)) {
   //      $settings = unserialize(xarModGetVar('xarbb', 'settings.'.$ptid));
   //   $settings['defaultview'] = 'c' . $categories;
 //      xarModSetVar('xarbb', 'settings.'.$ptid, serialize($settings));
    xarModSetVar('xarbb', 'number_of_categories.1', 1);
        xarModSetVar('xarbb', 'mastercids', $categories);
 //  }

    $query = 'SELECT type , fid, name, displayorder,fup
              FROM `'.$oldprefix.'_XForum_forums`
              WHERE TYPE = \'group\' OR TYPE = \'forum\'';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select categories failed : " . $dbconn->ErrorMsg());
    }
    $catid = array();
    while (!$result->EOF) {
        list($ctype, $id, $title, $order,$fup) = $result->fields;
        if ($fup==0) {
        $catid[$id] = xarModAPIFunc('categories', 'admin', 'create',
                                    array('name' => $title,
                                          'description' => $title,
                                          'parent_id' => $categories));
        echo "Creating category ($id) $title<br/>\n";
        }else {
        $catid[$id] = xarModAPIFunc('categories', 'admin', 'create',
                                    array('name' => $title,
                                          'description' => $title,
                                          'parent_id' => $catid[$fup]));
        }
        $result->MoveNext();
    }
    $result->Close();
    xarModSetVar('installer','categories',$categories);
    xarModSetVar('installer','catid',serialize($catid));

?>
