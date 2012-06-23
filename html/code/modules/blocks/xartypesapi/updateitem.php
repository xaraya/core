<?php
/**
 * @package modules
 * @subpackage blocks module
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * @author Chris Powis <crisp@xaraya.com>
 * @todo 
**/
function blocks_typesapi_updateitem(Array $args=array())
{
    if (empty($args)) {
        $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
        $vars = array('arguments', 'blocks', 'typesapi', 'updateitem');
        throw new EmptyParameterException($vars, $msg);
    }
    
    extract($args);    
    
    if (empty($type_id) || !is_numeric($type_id))
        $invalid[] = 'type_id';
    
    if (isset($type_state) && !is_numeric($type_state))
        $invalid[] = 'type_state';
    
    if (isset($type_category) && (!is_string($type_category) || strlen($type_category) > 64) )
        $invalid[] = 'type_category';
    
    if (isset($type_info) && !is_array($type_info))
        $invalid[] = 'type_info';
    
    if (!empty($invalid)) {
        $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
        $vars = array(join(', ', $invalid), 'blocks', 'typesapi', 'updateitem');
        throw new BadParameterException($vars, $msg);
    }

    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();
    $types_table = $tables['block_types'];    
    $set = array();
    $where = array();
    $bindvars = array();
    
    if (isset($type_state)) {
        $set[] = 'state = ?';
        $bindvars[] = $type_state;
    }
    
    if (isset($type_category)) {
        $set[] = 'category = ?';
        $bindvars[] = $type_category;
    }
    
    if (isset($type_info)) {
        $set[] = 'info = ?';
        $bindvars[] = serialize($type_info);
    }
    
    // someone passed us a type_id without params to update, just return the type_id
    if (empty($set)) return $type_id;
    
    $where[] = 'id = ?';
    $bindvars[] = $type_id;
        
    $query = "UPDATE $types_table";
    $query .= " SET " . join(',', $set);
    $query .= " WHERE " . join(' AND ', $where);

    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars);
    if (!$result) return;

    return $type_id;
    
}
?>