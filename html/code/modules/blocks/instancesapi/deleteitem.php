<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\InstancesApi;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\InstancesApi;
use Xaraya\Modules\Blocks\BlocksApi;
use EmptyParameterException;
use Exception;
use IDNotFoundException;

/**
 * blocks instancesapi deleteitem function
 * @extends MethodClass<InstancesApi>
 */
class DeleteitemMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Deletes an item from the API
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args Parameter data array
     * @return bool|void Returns true on success, false on failure
     * @throws \EmptyParameterException
     * @throws \IDNotFoundException
     * @see InstancesApi::deleteitem()
     */
    public function __invoke(array $args = [])
    {
        /** @var InstancesApi $instancesapi */
        $instancesapi = $this->instancesapi();
        /** @var BlocksApi $blocksapi */
        $blocksapi = $this->blocksapi();
        if (empty($args['block_id']) || !is_numeric($args['block_id'])) {
            $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
            $vars = ['block_id', 'blocks', 'instancesapi', 'deleteitem'];
            throw new EmptyParameterException($vars, $msg);
        }

        $instance = $instancesapi->getitem(['block_id' => $args['block_id']]);

        if (!$instance) {
            $msg = 'Block instance id "#(1)" does not exist';
            $vars = [$args['block_id']];
            throw new IDNotFoundException($vars, $msg);
        }

        try {
            $instance['method'] = 'delete';
            $block = $blocksapi->getblock($instance);

            if ($instance['type_category'] == 'group') {
                $instance_ids = $block->getInstances();
            } else {
                $group_ids = $block->getGroups();
            }

            // call block delete method if it has one
            $result = $this->block()->hasMethod($block, 'delete', true) ? $block->delete() : true;
            if (!$result) {
                return;
            }
        } catch (Exception $e) {
            // this is ok, since we might need to remove instances when the type files are already gone
            if ($instance['type_category'] == 'group') {
                $instance_ids = $instance['content']['group_instances'];
            } else {
                $group_ids = $instance['content']['instance_groups'];
            }
        }

        if (!empty($group_ids) && is_array($group_ids)) {
            $instance_groups = $instancesapi->getitems(['block_id' => array_keys($group_ids)]);
            foreach (array_keys($group_ids) as $block_id) {
                if (!isset($instance_groups[$block_id])) {
                    continue;
                }
                $g_block = $blocksapi->getblock($instance_groups[$block_id]);
                $g_block->detachInstance($args['block_id']);
                if (!$instancesapi->updateitem([
                    'block_id' => $block_id,
                    'content' => $g_block->storeContent(),
                ])) {
                    return;
                }
                unset($g_block);
            }
            unset($group_ids, $instance_groups);
        } elseif (!empty($instance_ids) && is_array($instance_ids)) {
            $group_instances = $instancesapi->getitems(['block_id' => $instance_ids]);
            foreach ($instance_ids as $block_id) {
                if (!isset($group_instances[$block_id])) {
                    continue;
                }
                $i_block = $blocksapi->getblock($group_instances[$block_id]);
                $i_block->detachGroup($args['block_id']);
                if (!$instancesapi->updateitem([
                    'block_id' => $block_id,
                    'content' => $i_block->storeContent(),
                ])) {
                    return;
                }
                unset($i_block);
            }
            unset($instance_ids, $group_instances);
        }
        unset($instance, $block);

        $dbconn = $this->db()->getConn();
        $tables = $this->db()->getTables();
        $block_table = $tables['block_instances'];

        $query = "DELETE FROM $block_table
                  WHERE id = ?";
        $bindvars[] = $args['block_id'];
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) {
            return;
        }

        // @todo: block scope hooks
        /*
        $item = array(
            'module' => 'blocks',
            'itemid' => $args['block_id'],
            'itemtype' => 3,
        );
        $this->mod()->notifyHooks('BlockDelete', $item);
        */
        return true;

    }
}
