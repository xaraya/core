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
    xarModDelVar('installer','importmodule');
    xarModDelVar('installer','userid');
    xarModDelVar('installer','categories');
    xarModDelVar('installer','catid');
    xarModDelVar('installer','forumid');
    xarModDelVar('installer','topicid');
    xarModDelVar('installer','postid');

if ($importmodule == 'articles') {
    $ptid = xarModGetVar('installer','ptid');
    $url = xarModURL('articles','user','view',
                     array('ptid' => $ptid));
    // Enable bbcode hooks for 'forums' pubtype of articles
    if (xarModIsAvailable('bbcode') && !xarModIsHooked('bbcode','articles',$ptid)) {
        xarModAPIFunc('modules','admin','enablehooks',
                      array('callerModName' => 'articles', 'callerItemType' => $ptid, 'hookModName' => 'bbcode'));
    }
} else {
    if (xarModIsAvailable('bbcode') && !xarModIsHooked('bbcode','xarbb')) {
        xarModAPIFunc('modules','admin','enablehooks',
                      array('callerModName' => 'xarbb', 'hookModName' => 'bbcode'));
    }
    $url = xarModURL('xarbb','user','main');
}
    xarModDelVar('installer','ptid');
    echo '<a href="import_phpbb.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="'.$url.'">Go to your imported forums</a><br/>';

?>
