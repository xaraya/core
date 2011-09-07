<?php
/**
 * @package modules
 * @subpackage blocks module
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * @author Chris Powis <crisp@xaraya.com>
 * @todo 
**/
function blocks_typesapi_refresh(Array $args=array())
{
    // only need to run this once 
    static $runonce = false;
    if ($runonce && empty($args['refresh'])) return true;
    
    // first get a list of block type files for all available modules 
    $files = xarMod::apiFunc('blocks', 'types', 'getfiles');

    foreach ($files as $file) {
        if (isset($args['module']) && $file['module'] != $args['module']) continue;
        // nothing fancy here, if a type file exists, see if we have an entry for it in the db
        if (!xarMod::apiFunc('blocks', 'types', 'getitem',
            array(
                'type' => $file['type'],
                'module' => $file['module'],
            ))) {
            // no entry in the db, create one now 
            if (!xarMod::apiFunc('blocks', 'types', 'createitem', 
                array(
                    'type' => $file['type'],
                    'module' => $file['module'],
                ))) return;
        }
    }

    // now get the list of all block types in the db
    $types = xarMod::apiFunc('blocks', 'types', 'getitems');    

    foreach ($types as $type) {
        if (isset($args['module']) && $type['module'] != $args['module']) continue;
        $update = array();
        // if the block belongs to a module, check the module is active 
        if (!empty($type['module']) && !xarMod::isAvailable($type['module'])) {
            $state = xarBlock::TYPE_STATE_MOD_UNAVAILABLE;
        } else {
            try { 
                // check the block can be instantiated
                $block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $type);
                $state = xarBlock::TYPE_STATE_ACTIVE;
                if ($block->type_category != $type['type_category']) {
                    $update['type_category'] = $block->type_category;
                }
                       
            } catch (FileNotFoundException $e) {
                $state = xarBlock::TYPE_STATE_MISSING;
            } catch (Exception $e) {
                $state = xarBlock::TYPE_STATE_ERROR;     
            }

        }
        if ($state != $type['type_state'])
            $update['type_state'] = $state;
            
        if (!empty($update)) {
            $update['type_id'] = $type['type_id'];
            if (!xarMod::apiFunc('blocks', 'types', 'updateitem', $update)) return;
        }       
        unset($block, $state, $update);

    }
    unset($files, $types);
    $runonce = true;
    return true;
}
?>