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

function sql_220_13()
{
    $prefix = xarDB::getPrefix();
    $roles_table = $prefix . '_roles';
    $props_table = $prefix . '_dynamic_properties';
    
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Refactoring roles display name as textbox
    ");
    $data['reply'] = xarML("
        Success!
    ");
    
    //Load Table Maintainance API
    sys::import('xaraya.tableddl');    
    $dbconn  = xarDB::getConn();
    try {
        $dbconn->begin();
        // get the list of available hooks 
        $bindvars = array();
        $query = "SELECT r.id, r.name
                  FROM $roles_table r";
              
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);        
        $roles = array();
        while($result->next()) {      
            list($id, $name) = $result->fields;
            $roles[$id] = array('id' => $id, 'name' => $name);
        }    
        $result->close();
        
        foreach ($roles as $role) {
            if (strpos($role['name'], '%') !== false) {
                $value = explode('%', $role['name']);
                if (!empty($value[0])) {
                    $name = $value[0];
                } else {
                    $name = !empty($value[1]) ? $value[1] : '';
                    if (!empty($value[2]))
                        $name .= ' ' . $value[2];
                    if (!empty($value[3]))
                        $name .= ' ' . $value[3];
                    if (!empty($value[4]))
                        $name .= ' ' . $value[4];
                }
                $query = "UPDATE $roles_table SET `name` = $name WHERE `id` = $role[id]";
                $dbconn->execute($query);
            }
        }
        $query = "UPDATE $props_table SET `type` = 2 WHERE `object_id` = 8 AND `type` = 30095";        
        $dbconn->execute($query);
        
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