<?php
/**
 * File: $Id$
 *
 * Import Slashcode cleanup for your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author Richard Cave <rcave@xaraya.com>
 */

/**
 * Note : this file is part of import_slashcode.php and cannot be run separately
 */

    echo "<strong>$step. Cleaning up</strong><br/>\n";

    // Set maximum_execution_time back to original value
    $old_max_execution_time = xarModGetVar('installer','old_max_execution_time');
    ini_set('maximum_execution_time', $old_max_execution_time);

    // Drop temporary topics table
    $table_topics_tree = xarDBGetSiteTablePrefix() . '_installer_topics';
    $dbimport->Execute("DROP TABLE " . $table_topics_tree);

    // Drop temporary userids table
    $table_userids = xarDBGetSiteTablePrefix() . '_installer_userids';
    $dbimport->Execute("DROP TABLE " . $table_userids);

    // Drop temporary commentids table
    $table_commentids = xarDBGetSiteTablePrefix() . '_installer_commentids';
    $dbimport->Execute("DROP TABLE " . $table_commentids);

    xarModDelVar('installer','dbtype');
    xarModDelVar('installer','old_max_execution_time');
    xarModDelVar('installer','new_max_execution_time');
    xarModDelVar('installer','importdbtype');
    xarModDelVar('installer','importdb');
    xarModDelVar('installer','reset');
    xarModDelVar('installer','resetcat');
    xarModDelVar('installer','userimport');
    xarModDelVar('installer','usercount');
    xarModDelVar('installer','storyimport');
    xarModDelVar('installer','storycount');
    xarModDelVar('installer','submissionimport');
    xarModDelVar('installer','submissioncount');
    xarModDelVar('installer','commentimport');
    xarModDelVar('installer','commentcount');
    xarModDelVar('installer','discussionimport');
    xarModDelVar('installer','discussioncount');
    xarModDelVar('installer','sections_cid');
    xarModDelVar('installer','sections');
    xarModDelVar('installer','categories_cid');
    xarModDelVar('installer','categories');
    xarModDelVar('installer','sections');
    xarModDelVar('installer','sectionid');
    xarModDelVar('installer','defaultgid');
    xarModDelVar('installer','admingid');
    xarModDelVar('installer','articles');
    //xarModDelVar('installer','userid');
    xarModDelVar('installer','polldiscussions');

    echo "<strong>TODO : import the rest...</strong><br/><br/>\n";
    echo '<a href="import_slashcode.php">Return to start of Slashcode import</a>&nbsp;&nbsp;&nbsp;
          <a href="index.php">Go to your imported site</a><br/>';

?>
