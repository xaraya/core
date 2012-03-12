<?php
/**
 * Blockgroup Block display interface
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
 * Display block
 *
 * @author  Chris Powis <crisp@xaraya.com>
 * @access  public
 * @return  void
*/
sys::import('modules.blocks.xarblocks.blockgroup');
class Blocks_BlockgroupBlockDisplay extends Blocks_BlockgroupBlock implements iBlockGroup
{
/**
 * Display func.
 * @param none
 */
    function display(Array $data=array())
    {

        $data = $this->getContent();

        if (empty($this->group_instances)) return;
        $instances = xarMod::apiFunc('blocks', 'instances', 'getitems', 
            array(
                'block_id' => $this->group_instances, 
                'type_state' => xarBlock::TYPE_STATE_ACTIVE,
                'state' => array(xarBlock::BLOCK_STATE_VISIBLE, xarBlock::BLOCK_STATE_HIDDEN),
            ));
        
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
        $data['blocks'] = $output;
        
        return $data;
    }
}
?>