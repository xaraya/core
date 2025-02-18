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
use BadParameterException;
use EmptyParameterException;
use xarDB;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks typesapi updateitem function
 * @extends MethodClass<TypesApi>
 */
class UpdateitemMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update item in API
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args Parameter data array
     * @return int|void Type id of the block
     * @throws \EmptyParameterException
     * @throws \BadParameterException
     * @see TypesApi::updateitem()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args)) {
            $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
            $vars = ['arguments', 'blocks', 'typesapi', 'updateitem'];
            throw new EmptyParameterException($vars, $msg);
        }

        extract($args);

        if (empty($type_id) || !is_numeric($type_id)) {
            $invalid[] = 'type_id';
        }

        if (isset($type_state) && !is_numeric($type_state)) {
            $invalid[] = 'type_state';
        }

        if (isset($type_category) && (!is_string($type_category) || strlen($type_category) > 64)) {
            $invalid[] = 'type_category';
        }

        if (isset($type_info) && !is_array($type_info)) {
            $invalid[] = 'type_info';
        }

        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
            $vars = [join(', ', $invalid), 'blocks', 'typesapi', 'updateitem'];
            throw new BadParameterException($vars, $msg);
        }

        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $types_table = $tables['block_types'];
        $set = [];
        $where = [];
        $bindvars = [];

        if (isset($type_state)) {
            $set[] = 'state = ?';
            $bindvars[] = $type_state;
        }

        if (isset($type_category)) {
            $set[] = 'category = ?';
            $bindvars[] = $type_category;
        }

        if (isset($type_info)) {
            $set[] = 'info = ?';
            $bindvars[] = serialize($type_info);
        }

        // we need to save the actual $classname and $filepath for getitems() - requires UPGRADE due to table change
        if (isset($classname)) {
            $set[] = 'class = ?';
            $bindvars[] = $classname;
        }

        if (isset($filepath)) {
            $set[] = 'filepath = ?';
            $bindvars[] = $filepath;
        }

        // someone passed us a type_id without params to update, just return the type_id
        if (empty($set)) {
            return $type_id;
        }

        $where[] = 'id = ?';
        $bindvars[] = $type_id;

        $query = "UPDATE $types_table";
        $query .= " SET " . join(',', $set);
        $query .= " WHERE " . join(' AND ', $where);

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) {
            return;
        }

        return $type_id;

    }
}
