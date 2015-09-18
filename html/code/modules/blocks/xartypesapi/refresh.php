<?php
/**
 * @package modules\blocks
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 */

/**
 * 
 * @author Chris Powis <crisp@xaraya.com>
 * 
 * @staticvar boolean $runonce
 * @param array $args Parameter data array
 * @return boolean True on success, false on failure
 */
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
                if ($block->type_category != $type['type_category']) 
                    $update['type_category'] = $block->type_category;
                $type_info = array();
                if ($block->text_type != $type['type_info']['text_type'])
                    $type_info['text_type'] = $block->text_type;
                if ($block->text_type_long != $type['type_info']['text_type_long'])
                    $type_info['text_type_long'] = $block->text_type_long;
                if ($block->author != $type['type_info']['author'])
                    $type_info['author'] = $block->author;
                if ($block->contact != $type['type_info']['contact'])
                    $type_info['contact'] = $block->contact;                       
                if ($block->credits != $type['type_info']['credits'])
                    $type_info['credits'] = $block->credits;
                if ($block->license != $type['type_info']['license'])
                    $type_info['license'] = $block->license;
                if (!empty($type_info)) {
                    $type_info += $type['type_info'];
                    $update['type_info'] = $type_info;
                } else {
                    unset($type_info);
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