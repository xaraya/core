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
        if (!xarSecurity::check('ManageBlocks')) {
            return;
        }

        if (!xarVar::fetch(
            'block_id',
            'int:1:',
            $block_id,
            null,
            xarVar::NOT_REQUIRED
        )) {
            return;
        }

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
        $isadmin = xarSecurity::check('', 0, 'Block', "$instance[type]:$instance[name]:$instance[block_id]", $instance['module'], '', 0, 800);
        /** @var AccessProperty $accessproperty */
        $accessproperty = DataPropertyMaster::getProperty(['name' => 'access']);
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
            return xarController::badRequest('no_privileges', $this->getContext());
        }

        if (!xarVar::fetch(
            'confirm',
            'checkbox',
            $confirmed,
            false,
            xarVar::NOT_REQUIRED
        )) {
            return;
        }

        if ($confirmed) {
            if (!xarSec::confirmAuthKey()) {
                return xarController::badRequest('bad_author', $this->getContext());
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

            $return_url = xarController::URL('blocks', 'admin', 'view_instances');
            xarController::redirect($return_url, null, $this->getContext());
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
