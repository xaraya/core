<?php
/**
 * File: $Id$
 *
 * Import phpBB clean-up for your Xaraya test site
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

    echo "<strong>$step. Cleaning up</strong><br/>\n";

    echo "<strong>TODO : import the rest (private messages, groups, ranks, ...)</strong><br/><br/>\n";
    //xarModDelVar('installer','userobjectid');
    xarModDelVar('installer','oldprefix');
    xarModDelVar('installer','userid');
    xarModDelVar('installer','categories');
    xarModDelVar('installer','catid');
    xarModDelVar('installer','forumid');
    xarModDelVar('installer','topicid');
    xarModDelVar('installer','postid');
    $ptid = xarModGetVar('installer','ptid');
    $url = xarModURL('articles','user','view',
                     array('ptid' => $ptid));
    // Enable bbcode hooks for 'forums' pubtype of articles
    if (xarModIsAvailable('bbcode')) {
        xarModAPIFunc('modules','admin','enablehooks',
                      array('callerModName' => 'articles', 'callerItemType' => $ptid, 'hookModName' => 'bbcode'));
    }
    xarModDelVar('installer','ptid');
    echo '<a href="import_phpbb.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="'.$url.'">Go to your imported forums</a><br/>';

?>
