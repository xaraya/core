<?php
/**
 * Quick & dirty import of Joomla 1.0.4+ data into Xaraya test sites
 *
 * @package tools
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 * @author michelv <michelv@xaraya.com>
 */

/**
 * Note : this file is part of import_joomla.php and cannot be run separately
 */

    echo "<strong>$step. Cleaning up</strong><br/>\n";

    echo "<strong>TODO : import the rest...</strong><br/><br/>\n";
    // Remove all temporary vars
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

    echo '<a href="import_joomla.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="index.php">Go to your imported site</a><br/>';

?>