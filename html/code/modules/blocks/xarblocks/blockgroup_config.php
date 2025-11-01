<?php

/**
 * Blockgroup Block configuration interface
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
 * Manage block config
 *
 * @author  Chris Powis <crisp@xaraya.com>
*/
sys::import('modules.blocks.xarblocks.blockgroup');

class Blocks_BlockgroupBlockConfig extends Blocks_BlockgroupBlock implements iBlockGroup
{
    /**
     * Modify Function to the Blocks Admin
     * @param array<string, mixed> $data Array containing title,content
     * @return array<mixed> Data array
     */
    public function configmodify(array $data = [])
    {
        if (!empty($this->group_instances)) {
            $group_instances = $this->mod()->apiFunc(
                'blocks',
                'instances',
                'getitems',
                ['block_id' => $this->group_instances]
            );
        }

        $instances = [];

        if (!empty($group_instances)) {
            $authid = $this->sec()->genAuthKey();
            $i = 1;
            $numitems = count($group_instances);
            foreach ($this->group_instances as $id) {
                if (!isset($group_instances[$id])) {
                    continue;
                }
                $instances[$id] = $group_instances[$id];
                $instances[$id]['modifyurl'] = $this->ctl()->getCurrentURL(['block_id' => $id]);
                // add in links to re-order blocks
                if ($i < $numitems) {
                    $instances[$id]['downurl'] = $this->ctl()->getCurrentURL(
                        ['block_id' => $this->block_id, 'interface' => 'config', 'block_method' => 'order', 'move' => $id, 'direction' => 'down', 'authid' => $authid, 'phase' => 'update']
                    );
                }
                if ($i > 1) {
                    $instances[$id]['upurl'] = $this->ctl()->getCurrentURL(
                        ['block_id' => $this->block_id, 'interface' => 'config', 'block_method' => 'order', 'move' => $id, 'direction' => 'up', 'authid' => $authid, 'phase' => 'update']
                    );
                }
                $i++;
            }
        }
        $data['instances'] = $instances;
        // State descriptions.
        $data['state_desc'] = $this->mod()->apiFunc('blocks', 'instances', 'getstates');

        $blocks = $this->mod()->apiFunc('blocks', 'instances', 'getitems', ['type_category' => 'block']);
        $block_options = [];
        $block_options[] = ['id' => '', 'name' => $this->ml('-- no new block --')];
        foreach ($blocks as $id => $block) {
            if ($block['block_id'] == $this->block_id || isset($instances[$block['block_id']])) {
                continue;
            }
            $block_options[] = [
                'id' => $block['block_id'],
                'name' => $this->prep()->text($block['name']),
            ];
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
    public function configupdate(array $data = [])
    {

        // remove block(s) from this block group
        $this->var()->check('remove_block', $remove_block, 'array', null);
        if (!empty($remove_block)) {
            $removes = $this->mod()->apiFunc(
                'blocks',
                'instances',
                'getitems',
                ['block_id' => array_keys($remove_block)]
            );
            if (!empty($removes)) {
                foreach ($removes as $id => $remove) {
                    $this->detachInstance($remove['block_id']);
                    $r_block = $this->mod()->apiFunc('blocks', 'blocks', 'getblock', $remove);
                    $r_block->detachGroup($this->block_id);
                    $remove['content'] = $r_block->storeContent();
                    if (!$this->mod()->apiFunc('blocks', 'instances', 'updateitem', $remove)) {
                        return;
                    }
                    unset($r_block);
                }
            }
        }

        // add a block to this block group
        $this->var()->check('add_block', $add_block, 'int:1:', null);
        if (!empty($add_block)) {
            $add = $this->mod()->apiFunc(
                'blocks',
                'instances',
                'getitem',
                ['block_id' => $add_block]
            );
            $this->attachInstance($add['block_id']);
            $a_block = $this->mod()->apiFunc('blocks', 'blocks', 'getblock', $add);
            $a_block->attachGroup($this->block_id);
            $add['content'] = $a_block->storeContent();
            if (!$this->mod()->apiFunc('blocks', 'instances', 'updateitem', $add)) {
                return;
            }
            unset($a_block);
        }
        return true;
    }

    /**
     * custom update method to handle block ordering
     */
    public function orderupdate()
    {
        $data = $this->getInfo();
        // re-order block instances
        $this->var()->check('move', $move, 'int:1:', null);
        $this->var()->check('direction', $direction, 'pre:trim:lower:enum:up:down', null);
        if (!empty($move) && !empty($direction)) {
            $this->orderInstance($move, $direction);
        }

        $data['content'] = $this->getContent();
        $data['return_url'] = $this->ctl()->getCurrentURL(['interface' => 'config', 'block_method' => null, 'move' => null, 'direction' => null, 'authid' => null, 'phase' => null], null) . '#group_members';

        return $data;
    }
}
