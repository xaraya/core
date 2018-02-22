<?php
/**
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/200.html
 */

function sql_230_06()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_themes';
    
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Adding a class column to the themes table
    ");
    $data['reply'] = xarML("
        Success!
    ");
    
    // Run the query
    $dbconn  = xarDB::getConn();
    try {
        // add the class column
        $dbconn->begin();
        $query = "ALTER TABLE $table ADD COLUMN class TINYINT";              
        $dbconn->Execute($query);
        $dbconn->commit();     

        // get themes from db 
        $dbconn->begin();
        $query = "SELECT themes.regid,
                         themes.directory
                  FROM $table AS themes";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array());
        // update theme classes        
        while($result->next()) {
            list($regid,$directory) = $result->fields;
            $info = xarTheme_getFileInfo($directory);
            if (!$info) continue; // skip themes missing a xartheme.php 
            $query = "UPDATE $table
                      SET class = ? WHERE regid = ?";
            $bindvars = array($info['class'], $regid);
            $stmt = $dbconn->prepareStatement($query);
            $stmt->executeQuery($bindvars);
        }
        $result->close();
        $dbconn->commit();     
        
    } catch (Exception $e) {
        // Damn
        $dbconn->rollback();
        $data['success'] = false;
        $data['reply'] = xarML("
        Failed!
        ");
    }
    return $data;   
    
}
?>