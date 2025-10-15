<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\TypesApi;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\TypesApi;
use Xaraya\Modules\Blocks\InstancesApi;
use EmptyParameterException;
use Exception;
use IDNotFoundException;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks typesapi deleteitem function
 * @extends MethodClass<TypesApi>
 */
class DeleteitemMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Deletes an item from the API
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args Parameter data array
     * @return int|void Returns given type id
     * @throws \EmptyParameterException
     * @throws \IDNotFoundException
     * @see TypesApi::deleteitem()
     */
    public function __invoke(array $args = [])
    {
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();
        /** @var InstancesApi $instancesapi */
        $instancesapi = $this->instancesapi();
        if (empty($args['type_id']) || !is_numeric($args['type_id'])) {
            $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
            $vars = ['type_id', 'blocks', 'typesapi', 'deleteitem'];
            throw new EmptyParameterException($vars, $msg);
        }

        $type = $typesapi->getitem(['type_id' => $args['type_id']]);

        if (!$type) {
            $msg = 'Block type id "#(1)" does not exist';
            $vars = [$args['type_id']];
            throw new IDNotFoundException($vars, $msg);
        }

        $type_instances = $instancesapi->getitems(['type' => $type['type'], 'module' => $type['module']]);

        if (!empty($type_instances)) {
            foreach (array_keys($type_instances) as $block_id) {
                try {
                    if (!$this->mod()->apiFunc(
                        'types',
                        'instances',
                        'deleteitem',
                        ['block_id' => $block_id]
                    )) {
                        return;
                    }
                } catch (IDNotFoundException $e) {
                    // this is ok, it may already have been deleted
                    continue;
                } catch (Exception $e) {
                    // oops, throw back
                    throw $e;
                }
            }
        }
        unset($type, $type_instances);

        $dbconn = $this->db()->getConn();
        $tables = $this->db()->getTables();
        $types_table = $tables['block_types'];

        $query = "DELETE FROM $types_table
                  WHERE id = ?";
        $bindvars[] = $args['type_id'];
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) {
            return;
        }

        // @todo: block scope hooks
        /*
        $item = array(
            'module' => 'blocks',
            'itemid' => $args['type_id'],
            'itemtype' => 1,
        );
        $this->mod()->notifyHooks('BlockDelete', $item);
        */

        return $args['type_id'];
    }
}
