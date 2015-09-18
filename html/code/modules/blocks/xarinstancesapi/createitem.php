<?php
/**
 * @package modules\blocks
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * Create an item
 * 
 * @author Chris Powis <crisp@xaraya.com>
 * 
 * @param array $args Parameter data array
 * @return type Returns the block id of the newly created item
 * @throws BadParameterException
 * @throws IdNotFoundException
 * @throws DuplicateException
 */
function blocks_instancesapi_createitem(Array $args=array())
{
    extract($args);
    
    if (empty($type_id) || !is_numeric($type_id))
        $invalid[] = 'type_id';
    
    if (empty($name) || !is_string($name) || strlen($name) > 64 || !preg_match('!^([a-z0-9_])*$!', $name))
        $invalid[] = 'name';

    if (!isset($title))
        $title = '';    
        
    if (!is_string($title) || strlen($title) > 254)
        $invalid[] = 'title';
    
    if (!isset($state))
        $state = xarBlock::BLOCK_STATE_VISIBLE;
    $states = xarMod::apiFunc('blocks', 'instances', 'getstates');

    if (!is_numeric($state) || !isset($states[$state]))
        $invalid[] = 'state';
    
    if (isset($content) && !is_array($content))
        $invalid[] = 'content';    
    
    if (!empty($invalid)) {
        $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
        $vars = array(join(', ',$invalid), 'blocks', 'instancesapi', 'createitem');
        throw new BadParameterException($vars, $msg);
    }
    
    if (!$type = xarMod::apiFunc('blocks', 'types', 'getitem', array('type_id' => $type_id))) {
        $msg = 'Invalid block type id "#(1)", type does not exist';
        $vars = array($type_id);
        throw new IdNotFoundException($vars, $msg);
    }    

    if (xarMod::apiFunc('blocks', 'instances', 'getitem', array('name' => $name))) {
        $msg = 'A block instance named "#(1)" already exists, name must be unique';
        $vars = array($name);
        throw new DuplicateException($vars, $msg);
    }
    
    if (empty($content))
        $content = $type['type_info'];

    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();
    $blocks_table = $tables['block_instances'];
    
    $query = "INSERT INTO $blocks_table    
              (id, type_id, name, title, state, content)
              VALUES (?,?,?,?,?,?)";
    $bindvars = array($dbconn->genId($blocks_table), $type_id, $name, $title, $state, serialize($content));

    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars);
    if (!$result) return;      

    $block_id = $dbconn->PO_Insert_ID($blocks_table, 'id');
    if (empty($block_id)) return;
       
    return $block_id;
}
?>