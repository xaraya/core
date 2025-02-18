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
use xarBlock;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
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
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();
        /** @var InstancesApi $instancesapi */
        $instancesapi = $this->instancesapi();
        if (!xarSecurity::check('AdminBlocks')) {
            return;
        }

        if (!xarVar::fetch(
            'type_id',
            'int:1:',
            $type_id,
            null,
            xarVar::DONT_SET
        )) {
            return;
        }


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

        if ($type['type_state'] == xarBlock::TYPE_STATE_MISSING ||
            $type['type_state'] == xarBlock::TYPE_STATE_MOD_UNAVAILABLE) {

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
                if (!$typesapi->deleteitem(['type_id' => $type_id])) {
                    return;
                }
                if (!xarVar::fetch(
                    'return_url',
                    'pre:trim:str:1:',
                    $return_url,
                    '',
                    xarVar::NOT_REQUIRED
                )) {
                    return;
                }
                if (empty($return_url)) {
                    $return_url = xarController::URL('blocks', 'admin', 'view_types');
                }
                xarController::redirect($return_url, null, $this->getContext());
            }

        }

        $data['type'] = $type;
        $data['type_states'] = $typesapi->getstates();
        $data['type_instances'] = $instancesapi->getitems(['type' => $type['type'], 'module' => $type['module']]);

        return $data;
    }
}
