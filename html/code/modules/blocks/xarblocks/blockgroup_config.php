<?php
/**
 * Blockgroup Block configuration interface
 *
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Manage block config
 *
 * @author  Chris Powis <crisp@xaraya.com>
 * @access  public
 * @return  void
*/
sys::import('modules.blocks.xarblocks.blockgroup');
class Blocks_BlockgroupBlockConfig extends Blocks_BlockgroupBlock implements iBlockGroup
{
/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function configmodify(Array $data=array())
    {
        if (!empty($this->group_instances))         
            $group_instances = xarMod::apiFunc('blocks', 'instances', 'getitems', 
                array('block_id' => $this->group_instances));

        $instances = array();
        
        if (!empty($group_instances)) {
            $authid = xarSecGenAuthKey();
            $i = 1;
            $numitems = count($group_instances);
            foreach ($this->group_instances as $id) {
                if (!isset($group_instances[$id])) continue;
                $instances[$id] = $group_instances[$id];
                $instances[$id]['modifyurl'] = xarServer::getCurrentURL( array('block_id' => $id));
                // add in links to re-order blocks
                if ($i < $numitems) {
                    $instances[$id]['downurl'] = xarServer::getCurrentURL(
                        array('block_id' => $this->block_id, 'interface' => 'config', 'block_method' => 'order', 'move' => $id, 'direction' => 'down', 'authid' => $authid, 'phase' => 'update'));
                }
                if ($i > 1) {
                    $instances[$id]['upurl'] = xarServer::getCurrentURL(
                        array('block_id' => $this->block_id, 'interface' => 'config', 'block_method' => 'order', 'move' => $id, 'direction' => 'up', 'authid' => $authid, 'phase' => 'update'));
                }
                $i++;
            }
        }
        $data['instances'] = $instances;
        // State descriptions.
        $data['state_desc'] = xarMod::apiFunc('blocks', 'instances', 'getstates');

        $blocks = xarMod::apiFunc('blocks', 'instances', 'getitems', array('type_category' => 'block'));
        $block_options = array();
        $block_options[] = array('id' => '', 'name' => xarML('-- no new block --'));
        foreach ($blocks as $id => $block) {
            if ($block['block_id'] == $this->block_id || isset($instances[$block['block_id']])) continue;
            $block_options[] = array(
                'id' => $block['block_id'],
                'name' => xarVarPrepForDisplay($block['name']),
            );
        }
        $data['block_options'] = $block_options;
        // @TODO: pager for many items?
        // $data['numitems'] = $numitems;
        return $data;
    }
/**
 * Updates the Block config from the Blocks Admin
 * @param $data array containing title,content
 */
    public function configupdate(Array $data=array())
    {

        // remove block(s) from this block group
        if (!xarVarFetch('remove_block', 'array', $remove_block, NULL, XARVAR_DONT_SET)) return;
        if (!empty($remove_block)) {
            $removes = xarMod::apiFunc('blocks', 'instances', 'getitems',
                array('block_id' => array_keys($remove_block)));
            if (!empty($removes)) {
                foreach ($removes as $id => $remove) {
                    $this->detachInstance($remove['block_id']);
                    $r_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $remove);
                    $r_block->detachGroup($this->block_id);
                    $remove['content'] = $r_block->storeContent();
                    if (!xarMod::apiFunc('blocks', 'instances', 'updateitem', $remove)) return;
                    unset($r_block);
                }
            }
        }

        // add a block to this block group
        if (!xarVarFetch('add_block', 'int:1:', $add_block, NULL, XARVAR_DONT_SET)) return;
        if (!empty($add_block)) {
            $add = xarMod::apiFunc('blocks', 'instances', 'getitem', 
                array('block_id' => $add_block));
            $this->attachInstance($add['block_id']);
            $a_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $add);
            $a_block->attachGroup($this->block_id);
            $add['content'] = $a_block->storeContent();
            if (!xarMod::apiFunc('blocks', 'instances', 'updateitem', $add)) return;
            unset($a_block);            
        }        
        return true;
    }

    // custom update method to handle block ordering
    public function orderupdate()
    {
        $data = $this->getInfo();
        // re-order block instances
        if (!xarVarFetch('move', 'int:1:', $move, NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('direction', 'pre:trim:lower:enum:up:down', $direction, NULL, XARVAR_DONT_SET)) return;
        if (!empty($move) && !empty($direction)) 
            $this->orderInstance($move, $direction);
        
        $data['content'] = $this->getContent();
        $data['return_url'] = xarServer::getCurrentURL(array('interface' => 'config', 'block_method' => null, 'move' => null, 'direction' => null, 'authid' => null, 'phase' => null), null, 'group_members');

        return $data;      
    }
}
?>