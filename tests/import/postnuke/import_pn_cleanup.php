<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ cleanup for your Xaraya test site
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

    echo "<strong>$step. Cleaning up</strong><br>\n";

    echo "<strong>TODO : import the rest...</strong><br><br>\n";
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
    echo '<a href="import_pn.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="index.php">Go to your imported site</a><br>';

?>
