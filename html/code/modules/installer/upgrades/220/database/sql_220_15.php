<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_220_15()
{
    // Define parameters
    $propertytable = xarDB::getPrefix() . '_dynamic_properties';
    $objecttable = xarDB::getPrefix() . '_dynamic_objects';
    
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Adding an access property to the objects object
    ");
    $data['reply'] = xarML("
        Success!
    ");
    
    // Run the query
    $dbconn  = xarDB::getConn();
    try {
        $dbconn->begin();
        $query = "INSERT INTO $propertytable (name, label, object_id, type, defaultvalue, source, status, seq,configuration)
    VALUES ('access', 'Access', 1, 2, '', '" . $objecttable . ".access', 67, 10, 'a:0:{}')";              
        $dbconn->Execute($data['sql']);        
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