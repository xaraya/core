<?php
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
 * @ based on phpBB import by author mikespub <mikespub@xaraya.com>
 * @ author jojodee <jojodee@xaraya.com>
*/

/**
 * Note : this file is part of import_xForum.php and cannot be run separately
 */
 //
    echo "<strong>$step. Creating xForum forums and allocating to categories</strong><br/>\n";

   $forumusers= xarModGetVar('installer','userid');
    if (!isset($forumusers)) {
        $forumusers = array();
    } else {
        $forumusers = unserialize($forumusers);
    }

    $catids= unserialize(xarModGetVar('installer', 'catid'));
    $query = 'SELECT fid, name, description, displayorder, posts, threads, fup
              FROM `'.$oldprefix.'_XForum_forums`
              WHERE type=\'forum\' OR type=\'sub\'
              ORDER BY fup ASC, fid ASC';

    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select forums failed : " . $dbconn->ErrorMsg());
    }

    $forumid = array();
    $catinfo = array();
    while (!$result->EOF) {
        list($id, $name, $descr, $order, $fposts, $fthreads, $fup) = $result->fields;
        if (!isset($catids[$fup])) {
            echo "Oops - no category id for $id<br />\n";
       }

       if ((empty($fup)) || ($fup==0)){
           $cids[$id] = xarModGetVar('xarbb', 'mastercids.1');
       } else {
           $cids[$id]=array($catids[$fup]);
       }
       // The API function is called
       // Hmmm - can't directly create all new data in forum here with current xarBB function
          $forumid[$id]=xarModAPIFunc('xarbb',
                               'admin',
                               'create',
                               array('fname'    => $name,
                                     'fdesc'    => $descr,
                                     'cids'     => $cids[$id],
                                     'fposter'  => 3)); //Set all forum creation to Admin


        if ($forumid[$id]) {
           echo "Creating forum ($fup) ->$forumid[$id] - $name - $descr<br/>\n";
        }else {
           echo "Problem with forum ". $forumid[$id];
        }

       $result->MoveNext();
    }
    $result->Close();
    xarModSetVar('installer','forumid',serialize($forumid));
    //Reset mastercid again - seems to be getting lost
    $cats=xarModGetVar('installer','categories');
    xarModSetVar('xarbb', 'mastercids', $cats);

    echo '<a href="import_xforum.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_xforum.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>