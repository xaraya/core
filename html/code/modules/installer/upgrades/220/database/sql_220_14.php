<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_220_14()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_dynamic_objects';
    
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Adding an access field to the objects table
    ");
    $data['reply'] = xarML("
        Success!
    ");
    
    // Run the query
    $dbconn  = xarDB::getConn();
    try {
        $dbconn->begin();
        $query = "ALTER TABLE $table ADD COLUMN access TEXT";              
        $dbconn->Execute($query);        
        $dbconn->commit();
        
    } catch (Exception $e) { throw($e);
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