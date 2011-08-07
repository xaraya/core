<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */

sys::import('xaraya.structures.containers.blocks.basicblock');

class Blocks_BlockgroupBlock extends BasicBlock implements iBlockGroup
{
    
    protected $type                = 'blockgroup';
    protected $module              = 'blocks';
    protected $text_type           = 'Blockgroup';
    protected $text_type_long      = 'Blockgroup';
    
    // let the blocks subsystem know we implement the iBlockGroup interface
    protected $type_category       = 'group';

    protected $allow_multiple      = true;
    protected $show_preview        = true;
    
    public $group_instances     = array();

/**
 * Display func.
 * @param none
 */
    function display(Array $data=array())
    {
        $data = parent::display($data);
        if (empty($data)) return;

        if (empty($this->group_instances)) return;
        
        $instances = xarMod::apiFunc('blocks', 'instances', 'getitems', 
            array('block_id' => $this->group_instances));
        
        if (empty($instances)) return;
        
        $output = '';
        foreach ($this->group_instances as $id) {
            if (!isset($instances[$id])) continue;
            $block_info = $instances[$id];
            $block_info['group_id'] = $this->block_id;
            $block_info['group'] = $this->name;
            // try for instance templates for this group
            if (isset($block_info['content']['instance_groups'][$this->block_id])) {
                $box_template = $block_info['content']['instance_groups'][$this->block_id]['box_template'];
                $block_template = $block_info['content']['instance_groups'][$this->block_id]['block_template'];
            }
            // fall back to instance defaults
            // checkme: should we honour template settings in pairs ?
            if (empty($box_template))
                $box_template = $block_info['content']['box_template'];            
            if (empty($block_template))
                $block_template = $block_info['content']['block_template'];
            
            // fall back to blockgroup
            if (empty($box_template))
                $box_template = $this->box_template;            
            $block_info['content']['box_template'] = $box_template;            
            $block_info['content']['block_template'] = $block_template;

            $output .= xarBlock::render($block_info);
        }
        if (empty($output)) return;
        $data['group'] = $this->name;
        $data['groupid'] = $this->block_id;

        $data['content']['blocks'] = $output;
        return $data;
                
        
        // blockgroup name (if any)
        $data['group'] = $data['name'];
        $data['groupid'] = $data['bid'];

        // get block instances for this blockgroup
        $instances = xarMod::apiFunc('blocks', 'user', 'getall',
            array('order' => 'group','gid' => $data['bid']));

        // output is concatenated string of template data for each block
        // rendered by the core xarBlock class render() method,
        // (the same method rendering this blockgroup itself :) )
        // this is the same behaviour as the old core xarBlock_renderGroup() function
        $output = '';
        if (!empty($instances)) {
            foreach ($instances as $info) {
                // Get the overriding template name.
                // Levels, in order (most significant first): group instance, instance, group
                $group_inst_template = explode(';',$info['group_inst_template'],3);
                $inst_template = explode(';',$info['template'],3);
                $group_template = explode(';',$info['group_template'],3);
                // groups have no outer template, we just want the inner setting
                if (empty($group_template[1])) {
                    // Default the box template to the group name.
                    $group_template[1] = $data['group'];
                }
                /*
                if (empty($group_template[1])) {
                    // Default the block template to the instance name.
                    $group_template[1] = $info['name'];
                }
                */

                // Cascade level over-rides for the box template.
                $info['_bl_box_template'] = !empty($group_inst_template[0]) ? $group_inst_template[0]
                    : (!empty($inst_template[0]) ? $inst_template[0] : $group_template[1]);

                // Global override of box template - usually comes from the 'template'
                // attribute of the xar:blockgroup tag.
                if (!empty($data['box_template'])) {
                    $info['_bl_box_template'] = $data['box_template'];
                }

                // Cascade level over-rides for the block template.
                $info['_bl_block_template'] = !empty($group_inst_template[1]) ? $group_inst_template[1]
                    : (!empty($inst_template[1]) ? $inst_template[1] : $info['name']);

                $info['_bl_template_base'] = $info['type'];

                // add blockgroup details to info
                $info['groupid'] = $data['groupid'];
                $info['group'] = $data['group'];

                // render the block
                $output .= xarBlock::render($info);
            }
        }

        // no output, nothing for the block to render
        if (empty($output)) return '';

        // add the rendered block output for the blockgroup
        $data['content']['blocks'] = $output;
        // and pass back for rendering
        return $data;
    }

/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function modify(Array $data=array())
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
                        array('block_id' => $this->block_id,   'method' => 'modify', 'tab' => 'order', 'move' => $id, 'direction' => 'down', 'authid' => $authid, 'phase' => 'update'));
                }
                if ($i > 1) {
                    $instances[$id]['upurl'] = xarServer::getCurrentURL(
                        array('block_id' => $this->block_id,  'method' => 'modify', 'tab' => 'order', 'move' => $id, 'direction' => 'up', 'authid' => $authid, 'phase' => 'update'));
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
    public function update(Array $data=array())
    {
        $data = parent::update($data);
        if (empty($data)) return;

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
        
        $data['content'] = $this->getContent();
        return $data;
    }
/**
 * Deletes the block from the Blocks Admin
 * @param $data array containing title,content
 */
    public function delete(Array $data=array())
    {
        return true;
    }

    // custom update method to handle block ordering
    public function updateorder()
    {
        $data = $this->getInfo();
        // re-order block instances
        if (!xarVarFetch('move', 'int:1:', $move, NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('direction', 'pre:trim:lower:enum:up:down', $direction, NULL, XARVAR_DONT_SET)) return;
        if (!empty($move) && !empty($direction)) 
            $this->orderInstance($move, $direction);
        
        $data['content'] = $this->getContent();
        $data['return_url'] = xarModURL('blocks', 'admin', 'modify_instance', 
            array('tab' => 'config', 'block_id' => $this->block_id), null, 'group_members');
        return $data;      
    }
/**
 * Implement required methods of the iBlockGroup interface
**/
    public function attachInstance($block_id)
    {
        if (in_array($block_id, $this->group_instances)) return true;
        $this->group_instances[] = $block_id;
        return true;      
    }

    public function detachInstance($block_id)
    {
        if (!in_array($block_id, $this->group_instances)) return true;
        $instances = array();
        foreach ($this->group_instances as $id) {
            if ($id == $block_id) continue; 
            $instances[] = $id;
        }
        $this->group_instances = $instances;
        return true;
    }

    public function orderInstance($block_id, $direction)
    {
        foreach ($this->group_instances as $i => $id) {
            if ($id != $block_id) continue;
            $position = $direction == 'up' ? $i-1 : $i+1;
            if (isset($this->group_instances[$position])) {
                $temp = $this->group_instances[$position];
                $this->group_instances[$position] = $block_id;
                $this->group_instances[$i] = $temp;
            }
            break;
        }
        return true;
    }

    public function getInstances()
    {
        $instances = array();
        foreach ($this->group_instances as $id) 
            $instances[] = $id;
        return $this->group_instances = $instances;
    }
        


}
?>