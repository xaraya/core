<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_230_02()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_themes';
    
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Adding a configuration field to the themes table
    ");
    $data['reply'] = xarML("
        Success!
    ");
    
    // Run the query
    $dbconn  = xarDB::getConn();
    try {
        $dbconn->begin();
        $query = "ALTER TABLE $table ADD COLUMN configuration TEXT";              
        $dbconn->Execute($query);        
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