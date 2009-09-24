<?php
/**
 *  Return the field names and correct values for joining on users table
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * return the field names and correct values for joining on users table
 * example : SELECT ..., $name, $email,...
 *           FROM ...
 *           LEFT JOIN $table
 *               ON $field = <name of userid field>
 *           WHERE ...
 *               AND $email LIKE '%xaraya.com'
 *               AND $where
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['ids'] optional array of ids that we are selecting on
 * @returns array
 * @return array('table' => 'xar_roles',
 *               'field' => 'xar_roles.id',
 *               'where' => 'xar_roles.id IN (...)',
 *               'name'  => 'xar_roles.name',
 *               ...
 *               'email'  => 'xar_roles.email')
 */
function roles_userapi_leftjoin($args)
{
    // Get arguments from argument array
    extract($args);

    // Optional argument
    if (!isset($ids)) {
        $ids = array();
    }

    // Security check
    if (!xarSecurityCheck('ViewRoles',0)) return;

// TODO: check this !
    foreach ($ids as $id) {
        if (!xarSecurityCheck('ReadRole',0,'All',"All:All:$id")) return;
    }

    // Table definition
    $xartable = xarDB::getTables();
    $rolestable = $xartable['roles'];

    $leftjoin = array();

    // Specify LEFT JOIN ... ON ... [WHERE ...] parts
    $leftjoin['table'] = $rolestable;
    $leftjoin['field'] = $rolestable . '.id';
    if (count($ids) > 0) {
        $cleanids = array();
        foreach ($ids as $id) {
            $id = intval($id);
            if (!is_int($id) || $id < 1) continue;
            $cleanids[] = $id;
        }
        $allids = join(', ', $cleanids);
        $leftjoin['where'] = $rolestable . '.id IN (' .
                             $allids . ')';
    } else {
        $leftjoin['where'] = '';
    }

    // Add available columns in the roles table
    // note : we forget about pass and auth module for now :-)
    $columns = array('id','uname','name','email');
    foreach ($columns as $column) {
        $leftjoin[$column] = $rolestable . '.' . $column;
    }

    return $leftjoin;
}

?>
