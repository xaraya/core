<?php
/**
 * Import PostNuke .71+ cleanup for your Xaraya test site
 *
 * @package tools
 * @copyright (C) 2003 The Digital Development Foundation
 * @link http://www.xaraya.com
 *
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Note : this file is part of import_pn.php and cannot be run separately
 */

    echo "<strong>$step. Cleaning up</strong><br/>\n";

    echo "<strong>TODO : import the rest...</strong><br/><br/>\n";
    //xarModDelVar('installer','userobjectid');
    xarModDelVar('installer','oldprefix');
    xarModDelVar('installer','reset');
    xarModDelVar('installer','resetcat');
    xarModDelVar('installer','imgurl');
    xarModDelVar('installer','userid');
    xarModDelVar('installer','topics');
    xarModDelVar('installer','topicid');
    xarModDelVar('installer','categories');
    xarModDelVar('installer','catid');
    xarModDelVar('installer','sections');
    xarModDelVar('installer','sectionid');
    xarModDelVar('installer','faqs');
    xarModDelVar('installer','faqid');
    xarModDelVar('installer','weblinks');

    // phpBB_14 cleanup
    xarModDelVar('installer','importmodule');
    xarModDelVar('installer','forumid');
    xarModDelVar('installer','postid');

    // CHECKME - this vars used in two places - topics and phpbb14
    // xarModDelVar('installer','categories');
    // xarModDelVar('installer','catid');
    // xarModDelVar('installer','topicid');

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

    echo '<a href="import_pn.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="index.php">Go to your imported site</a><br/>';

?>