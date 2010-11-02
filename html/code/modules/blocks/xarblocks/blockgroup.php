<?php
/**
 * @package modules
 * @subpackage blocks module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/13.html
 */

sys::import('xaraya.structures.containers.blocks.basicblock');

class Blocks_BlockgroupBlock extends BasicBlock implements iBlock
{
    public $name                = 'BlockgroupBlock';
    public $module              = 'blocks';
    public $text_type           = 'Blockgroup';
    public $text_type_long      = 'Blockgroup';
    public $allow_multiple      = true;
    public $show_preview        = true;
    public $nocache             = 0;
    public $pageshared          = 1;
    public $usershared          = 1;

    public function __construct(Array $data=array())
    {
        parent::__construct($data);
    }

/**
 * Display func.
 * @param $data array containing title,content
 */
    function display(Array $data=array())
    {
        $data = parent::display($data);
        if (empty($data)) return;

        // blockgroup name (if any)
        $data['group_name'] = $data['name'];

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
                if (empty($group_template[0])) {
                    // Default the box template to the group name.
                    $group_template[0] = $data['group_name'];
                }

                if (empty($group_bl_template[1])) {
                    // Default the block template to the instance name.
                    $group_template[1] = $info['name'];
                }

                // Cascade level over-rides for the box template.
                $info['_bl_box_template'] = !empty($group_inst_template[0]) ? $group_inst_template[0]
                    : (!empty($inst_template[0]) ? $inst_template[0] : $group_template[0]);

                // Global override of box template - usually comes from the 'template'
                // attribute of the xar:blockgroup tag.
                if (!empty($data['box_template'])) {
                    $info['_bl_box_template'] = $data['box_template'];
                }

                // Cascade level over-rides for the block template.
                $info['_bl_block_template'] = !empty($group_inst_template[1]) ? $group_inst_template[1]
                    : (!empty($inst_template[1]) ? $inst_template[1] : $group_template[1]);

                $info['_bl_template_base'] = $info['type'];

                // add blockgroup details to info
                $info['bgid'] = $data['bid'];
                $info['group_name'] = $data['group_name'];

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
        $data = parent::modify($data);
        if (empty($data)) return;

        $instances = xarMod::apiFunc('blocks', 'user', 'getall',
            array('order' => 'group','gid' => $data['bid']));

        $authid = xarSecGenAuthKey();
        $i = 1;
        $numitems = count($instances);
        foreach ($instances as $id => $instance) {
            $instances[$id]['modifyurl'] = xarModURL('blocks', 'admin', 'modify_instance', array('bid' => $instance['bid']));
            // add in links to re-order blocks
            if ($i < $numitems) {
                $instances[$id]['downurl'] = xarModURL('blocks', 'admin', 'update_instance',
                    array('tab' => 'order', 'bid' => $data['bid'], 'move' => $instance['bid'], 'direction' => 'down', 'authid' => $authid));
            }
            if ($i > 1) {
                $instances[$id]['upurl'] = xarModURL('blocks', 'admin', 'update_instance',
                    array('tab' => 'order', 'bid' => $data['bid'], 'move' => $instance['bid'], 'direction' => 'up', 'authid' => $authid));
            }
            $i++;
        }

        $data['group'] = $instances;
        // State descriptions.
        $data['state_desc'][0] = xarML('Hidden');
        $data['state_desc'][1] = xarML('Inactive');
        $data['state_desc'][2] = xarML('Visible');

        $blocks = xarMod::apiFunc('blocks', 'user', 'getall');
        $block_options = array();
        $block_options[] = array('id' => '', 'name' => xarML('-- no new block --'));
        foreach ($blocks as $id => $block) {
            if ($block['bid'] == $data['bid'] || isset($instances[$block['bid']])) continue;
            $block_options[] = array(
                'id' => $block['bid'],
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
            foreach ($remove_block as $bid => $block) {
                $instance = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => $bid));
                $groups = $instance['groups'];
                $newgroups = array();
                foreach ($groups as $group) {
                    if ($group['id'] == $data['bid']) continue;
                    $newgroups[] = $group;
                }
                $instance['groups'] = $newgroups;
                if (!xarMod::apiFunc('blocks', 'admin', 'update_instance', $instance)) return;
            }
        }

        // add a block to this block group
        if (!xarVarFetch('add_block', 'int:1:', $add_block, NULL, XARVAR_DONT_SET)) return;
        if (!empty($add_block)) {
            $instance = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => $add_block));
            if (!empty($instance)) {
                $groups = $instance['groups'];
                $groups[] = array('id' => $data['bid'], 'template' => null);
                $instance['groups'] = $groups;
                if (!xarMod::apiFunc('blocks', 'admin', 'update_instance', $instance)) return;
            }
        }
        // Resequence blocks within groups.
        if (!xarMod::apiFunc('blocks', 'admin', 'resequence')) {return;}
        return $data;
    }
/**
 * Deletes the block from the Blocks Admin
 * @param $data array containing title,content
 */
    public function delete(Array $data=array())
    {
        $data = parent::delete($data);

        // get block instances for this blockgroup
        $instances = xarMod::apiFunc('blocks', 'user', 'getall',
            array('order' => 'group','gid' => $data['bid']));

        // remove this group from all block group instances
        foreach (array_keys($instances) as $bid) {
            $instance = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => $bid));
            $groups = $instance['groups'];
            $newgroups = array();
            foreach ($groups as $group) {
                if ($group['id'] == $data['bid']) continue;
                $newgroups[] = $group;
            }
            $instance['groups'] = $newgroups;
            if (!xarMod::apiFunc('blocks', 'admin', 'update_instance', $instance)) return;
        }

        return $data;
    }

    // custom update method to handle block ordering
    public function updateorder()
    {
        $data = $this->getInfo();
        // re-order block instances
        if (!xarVarFetch('move', 'int:1:', $move, NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('direction', 'pre:trim:lower:enum:up:down', $direction, NULL, XARVAR_DONT_SET)) return;
        if (!empty($move) && !empty($direction)) {

            // get block instances for this blockgroup
            $instances = xarMod::apiFunc('blocks', 'user', 'getall',
                array('order' => 'group','gid' => $data['bid']));

            $seeninst = array();
            if (!empty($instances)) {
                $i = 0;
                foreach ($instances as $inst) {
                    if ($move == $inst['bid']) $currentpos = $i;
                    $seeninst[] = $inst['bid'];
                    $i++;
                }
            }

            if (!empty($seeninst) && !empty($move) && in_array($move, $seeninst) && !empty($direction)) {
                $i = 0;
                foreach ($instances as $inst) {
                    if ($i == $currentpos && $direction == 'up' && isset($seeninst[$i-1])) {
                        $temp = $seeninst[$i-1];
                        $seeninst[$i-1] = $inst['bid'];
                        $seeninst[$i] = $temp;
                        break;
                    } elseif ($i == $currentpos && $direction == 'down' && isset($seeninst[$i+1])) {
                        $temp = $seeninst[$i+1];
                        $seeninst[$i+1] = $inst['bid'];
                        $seeninst[$i] = $temp;
                        break;
                    }
                    $i++;
                }
                $group_instance_order = $seeninst;
                // Pass to API
                // CHECKME: Do we need this api func now? Only ever called here
                // we could probably move the function to here and lose the file :)
                if (!xarModAPIFunc('blocks', 'admin', 'update_group',
                    array(
                        'id' => $data['bid'],
                        'instance_order' => $group_instance_order)
                    )
                ) return;
            }

        }
        $data['return_url'] = xarModURL('blocks', 'admin', 'modify_instance', array('bid' => $data['bid']), null, 'group_members');
        return $data;
    }
}
?>