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
use xarDB;
use xarMod;
use xarVar;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks typesapi getitems function
 * @extends MethodClass<TypesApi>
 */
class GetitemsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Fetches multiple items from the API
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args Parameter data array
     * @return array|void Items fetched from API
     * @throws \BadParameterException
     * @see TypesApi::getitems()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!empty($type_id) && !is_numeric($type_id)) {
            $invalid[] = 'type_id';
        }

        if (!empty($type) && !is_string($type)) {
            $invalid[] = 'type';
        }

        if (isset($type_category) && !is_string($type_category)) {
            $invalid[] = 'type_category';
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
            } elseif (!is_string($module) ||
                (!$this->mod()->isAvailable($module) &&
                    (!$this->var()->isCached('Blocks.event', 'modremove') || $this->var()->getCached('Blocks.event', 'modremove') != $module))
            ) {
                $invalid[] = 'module';
            } else {
                $modinfo = $this->mod()->getBaseInfo($module);
                $module_id = $modinfo['systemid'];
            }
        }

        if (isset($module_id) && !is_numeric($module_id)) {
            $invalid[] = 'module_id';
        }

        if (isset($startnum) && !is_numeric($startnum)) {
            $invalid[] = 'startnum';
        }

        if (isset($numitems) && !is_numeric($numitems)) {
            $invalid[] = 'numitems';
        }

        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
            $vars = [join(', ', $invalid), 'blocks', 'typesapi', 'getitems'];
            throw new BadParameterException($vars, $msg);
        }

        $dbconn = $this->db()->getConn();
        $tables = $this->db()->getTables();
        $types_table   = $tables['block_types'];
        $modules_table = $tables['modules'];

        $select = [];
        $where = [];
        // $orderby = array(); todo
        $bindvars = [];

        $select['type_id'] = 'types.id';
        $select['type'] = 'types.type';
        $select['type_info'] = 'types.info';
        $select['type_category'] = 'types.category';
        $select['type_state'] = 'types.state';
        // we need to get the actual $classname and $filepath for getinfo() - requires UPGRADE due to table change
        $select['classname'] = 'types.class';
        $select['filepath'] = 'types.filepath';
        $select['module'] = 'mods.name';

        $query = "SELECT " . join(',', $select);
        $query .= " FROM $types_table types
                    LEFT JOIN $modules_table mods ON mods.id = types.module_id";

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

        if (!empty($where)) {
            $query .= ' WHERE ' . join(' AND ', $where);
        }

        if (empty($orderby)) {
            $orderby[] = 'types.type ASC';
            $orderby[] = 'mods.name ASC';
        }

        if (!empty($orderby)) {
            $query .= ' ORDER BY ' . join(',', $orderby);
        }

        $stmt = $dbconn->prepareStatement($query);
        if (!empty($numitems)) {
            $stmt->setLimit($numitems);
            if (empty($startnum)) {
                $startnum = 1;
            }
            $stmt->setOffset($startnum - 1);
        }

        $result = $stmt->executeQuery($bindvars);
        if (!$result) {
            return;
        }

        $types = [];
        while ($result->next()) {
            $item = [];
            foreach (array_keys($select) as $field) {
                $val = array_shift($result->fields);
                switch ($field) {
                    case 'type_info':
                        // normalize content
                        $val = @unserialize((string) $val);
                        $item[$field] = $val;
                        $item['content'] = $val;
                        break;
                    case 'classname':
                    case 'filepath':
                    case 'module':
                        $item[$field] = !empty($val) ? $val : '';
                        break;
                    default:
                        $item[$field] = $val;
                        break;
                }
            }
            $types[$item['type_id']] = $item;
        }
        $result->close();

        return $types;

    }
}
