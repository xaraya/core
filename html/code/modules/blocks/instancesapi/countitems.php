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
 * blocks instancesapi countitems function
 * @extends MethodClass<InstancesApi>
 */
class CountitemsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Returns item count
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args Parameter data array
     * @return int|void Item count
     * @throws \BadParameterException
     * @see InstancesApi::countitems()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (isset($state)) {
            if (is_numeric($state)) {
                $state = [$state];
            } elseif (is_array($state)) {
                foreach ($state as $dt) {
                    if (!is_numeric($dt)) {
                        $invalid[] = 'state';
                        break;
                    }
                }
            }
        }

        if (isset($type) && !is_string($type)) {
            $invalid[] = 'type';
        }

        if (isset($type_id) && (empty($type_id) || !is_numeric($type_id))) {
            $invalid[] = 'type_id';
        }

        if (isset($type_state)) {
            if (is_numeric($type_state)) {
                $type_state = [$type_state];
            } elseif (is_array($type_state)) {
                foreach ($type_state as $dt) {
                    if (!is_numeric($dt)) {
                        $invalid[] = 'type_state';
                        break;
                    }
                }
            } else {
                $invalid[] = 'type_state';
            }
        }

        if (isset($module)) {
            if (empty($module)) {
                $module_id = 0;
            } elseif (!is_string($module) || !$this->mod()->isAvailable($module)) {
                $invalid[] = 'module';
            } else {
                $modinfo = $this->mod()->getBaseInfo($module);
                $module_id = $modinfo['systemid'];
            }
        }

        if (isset($type_category) && !is_string($type_category)) {
            $invalid[] = 'type_category';
        }

        if (isset($filter) && !is_string($filter)) {
            $invalid[] = 'filter';
        }

        if (isset($filter_fields) && !empty($filter_fields)) {
            if (is_string($filter_fields)) {
                $filter_fields = [$filter_fields];
            }
            if (is_array($filter_fields)) {
                $allowed = ['name', 'type', 'module'];
                foreach ($filter_fields as $dt) {
                    if (!in_array($dt, $allowed) || !is_string($dt)) {
                        $invalid[] = 'filter_fields';
                        break;
                    }
                }
            } else {
                $invalid[] = 'filter_fields';
            }
        }

        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
            $vars = [join(', ', $invalid), 'blocks', 'instancesapi', 'countitems'];
            throw new BadParameterException($vars, $msg);
        }

        $dbconn = $this->db()->getConn();
        $tables = $this->db()->getTables();
        $blocks_table  = $tables['block_instances'];
        $types_table   = $tables['block_types'];
        $modules_table = $tables['modules'];

        // assemble query parts
        $from = [];
        $join = [];
        $where = [];
        $bindvars = [];

        $from[] = "$blocks_table blocks";
        $from[] = "$types_table types";

        $join[] = "LEFT JOIN $modules_table mods ON mods.id = types.module_id";

        $where[] = 'blocks.type_id = types.id';

        if (!empty($state)) {
            $where[] = 'blocks.state IN (' . implode(',', array_fill(0, count($state), '?')) . ')';
            $bindvars = array_merge($bindvars, $state);
        }
        if (!empty($type_id)) {
            $where[] = 'types.id = ?';
            $bindvars[] = $type_id;
        }
        if (!empty($type)) {
            $where[] = 'types.type = ?';
            $bindvars[] = $type;
        }
        if (isset($module_id)) {
            $where[] = 'types.module_id = ?';
            $bindvars[] = $module_id;
        }
        if (!empty($type_category)) {
            $where[] = 'types.category = ?';
            $bindvars[] = $type_category;
        }
        if (!empty($type_state)) {
            $where[] = 'types.state IN (' . implode(',', array_fill(0, count($type_state), '?')) . ')';
            $bindvars = array_merge($bindvars, $type_state);
        }

        if (!empty($filter)) {
            if (empty($filter_fields)) {
                $filter_fields = ['name'];
            }
            $likes = [];
            foreach ($filter_fields as $field) {
                $likes[] = $select[$field] . " LIKE ?";
                $bindvars[] = "$filter%";
            }
            $where[] = '(' . join(' OR ', $likes) . ')';
        }

        // build query
        $query = "SELECT COUNT(blocks.id)";
        $query .= " FROM " . join(',', $from);
        $query .= " " . join(' ', $join);
        if (!empty($where)) {
            $query .= ' WHERE ' . join(' AND ', $where);
        }
        $result = $dbconn->Execute($query, $bindvars);
        if (!$result) {
            return;
        }
        $result->first();
        [$count] = $result->fields;

        $result->Close();

        return $count;
    }
}
