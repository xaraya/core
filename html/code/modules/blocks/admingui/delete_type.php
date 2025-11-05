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
use Xaraya\Modules\Blocks\TypesApi;
use Xaraya\Modules\Blocks\InstancesApi;
use EmptyParameterException;
use IDNotFoundException;
use ixarBlock;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks admin delete_type function
 * @extends MethodClass<AdminGui>
 */
class DeleteTypeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Function to delete type
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args Set of optional parameters
     * @return array|string|void Returns data array
     * @throws \EmptyParameterException
     * @throws \IDNotFoundException
     * @see AdminGui::deleteType()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();
        /** @var InstancesApi $instancesapi */
        $instancesapi = $this->instancesapi();
        if (!$this->sec()->checkAccess('AdminBlocks')) {
            return;
        }

        $this->var()->check(
            'type_id',
            $type_id,
            'int:1:',
            null
        );


        if (!isset($type_id)) {
            $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
            $vars = ['type_id', 'blocks', 'admin', 'delete_type'];
            throw new EmptyParameterException($vars, $msg);
        }

        $type = $typesapi->getitem(['type_id' => $type_id]);

        if (!$type) {
            $msg = 'Block type id "#(1)" does not exist';
            $vars = [$type_id];
            throw new IDNotFoundException($vars, $msg);
        }

        $data = [];

        if ($type['type_state'] == ixarBlock::TYPE_STATE_MISSING
            || $type['type_state'] == ixarBlock::TYPE_STATE_MOD_UNAVAILABLE) {

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
                if (!$typesapi->deleteitem(['type_id' => $type_id])) {
                    return;
                }
                $this->var()->find(
                    'return_url',
                    $return_url,
                    'pre:trim:str:1:',
                    ''
                );
                if (empty($return_url)) {
                    $return_url = $this->ctl()->getModuleURL('blocks', 'admin', 'view_types');
                }
                $this->ctl()->redirect($return_url);
                return true;
            }

        }

        $data['type'] = $type;
        $data['type_states'] = $typesapi->getstates();
        $data['type_instances'] = $instancesapi->getitems(['type' => $type['type'], 'module' => $type['module']]);

        return $data;
    }
}
