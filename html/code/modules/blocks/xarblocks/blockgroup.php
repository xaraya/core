<?php
/**
 * Blockgroup Block
 *
 * @package modules\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 */

/**
 * Initialise block info
 *
 * @author  Chris Powis <crisp@xaraya.com>
*/
sys::import('xaraya.structures.containers.blocks.basicblock');

/**
 * Blocks Block Group Block
 */
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
	 * Implement required methods of the iBlockGroup interface
	 **/
    public function attachInstance($block_id)
    {
        if (in_array($block_id, $this->group_instances)) return true;
        $this->group_instances[] = $block_id;
        return true;      
    }

	/**
     * Detach the given block instance
     * 
     * @param string $block_id block id to be detached
     * @return boolean Returns true if $block_id not in group instances array 
     */
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

	/**
	 * Get the group instances in array
	 */
    public function getInstances()
    {
        $instances = array();
        foreach ($this->group_instances as $id) 
            $instances[] = $id;
        return $this->group_instances = $instances;
    }

}
