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
 * Note : this file is part of import_xforum.php and cannot be run separately
 */

    echo "<strong>$step. Cleaning up</strong><br/>\n";

    echo "<strong>TODO : import the rest (private messages, groups, ranks, ...)</strong><br/><br/>\n";
   $basecat=xarModGetVar('xarbb','mastercids');
    xarModSetVar('xarbb', 'mastercids', $basecat);
    //xarModDelVar('installer','userobjectid');
    xarModDelVar('installer','oldprefix');
    xarModDelVar('installer','userid');
    xarModDelVar('installer','categories');
    xarModDelVar('installer','catid');
    xarModDelVar('installer','forumid');
    xarModDelVar('installer','threadid');
    xarModDelVar('installer','postid');
    $ptid = xarModGetVar('installer','ptid');
    $url = xarModURL('xarbb','user','view',
                     array('ptid' => $ptid));
    // Enable bbcode hooks for 'forums' pubtype of articles
    if (xarModIsAvailable('bbcode')) {
        xarModAPIFunc('modules','admin','enablehooks',
                      array('callerModName' => 'xarbb', 'callerItemType' => $ptid, 'hookModName' => 'bbcode'));
    }
    xarModDelVar('installer','ptid');
    echo '<a href="import_xforum.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="'.$url.'">Go to your imported forums</a><br/>';

?>