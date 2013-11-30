<?php
/**
 * @package modules\blocks
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * @author Chris Powis <crisp@xaraya.com>
 * @todo 
**/

/**
 * 
 * Updates an item in the API
 * 
 * @param array $args
 * @return integer Returns block id
 * @throws BadParameterException
 */
function blocks_instancesapi_updateitem(Array $args=array())
{
    extract($args);
    
    if (empty($block_id) || !is_numeric($block_id))
        $invalid[] = 'block_id';
    
    if (isset($state) && !is_numeric($state))
        $invalid[] = 'state';
        
    if (isset($name) && (!is_string($name) || strlen($name) > 64))
        $invalid[] = 'name';
        
    if (isset($title) && (!is_string($title) || strlen($title) > 254))
        $invalid[] = 'title';
    
    if (isset($content) && !is_array($content))
        $invalid[] = 'content'; 

    if (!empty($invalid)) {
        $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
        $vars = array(join(', ',$invalid), 'blocks', 'instancesapi', 'updateitem');
        throw new BadParameterException($vars, $msg);
    }

    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();
    $blocks_table = $tables['block_instances'];    
    $set = array();
    $where = array();
    $bindvars = array();
    
    if (isset($name)) {
        $set[] = 'name = ?';
        $bindvars[] = $name;
    }
    
    if (isset($title)) {
        $set[] = 'title = ?';
        $bindvars[] = $title;
    }    
    
    if (isset($state)) {
        $set[] = 'state = ?';
        $bindvars[] = $state;
    }
    
    if (isset($content)) {
        $set[] = 'content = ?';
        $bindvars[] = serialize($content);
    }

    // someone passed us a block_id without params to update, just return the block_id
    if (empty($set)) return $block_id;

    $where[] = 'id = ?';
    $bindvars[] = $block_id;
        
    $query = "UPDATE $blocks_table";
    $query .= " SET " . join(',', $set);
    $query .= " WHERE " . join(' AND ', $where);

    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars);
    if (!$result) return;

    return $block_id;
}
?>