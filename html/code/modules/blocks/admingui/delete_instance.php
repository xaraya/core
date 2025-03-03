<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\AdminGui;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\AdminGui;
use Xaraya\Modules\Blocks\InstancesApi;
use Xaraya\Modules\Blocks\BlocksApi;
use AccessProperty;
use DataPropertyMaster;
use EmptyParameterException;
use Exception;
use IDNotFoundException;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks admin delete_instance function
 * @extends MethodClass<AdminGui>
 */
class DeleteInstanceMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Delete a block instance
     * @author Jim McDonald
     * @author Paul Rosania
     * @return array|string|void Data array
     * @throws \EmptyParameterException Thrown if no block id has been passed
     * @throws \IDNotFoundException Thrown if no block with the given block id was found in the API
     * @see AdminGui::deleteInstance()
     */
    public function __invoke(array $args = [])
    {
        /** @var InstancesApi $instancesapi */
        $instancesapi = $this->instancesapi();
        /** @var BlocksApi $blocksapi */
        $blocksapi = $this->blocksapi();
        if (!$this->sec()->checkAccess('ManageBlocks')) {
            return;
        }

        $this->var()->find(
            'block_id',
            $block_id,
            'int:1:',
            null
        );

        if (!isset($block_id)) {
            $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
            $vars = ['block_id', 'blocks', 'admin', 'delete_instance'];
            throw new EmptyParameterException($vars, $msg);
        }

        $instance = $instancesapi->getitem(['block_id' => $block_id]);

        if (!$instance) {
            $msg = 'Block instance id "#(1)" does not exist';
            $vars = [$block_id];
            throw new IDNotFoundException($vars, $msg);
        }

        // admin access is needed for some operations
        $isadmin = $this->sec()->check('', 0, 'Block', "$instance[type]:$instance[name]:$instance[block_id]", $instance['module'], '', 0, 800);
        /** @var AccessProperty $accessproperty */
        $accessproperty = $this->prop()->getProperty(['name' => 'access']);
        // check delete access
        if ($isadmin) {
            $candelete = true;
        } else {
            $args = [
                'module' => $instance['module'],
                'component' => 'Block',
                'instance' => "$instance[type]:$instance[name]:$instance[block_id]",
                'group' => $instance['content']['delete_access']['group'],
                'level' => $instance['content']['delete_access']['level'],
            ];
            $candelete = $accessproperty->check($args);
        }
        if (!$candelete) {
            return $this->ctl()->badRequest('no_privileges');
        }

        $this->var()->find(
            'confirm',
            $confirmed,
            'checkbox',
            false
        );

        if ($confirmed) {
            if (!$this->sec()->confirmAuthKey()) {
                return $this->ctl()->badRequest('bad_author');
            }

            // delete instance from db
            try {
                if (!$instancesapi->deleteitem(['block_id' => $instance['block_id']])) {
                    return;
                }
            } catch (IDNotFoundException $e) {
                // ok, it's already gone
            } catch (Exception $e) {
                // oops, throw back
                throw $e;
            }

            $return_url = $this->ctl()->getModuleURL('blocks', 'admin', 'view_instances');
            $this->ctl()->redirect($return_url);
            return true;
        }

        $data = [];
        $data['instance'] = $instance;
        try {
            $instance['method'] = 'delete';
            $block = $blocksapi->getblock($instance);

            if ($instance['type_category'] == 'group') {
                $instance_ids = $block->getInstances();
            } else {
                $group_ids = $block->getGroups();
            }
        } catch (Exception $e) {
            // this is ok, since the files could be missing
            if ($instance['type_category'] == 'group') {
                $instance_ids = $instance['content']['group_instances'];
            } else {
                $group_ids = $instance['content']['instance_groups'];
            }
        }
        if (!empty($instance_ids) && is_array($instance_ids)) {
            $data['group_instances'] = $instancesapi->getitems(['block_id' => $instance_ids]);
        } elseif (!empty($group_ids) && is_array($group_ids)) {
            $data['instance_groups'] = $instancesapi->getitems(['type_category' => 'group', 'block_id' => array_keys($group_ids)]);
        }

        return $data;
    }
}
