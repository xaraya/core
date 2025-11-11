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
use BadParameterException;

/**
 * blocks instancesapi updateitem function
 * @extends MethodClass<InstancesApi>
 */
class UpdateitemMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Updates an item in the API
     * @param array<string,mixed> $args
     * @return int|void Returns block id
     * @throws \BadParameterException
     * @see InstancesApi::updateitem()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($block_id) || !is_numeric($block_id)) {
            $invalid[] = 'block_id';
        }

        if (isset($state) && !is_numeric($state)) {
            $invalid[] = 'state';
        }

        if (isset($name) && (!is_string($name) || strlen($name) > 64)) {
            $invalid[] = 'name';
        }

        if (isset($title) && (!is_string($title) || strlen($title) > 254)) {
            $invalid[] = 'title';
        }

        if (isset($content) && !is_array($content)) {
            $invalid[] = 'content';
        }

        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
            $vars = [join(', ', $invalid), 'blocks', 'instancesapi', 'updateitem'];
            throw new BadParameterException($vars, $msg);
        }

        $dbconn = $this->db()->getConn();
        $tables = $this->db()->getTables();
        $blocks_table = $tables['block_instances'];
        $set = [];
        $where = [];
        $bindvars = [];

        if (isset($name)) {
            $set[] = 'name = ?';
            $bindvars[] = $name;
        }

        if (isset($title)) {
            $set[] = 'title = ?';
            $bindvars[] = $title;
        }

        if (isset($state)) {
            $set[] = 'state = ?';
            $bindvars[] = $state;
        }

        if (isset($content)) {
            $set[] = 'content = ?';
            $bindvars[] = serialize($content);
        }

        // someone passed us a block_id without params to update, just return the block_id
        if (empty($set)) {
            return $block_id;
        }

        $where[] = 'id = ?';
        $bindvars[] = $block_id;

        $query = "UPDATE $blocks_table";
        $query .= " SET " . join(',', $set);
        $query .= " WHERE " . join(' AND ', $where);

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) {
            return;
        }

        return $block_id;
    }
}
