<?php
/**
 * File: $Id$
 *
 * Import Slashcode discussions into your Xaraya test site
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

    echo "<strong>$step. Importing discussions</strong><br/>\n";

    // Import discussions
    $discussioncount = xarModGetVar('installer','discussioncount');
    echo "Found " . $discussioncount . " discussions<br/>\n";

    // Set table names
    $table_discussions = 'discussions';

    $query = 'SELECT COUNT(id) FROM ' . $table_discussions . ' WHERE stoid > 0';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count of " . $table_discussions . " failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    echo "including " . $count . " story discussions<br/>\n";

    $polldiscussions = unserialize(xarModGetVar('installer','polldiscussions'));
    echo "including " . count($polldiscussions) . " poll discussions<br/>\n";

    echo "<strong>TODO : do something with other discussions ?</strong><br/>\n";

    echo '<a href="import_slashcode.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    echo '<a href="import_slashcode.php?step=' . ($step+1) . '&module=comments">Go to step ' . ($step+1) . '</a><br/>';

?>
