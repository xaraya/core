<?php
/**
 * File: $Id$
 *
 * Database Insert Tool
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2004 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage database
 * @author Richard Cave <rcave@xaraya.com>
 */

// initialize the Xaraya core
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

// Get new database connection
$dbconn =& xarDBNewConn();

// Get a data dictionary object with item create methods.
$datadict =& xarDBNewDataDict($dbconn);

?>
<form method="POST" action="insert_data.php">
    <p>
     Debug (will only print INSERT statements): 
    <input type="checkbox" name="debug" id="debug" value="1" checked />
    </p>
    <p>
     Number of rows to insert: 
    <input type="text" name="rows" id="rows" value="10" size="6" maxlength="6" />
    </p>
    <p>
    Select table:
    </p>
    <table border="0" cellpadding="2">
<?php

    // Get the Xaraya tables
    foreach($datadict->getTables() as $table) {
        print("<tr><td align=\"left\">");
        print("<input type=\"radio\" value=\"".$table."\" name=\"table\">");
        print("</td><td>");
        print($table);
        print("</td></tr>");
    }

?>
    </table>
    <p>
        <input type="submit" value="Insert Rows">
    </p>
</form>
<?php
    // For BitKeeper QA check
?>
