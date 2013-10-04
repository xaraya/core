<?php
/**
 * @package modules
 * @subpackage blocks module
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
function blocks_typesapi_createitem(Array $args=array())
{
    extract($args);
    
    // type name is required 
    if (!isset($type) || !is_string($type) || strlen($type) > 64)
        $invalid[] = 'type';
    
    // module is optional
    if (!isset($module))
        $module = '';
    if (!empty($module)) {
        $modinfo = xarMod::getBaseInfo($module);
        if (!$modinfo) {
            $invalid[] = 'module';
        } else {
            $module_id = $modinfo['systemid'];
        }
    }
    if (empty($module_id))
        $module_id = 0;
    if (!is_numeric($module_id))
        $invalid[] = 'module_id';
    
    // state is optional 
    if (!isset($state)) 
        $state = xarBlock::TYPE_STATE_ACTIVE;
    $states = xarMod::apiFunc('blocks', 'types', 'getstates');
    if (!is_numeric($state) || !isset($states[$state]))
        $invalid[] = 'state';

    // everything else to store we get from the block type object 
    
    if (!empty($invalid)) {
        $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
        $vars = array(join(', ', $invalid), 'blocks', 'typesapi', 'createitem');
        throw new BadParameterException($vars, $msg);
    }
        
    // check for duplicates
    if (xarMod::apiFunc('blocks', 'types', 'getitem', array('type' => $type, 'module' => $module))) {
        if (empty($module)) {
            $msg = 'Unable to create standalone block type "#(1)", type already exists';
            $vars = array($type);
        } else {
            $msg = 'Unable to create block type "#(1)" belonging to #(2) module, type already exists';
            $vars = array($type, $module);
        }
        throw new DuplicateException($vars, $msg);
    }
    
    // get an instance of this block type object
    $blocktype = xarMod::apiFunc('blocks', 'types', 'getobject', 
        array('type' => $type, 'module' => $module));
        
    $category = $blocktype->type_category;
    $info = serialize($blocktype->storeContent());
    
    unset($blocktype);

    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();
    $types_table = $tables['block_types'];
    
    $query = "INSERT INTO $types_table
              (id, module_id, state, type, category, info)
              VALUES (?,?,?,?,?,?)";
    $bindvars = array($dbconn->genId($types_table), $module_id, $state, $type, $category, $info);        

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;
    $id = $dbconn->PO_Insert_ID($types_table, 'id');
    if (empty($id)) return;
    return $id;
}
?>