<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi leftjoin function
 * @extends MethodClass<UserApi>
 */
class LeftjoinMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * return the field names and correct values for joining on users table
     * example : SELECT ..., $name, $email,...
     *           FROM ...
     *           LEFT JOIN $table
     *               ON $field = <name of userid field>
     *           WHERE ...
     *               AND $email LIKE '%xaraya.com'
     *               AND $where
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * array    $args['ids'] optional array of ids that we are selecting on
     * @return array|void array('table' => 'xar_roles',
     * 'field' => 'xar_roles.id',
     * 'where' => 'xar_roles.id IN (...)',
     * 'name'  => 'xar_roles.name',
     * ...
     * 'email'  => 'xar_roles.email')
     * @see UserApi::leftjoin()
     */
    public function __invoke(array $args = [])
    {
        // Get arguments from argument array
        extract($args);

        // Optional argument
        if (!isset($ids)) {
            $ids = [];
        }

        // Security check
        if (!$this->sec()->checkAccess('ViewRoles', 0)) {
            return;
        }

        // TODO: check this !
        foreach ($ids as $id) {
            if (!$this->sec()->check('ReadRoles', 0, 'All', "All:All:$id")) {
                return;
            }
        }

        // Table definition
        $xartable = $this->db()->getTables();
        $rolestable = $xartable['roles'];

        $leftjoin = [];

        // Specify LEFT JOIN ... ON ... [WHERE ...] parts
        $leftjoin['table'] = $rolestable;
        $leftjoin['field'] = $rolestable . '.id';
        if (count($ids) > 0) {
            $cleanids = [];
            foreach ($ids as $id) {
                $id = intval($id);
                if (!is_int($id) || $id < 1) {
                    continue;
                }
                $cleanids[] = $id;
            }
            $allids = join(', ', $cleanids);
            $leftjoin['where'] = $rolestable . '.id IN ('
                                 . $allids . ')';
        } else {
            $leftjoin['where'] = '';
        }

        // Add available columns in the roles table
        // note : we forget about pass and auth module for now :-)
        $columns = ['id','uname','name','email'];
        foreach ($columns as $column) {
            $leftjoin[$column] = $rolestable . '.' . $column;
        }

        return $leftjoin;
    }
}
